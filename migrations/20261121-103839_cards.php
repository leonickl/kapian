<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->sql(<<<'SQL'
        create view if not exists cards as

        select cards.vocab_id,
            cards.direction,
            cards.hanzi,
            cards.pinyin,
            cards.translation,
            cards.user_id,
            coalesce(progresses.progress, 0) as progress,
            progresses.modified_at as studied_at
        from (
            select id as vocab_id,
                'hanzi' as direction,
                hanzi as front,
                hanzi,
                pinyin,
                translation,
                deleted_at,
                user_id
            from visible_vocabs

            union

            select id as vocab_id,
                'pinyin' as direction,
                pinyin as front,
                hanzi,
                pinyin,
                translation,
                deleted_at,
                user_id
            from visible_vocabs

            union

            select id as vocab_id,
                'translation' as direction,
                translation as front,
                hanzi,
                pinyin,
                translation,
                deleted_at,
                user_id
            from visible_vocabs

            union

            select vv.id as vocab_id,
                'strokes' as direction,
                vv.pinyin as front,
                vv.hanzi,
                vv.pinyin,
                vv.translation,
                vv.deleted_at,
                vv.user_id
            from visible_vocabs as vv
            inner join tag_vocab
                on vv.id = tag_vocab.vocab_id
                and tag_vocab.deleted_at is null
            inner join tags
                on tags.id = tag_vocab.tag_id
                and tags.strokes = 1
                and tags.deleted_at is null
            where vv.hanzi is not null
                and vv.hanzi is not ''
        ) as cards

        left join progresses on cards.vocab_id = progresses.vocab_id
            and progresses.user_id = cards.user_id
            and cards.direction = progresses.direction
            and progresses.deleted_at is null

        where cards.deleted_at is null
            and front is not null and front is not '';
        SQL);
});
