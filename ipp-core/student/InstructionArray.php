<?php

namespace IPP\Student;

class InstructionArray
{
    /** @var array<Instruction> $array */
    public array $array;
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
        if ($this->current <= $this->instructionCounter)
        {
            $this->current += 1;
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
}