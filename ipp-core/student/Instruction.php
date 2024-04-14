<?php

namespace IPP\Student;

use DOMElement;
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
                throw new InvalidSourceStructureException("Invalid element");
            }
            $argOrder = preg_replace('/\D/', '', $subElement_tag);
            $argument->order = intval($argOrder);

            if (in_array($subElement_tag, $subElement_array)) {
                throw new InvalidSourceStructureException("Invalid element");
            }
            $subElement_array[] = $subElement_tag;

            $type = $subElement->getAttribute("type");
            $argument->type = $type;

            if ($type == "") {
                throw new InvalidSourceStructureException("Missing attribute");
            }

            if (!in_array($type, ['string', 'int', 'bool', 'label', 'type', 'nil', 'var'])) {
                throw new InvalidSourceStructureException("Invalid attribute");
            }

            if ($subElement->firstChild != null) {
                $argument->value = trim($subElement->firstChild->textContent);
            }

            $this->args[] = $argument;
            $this->argsCount += 1;
            $subElement = $subElement->nextElementSibling;
        }
        $this->sort();
        if (count($this->args) != 0 && count($this->args) != $this->args[count($this->args) - 1]->order)
        {
            throw new InvalidSourceStructureException("Invalid element");
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
