<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->create('auth_tokens', [
        'user_id' => 'int not null',
        'selector' => 'varchar(24) not null',
        'hashed_validator' => 'varchar(64) not null',
        'expires' => 'datetime not null',
    ]);
});
