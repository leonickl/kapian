<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->create('verification_link', [
        'token' => 'string not null',
        'user_id' => 'int references user(id)',
    ]);
});
