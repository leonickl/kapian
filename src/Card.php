<?php

namespace App;

use App\Models\Vocab;

readonly class Card
{
    public function __construct(
        public int $vocab_id,
        public string $direction,
        public ?string $front,
        public ?string $back_top,
        public ?string $back_bottom,
    ) {}

    public function vocab(): Vocab
    {
        return Vocab::find($this->vocab_id);
    }
}
