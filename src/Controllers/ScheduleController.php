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
        $today = date('Y-m-d');

        $colour = function (string $date) use ($today) {
            if (in_array($date, ['Buried', 'New'])) {
                return 'gray';
            }

            return match ($date <=> $today) {
                -1 => 'warn',
                0 => '',
                1 => 'secondary',
            };
        };

        return view('schedule', [
            'counts' => $this->dateCounts(),
            'colour' => $colour,
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

        if (! array_key_exists(date('Y-m-d'), $dates)) {
            $dates[date('Y-m-d')] = 0;
        }

        ksort($dates);

        return $dates;
    }
}