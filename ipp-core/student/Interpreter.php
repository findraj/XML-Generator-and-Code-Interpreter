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
                ErrorHandler::ErrorAndExit("Wrong operand", ReturnCode::OPERAND_VALUE_ERROR);
            }
            else
            {
                $params = $instructionArray->instructionDictionary[$instruction->name];

                if (count($params) != count($instruction->args))
                {
                    ErrorHandler::ErrorAndExit("Wrong operand", ReturnCode::OPERAND_TYPE_ERROR);
                }

                $index = 0;
                foreach ($params as $param)
                {
                    if ($param != "symb")
                    {
                        if ($param != $instruction->args[$index]->type)
                        {
                            ErrorHandler::ErrorAndExit("Wrong operand", ReturnCode::OPERAND_TYPE_ERROR);
                        }
                    }
                $index += 1;
                }
            }
            if ($instruction->name == "MOVE")
            {
                $symb = $this->getSymb($instruction->args[1]->value);
                $this->frame->setVar($instruction->args[0]->value, $symb[0], $symb[1]);
            }
            else if ($instruction->name == "CREATEFRAME")
            {

            }

            $instruction = $instructionArray->getNextInstruction();
        }

        return 0;
    }

    /** @return array<string> */
    public function getSymb(string $symb) : array
    {
        $result = array();
        if (strstr($symb, "F@")) // variable
        {
            $result[] = "var";
            $result[] = $this->frame->getVar($symb);
        }
        else
        {
            $exploded = explode("@", $symb);
            if (count($exploded) > 2)
            {
                ErrorHandler::ErrorAndExit("Wrong operand", ReturnCode::OPERAND_VALUE_ERROR);
            }
            $type = $exploded[0];
            if (count($exploded) == 1)
            {
                $value = "";
            }
            else
            {
                $value = $exploded[1];
            }
            $result[] = $type;
            $result[] = $value;

            if (!in_array($type, ['string', 'int', 'bool', 'label', 'type', 'nil', 'var']))
            {
                ErrorHandler::ErrorAndExit("Wrong operand", ReturnCode::OPERAND_VALUE_ERROR);
            }

            if ($type == "nil")
            {
                if ($value != "nil")
                {
                    ErrorHandler::ErrorAndExit("Wrong operand", ReturnCode::OPERAND_VALUE_ERROR);
                }
            }
        }
        return $result;
    }
}
