<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;
use IPP\Core\Exception\XMLException;
use IPP\Student\XMLParser;

class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {
        // TODO: Start your code here
        // php interpret.php --source=./student/supplementary-test/interpret/read_test.src --input=./student/supplementary-test/interpret/read_test.in
        // Check \IPP\Core\AbstractInterpreter for predefined I/O objects:
        // $val = $this->input->readString();
        // $this->stdout->writeString($val);
        // $this->stdout->writeString("stdout");
        // $this->stderr->writeString("stderr");
        // throw new NotImplementedException;

        $dom = $this->source->getDOMDocument();
        $XMLparser = new XMLParser($dom);
        $instructionArray = $XMLparser->checkInstructions();
        $instructionArray->sort();

        // $instruction = $instructionArray->getNextInstruction();
        // while ($instruction != null)
        // {
        //     print_r($instruction);
        //     $instruction = $instructionArray->getNextInstruction();
        // }

        $frames = new Frame();

        return 0;
    }
}
