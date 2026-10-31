<?php

namespace App\Controllers;

use PXP\Http\Controllers\Controller;
use PXP\Http\Response\Redirect;

class MainController extends Controller
{
    public function index()
    {
        if (! auth()) {
            return Redirect::route('login');
        }

        return view('main');
    }
}
