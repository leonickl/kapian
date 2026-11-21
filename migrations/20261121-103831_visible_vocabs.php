<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->sql(<<<'SQL'
        create view if not exists visible_vocabs as

        select vocabs.*, tag_shares.recipient_id as user_id
            from vocabs
        inner join tag_vocab
            on tag_vocab.vocab_id = vocabs.id
        inner join tag_shares
            on tag_vocab.tag_id = tag_shares.tag_id
        where vocabs.deleted_at is null
            and tag_vocab.deleted_at is null
            and tag_shares.deleted_at is null

        union

        select *, author_id as user_id
        from vocabs
        where deleted_at is null
        SQL);
});
