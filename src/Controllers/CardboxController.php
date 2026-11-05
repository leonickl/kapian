<?php

namespace App\Controllers;

use App\Models\Progress;
use PXP\Data\DB;
use PXP\Http\Controllers\Controller;
use App\Models\QueueItem;
use App\Card;
use PXP\Lib\Notification;

class CardboxController extends Controller
{
    public function index()
    {
        $progressLabel = function (int $progress) {
            if ($progress === -1) {
                return 'Buried';
            }

            if ($progress === 0) {
                return "New";
            }

            if ($progress < 0) {
                return "Basement $progress";
            }

            return "Level $progress";
        };

        $progresses = DB::init()
            ->select('select progress, count(*) as count from cards '
                .'where user_id = ? group by progress', [user()->id])
            ->groupBy(fn ($row) => $row->progress)
            ->map(fn ($progress) => $progress[0]->count);

        return view('cardbox', [
            'progressLabel' => $progressLabel,
            'progresses' => $progresses,
        ]);
    }

    public function study(int $level)
    {
        $user = user();

        $before = QueueItem::forUser($user)->count();

        QueueItem::push(
            user: $user,
            cards: Card::forLevel($level, $user),
        );

        $after = QueueItem::forUser($user)->count();

        $diff = $after - $before;

        Notification::success("Added $diff cards to the study-queue.");

        return redirect('study');
    }
}