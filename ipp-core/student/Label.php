<?php

namespace IPP\Student;

class Label
{
    public int $line;
    public string $label;

    public function __construct(int $line, string $label)
    {
        $this->line = $line;
        $this->label = $label;
    }
}