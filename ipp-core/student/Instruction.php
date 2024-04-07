<?php

namespace IPP\Student;

use DOMElement;
use IPP\Core\ReturnCode;

class Instruction
{
    public string $name;
    public int $argsCount;
    /** @var  array<DOMElement> $args */
    public array $args;

    public function __construct(protected DOMElement $element)
    {
        $this->name = $element->getAttribute("opcode");
        $this->argsCount = 0;
        $this->args = array();
        
        $arg1 = $element->getAttribute("arg1");
        if ($arg1 != "")
        {
            $this->args[] = $arg1;
            $this->argsCount += 1;
        }

        $arg2 = $element->getAttribute("arg2");
        if ($arg2 != "" && !in_array("arg1", $this->args))
        {
            ErrorHandler::ErrorAndExit("arg2 must follow arg1", ReturnCode::INVALID_SOURCE_STRUCTURE);
        }

        if ($arg2 != "")
        {
            $this->args[] = $arg2;
            $this->argsCount += 1;
        }

        $arg3 = $element->getAttribute("arg3");
        if ($arg3 != "" && !in_array("arg2", $this->args))
        {
            ErrorHandler::ErrorAndExit("arg3 must follow arg2", ReturnCode::INVALID_SOURCE_STRUCTURE);
        }

        if ($arg3 != "")
        {
            $this->args[] = $arg3;
            $this->argsCount += 1;
        }
    }
}