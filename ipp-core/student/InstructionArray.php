<?php
/**
 * @author Jan Findra (xfindr01)
 */

namespace IPP\Student;

/**
 * Class InstructionArray represents an array of instructions in the IPPcode24 language.
 */
class InstructionArray
{
    /** @var array<Instruction> $array */
    public array $array;
    /** @var array<Label> $labelArray */
    public array $labelArray;
    /** @var array<array<string>> $instructionDictionary */
    public array $instructionDictionary;
    /** @var int $instructionCounter */
    public int $instructionCounter;
    /** @var int $current */
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

    /**
     * Inserts an instruction into the array.
     * @param Instruction $instruction Instruction to be inserted.
     */
    public function insertInstruction(Instruction $instruction) : void
    {
        $this->instructionCounter += 1;
        $this->array[] = $instruction;
    }

    /**
     * Returns the next instruction in the array.
     * @return Instruction|null Next instruction in the array.
     */
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

    /**
     * Compares two instructions by their order.
     * @param Instruction $a First instruction.
     * @param Instruction $b Second instruction.
     * @return int -1 if a < b, 0 if a = b, 1 if a > b.
     */
    private function compareByOrder(Instruction $a, Instruction $b) : int
    {
        if ($a->order == $b->order)
        {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

    /**
     * Sorts the instructions by their order.
     */
    public function sort() : void
    {
        usort($this->array, array($this, 'compareByOrder'));
    }

    /**
     * Finds all labels in the array and stores them in the labelArray.
     */
    public function findLabels() : void
    {
        $this->labelArray = array();
        $instruction = $this->getNextInstruction();
        while ($instruction != null) // find all labels
        {
            if ($instruction->name == "LABEL")
            {
                $label = new Label($this->current, $instruction->args[0]->value);
                foreach ($this->labelArray as $labelObject)
                {
                    if ($labelObject->label == $label->label)
                    {
                        throw new SemanticException("Label already exists");
                    }
                }
                $this->labelArray[] = $label;
            }
            $instruction = $this->getNextInstruction();
        }
        $this->current = 0;
    }

    /**
     * Returns a label object by its label.
     * @param string $label Label to be found.
     * @return Label|null Label object.
     */
    public function getLabel(string $label) : ?Label
    {
        foreach ($this->labelArray as $labelObject) // go through all labels
        {
            if ($labelObject->label == $label) // find the label
            {
                return $labelObject;
            }
        }
        throw new SemanticException("Label not found");
    }
}