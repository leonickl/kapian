<?php

namespace App\Models;

use PXP\Auth\Models\User;
use PXP\Data\DB;
use PXP\Data\Model;
use PXP\Ds\Vector;
use App\Card;

/**
 * @property-read int $id
 * @property-read ?string $hanzi
 * @property-read ?string $pinyin
 * @property-read ?string $translation
 * @property-read int $author_id
 */
class Vocab extends Model
{
    protected string $table = 'vocabs';

    public static function allVisible()
    {
        $vocabs = DB::init()->select('select * from visible_vocabs '
            .'where user_id = ? order by id desc', [user()->id]);

        return self::map($vocabs);
    }

    /**
     * @return Vector<Tag>
     */
    public function tags(): Vector
    {
        $tags = DB::init()->select(
            <<<'SQL'
                select tags.*
                from tags
                inner join tag_vocab on tag_vocab.tag_id = tags.id
                where tag_vocab.vocab_id = ?
                    and tag_vocab.deleted_at is null
                    and tags.deleted_at is null
                order by lower(tags.label)
                SQL,
            [$this->id],
        );

        return Tag::map($tags);
    }

    /**
     * @param  list<Tag>  $tags
     */
    public function attachTags(array $tags): static
    {
        $records = array_map(fn (Tag $tag) => [
            'vocab_id' => $this->id,
            'tag_id' => $tag->id,
        ], $tags);

        DB::init()->insertMany('tag_vocab', $records, ignoreDuplicates: true);

        return $this;
    }

    /**
     * @param  list<Tag>  $tags
     */
    public function syncTags(array $tags): static
    {
        $db = DB::init();

        $tagIds = array_values(array_unique(array_map(fn (Tag $tag) => $tag->id, $tags)));
        $now = date('Y-m-d H:i:s');

        if ($tagIds === []) {
            // delete all tags of this vocab

            $db->sql(
                'update tag_vocab set deleted_at = ?, modified_at = ? '
                    .'where vocab_id = ? and deleted_at is null',
                [$now, $now, $this->id],
            );

            return $this;
        }

        $placeholders = implode(', ', array_fill(0, count($tagIds), '?'));

        // delete all tags not given here
        $db->sql(
            'update tag_vocab set deleted_at = ?, modified_at = ? '
                ."where vocab_id = ? and deleted_at is null and tag_id not in ($placeholders)",
            [$now, $now, $this->id, ...$tagIds],
        );

        // add all tags given here
        $this->attachTags($tags);

        // restore previously deleted tags
        $db->sql(
            'update tag_vocab set deleted_at = null, modified_at = ? '
                ."where vocab_id = ? and tag_id in ($placeholders)",
            [$now, $this->id, ...$tagIds],
        );

        return $this;
    }

    public function author(): User
    {
        return User::find($this->author_id);
    }

    public function summary(): string
    {
        return $this->hanzi
            ?: $this->pinyin
            ?: $this->translation
            ?: '---';
    }

    public function canEdit(?User $user = null): bool
    {
        $user ??= user();

        return $this->author_id === $user->id;
    }

    public function isSharedWith(?User $user = null): bool
    {
        $user ??= user();

        return $this->tags()
            ->filter(fn (Tag $tag) => $tag->isSharedWith($user))
            ->count() > 0;
    }

    public function canShow(?User $user = null): bool
    {
        $user ??= user();

        return $this->author_id === $user->id
            || $this->isSharedWith($user);
    }

    public function cards(): Vector
    {
        return Card::forUserAndVocab(user: user(), vocab: $this);
    }
}
