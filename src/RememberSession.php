<?php

namespace App;

use PXP\Auth\Models\User;
use PXP\Data\DB;

class RememberSession
{
    public function rememberSession(): void
    {
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(33));

        $hashedValidator = hash('sha256', $validator);
        $expires = date('Y-m-d H:i:s', time() + 60*60*24*30);  // 30 days

        DB::init()->insert('auth_tokens', [
            'user_id' => user()->id,
            'selector' => $selector,
            'hashed_validator' => $hashedValidator,
            'expires' => $expires,
        ]);

        setcookie(
            name: 'remember_me',
            value: $selector . ':' . $validator,
            expires_or_options: time() + 60*60*24*30,
            path: '/',
            domain: '',
            secure: true,
            httponly: true,
        );
    }

    public function destroySession(): void
    {
        setcookie('remember_me', '', time() - 3600, '/');
    }

    public function login(object $token): void
    {
        $user = User::find($token->user_id);

        session_regenerate_id();
        session(['identifier' => $user->identifier()]);

        $this->rotateSession($token);
    }

    public function rotateSession(object $token): void
    {
        DB::init()->delete('auth_tokens', $token->id);

        $this->rememberSession();
    }
}