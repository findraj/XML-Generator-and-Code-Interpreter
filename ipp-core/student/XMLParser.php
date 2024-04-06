<?php

namespace IPP\Student;

use DOMDocument;
use DOMElement;
use DOMNode;
use IPP\Core\Exception\XMLException;
use IPP\Core\ReturnCode;

class XMLParser
{
    public function __construct(protected DOMDocument $domDocument)
    {
        $root = $domDocument->firstElementChild; // XML root
        $this->checkRoot($root);
    }

    private function checkRoot(DOMElement $root)
    {
        if ($root->tagName != "program")
        {
            throw new XMLException("Wrong root name");
            exit(ReturnCode::INVALID_XML_ERROR);
        }

        $rootAttributeList = ["language", "name", "description"];
        foreach ($root->attributes as $attribute) {
            $attributeName = $attribute->nodeName;
            if (!in_array($attributeName, $rootAttributeList)) {
                throw new XMLException("Wrong root element attributes");
                exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
            }
        }

        $language = $root->getAttribute("language");
        if ($language == "")
        {
            throw new XMLException("Root element does not contain language attribute");
            exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
        if ($language != "IPPcode24")
        {
            throw new XMLException("Attribute language has wrong value");
            exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
    }
}