<?php

namespace App\Controllers;

use PXP\Http\Controllers\Controller;

class PWAController extends Controller
{
    public function manifest()
    {
        header('Content-Type: application/json');

        return json_encode([
            "name" => config('pwa.name'),
            "short_name" => config('pwa.short'),
            "start_url" => ".",
            "display" => "standalone",
            "background_color" => "#ffffff",
            "description" => config('pwa.description'),
            "icons" => [
                [
                    "src" => '/icon.svg',
                    "sizes" => "any",
                    "type" => "image/svg+xml",
                ],
            ],
        ], JSON_PRETTY_PRINT);
    }

    public function icon()
    {
        header('Content-Type: image/svg+xml');

        return file_get_contents(path('assets/icon.svg'));
    }

    public function sw()
    {
        header('Content-Type: application/javascript');

        return file_get_contents(path('assets/js/sw.js'));
    }

    public function js()
    {
        header('Content-Type: application/javascript');

        return file_get_contents(path('assets/js/app.js'));
    }
}