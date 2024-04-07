<?php

namespace IPP\Student;

use DOMElement;

class Argument
{
    public string $type;
    public string $value;

    public function __construct(protected DOMElement $element)
    {
        $this->type = $element->getAttribute("type");
        $this->value = $element->firstChild->nodeName;
    }
}