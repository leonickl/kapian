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