<?php

namespace App\Template;

interface TemplateInterface {
    public function setParameters(iterable $parameters): void;

    public function addParameter(string $name, $value): void;

    public function setFile(string $filename): void;

    public function render(): void;
}