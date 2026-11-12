<?php

namespace App\Models;

use InvalidArgumentException;
use PXP\Auth\Models\User;
use PXP\Data\DB;
use PXP\Data\Model;
use PXP\Ds\Vector;

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
            ...self::allOwn(user()->id),
        )
            ->sort(fn ($one, $other) => $one->label <=> $other->label);
    }

    /**
     * @return Vector<Tag>
     */
    public static function allOwn(int $ownerId): Vector
    {
        return self::select()
            ->whereIs('owner_id', $ownerId)
            ->order('label')
            ->get();
    }

    /**
     * @return list<string>
     */
    public static function allOwnLabels(int $ownerId): array
    {
        return self::allOwn($ownerId)
            ->map(fn (Tag $tag) => $tag->label)
            ->toArray();
    }

    public static function findOrCreate(int $ownerId, string $label): self
    {
        $label = self::normalize($label);

        if ($label === '') {
            throw new InvalidArgumentException('Tag label must not be empty');
        }

        $existing = self::allOwn($ownerId)
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

    public function owner(): User
    {
        return User::find($this->owner_id);
    }

    public function label(): string
    {
        return $this->owner_id === user()->id
            ? $this->label
            : $this->label.' ('.$this->owner()->name().')';
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

    public function canShare(?User $user = null): bool
    {
        $user ??= user();

        return $this->owner_id === $user->id;
    }

    public function canEdit(?User $user = null): bool
    {
        $user ??= user();

        return $this->owner_id === $user->id;
    }

    public function canShow(?User $user = null): bool
    {
        $user ??= user();

        return $this->owner_id === $user->id
            || TagShare::exists(tag: $this, user: user());
    }

    public function shares(): Vector
    {
        return TagShare::findAllBy('tag_id', $this->id);
    }

    public function isSharedWith(?User $user = null): bool
    {
        $user ??= user();

        return $this->shares()
            ->filter(fn (TagShare $share) => $share->recipient_id === $user->id)
            ->count() > 0;
    }
}
