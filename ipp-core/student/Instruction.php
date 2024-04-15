<?php
/**
 * @author Jan Findra (xfindr01)
 */

namespace IPP\Student;

use DOMElement;
use IPP\Student\Argument;

/**
 * Class Instruction represents an instruction in the IPPcode24 language.
 */
class Instruction
{
    /** @var int $order */
    public int $order;
    /** @var string $name */
    public string $name;
    /** @var int $argsCount */
    public int $argsCount;
    /** @var array<Argument> $args */
    public array $args;

    public function __construct(protected DOMElement $element)
    {
        $this->name = $element->getAttribute("opcode");
        $this->argsCount = 0;
        $this->args = array();

        $subElement_array = array();
        $subElement = $element->firstElementChild;
        while ($subElement != null) // iterate over arguments
        {
            $argument = new Argument();

            $subElement_tag = $subElement->tagName;
            if (!preg_match("/arg[0-3]/i", $subElement_tag)) // check if the tag is arg1, arg2 or arg3
            {
                throw new InvalidSourceStructureException("Invalid element");
            }
            $argOrder = preg_replace('/\D/', '', $subElement_tag);
            $argument->order = intval($argOrder);

            if (in_array($subElement_tag, $subElement_array)) // check if the tag is unique
            {
                throw new InvalidSourceStructureException("Invalid element");
            }
            $subElement_array[] = $subElement_tag;

            $type = $subElement->getAttribute("type");
            $argument->type = $type;

            if ($type == "") // check if the type is not empty
            {
                throw new InvalidSourceStructureException("Missing attribute");
            }

            if (!in_array($type, ['string', 'int', 'bool', 'label', 'type', 'nil', 'var'])) // check if the type is valid
            {
                throw new InvalidSourceStructureException("Invalid attribute");
            }

            if ($subElement->firstChild != null) // check if the value is not empty
            {
                $argument->value = trim($subElement->firstChild->textContent);
            }

            $this->args[] = $argument;
            $this->argsCount += 1;
            $subElement = $subElement->nextElementSibling;
        }
        $this->sort();
        if (count($this->args) != 0 && count($this->args) != $this->args[count($this->args) - 1]->order) // check if the arguments are in order
        {
            throw new InvalidSourceStructureException("Invalid element");
        }
    }

    /**
     * Compare two arguments by their order.
     * @param Argument $a First argument.
     * @param Argument $b Second argument.
     * @return int -1 if a < b, 0 if a = b, 1 if a > b.
     */
    private function compareByOrder(Argument $a, Argument $b) : int
    {
        if ($a->order == $b->order)
        {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

    /**
     * Sort the arguments by their order.
     */
    public function sort() : void
    {
        usort($this->args, array($this, 'compareByOrder'));
    }
}
