<?php

namespace App;

use PXP\Auth\Models\User;
use PXP\Data\DB;
use PXP\Ds\Vector;

class Study
{
    public function __construct(private User $user, private int $progress) {}

    public function next(): ?Card
    {
        $cards = $this->cards();

        if ($cards->count() === 0) {
            return null;
        }

        return new Card(...(array) $cards->sample()->first());
    }

    public function cards(): Vector
    {
        $sql = <<<'SQL'
            select vocab_id,
                direction,
                hanzi,
                pinyin,
                translation
            from cards
            where cards.user_id = :user_id
                and progress = cast(:progress as int);
        SQL;

        return DB::init()->select($sql, [
            'user_id' => $this->user->id,
            'progress' => $this->progress,
        ]);
    }
}
