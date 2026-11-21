<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->create('tags', [
        'owner_id' => 'int not null references users(id)',
        'label' => 'string not null',
    ]);

    $db->sql('create unique index if not exists '.
        'unique_tags_owner_label on tags(owner_id, lower(label)) '.
        'where deleted_at is null');

    $db->create('tag_vocab', [
        'vocab_id' => 'int not null references vocabs(id)',
        'tag_id' => 'int not null references tags(id)',
    ]);

    $db->sql('create unique index if not exists '.
        'unique_tag_vocab on tag_vocab(vocab_id, tag_id)');
});
