<?php

namespace App\Controllers;

use App\Models\Tag;
use PXP\Http\Controllers\Controller;
use PXP\Auth\Models\User;
use PXP\Http\Response\Redirect;
use App\Models\TagShare;
use PXP\Lib\Notification;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class TagController extends Controller
{
    public function index()
    {
        return view('tags', [
            'tags' => Tag::allVisible(),
        ]);
    }

    public function show(int $tagId)
    {
        $tag = Tag::find($tagId);

        if (! $tag->isShowable()) {
            Notification::warn('You cannot view this tag.');

            return Redirect::route('tags');
        }

        return view('tag', [
            'tag' => $tag,
            'people' => $tag->isShareable()
                ? User::all(['id', 'name'])
                    ->filter(fn ($user) => $user->id !== user()->id)
                    : [],
        ]);
    }

    public function share(int $tagId, int $recipientId)
    {
        $tag = Tag::find($tagId);
        $recipient = User::find($recipientId);

        if (! $tag->isShareable()) {
            Notification::warn('You cannot share this tag.');

            return Redirect::path(route('tags.show', tagId: $tag->id));
        }

        if ($tag->author_id === $recipient->id) {
            Notification::warn('You cannot share a tag with yourself.');

            return Redirect::path(route('tags.show', tagId: $tag->id));
        }

        $existing = TagShare::select()
            ->whereIs('tag_id', $tag->id)
            ->whereIs('recipient_id', $recipient->id)
            ->deleted()
            ->get();

        if ($existing->count() === 0) {
            $share = TagShare::create(
                tag_id: $tag->id,
                recipient_id: $recipient->id,
            );

            Notification::success('Tag is now shared with '.$recipient->name().'.');

            return Redirect::path(route('tags.show', tagId: $tag->id));
        }

        $share = $existing->first();

        if ($share->deleted_at !== null) {
            $share->restore();

            Notification::success('Tag is now shared with '.$recipient->name().'.');

            return Redirect::path(route('tags.show', tagId: $tag->id));
        }

        $modifiedAt = strtotime($share->modified_at);
        $tenMinutesAgo = strtotime('10 minutes ago');

        if ($modifiedAt <= $tenMinutesAgo) {
            $diff = Carbon::parse($share->modified_at)->locale('en')->diffForHumans([
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            ]);

            Notification::warn("You can only unshare a tag within the first 10 minuntes. Now, is's already $diff ago.");

            return Redirect::path(route('tags.show', tagId: $tag->id));
        }

        $share->delete();

        Notification::info('Stopped sharing with '.$share->recipient()->name.'.');

        return Redirect::path(route('tags.show', tagId: $tag->id));
    }
}
