<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->create('tag_shares', [
        'tag_id' => 'int not null references tags(id)',
        'recipient_id' => 'int not null references users(id)',
    ]);

    $db->sql('create unique index if not exists '.
        'unique_tag_shares on tag_shares(tag_id, recipient_id)');
});
