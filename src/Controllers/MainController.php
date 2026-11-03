<?php

namespace App\Controllers;

use PXP\Http\Controllers\Controller;

class MainController extends Controller
{
    public function index()
    {
        if (! auth()) {
            return redirect('login');
        }

        return view('main');
    }
}
