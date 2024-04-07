<?php

namespace IPP\Student;

class InstructionArray
{
    /** @var array<Instruction> $array */
    public array $array;
    /** @var array<Label> $labelArray */
    public array $labelArray;
    public int $instructionCounter;
    public int $current;

    public function __construct()
    {
        $this->array = array();
        $this->instructionCounter = 0;
        $this->current = 0;
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
        $instruction = $this->getNextInstruction();
        while ($instruction != null)
        {
            if ($instruction->name == "LABEL")
            {
                $label = new Label($this->current, $instruction->args[0]->value);
                $this->labelArray[] = $label;
            }
            $instruction = $this->getNextInstruction();
        }
        $this->current = 0;
    }
}