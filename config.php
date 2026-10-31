<?php

use PXP\Auth\Models\Identity;
use PXP\Auth\Models\User;

return [
    'title' => 'Kǎpiàn',
    'app-url' => env('APP_URL', 'http://localhost:8087'),
    'port' => 8087,

    'css' => [
        'media',
        'colors',
        'base',
        'snippets',
        'button',
        'table',
        'notification',
        'components',
        'form',
        'mobile',
        'flashcard',
    ],

    'mail' => (object) [
        'host' => env('MAIL_HOST'),
        'user' => env('MAIL_USER'),
        'pass' => env('MAIL_PASS'),
        'port' => env('MAIL_PORT'),
    ],

    'modules' => [
        'auth' => 'leonickl/pxp-auth',
    ],

    'resolver' => [
        Identity::class => User::class,
    ],
];
