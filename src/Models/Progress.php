<?php

namespace App\Models;

use App\Card;
use PXP\Auth\Models\User;
use PXP\Data\Model;

/**
 * @property-read int $id
 * @property-read int $vocab_id
 * @property-read string $direction
 * @property-read int $progress
 * @property-read int $user_id
 */
class Progress extends Model
{
    protected $table = 'progresses';

    public static function findOrCreate(Card $card, User $user): ?self
    {
        $progress = self::select()
            ->whereIs('vocab_id', $card->vocab_id)
            ->whereIs('direction', $card->direction)
            ->whereIs('user_id', $user->id)
            ->get()
            ->first();

        if ($progress !== null) {
            return $progress;
        }

        return self::create(
            vocab_id: $card->vocab_id,
            direction: $card->direction,
            user_id: $user->id,
        );
    }

    public function vocab(): Vocab
    {
        return Vocab::findBy($this->vocab_id);
    }

    public function user(): User
    {
        return User::findBy($this->user_id);
    }

    public function delta(int $i): self
    {
        $this->progress = max(0, $this->progress + $i);

        return $this;
    }
}
