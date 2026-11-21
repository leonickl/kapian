<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->create('progresses', [
        'vocab_id' => 'int references vocabs(id)',
        'direction' => 'string not null',
        'progress' => 'int default 0',
        'user_id' => 'int references users(id)',
    ]);

    $db->sql('create unique index if not exists '.
        'unique_progress on progresses(vocab_id, direction, user_id)');
});
