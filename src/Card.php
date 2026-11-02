<?php

namespace App;

use App\Models\Vocab;

readonly class Card
{
    public function __construct(
        public int $vocab_id,
        public string $direction,
        public ?string $hanzi,
        public ?string $pinyin,
        public ?string $translation,
    ) {}

    public function vocab(): Vocab
    {
        return Vocab::find($this->vocab_id);
    }
}
