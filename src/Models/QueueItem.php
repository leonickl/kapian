<?php

namespace App\Models;

use App\Card;
use PXP\Auth\Models\User;
use PXP\Data\Model;
use PXP\Ds\Vector;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int $vocab_id
 * @property-read string $direction
 */
class QueueItem extends Model
{
    protected $table = 'queue';

    public static function forUser(User $user): Vector
    {
        return self::findAllBy('user_id', $user->id);
    }

    public static function push(User $user, Vector $cards): void
    {
        foreach ($cards as $card) {
            if (self::findByCard($user, $card)) {
                continue;
            }

            self::create(
                user_id: $user->id,
                vocab_id: $card->vocab_id,
                direction: $card->direction,
            );
        }
    }

    public static function findByCard(User $user, Card $card): ?self
    {
        return self::select()
            ->whereIs('user_id', $user->id)
            ->whereIs('vocab_id', $card->vocab_id)
            ->whereIs('direction', $card->direction)
            ->get()
            ->first();
    }

    public function user(): User
    {
        return User::find($this->user_id);
    }

    public function vocab(): Vocab
    {
        return Vocab::find($this->vocab_id);
    }

    public function card(): ?Card
    {
        return Card::find(
            user: $this->user(), 
            vocab: $this->vocab(), 
            direction: $this->direction,
        );
    }
}
