<?php

namespace App\View;

use App\Template\TemplateParameters;

final class ApartmentsTemplateParameters extends TemplateParameters {
    public array $apartments;

    public int $page;

}