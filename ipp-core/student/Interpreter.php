<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;
use IPP\Core\Exception\XMLException;
use IPP\Core\ReturnCode;
use IPP\Student\XMLParser;
use IPP\Student\Frame;

class Interpreter extends AbstractInterpreter
{
    private Frame $frame;

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
        $instructionArray->findLabels();

        // $instruction = $instructionArray->getNextInstruction();
        // while ($instruction != null)
        // {
        //     print_r($instruction);
        //     $instruction = $instructionArray->getNextInstruction();
        // }

        $this->frame = new Frame();

        $instruction = $instructionArray->getNextInstruction();
        while ($instruction != null)
        {
            if (!array_key_exists($instruction->name, $instructionArray->instructionDictionary))
            {
                ErrorHandler::ErrorAndExit("Wrong operand", ReturnCode::INVALID_SOURCE_STRUCTURE);
            }
            else
            {
                $params = $instructionArray->instructionDictionary[$instruction->name];

                if (count($params) != count($instruction->args))
                {
                    ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->name, ReturnCode::INVALID_SOURCE_STRUCTURE);
                }

                $index = 0;
                foreach ($params as $param)
                {
                    if ($param != "symb")
                    {
                        if ($param != $instruction->args[$index]->type)
                        {
                            ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->name, ReturnCode::OPERAND_TYPE_ERROR);
                        }
                    }
                    if ($param != "var") // check right argument definition and in case of variable, replace with saved value
                    {
                        $instruction->args[$index] = $this->getSymb($instruction->args[$index]);
                    }
                $index += 1;
                }
            }
            if ($instruction->name == "MOVE")
            {
                $symb = $this->getSymb($instruction->args[1]);
                $this->frame->setVar($instruction->args[0]->value, $symb->type, $symb->value);
            }
            else if ($instruction->name == "CREATEFRAME")
            {

            }

            $instruction = $instructionArray->getNextInstruction();
        }

        return 0;
    }

    public function getSymb(Argument $symb) : Argument
    {
        if ($symb->type == "var")
        {
            return $this->frame->getVar($symb->value);
        }
        else if ($symb->type == "int")
        {
            if (!is_numeric($symb->value))
            {
                ErrorHandler::ErrorAndExit("Wrong operand " . $symb->value, ReturnCode::OPERAND_VALUE_ERROR);
            }
            return $symb;
        }
        // TODO
    }
}
