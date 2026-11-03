<?php

namespace App\Middleware;

use App\RememberSession as RememberSessionService;
use PXP\Auth\Auth;
use PXP\Data\Query\Select;
use PXP\Http\Middleware\Middleware;

class RememberSession extends Middleware
{
    public function apply(): mixed
    {
        if (Auth::auth() || ! isset($_COOKIE['remember_me'])) {
            return true;
        }

        [$selector, $validator] = explode(':', $_COOKIE['remember_me'], 2);

        $token = new Select(['*'])
            ->from('auth_tokens')
            ->whereIs('selector', $selector)
            ->where('expires', '>=', date('Y-m-d H:i:s'))
            ->execute()
            ->first();

        if (! $token || ! hash_equals($token->hashed_validator, hash('sha256', $validator))) {
            new RememberSessionService()->destroySession();

            return true;
        }

        new RememberSessionService()->login($token);

        return true;
    }
}
