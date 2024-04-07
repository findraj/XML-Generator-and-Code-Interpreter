<?php

namespace IPP\Student;

class Variable
{
    public string $name;
    public ?string $type;
    public ?string $value;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->type = null;
        $this->value = null;
    }
}