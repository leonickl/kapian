<?php

use PXP\Auth\Auth;
use PXP\Auth\Models\User;

function user(): User
{
    return Auth::user()
        ?? error('An authenticated user is required.');
}
