<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->create('vocabs', [
        'hanzi' => 'string nullable',
        'pinyin' => 'string nullable',
        'translation' => 'string nullable',
        'author_id' => 'int references user(id)',
    ]);
});
