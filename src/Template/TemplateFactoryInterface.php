<?php

namespace App\Template;

use App\Template\TemplateInterface;

interface TemplateFactoryInterface {
    public function create(string $filename, ?iterable $parameters = NULL): TemplateInterface;
}
