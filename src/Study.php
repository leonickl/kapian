<?php

namespace App;

use App\Models\QueueItem;
use PXP\Auth\Models\User;

class Study
{
    /**
     * Shuffle user's queue and return first one with
     * an existing card. On the way, delete entries
     * that do not have a corresponding card.
     */
    public static function next(): ?Card
    {
        $items = QueueItem::forUser(user())->toArray();

        shuffle($items);

        foreach ($items as $item) {
            $card = $item->card();

            if ($card !== null) {
                return $card;
            }

            $item->delete();
        }

        return null;
    }

    /**
     * Count all left cards from the current study-queue.
     */
    public static function left(): int
    {
        return QueueItem::forUser(user())->count();
    }
}
