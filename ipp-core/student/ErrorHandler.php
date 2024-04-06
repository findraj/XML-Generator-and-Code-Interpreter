<?php

namespace IPP\Student;

/**
 * Exception for source structure errors
 */
class ErrorHandler
{
    public static function ErrorAndExit(string $message = "Unknown error", int $returnCode)
    {
        fwrite(STDERR, $message);
        exit($returnCode);
    }
}
