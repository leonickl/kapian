<?php

namespace App\Models;

use PXP\Auth\Models\User;
use PXP\Data\DB;
use PXP\Data\Model;
use PXP\Ds\Vector;

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
        $vocabs = DB::init()->select(<<<'SQL'
            select * from (
                select vocabs.*
                    from vocabs
                inner join tag_vocab
                    on tag_vocab.vocab_id = vocabs.id
                inner join tag_shares
                    on tag_vocab.tag_id = tag_shares.tag_id
                where tag_shares.recipient_id = :user_id
                    and vocabs.deleted_at is null
                    and tag_vocab.deleted_at is null
                    and tag_shares.deleted_at is null

                union

                select *
                from vocabs
                where author_id = :user_id
                    and deleted_at is null
            ) as voc
            
            order by id desc;
            SQL,
            ['user_id' => user()->id],
        );
        
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

    public function author(): User
    {
        return User::find($this->author_id);
    }

    /**
     * @return Vector<static>
     */
    public function siblings(): Vector
    {
        return self::findAllBy('pinyin', $this->pinyin);
    }

    public function summary(): string
    {
        return $this->hanzi
            ?: $this->pinyin
            ?: $this->translation
            ?: '---';
    }
}
