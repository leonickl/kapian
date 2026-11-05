<?php

namespace App\Controllers;

use App\Models\Progress;
use App\Study;
use InvalidArgumentException;
use PXP\Auth\Auth;
use PXP\Data\DB;
use PXP\Http\Controllers\Controller;
use PXP\Lib\Notification;
use App\Models\Tag;
use App\Card;
use App\Models\QueueItem;

class StudyController extends Controller
{
    public function index()
    {
        return view('study.index', [
            'left' => Study::left(),
        ]);
    }

    public function front()
    {
        $card = Study::next();

        if ($card === null) {
            Notification::info("Current study-queue has no cards left. Try adding some cards to it.");

            return redirect('study');
        }

        session(['card' => $card]);

        return view('study.card', [
            'card' => $card,
            'back' => false,
            'left' => Study::left(),
        ]);
    }

    public function back()
    {
        $card = session('card');

        if ($card === null) {
            return redirect('study.front');
        }

        return view('study.card', [
            'card' => $card,
            'back' => true,
            'left' => Study::left(),
        ]);
    }

    public function skip()
    {
        $card = session('card');

        if ($card !== null) {
            Notification::info("Skipped card '$card->direction'"
                ."from vocab '".$card->vocab()->summary()."'.");
        }

        return redirect('study.front');
    }

    public function bury()
    {
        $card = session('card');

        if ($card === null) {
            return redirect('study.front');
        }

        Progress::findOrCreate($card, user())
            ->fill(progress: -1)
            ->save();

        QueueItem::findByCard(user(), $card)?->delete();

        Notification::info("Buried card '$card->direction'"
            ."from vocab '".$card->vocab()->summary()."'.");

        return redirect('study.front');
    }

    public function result(string $result)
    {
        $delta = match ($result) {
            'correct' => 1,
            'wrong' => -1,
            'again' => 0,
            default => throw new InvalidArgumentException("Invalid result type '$result'"),
        };

        $card = session('card');

        if ($card === null) {
            return redirect('study.front');
        }

        $user = user();

        Progress::findOrCreate($card, $user)
            ->delta($delta)
            ->save();

        QueueItem::findByCard(user(), $card)?->delete();

        $summary = $card->vocab()->summary();

        match ($result) {
            'correct' => Notification::success("Promoted card '$card->direction' from vocab '$summary'"),
            'wrong' => Notification::warn("Demoted card '$card->direction' from vocab '$summary'"),
            'again' => Notification::info("Retained card '$card->direction' from vocab '$summary'"),
            default => Notification::info("Retained card '$card->direction' from vocab '$summary'"),
        };

        return redirect('study.front');
    }

    public function cancel()
    {
        QueueItem::forUser(user())
            ->each(fn (QueueItem $item) => $item->delete());

        Notification::warn("Study-queue was cancelled.");

        return redirect('study');
    }
}
