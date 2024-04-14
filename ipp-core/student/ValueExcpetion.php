<?php

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for source structure errors
 */
class ValueException extends IPPException
{
    public function __construct(string $message = "Value error", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::VALUE_ERROR, $previous);
    }
}