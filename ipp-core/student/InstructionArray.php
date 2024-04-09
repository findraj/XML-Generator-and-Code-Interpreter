<?php

namespace IPP\Student;

use IPP\Core\ReturnCode;

class InstructionArray
{
    /** @var array<Instruction> $array */
    public array $array;
    /** @var array<Label> $labelArray */
    public array $labelArray;
    /** @var array<array<string>> $instructionDictionary */
    public array $instructionDictionary;
    public int $instructionCounter;
    public int $current;

    public function __construct()
    {
        $this->array = array();
        $this->instructionCounter = 0;
        $this->current = 0;
        $this->instructionDictionary = array(
            "MOVE" => array("var", "symb"),
            "CREATEFRAME" => array(),
            "PUSHFRAME" => array(),
            "POPFRAME" => array(),
            "DEFVAR" => array("var"),
            "CALL" => array("label"),
            "RETURN" => array(),
            "PUSHS" => array("symb"),
            "POPS" => array("var"),
            "ADD" => array("var", "symb", "symb"),
            "SUB" => array("var", "symb", "symb"),
            "MUL" => array("var", "symb", "symb"),
            "IDIV" => array("var", "symb", "symb"),
            "LT" => array("var", "symb", "symb"),
            "GT" => array("var", "symb", "symb"),
            "EQ" => array("var", "symb", "symb"),
            "AND" => array("var", "symb", "symb"),
            "OR" => array("var", "symb", "symb"),
            "NOT" => array("var", "symb"),
            "INT2CHAR" => array("var", "symb"),
            "STRI2INT" => array("var", "symb", "symb"),
            "READ" => array("var", "type"),
            "WRITE" => array("symb"),
            "CONCAT" => array("var", "symb", "symb"),
            "STRLEN" => array("var", "symb"),
            "GETCHAR" => array("var", "symb", "symb"),
            "SETCHAR" => array("var", "symb", "symb"),
            "TYPE" => array("var", "symb"),
            "LABEL" => array("label"),
            "JUMP" => array("label"),
            "JUMPIFEQ" => array("label", "symb", "symb"),
            "JUMPIFNEQ" => array("label", "symb", "symb"),
            "EXIT" => array("symb"),
            "DPRINT" => array("symb"),
            "BREAK" => array()
        );
        
    }

    public function insertInstruction(Instruction $instruction) : void
    {
        $this->instructionCounter += 1;
        $this->array[] = $instruction;
    }

    public function getNextInstruction() : ?Instruction
    {
        $this->current += 1;
        if ($this->current <= $this->instructionCounter)
        {
            return $this->array[$this->current - 1];
        }
        else
        {
            return null;
        }
    }

    private function compareByOrder(Instruction $a, Instruction $b) : int
    {
        if ($a->order == $b->order) {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

    public function sort() : void
    {
        usort($this->array, array($this, 'compareByOrder'));
    }

    public function findLabels() : void
    {
        $this->labelArray = array();
        $instruction = $this->getNextInstruction();
        while ($instruction != null)
        {
            if ($instruction->name == "LABEL")
            {
                $label = new Label($this->current, $instruction->args[0]->value);
                foreach ($this->labelArray as $labelObject)
                {
                    if ($labelObject->label == $label->label)
                    {
                        ErrorHandler::ErrorAndExit("Label already exists", ReturnCode::SEMANTIC_ERROR);
                    }
                }
                $this->labelArray[] = $label;
            }
            $instruction = $this->getNextInstruction();
        }
        $this->current = 0;
    }

    public function getLabel(string $label) : ?Label
    {
        foreach ($this->labelArray as $labelObject)
        {
            if ($labelObject->label == $label)
            {
                return $labelObject;
            }
        }
        ErrorHandler::ErrorAndExit("Label not found", ReturnCode::SEMANTIC_ERROR);
        return null;
    }
}