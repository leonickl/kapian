<?php

namespace App;

use App\Models\QueueItem;
use App\Models\Tag;
use App\Models\Vocab;
use PXP\Auth\Models\User;
use PXP\Ds\Vector;
use InvalidArgumentException;

readonly class Card
{
    public function __construct(
        public int $vocab_id,
        public string $direction,
        public ?string $hanzi,
        public ?string $pinyin,
        public ?string $translation,
        mixed ...$others,
    ) {}

    private static function query(int $userId)
    {
        return select('*')
            ->from('cards')
            ->deleted(true)  // only because cards view has no deleted_at
            ->whereIs('user_id', $userId);
    }

    public static function forUser(User $user): Vector
    {
        return self::query($user->id)
            ->execute()
            ->map(fn (object $card) => new self(...(array) $card));
    }

    public static function find(QueueItem $item): ?Card
    {
        return self::query($item->user_id)
            ->whereIs('vocab_id', $item->vocab_id)
            ->whereIs('direction', $item->direction)
            ->execute()
            ->map(fn (object $card) => new self(...(array) $card))
            ->first();
    }

    public static function forTag(Tag $tag, User $user): Vector
    {
        $vocabIds = Tag::find($tag->id)
            ->vocabs()
            ->map(fn (object $card) => $card->id)
            ->toArray();

        return self::query($user->id)
            ->whereIn('vocab_id', $vocabIds)
            ->execute()
            ->map(fn (object $card) => new self(...(array) $card));
    }

    public static function forLevel(int $level, User $user)
    {
        return self::query($user->id)
            ->execute()
            ->filter(fn (object $card) => (int) $card->progress === $level)
            ->map(fn (object $card) => new self(...(array) $card));
    }

    public function vocab(): Vocab
    {
        return Vocab::find($this->vocab_id);
    }

    public function siblings(): Vector
    {
        $field = $this->direction;

        if (! in_array($field, ['hanzi', 'pinyin', 'translation'])) {
            return v();
        }

        return self::forUser(user())
            ->filter(fn (self $card) => $card->vocab_id !== $this->vocab_id
                && $card->direction === $field
                && $card->$field === $this->$field);
    }

    public function front(): ?string
    {
        return match ($this->direction) {
            'hanzi' => $this->hanzi,
            'pinyin' => $this->pinyin,
            'translation' => $this->translation,
            default => throw new InvalidArgumentException("Unknown card direction '$this->direction'."),
        };
    }
}
