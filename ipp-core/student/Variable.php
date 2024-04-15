<?php
/**
 * @author Jan Findra (xfindr01)
 */

namespace IPP\Student;

/**
 * Variable class for storing variable information
 */
class Variable
{
    /** @var string $name */
    public string $name;
    /** @var string|null $type */
    public ?string $type;
    /** @var string|null $value */
    public ?string $value;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->type = null;
        $this->value = null;
    }
}