<?php

namespace App\Controllers;

use App\Card;
use App\Models\QueueItem;
use App\Models\Tag;
use App\Models\TagShare;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use PXP\Auth\Models\User;
use PXP\Http\Controllers\Controller;
use PXP\Lib\Notification;

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

        if (! $tag->canShow()) {
            Notification::warn('You cannot view this tag.');

            return redirect('tags');
        }

        $people = $tag->canShare()
            ? User::all(['id', 'name'])
                ->filter(fn ($user) => $user->id !== user()->id)
            : [];

        return view('tag', [
            'tag' => $tag,
            'people' => $people,
        ]);
    }

    public function edit(int $tagId)
    {
        $tag = Tag::find($tagId);

        if (! $tag->canEdit()) {
            Notification::warn('You cannot edit this tag.');

            return redirect('tags.show', tagId: $tagId);
        }

        return view('tags.edit', [
            'tag' => $tag,
        ]);
    }

    public function update(int $tagId)
    {
        $tag = Tag::find($tagId);

        if (! $tag->canEdit()) {
            Notification::warn('You cannot edit this tag.');

            return redirect('tags.show', tagId: $tagId);
        }

        $data = request()->validate(fn ($req) => [
            $req->label->string(),
        ]);

        $tag->label = $data->label;
        $tag->save();

        Notification::success('Successfully updated tag.');

        return redirect('tags.show', tagId: $tagId);
    }

    public function toggleStrokes(int $tagId)
    {
        $tag = Tag::find($tagId);

        if (! $tag->canEdit()) {
            Notification::warn('You cannot edit this tag.');

            return redirect('tags.show', tagId: $tagId);
        }

        $tag->strokes = ! $tag->strokes;
        $tag->save();

        if ($tag->strokes) {
            Notification::success('Enabled stroke cards for this tag.');
        } else {
            Notification::warn('Disabled stroke cards for this tag.');
        }

        return redirect('tags.show', tagId: $tagId);
    }


    public function share(int $tagId, int $recipientId)
    {
        $tag = Tag::find($tagId);
        $recipient = User::find($recipientId);

        if (! $tag->canShare()) {
            Notification::warn('You cannot share this tag.');

            return redirect('tags.show', tagId: $tag->id);
        }

        if ($tag->author_id === $recipient->id) {
            Notification::warn('You cannot share a tag with yourself.');

            return redirect('tags.show', tagId: $tag->id);
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

            return redirect('tags.show', tagId: $tag->id);
        }

        $share = $existing->first();

        if ($share->deleted_at !== null) {
            $share->restore();

            Notification::success('Tag is now shared with '.$recipient->name().'.');

            return redirect('tags.show', tagId: $tag->id);
        }

        $modifiedAt = strtotime($share->modified_at);
        $tenMinutesAgo = strtotime('10 minutes ago');

        if ($modifiedAt <= $tenMinutesAgo) {
            $diff = Carbon::parse($share->modified_at)->locale('en')->diffForHumans([
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            ]);

            Notification::warn("You can only unshare a tag within the first 10 minuntes. Now, is's already $diff ago.");

            return redirect('tags.show', tagId: $tag->id);
        }

        $share->delete();

        Notification::info('Stopped sharing with '.$share->recipient()->name.'.');

        return redirect('tags.show', tagId: $tag->id);
    }

    public function study(int $tagId)
    {
        $tag = Tag::find($tagId);

        if (! $tag->canShow()) {
            Notification::warn('You cannot study this tag.');

            return redirect('study');
        }

        $user = user();

        $before = QueueItem::forUser($user)->count();

        QueueItem::push(
            user: $user,
            cards: Card::forTag($tag, $user),
        );

        $after = QueueItem::forUser($user)->count();

        $diff = $after - $before;

        Notification::success("Added $diff cards to the study-queue.");

        return redirect('study');
    }
}
