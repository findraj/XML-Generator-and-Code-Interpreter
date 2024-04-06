<?php

namespace IPP\Student;

use IPP\Core\ReturnCode;

/**
 * Exception for source structure errors
 */
class ErrorHandler
{
    public static function ErrorAndExit(string $message = "Unknown error", int $returnCode = ReturnCode::INTERNAL_ERROR)
    {
        fwrite(STDERR, $message);
        exit($returnCode);
    }
}
