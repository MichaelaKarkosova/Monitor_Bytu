<?php
namespace App\Write;

use App\ValueObject\Job;
use App\ValueObject\JobsResult;

interface WriterInterface {
    /**
     * @param Job[] $products
     * @return void
     */
    public function write(JobsResult $reader): void;
    public function writeDetails(array $details): void;

}
