<?php

namespace IPP\Student;

use DOMDocument;
use DOMElement;
use IPP\Student\InstructionArray;

class XMLParser
{
    private DOMElement $root;

    public function __construct(protected DOMDocument $domDocument)
    {
        $this->root = $domDocument->firstElementChild; // XML root
        $this->checkRoot($this->root);
    }

    private function checkRoot(DOMElement $root) : void
    {
        if ($root->tagName != "program")
        {
            throw new InvalidSourceStructureException("Root element must be program");
        }

        $rootAttributeList = ["language", "name", "description"];
        foreach ($root->attributes as $attribute) {
            $attributeName = $attribute->nodeName;
            if (!in_array($attributeName, $rootAttributeList)) {
                throw new InvalidSourceStructureException("Invalid attribute in root element");
            }
        }

        $language = $root->getAttribute("language");
        if ($language == "")
        {
            throw new InvalidSourceStructureException("Missing attribute language");
        }
        if ($language != "IPPcode24")
        {
            throw new InvalidSourceStructureException("Invalid attribute language");
        }
    }

    public function checkInstructions() : InstructionArray
    {
        $instructionArray = new InstructionArray();
        $child = $this->root->firstElementChild;
        $order_array = array();
        while ($child != null)
        {
            if ($child->tagName != "instruction")
            {
                throw new InvalidSourceStructureException("Invalid element");
            }

            $order = $child->getAttribute("order");
            if ($order == "")
            {
                throw new InvalidSourceStructureException("Missing attribute");
            }

            $instruction = new Instruction($child);
            $instruction->order = intval($order);

            if ($child->getAttribute("opcode") == "")
            {
                throw new InvalidSourceStructureException("Missing attribute opcode");
            }

            if (in_array($order, $order_array))
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