<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->create('queue', [
        'user_id' => 'int not null references users(id)',
        'vocab_id' => 'int not null references vocabs(id)',
        'direction' => 'string not null',
    ]);
});
