<?php

use PXP\Data\DB;
use PXP\Data\Migrations\Migration;

return new Migration(function (DB $db) {
    $db->addColumns('tags', [
        'strokes' => 'boolean not null default 0',
    ]);
});
