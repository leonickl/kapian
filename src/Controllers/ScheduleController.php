<?php

namespace App\Controllers;

use PXP\Http\Controllers\Controller;
use App\Card;
use App\Models\QueueItem;
use PXP\Lib\Notification;

class ScheduleController extends Controller
{
    public function index()
    {
        return view('schedule', [
            'counts' => $this->dateCounts(),
            'today' => date('Y-m-d'),
        ]);
    }

    public function date(string $date)
    {
        $counts = $this->dateCounts();

        return view('schedule.date', [
            'date' => $date,
            'count' => $counts[$date] ?? 0,
        ]);
    }

    public function study(string $date)
    {
        $user = user();

        $before = QueueItem::forUser($user)->count();

        QueueItem::push(
            user: $user,
            cards: v(...$this->dateCards()[$date] ?? []),
        );

        $after = QueueItem::forUser($user)->count();

        $diff = $after - $before;

        Notification::success("Added $diff cards to the study-queue.");

        return redirect('study');
    }

    private function dateCards()
    {
        return Card::forUser(user())
            ->filter()
            ->groupBy(function ($card) {
                $next = $card->nextStudy();
                
                if ($next === false) {
                    return 'Buried';
                }

                if ($next === true) {
                    return 'New';
                }

                return $next->format('Y-m-d');
            });
    }

    private function dateCounts()
    {
        $dates = $this->dateCards()
            ->map(fn ($cards) => count($cards))
            ->toArray();

        // sort by keys and preserve them
        $dates = array_flip($dates);
        uasort($dates, fn ($one, $other) => $one <=> $other);
        $dates = array_flip($dates);

        return $dates;
    }
}