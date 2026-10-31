<?php

namespace App\Controllers;

use PXP\Http\Controllers\Controller;
use PXP\Auth\Auth;
use App\Study;
use App\Models\Progress;
use InvalidArgumentException;
use PXP\Lib\Notification;
use PXP\Http\Response\Redirect;

class StudyController extends Controller
{
    public function index()
    {
        $progresses = Progress::select('progress')
            ->where('progress', 'is not', -1)
            ->whereIs('user_id', user()->id)
            ->order('progress')
            ->get()
            ->map(fn ($record) => $record->progress)
            ->unique();

        return view('study', [
            'title' => $this->title(...),
            'progresses' => $progresses,
        ]);
    }

    public function study(int $progress)
    {
        $card = new Study(Auth::user(), $progress)->next();

        if ($card === null) {
            $title = $this->title($progress);

            Notification::info("$title has no cards left to study. Try studying in another one.");

            return Redirect::route('study');
        }

        session(['card' => $card]);

        return view('study.study', [
            'title' => $this->title($progress),
            'progress' => $progress,
            'card' => $card,
        ]);
    }

    public function check(int $progress)
    {
        $card = session('card');

        return view('study.check', [
            'title' => $this->title($progress),
            'progress' => $progress,
            'card' => $card,
        ]);
    }

    public function bury(int $progress)
    {
        $card = session('card');
        $user = user();

        Progress::findOrCreate($card, $user)
            ->fill(progress: -1)
            ->save();

        $summary = $card->vocab()->summary();
        
        Notification::success("Buried card '$card->direction' from vocab '$summary'.");

        return Redirect::path(route('study.study', progress: $progress));
    }

    public function result(int $progress, string $result)
    {
        $delta = match($result) {
            'correct' => 1,
            'wrong' => -1,
            'again' => 0,
            default => throw new InvalidArgumentException("Invalid result type '$result'"),
        };

        $card = session('card');
        $user = user();

        Progress::findOrCreate($card, $user)
            ->delta($delta)
            ->save();

        $summary = $card->vocab()->summary();

        match($result) {
            'correct' => Notification::success("Promoted card '$card->direction' from vocab '$summary'"),
            'wrong' => Notification::warn("Demoted card '$card->direction' from vocab '$summary'"),
            'again' => Notification::info("Retained card '$card->direction' from vocab '$summary'"),
            default => Notification::info("Retained card '$card->direction' from vocab '$summary'"),
        };

        return Redirect::path(route('study.study', progress: $progress));
    }

    private function title(int $progress): string
    {
        if ($progress >= 0) {
            return "Level $progress";
        }

        if ($progress === -1) {
            return "Buried";
        }

        return "Basement $progress";
    }
}
