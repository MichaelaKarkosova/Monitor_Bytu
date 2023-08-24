<?php

namespace App\ValueObject;

class JobsResult {
    public string $type;

    public array $apartments;

    public function __construct(string $type, array $apartments)
    {
        $this->type = $type;
        $this->apartments = $apartments;
    }
}