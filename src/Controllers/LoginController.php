<?php

namespace App\Controllers;

use PXP\Auth\Auth;
use PXP\Auth\Models\Identity;
use PXP\Auth\Models\Passkey;
use PXP\Auth\Passkeys;
use PXP\Auth\Validation\UserValidator;
use PXP\Http\Controllers\Controller;
use PXP\Http\Response\Redirect;
use PXP\Http\Response\Response;
use PXP\Lib\Notification;
use RuntimeException;
use PXP\Auth\Models\User;
use PXP\Data\DB;
use App\RememberSession;

class LoginController extends Controller
{
    public function form(): Response
    {
        if (Auth::auth()) {
            return redirect('main');
        }

        return view('login', [
            'passkeys' => config('auth.relying-party') !== null,
        ]);
    }

    public function login(): Response
    {
        if (request()->string('secret') === '') {
            Notification::warn('Bitte Passwort eingeben oder die Passkey-Anmeldung nutzen.');

            return redirect('login');
        }

        $request = request()->validate(
            (new UserValidator)->validateLogin(...),
        );

        if (! Auth::login($request)) {
            Notification::warn('Ungültige Zugangsdaten gegeben.');

            return redirect('login');
        }

        if (! Auth::user()) {
            return redirect('login');
        }

        if (request()->bool('remember')) {
            new RememberSession()->rememberSession();
        }

        return redirect('main');
    }

    public function logout(): Response
    {
        Auth::logout();

        new RememberSession()->destroySession();

        return redirect('main');
    }
}
