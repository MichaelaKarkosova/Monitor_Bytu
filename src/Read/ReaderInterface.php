<?php

namespace App\Read;

use App\ValueObject\ApartmentsResult;

interface ReaderInterface {
    public function read(string $source): ApartmentsResult;
    public function getDetails(): array;
}
