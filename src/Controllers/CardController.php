<?php

namespace App\Controllers;

use PXP\Http\Controllers\Controller;
use App\Models\Vocab;
use App\Models\Progress;
use App\Card;

class CardController extends Controller
{
    public function unbury(int $vocabId, string $direction)
    {
        $user = user();
        $vocab = Vocab::find($vocabId);

        if (! $vocab->canShow()) {
            Notification::warn('You cannot unbury this card.');

            return redirect('cardbox.level', level: -1);
        }

        $card = Card::find(user: $user, vocab: $vocab, direction: $direction);

        $progress = $card->progress();
        $progress->progress = 0;
        $progress->save();

        return redirect('cardbox.level', level: -1);
    }
}