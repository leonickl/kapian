<?php

namespace App\Models;

use InvalidArgumentException;
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
}
