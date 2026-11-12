<?php

use PXP\Data\DB;

require __DIR__.'/vendor/autoload.php';

$db = DB::init();

$db->create('users', [
    'email' => 'text not null unique',
    'password_hash' => 'text not null',
    'role' => 'int not null default 0',
    'name' => 'string not null',
    'verified' => 'int not null default 0',
]);

$db->sql('create unique index if not exists '.
    'unique_users_email on users(email)');

$db->create('verification_link', [
    'token' => 'string not null',
    'user_id' => 'int references user(id)',
]);

$db->create('vocabs', [
    'hanzi' => 'string nullable',
    'pinyin' => 'string nullable',
    'translation' => 'string nullable',
    'author_id' => 'int references user(id)',
]);

$db->create('progresses', [
    'vocab_id' => 'int references vocabs(id)',
    'direction' => 'string not null',
    'progress' => 'int default 0',
    'user_id' => 'int references users(id)',
]);

$db->sql('create unique index if not exists '.
    'unique_progress on progresses(vocab_id, direction, user_id)');

$db->create('tags', [
    'owner_id' => 'int not null references users(id)',
    'label' => 'string not null',
]);

$db->sql('create unique index if not exists '.
    'unique_tags_owner_label on tags(owner_id, lower(label)) '.
    'where deleted_at is null');

$db->create('tag_vocab', [
    'vocab_id' => 'int not null references vocabs(id)',
    'tag_id' => 'int not null references tags(id)',
]);

$db->sql('create unique index if not exists '.
    'unique_tag_vocab on tag_vocab(vocab_id, tag_id)');

$db->create('tag_shares', [
    'tag_id' => 'int not null references tags(id)',
    'recipient_id' => 'int not null references users(id)',
]);

$db->sql('create unique index if not exists '.
    'unique_tag_shares on tag_shares(tag_id, recipient_id)');

$db->sql(<<<'SQL'
    create view if not exists visible_vocabs as

    select vocabs.*, tag_shares.recipient_id as user_id
        from vocabs
    inner join tag_vocab
        on tag_vocab.vocab_id = vocabs.id
    inner join tag_shares
        on tag_vocab.tag_id = tag_shares.tag_id
    where vocabs.deleted_at is null
        and tag_vocab.deleted_at is null
        and tag_shares.deleted_at is null

    union

    select *, author_id as user_id
    from vocabs
    where deleted_at is null
    SQL);

$db->sql(<<<'SQL'
    create view if not exists cards as

    select cards.vocab_id,
        cards.direction,
        cards.hanzi,
        cards.pinyin,
        cards.translation,
        cards.user_id,
        coalesce(progresses.progress, 0) as progress
    from (
        select id as vocab_id,
            'hanzi' as direction,
            hanzi as front,
            hanzi,
            pinyin,
            translation,
            deleted_at,
            user_id
        from visible_vocabs

        union

        select id as vocab_id,
            'pinyin' as direction,
            pinyin as front,
            hanzi,
            pinyin,
            translation,
            deleted_at,
            user_id
        from visible_vocabs

        union

        select id as vocab_id,
            'translation' as direction,
            translation as front,
            hanzi,
            pinyin,
            translation,
            deleted_at,
            user_id
        from visible_vocabs

        union

        select vv.id as vocab_id,
            'strokes' as direction,
            vv.pinyin as front,
            vv.hanzi,
            vv.pinyin,
            vv.translation,
            vv.deleted_at,
            vv.user_id
        from visible_vocabs as vv
        inner join tag_vocab
            on vv.id = tag_vocab.vocab_id
            and tag_vocab.deleted_at is null
        inner join tags
            on tags.id = tag_vocab.tag_id
            and tags.strokes = 1
            and tags.deleted_at is null
        where vv.hanzi is not null
            and vv.hanzi is not ''
    ) as cards

    left join progresses on cards.vocab_id = progresses.vocab_id
        and progresses.user_id = cards.user_id
        and cards.direction = progresses.direction
        and progresses.deleted_at is null

    where cards.deleted_at is null
        and front is not null and front is not '';
    SQL);

$db->create('auth_tokens', [
    'user_id' => 'int not null',
    'selector' => 'varchar(24) not null',
    'hashed_validator' => 'varchar(64) not null',
    'expires' => 'datetime not null',
]);

$db->create('queue', [
    'user_id' => 'int not null references users(id)',
    'vocab_id' => 'int not null references vocabs(id)',
    'direction' => 'string not null',
]);

$db->addColumns('tags', [
    'strokes' => 'boolean not null default 0',
]);
