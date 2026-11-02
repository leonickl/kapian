<?php

namespace App\Controllers;

use App\Models\Tag;
use App\Models\Vocab;
use PXP\Ds\Obj;
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
        return view('vocabs.form', [
            'vocab' => null,
            'tags' => Tag::labelsForUser(user()->id),
            'selectedTags' => session()->take('previous-tags', []),
        ]);
    }

    public function edit(int $vocabId): View|Redirect
    {
        $vocab = Vocab::find($vocabId);

        if (! $vocab->canEdit()) {
            Notification::warn('You cannot edit this vocab.');

            return Redirect::route('vocabs');
        }

        return view('vocabs.form', [
            'vocab' => $vocab,
            'tags' => Tag::labelsForUser(user()->id),
            'selectedTags' => $vocab->tags()
                ->map(fn (Tag $tag) => $tag->label)
                ->toArray(),
        ]);
    }

    public function update(int $vocabId): Redirect
    {
        $vocab = Vocab::find($vocabId);

        if (! $vocab->canEdit()) {
            Notification::warn('You cannot edit this vocab.');

            return Redirect::route('vocabs');
        }

        $data = $this->validateVocab();
        $labels = $this->tagLabels($data->tags);

        $vocab
            ->fill(
                hanzi: trim((string) $data->hanzi),
                pinyin: trim((string) $data->pinyin),
                translation: trim((string) $data->translation),
            )
            ->save();

        $vocab->syncTags($this->resolveTags($labels));

        Notification::success("Updated vocab '{$vocab->summary()}'.");

        return Redirect::route('vocabs');
    }

    public function store(): Redirect
    {
        $data = $this->validateVocab();
        $labels = $this->tagLabels($data->tags);
        $user = user();

        $vocab = Vocab::create(
            hanzi: trim((string) $data->hanzi),
            pinyin: trim((string) $data->pinyin),
            translation: trim((string) $data->translation),
            author_id: $user->id,
        );

        $tags = $this->resolveTags($labels);

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

    private function validateVocab(): Obj
    {
        return request()->validate(fn ($req) => [
            $req->hanzi->string()->nullable()->max(100),
            $req->pinyin->string()->nullable()->max(100),
            $req->translation->string()->nullable()->max(100),
            $req->tags->array()->default([]),
        ]);
    }

    /**
     * @return list<string>
     */
    private function tagLabels(mixed $tags): array
    {
        $labels = [];

        foreach (is_array($tags) ? $tags : [] as $tag) {
            if (! is_string($tag)) {
                continue;
            }

            $label = Tag::normalize($tag);

            if ($label !== '') {
                $labels[mb_strtolower($label)] ??= $label;
            }
        }

        return array_values($labels);
    }

    /**
     * @param  list<string>  $labels
     * @return list<Tag>
     */
    private function resolveTags(array $labels): array
    {
        $user = user();

        return array_map(
            fn (string $label) => Tag::findOrCreate($user->id, $label),
            $labels,
        );
    }
}
