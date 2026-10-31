<?php

namespace App\Controllers;

use App\Models\Tag;
use App\Models\Vocab;
use PXP\Http\Controllers\Controller;
use PXP\Http\Response\Redirect;
use PXP\Http\Response\View;
use PXP\Lib\Notification;

class VocabController extends Controller
{
    public function index(): View
    {
        return view('vocabs', [
            'vocabs' => Vocab::allVisible(),
        ]);
    }

    public function create(): View
    {
        return view('vocabs.create', [
            'tags' => Tag::labelsForUser(user()->id),
            'previousTags' => session()->take('previous-tags'),
        ]);
    }

    public function store(): Redirect
    {
        $data = request()->validate(fn ($req) => [
            $req->hanzi->string()->nullable()->max(100),
            $req->pinyin->string()->nullable()->max(100),
            $req->translation->string()->nullable()->max(100),
            $req->tags->array()->default([]),
        ]);

        $labels = [];

        foreach (is_array($data->tags) ? $data->tags : [] as $tag) {
            if (! is_string($tag)) {
                continue;
            }

            $label = Tag::normalize($tag);

            if ($label !== '') {
                $labels[mb_strtolower($label)] ??= $label;
            }
        }

        $labels = array_values($labels);
        $user = user();

        $vocab = Vocab::create(
            hanzi: trim($data->hanzi),
            pinyin: trim($data->pinyin),
            translation: trim($data->translation),
            author_id: $user->id,
        );

        $tags = array_map(
            fn (string $label) => Tag::findOrCreate($user->id, $label),
            $labels,
        );

        $vocab->attachTags($tags);

        $summary = $vocab->summary();

        Notification::success("Created new vocab '$summary'.");

        if (request()->has('next-create')) {
            session([
                'previous-tags' => array_map(fn (Tag $tag) => $tag->label, $tags),
            ]);
        }

        return Redirect::route(match (true) {
            request()->has('next-index') => 'vocabs',
            request()->has('next-create') => 'vocabs.create',
            default => 'vocabs',
        });
    }
}
