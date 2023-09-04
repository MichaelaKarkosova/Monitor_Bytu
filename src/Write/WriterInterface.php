<?php
namespace App\Write;

use App\ValueObject\apartment;
use App\ValueObject\ApartmentsResult;

interface WriterInterface {
    /**
     * @param apartment[] $products
     * @return void
     */
    public function write(ApartmentsResult $reader): void;
    public function writeDetails(array $details): void;

}
