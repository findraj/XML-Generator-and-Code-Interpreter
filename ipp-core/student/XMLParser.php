<?php
/**
 * @author Jan Findra (xfindr01)
 */

namespace IPP\Student;

use DOMDocument;
use DOMElement;
use IPP\Student\InstructionArray;

/**
 * XML parser for parsing XML source code
 */
class XMLParser
{
    /** @var DOMElement $root */
    private DOMElement $root;

    public function __construct(protected DOMDocument $domDocument)
    {
        $this->root = $domDocument->firstElementChild; // XML root
        $this->checkRoot($this->root);
    }

    /**
     * Check root element
     * @param DOMElement $root
     */
    private function checkRoot(DOMElement $root) : void
    {
        if ($root->tagName != "program") // Check if root element is program
        {
            throw new InvalidSourceStructureException("Root element must be program");
        }

        $rootAttributeList = ["language", "name", "description"];
        foreach ($root->attributes as $attribute) // go through all attributes
        {
            $attributeName = $attribute->nodeName;
            if (!in_array($attributeName, $rootAttributeList)) // check if the attribute is valid
            {
                throw new InvalidSourceStructureException("Invalid attribute in root element");
            }
        }

        $language = $root->getAttribute("language");
        if ($language == "") // check if the language attribute is present
        {
            throw new InvalidSourceStructureException("Missing attribute language");
        }
        if ($language != "IPPcode24") // check if the language attribute is valid
        {
            throw new InvalidSourceStructureException("Invalid attribute language");
        }
    }

    /**
     * Check instructions
     * @return InstructionArray
     */
    public function checkInstructions() : InstructionArray
    {
        $instructionArray = new InstructionArray();
        $child = $this->root->firstElementChild;
        $order_array = array();
        while ($child != null) // go through all instructions
        {
            if ($child->tagName != "instruction") // check if the element is instruction
            {
                throw new InvalidSourceStructureException("Invalid element");
            }

            $order = $child->getAttribute("order");
            if ($order == "") // check if the order attribute is present
            {
                throw new InvalidSourceStructureException("Missing attribute");
            }

            $instruction = new Instruction($child);
            $instruction->order = intval($order);

            if ($child->getAttribute("opcode") == "") // check if the opcode attribute is present
            {
                throw new InvalidSourceStructureException("Missing attribute opcode");
            }

            if (in_array($order, $order_array)) // check if the order is unique
            {
                throw new InvalidSourceStructureException("Order must be unique");
            }
            $order_array[] = $order;

            if (intval($order) < 1)
            {
                throw new InvalidSourceStructureException("Order must be positive integer");
            }

            $instructionArray->insertInstruction($instruction);
            $child = $child->nextElementSibling;
        }

        return $instructionArray;
    }
}