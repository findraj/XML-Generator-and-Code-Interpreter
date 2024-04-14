<?php

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for source structure errors
 */
class InvalidSourceStructureException extends IPPException
{
    public function __construct(string $message = "Invalid source structure error", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INVALID_SOURCE_STRUCTURE, $previous);
    }
}