<?php

namespace App\View;

use App\Template\TemplateParameters;

final class JobsTemplateParameters extends TemplateParameters {
    public array $apartments;

    public int $page;

    public int $count;

}