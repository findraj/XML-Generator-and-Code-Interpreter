<?php

namespace IPP\Student;

use DOMElement;
use IPP\Core\ReturnCode;
use IPP\Student\Argument;

class Instruction
{
    public int $order;
    public string $name;
    public int $argsCount;
    /** @var  array<Argument> $args */
    public array $args;

    public function __construct(protected DOMElement $element)
    {
        $this->name = $element->getAttribute("opcode");
        $this->argsCount = 0;
        $this->args = array();

        $subElement_array = array();
        $subElement = $element->firstElementChild;
        while ($subElement != null) {
            $argument = new Argument();

            $subElement_tag = $subElement->tagName;
            if (!preg_match("/arg[0-3]/i", $subElement_tag)) {
                ErrorHandler::ErrorAndExit("Wrong argument format", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }
            $argOrder = preg_replace('/\D/', '', $subElement_tag);
            $argument->order = $argOrder;

            if (in_array($subElement_tag, $subElement_array)) {
                ErrorHandler::ErrorAndExit("Argument numbers must be unique", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }
            $subElement_array[] = $subElement_tag;

            $type = $subElement->getAttribute("type");
            $argument->type = $type;

            if ($type == "") {
                ErrorHandler::ErrorAndExit("Argument must have attribute type", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }

            if (!in_array($type, ['string', 'int', 'bool', 'label', 'type', 'nil', 'var'])) {
                ErrorHandler::ErrorAndExit("Wrong argument type", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }

            $argument->value = trim($subElement->firstChild->textContent);

            $this->args[] = $argument;
            $this->argsCount += 1;
            $subElement = $subElement->nextElementSibling;
        }
        $this->sort();
        if (count($this->args) != $this->args[count($this->args) - 1]->order)
        {
            ErrorHandler::ErrorAndExit("Wrong argument type", ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
    }

    private function compareByOrder(Argument $a, Argument $b) : int
    {
        if ($a->order == $b->order) {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

    public function sort() : void
    {
        usort($this->args, array($this, 'compareByOrder'));
    }
}
