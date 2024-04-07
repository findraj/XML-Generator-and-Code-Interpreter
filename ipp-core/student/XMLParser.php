<?php

namespace IPP\Student;

use DOMDocument;
use DOMElement;
use IPP\Student\ErrorHandler;
use IPP\Core\ReturnCode;
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
            ErrorHandler::ErrorAndExit("Wrong root name", ReturnCode::INVALID_SOURCE_STRUCTURE);
            exit(ReturnCode::INVALID_XML_ERROR);
        }

        $rootAttributeList = ["language", "name", "description"];
        foreach ($root->attributes as $attribute) {
            $attributeName = $attribute->nodeName;
            if (!in_array($attributeName, $rootAttributeList)) {
                ErrorHandler::ErrorAndExit("Wrong root element attributes", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }
        }

        $language = $root->getAttribute("language");
        if ($language == "")
        {
            ErrorHandler::ErrorAndExit("Root element does not contain language attribute", ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
        if ($language != "IPPcode24")
        {
            ErrorHandler::ErrorAndExit("Attribute language has wrong value", ReturnCode::INVALID_SOURCE_STRUCTURE);
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
                ErrorHandler::ErrorAndExit("Wrong element", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }

            $order = $child->getAttribute("order");
            if ($order == "")
            {
                ErrorHandler::ErrorAndExit("Missing attribute", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }

            $instruction = new Instruction($child);
            $instruction->order = intval($order);

            if ($child->getAttribute("opcode") == "")
            {
                ErrorHandler::ErrorAndExit("Missing attribute", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }

            if (in_array($order, $order_array))
            {
                ErrorHandler::ErrorAndExit("Attribute order values must be unique", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }
            $order_array[] = $order;

            if (intval($order) < 1)
            {
                ErrorHandler::ErrorAndExit("Attribute order value must be at least 1", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }

            $instructionArray->insertInstruction($instruction);
            $child = $child->nextElementSibling;
        }

        return $instructionArray;
    }
}