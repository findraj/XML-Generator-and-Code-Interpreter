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
                    if ($param != "var") // check right constant definition
                    {
                        $instruction->args[$index] = $this->getSymb($instruction->args[$index]);
                    }
                $index += 1;
                }
            }
            switch ($instruction->name) {
                case "MOVE":
                    $symb = $this->getSymb($instruction->args[1]);
                    $this->frame->setVar($instruction->args[0]->value, $symb->type, $symb->value);
                    break;

                case "CREATEFRAME":
                    $this->frame->createFrame();
                    break;

                case "PUSHFRAME":
                    $this->frame->pushFrame();
                    break;

                case "POPFRAME":
                    $this->frame->popFrame();
                    break;

                case "DEFVAR":
                    $exploded = explode("@", $instruction->args[0]->value);
                    $this->frame->defVar($exploded[0], $exploded[1]);
                    break;

                case "CALL":
                    // TODO
                    break;

                case "RETURN":
                    // TODO
                    break;

                case "PUSHS":
                    // TODO
                    break;

                case "POPS":
                    // TODO
                    break;

                case "ADD":
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(intval($this->getSymb($instruction->args[1])->value) + intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "SUB":
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(intval($this->getSymb($instruction->args[1])->value) - intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "MUL":
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(intval($this->getSymb($instruction->args[1])->value) * intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "IDIV":
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(intval($this->getSymb($instruction->args[1])->value) / intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "LT":
                    $this->frame->setVar($instruction->args[0]->value, "bool", strval(intval($this->getSymb($instruction->args[1])->value) < intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "GT":
                    $this->frame->setVar($instruction->args[0]->value, "bool", strval(intval($this->getSymb($instruction->args[1])->value) > intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "EQ":
                    $this->frame->setVar($instruction->args[0]->value, "bool", strval(intval($this->getSymb($instruction->args[1])->value) == intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "AND":
                    $this->frame->setVar($instruction->args[0]->value, "bool", strval(boolval($this->getSymb($instruction->args[1])->value) && boolval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "OR":
                    $this->frame->setVar($instruction->args[0]->value, "bool", strval(boolval($this->getSymb($instruction->args[1])->value) || boolval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "NOT":
                    $this->frame->setVar($instruction->args[0]->value, "bool", strval(!boolval($this->getSymb($instruction->args[1])->value)));
                    break;

                case "INT2CHAR":
                    $this->frame->setVar($instruction->args[0]->value, "string", chr(intval($this->getSymb($instruction->args[1])->value)));
                    break;

                case "STRI2INT":
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(ord($this->getSymb($instruction->args[1])->value[intval($this->getSymb($instruction->args[2])->value)])));
                    break;

                case "READ":
                    $input = $this->input->readString();
                    if ($input != "")
                    {
                        $exploded = explode("@", $instruction->args[1]->value);
                        if ($exploded[1] == "int")
                        {
                            if (!is_numeric($input))
                            {
                                $this->frame->setVar($instruction->args[0]->value, "nil", "nil");
                            }
                            else
                            {
                                $this->frame->setVar($instruction->args[0]->value, "int", $input);
                            }
                        }
                        else if ($exploded[1] == "string")
                        {
                            if (!is_string($input))
                            {
                                $this->frame->setVar($instruction->args[0]->value, "nil", "nil");
                            }
                            else
                            {
                                $this->frame->setVar($instruction->args[0]->value, "string", $input);
                            }
                        }
                        else if ($exploded[1] == "bool")
                        {
                            if (!is_bool(boolval($input)))
                            {
                                $this->frame->setVar($instruction->args[0]->value, "nil", "nil");
                            }
                            else
                            {
                                $this->frame->setVar($instruction->args[0]->value, "bool", $input);
                            }
                        }
                    }
                    else
                    {
                        $this->frame->setVar($instruction->args[0]->value, "nil", "nil");
                    }
                    break;

                case "WRITE":
                    $toPrint = $this->getSymb($instruction->args[0]);
                    if ($toPrint->type == "nil")
                    {
                        $this->stdout->writeString("");
                    }
                    else
                    {
                        $this->stdout->writeString($toPrint->value);
                    }
                    break;
                    
                default:
                    ErrorHandler::ErrorAndExit("Unknown instruction: " . $instruction->name, ReturnCode::INVALID_SOURCE_STRUCTURE);
                    break;
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
        else if ($symb->type == "string")
        {
            if (!is_string($symb->value))
            {
                ErrorHandler::ErrorAndExit("Wrong operand " . $symb->value, ReturnCode::OPERAND_VALUE_ERROR);
            }
            return $symb;
        }
        else if ($symb->type == "bool")
        {
            if (!is_bool(boolval($symb->value)))
            {
                ErrorHandler::ErrorAndExit("Wrong operand " . $symb->value, ReturnCode::OPERAND_VALUE_ERROR);
            }
            return $symb;
        }
        else if ($symb->type == "nil")
        {
            if ($symb->value != "nil")
            {
                ErrorHandler::ErrorAndExit("Wrong operand " . $symb->value, ReturnCode::OPERAND_VALUE_ERROR);
            }
            return $symb;
        }
        else
        {
            ErrorHandler::ErrorAndExit("Wrong operand " . $symb->value, ReturnCode::OPERAND_TYPE_ERROR);
        }
    }
}
