<?php

namespace App;

use PXP\Auth\Models\User;
use PXP\Data\DB;

class Study
{
    public function __construct(private User $user, private int $progress) {}

    public function next(): ?Card
    {
        $sql = <<<'SQL'
            select cards.* from (
                select id as vocab_id,
                    'hanzi' as direction,
                    hanzi as front,
                    pinyin as back_top,
                    translation as back_bottom
                from vocabs
                    where deleted_at is null
                    and author_id = :user_id

                union

                select id as vocab_id,
                    'pinyin' as direction,
                    pinyin as front,
                    hanzi as back_top,
                    translation as back_bottom
                from vocabs
                    where deleted_at is null
                    and author_id = :user_id

                union

                select id as vocab_id,
                    'translation' as direction,
                    translation as front,
                    pinyin as back_top,
                    hanzi as back_bottom
                from vocabs
                    where deleted_at is null
                    and author_id = :user_id
            ) as cards

            left join progresses on cards.vocab_id = progresses.vocab_id
                and cards.direction = progresses.direction
                and progresses.user_id = :user_id

            where front is not null and front is not ''
                and progress = :progress;
        SQL;

        $cards = DB::init()->select($sql, [
            'user_id' => $this->user->id,
            'progress' => $this->progress,
        ]);

        if ($cards->count() === 0) {
            return null;
        }

        return new Card(...(array) $cards->sample()->first());
    }
}
