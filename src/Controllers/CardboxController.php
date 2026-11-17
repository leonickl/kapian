<?php

namespace App\Controllers;

use App\Card;
use App\Models\Progress;
use App\Models\QueueItem;
use PXP\Http\Controllers\Controller;
use PXP\Lib\Notification;
use App\Palette;

class CardboxController extends Controller
{
    public function index()
    {
        $counts = Progress::levelCounts()->toArray();
        $shades = Palette::shades(count($counts), hue: 40, lMax: .8, lMin: .43);

        $colours = array_combine(
            keys: array_keys($counts),
            values: $shades,
        );

        return view('cardbox', [
            'progressLabel' => fn (int $progress) => Progress::new(progress: $progress)->label(),
            'counts' => $counts,
            'colours' => $colours,
        ]);
    }

    public function level(int $level)
    {
        return view('cardbox.level', [
            'level' => $level,
            'label' => Progress::new(progress: $level)->label(),
            'cards' => Card::forLevel($level, user()),
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
