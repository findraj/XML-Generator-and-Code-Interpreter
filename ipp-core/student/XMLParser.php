<?php

namespace IPP\Student;

use DOMDocument;
use DOMElement;
use IPP\Student\ErrorHandler;
use IPP\Core\ReturnCode;

class XMLParser
{
    public function __construct(protected DOMDocument $domDocument)
    {
        $root = $domDocument->firstElementChild; // XML root
        $this->checkRoot($root);
        $this->checkInstructions($root);
    }

    private function checkRoot(DOMElement $root)
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

    private function checkInstructions(DOMElement $root)
    {
        $child = $root->firstElementChild;
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

            if ($child->getAttribute("opcode") == "")
            {
                ErrorHandler::ErrorAndExit("Missing attribute", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }

            if (in_array($order, $order_array))
            {
                ErrorHandler::ErrorAndExit("Attribute order values must be unique", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }
            $order_array[] = $order;

            $argument_array = array();
            $argument = $child->firstElementChild;
            while ($argument != null)
            {
                $argument_tag = $argument->tagName;
                if (!preg_match("/arg[\d+]/i", $argument_tag))
                {
                    ErrorHandler::ErrorAndExit("Wrong argument format", ReturnCode::INVALID_SOURCE_STRUCTURE);
                }

                if (in_array($argument_tag, $argument_array))
                {
                    ErrorHandler::ErrorAndExit("Argument numbers must be unique", ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
                $argument_array[] = $argument_tag;

                $type = $argument->getAttribute("type");
                if ($type == "")
                {
                    ErrorHandler::ErrorAndExit("Argument must have attribute type", ReturnCode::INVALID_SOURCE_STRUCTURE);
                }

                if (!in_array($type, ['string', 'int', 'bool', 'label', 'type', 'nil', 'var']))
                {
                    ErrorHandler::ErrorAndExit("Wrong argument type", ReturnCode::INVALID_SOURCE_STRUCTURE);
                }

                $argument = $argument->nextElementSibling;
            }

            $child = $child->nextElementSibling;
        }
    }
}