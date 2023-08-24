<?php

namespace App\Read;

use App\ValueObject\JobsResult;

interface ReaderInterface {
    public function read(string $source): JobsResult;
    public function getDetails(): array;
}
