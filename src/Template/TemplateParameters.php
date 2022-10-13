<?php

namespace App\Template;

use ArrayIterator;
use IteratorAggregate;

abstract class TemplateParameters implements IteratorAggregate {
    public function getIterator(): ArrayIterator {
        return new ArrayIterator((array) $this);
    }
}
