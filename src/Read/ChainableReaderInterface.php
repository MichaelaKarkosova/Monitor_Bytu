<?php
namespace App\Read;

use App\Read\ReaderInterface;

interface ChainableReaderInterface extends ReaderInterface {
    public function canRead(string $source): bool;
}
