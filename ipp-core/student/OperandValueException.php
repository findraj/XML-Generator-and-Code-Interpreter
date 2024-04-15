<?php
/**
 * @author Jan Findra (xfindr01)
 */

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for operand value error
 */
class OperandValueException extends IPPException
{
    public function __construct(string $message = "Operand value error", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::OPERAND_VALUE_ERROR, $previous);
    }
}