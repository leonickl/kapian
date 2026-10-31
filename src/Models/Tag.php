<?php

namespace App\Models;

use InvalidArgumentException;
use PXP\Data\DB;
use PXP\Data\Model;
use PXP\Ds\Vector;
use PXP\Auth\Models\User;

/**
 * @property-read int $id
 * @property-read int $owner_id
 * @property-read string $label
 */
class Tag extends Model
{
    protected string $table = 'tags';

    public static function allVisible()
    {
        $shared = DB::init()->select(<<<'SQL'
            select tags.*
                from tags
            inner join tag_shares
                on tags.id = tag_shares.tag_id
            where tag_shares.recipient_id = ?
                and tag_shares.deleted_at is null
                and tags.deleted_at is null;
            SQL,
            [user()->id],
        );

        return v(
            ...self::map($shared),
            ...self::forUser(user()->id),
        )
            ->sort(fn ($one, $other) => $one->label <=> $other->label);
    }

    /**
     * @return Vector<Tag>
     */
    public static function forUser(int $ownerId): Vector
    {
        return self::select()
            ->whereIs('owner_id', $ownerId)
            ->order('label')
            ->get();
    }

    /**
     * @return list<string>
     */
    public static function labelsForUser(int $ownerId): array
    {
        return self::forUser($ownerId)
            ->map(fn (Tag $tag) => $tag->label)
            ->toArray();
    }

    public static function findOrCreate(int $ownerId, string $label): self
    {
        $label = self::normalize($label);

        if ($label === '') {
            throw new InvalidArgumentException('Tag label must not be empty');
        }

        $existing = self::forUser($ownerId)
            ->filter(fn (Tag $tag) => mb_strtolower($tag->label) === mb_strtolower($label))
            ->first();

        if ($existing instanceof self) {
            return $existing;
        }

        return self::create(owner_id: $ownerId, label: $label);
    }

    public static function normalize(string $label): string
    {
        return trim(preg_replace('/\s+/u', ' ', $label) ?? $label);
    }

    public function vocabs(): Vector
    {
        $sql = <<<'SQL'
            select vocabs.*
            from vocabs
            inner join tag_vocab
            on tag_vocab.vocab_id = vocabs.id
            where tag_vocab.tag_id = ?
            and vocabs.deleted_at is null
            and tag_vocab.deleted_at is null;
            SQL;

        $vocabs = DB::init()->select($sql, [$this->id]);

        return Vocab::map($vocabs);
    }

    public function isShareable(): bool
    {
        return $this->owner_id === user()->id;
    }

    public function isShowable(): bool
    {
        return $this->owner_id === user()->id
            || TagShare::select()
                ->whereIs('tag_id', $this->id)
                ->whereIs('recipient_id', user()->id)
                ->get()
                ->count() > 0;
    }

    public function shares(): Vector
    {
        return TagShare::findAllBy('tag_id', $this->id);
    }

    public function isSharedWith(User $user): bool
    {
        return $this->shares()
            ->filter(fn (TagShare $share) => $share->recipient_id === $user->id)
            ->count() > 0;
    }
}
