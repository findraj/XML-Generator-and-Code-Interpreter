<?php
/**
 * @author Jan Findra (xfindr01)
 */

namespace IPP\Student;

/**
 * Label class for storing label information
 */
class Label
{
    /** @var int $line */
    public int $line;
    /** @var string $label */
    public string $label;

    public function __construct(int $line, string $label)
    {
        $this->line = $line;
        $this->label = $label;
    }
}