<?php

namespace App\Models;

use PXP\Data\Model;
use PXP\Auth\Models\User;

/**
 * @property-read int $id
 * @property-read int $tag_id
 * @property-read int $recipient_id
 */
class TagShare extends Model
{
    protected $table = 'tag_shares';

    public function tag(): Tag
    {
        return Tag::find($this->tag_id);
    }

    public function recipient(): User
    {
        return User::find($this->recipient_id);
    }
}