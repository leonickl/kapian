<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->create('users', [
        'email' => 'text not null unique',
        'password_hash' => 'text not null',
        'role' => 'int not null default 0',
        'name' => 'string not null',
        'verified' => 'int not null default 0',
    ]);

    $db->sql('create unique index if not exists '.
        'unique_users_email on users(email)');
});
