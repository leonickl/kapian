<?php

namespace App\Controllers;

use PXP\Http\Controllers\Controller;
use App\Models\Tag;

class TagController extends Controller
{
    public function index()
    {
        return view('tags', [
            'tags' => Tag::forUser(user()->id),
        ]);
    }

    public function show(int $id)
    {
        return view('tag', [
            'tag' => Tag::find($id),
        ]);
    }

    public function share(int $id)
    {
        $tag = Tag::find($id);
    }
}