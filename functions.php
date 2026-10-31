<?php

use PXP\Auth\Models\User;
use PXP\Auth\Auth;

function user(): User
{
    return Auth::user()
        ?? error('An authenticated user is required.');
}