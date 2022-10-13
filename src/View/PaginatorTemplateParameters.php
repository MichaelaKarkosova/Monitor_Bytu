<?php

namespace App\View;

use App\Template\TemplateParameters;

final class PaginatorTemplateParameters extends TemplateParameters {
    public int $page;

    public array $apartments;

    public string $http;
}