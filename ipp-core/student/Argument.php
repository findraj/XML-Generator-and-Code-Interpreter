<?php
/**
 * @author Jan Findra (xfindr01)
 */

namespace IPP\Student;

use DOMElement;

/**
 * Class Argument represents an argument of an instruction.
 */
class Argument
{
    public int $order;
    public string $type;
    public ?string $value = null;
}