<?php

namespace App\Controllers;

use App\Models\Progress;
use App\Study;
use InvalidArgumentException;
use PXP\Auth\Auth;
use PXP\Data\DB;
use PXP\Http\Controllers\Controller;
use PXP\Lib\Notification;

class StudyController extends Controller
{
    public function index()
    {
        $cardCounts = DB::init()
            ->select('select progress, count(*) as count from cards where user_id = ? group by progress', [user()->id])
            ->groupBy(fn ($row) => $row->progress)
            ->map(fn ($progress) => $progress[0]->count);

        return view('study.index', [
            'title' => $this->title(...),
            'progresses' => Progress::forUser(user()),
            'cardCounts' => $cardCounts,
        ]);
    }

    public function study(int $progress)
    {
        $card = new Study(Auth::user(), $progress)->next();

        if ($card === null) {
            $title = $this->title($progress);

            Notification::info("$title has no cards left to study. Try studying in another one.");

            return redirect('study');
        }

        session(['card' => $card]);

        return view('study.card', [
            'title' => $this->title($progress),
            'progress' => $progress,
            'card' => $card,
            'back' => false,
        ]);
    }

    public function check(int $progress)
    {
        $card = session('card');

        if ($card === null) {
            return redirect('study.study', progress: $progress);
        }

        return view('study.card', [
            'title' => $this->title($progress),
            'progress' => $progress,
            'card' => $card,
            'back' => true,
        ]);
    }

    public function bury(int $progress)
    {
        $card = session('card');

        if ($card === null) {
            return redirect('study.study', progress: $progress);
        }

        $user = user();

        Progress::findOrCreate($card, $user)
            ->fill(progress: -1)
            ->save();

        $summary = $card->vocab()->summary();

        Notification::success("Buried card '$card->direction' from vocab '$summary'.");

        return redirect('study.study', progress: $progress);
    }

    public function result(int $progress, string $result)
    {
        $delta = match ($result) {
            'correct' => 1,
            'wrong' => -1,
            'again' => 0,
            default => throw new InvalidArgumentException("Invalid result type '$result'"),
        };

        $card = session('card');

        if ($card === null) {
            return redirect('study.study', progress: $progress);
        }

        $user = user();

        Progress::findOrCreate($card, $user)
            ->delta($delta)
            ->save();

        $summary = $card->vocab()->summary();

        match ($result) {
            'correct' => Notification::success("Promoted card '$card->direction' from vocab '$summary'"),
            'wrong' => Notification::warn("Demoted card '$card->direction' from vocab '$summary'"),
            'again' => Notification::info("Retained card '$card->direction' from vocab '$summary'"),
            default => Notification::info("Retained card '$card->direction' from vocab '$summary'"),
        };

        return redirect('study.study', progress: $progress);
    }

    private function title(int $progress): string
    {
        if ($progress >= 0) {
            return "Level $progress";
        }

        if ($progress === -1) {
            return 'Buried';
        }

        return "Basement $progress";
    }
}
