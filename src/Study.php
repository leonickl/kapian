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
                from visible_vocabs
                    where deleted_at is null
                    and user_id = :user_id

                union

                select id as vocab_id,
                    'pinyin' as direction,
                    pinyin as front,
                    hanzi as back_top,
                    translation as back_bottom
                from visible_vocabs
                    where deleted_at is null
                    and user_id = :user_id

                union

                select id as vocab_id,
                    'translation' as direction,
                    translation as front,
                    pinyin as back_top,
                    hanzi as back_bottom
                from visible_vocabs
                    where deleted_at is null
                    and user_id = :user_id
            ) as cards

            left join progresses on cards.vocab_id = progresses.vocab_id
                and cards.direction = progresses.direction
                and progresses.user_id = :user_id
                and progresses.deleted_at is null

            where front is not null and front is not ''
                and coalesce(progress, 0) = cast(:progress as integer);
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
