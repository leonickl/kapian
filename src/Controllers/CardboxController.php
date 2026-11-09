<?php

namespace App\Controllers;

use App\Card;
use App\Models\Progress;
use App\Models\QueueItem;
use PXP\Http\Controllers\Controller;
use PXP\Lib\Notification;

class CardboxController extends Controller
{
    public function index()
    {
        return view('cardbox', [
            'progressLabel' => fn (int $progress) => Progress::new(progress: $progress)->label(),
            'progresses' => Progress::levelCounts(),
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

        Notification::success('Added '.($after - $before).' cards to the study-queue.');

        return redirect('study');
    }
}
