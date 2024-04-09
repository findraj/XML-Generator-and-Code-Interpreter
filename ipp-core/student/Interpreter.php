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
    /** @var array<Argument> $stack */
    private array $stack;
    /** @var array<int> $positon */
    private array $positon;

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
                $index += 1;
                }
            }
            switch ($instruction->name) {
                case "MOVE":
                    if ($instruction->args[1]->value == null)
                    {
                        $instruction->args[1]->value = '';
                    }
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
                    $this->positon[] = $instructionArray->current;
                    $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                    break;

                case "RETURN":
                    $instructionArray->current = array_pop($this->positon);
                    break;

                case "PUSHS":
                    $this->stack[] = $this->getSymb($instruction->args[0]);
                    break;

                case "POPS":
                    if (count($this->stack) == 0)
                    {
                        ErrorHandler::ErrorAndExit("Stack is empty", ReturnCode::VALUE_ERROR);
                    }
                    else
                    {
                        $this->frame->setVar($instruction->args[0]->value, $this->stack[count($this->stack) - 1]->type, $this->stack[count($this->stack) - 1]->value);
                        array_pop($this->stack);
                    }
                    break;

                case "ADD":
                    $this->checkInt($this->getSymb($instruction->args[1]));
                    $this->checkInt($this->getSymb($instruction->args[2]));
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(intval($this->getSymb($instruction->args[1])->value) + intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "SUB":
                    $this->checkInt($this->getSymb($instruction->args[1]));
                    $this->checkInt($this->getSymb($instruction->args[2]));
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(intval($this->getSymb($instruction->args[1])->value) - intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "MUL":
                    $this->checkInt($this->getSymb($instruction->args[1]));
                    $this->checkInt($this->getSymb($instruction->args[2]));
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(intval($this->getSymb($instruction->args[1])->value) * intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "IDIV":
                    $this->checkInt($this->getSymb($instruction->args[1]));
                    $this->checkInt($this->getSymb($instruction->args[2]));
                    if (intval($this->getSymb($instruction->args[2])->value) == 0)
                    {
                        ErrorHandler::ErrorAndExit("Division by zero", ReturnCode::OPERAND_VALUE_ERROR);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(intval($this->getSymb($instruction->args[1])->value) / intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "LT":
                    $this->frame->setVar($instruction->args[0]->value, "bool", strval($this->getSymb($instruction->args[1])->value < $this->getSymb($instruction->args[2])->value));
                    break;

                case "GT":
                    $this->frame->setVar($instruction->args[0]->value, "bool", strval($this->getSymb($instruction->args[1])->value > $this->getSymb($instruction->args[2])->value));
                    break;

                case "EQ":
                    if ($this->getSymb($instruction->args[1])->value == $this->getSymb($instruction->args[2])->value)
                    {
                        $result = "true";
                    }
                    else
                    {
                        $result = "false";
                    }
                    $this->frame->setVar($instruction->args[0]->value, "bool", $result);
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
                        $tmp = new Argument();
                        $tmp->type = $instruction->args[1]->value;
                        $tmp->value = $input;
                        $this->getSymb($tmp); // just to check if the input is correct
                        $this->frame->setVar($instruction->args[0]->value, $instruction->args[1]->value, $input);
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
                
                case "CONCAT":
                    $this->frame->setVar($instruction->args[0]->value, "string", $this->getSymb($instruction->args[1])->value . $this->getSymb($instruction->args[2])->value);
                    break;

                case "STRLEN":
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(strlen($this->getSymb($instruction->args[1])->value)));
                    break;

                case "GETCHAR":
                    $this->frame->setVar($instruction->args[0]->value, "string", $this->getSymb($instruction->args[1])->value[intval($this->getSymb($instruction->args[2])->value)]);
                    break;

                case "SETCHAR":
                    $this->frame->setVar($instruction->args[0]->value, "string", substr_replace($this->getSymb($instruction->args[1])->value, $this->getSymb($instruction->args[2])->value, intval($this->getSymb($instruction->args[3])->value), 1));
                    break;

                case "TYPE":
                    $symb = $this->getSymb($instruction->args[1]);
                    if ($symb == null)
                    {
                        $this->frame->setVar($instruction->args[0]->value, "string", "");
                    }
                    else if ($symb->type == "var")
                    {
                        $this->frame->setVar($instruction->args[0]->value, "string", $this->frame->getVar($symb->value)->type);
                    }
                    else
                    {
                        $this->frame->setVar($instruction->args[0]->value, "string", $symb->type);
                    }
                    break;

                case "LABEL":
                    break;

                case "JUMP":
                    $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                    break;

                case "JUMPIFEQ":
                    if ($this->getSymb($instruction->args[1])->value == $this->getSymb($instruction->args[2])->value)
                    {
                        $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                    }
                    break;

                case "JUMPIFNEQ":
                    if ($this->getSymb($instruction->args[1])->value != $this->getSymb($instruction->args[2])->value)
                    {
                        $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                    }
                    break;

                case "EXIT":
                    exit(intval($this->getSymb($instruction->args[0])->value));

                case "DPRINT":
                    $toPrint = $this->getSymb($instruction->args[0]);
                    if ($toPrint->type == "nil")
                    {
                        $this->stderr->writeString("");
                    }
                    else
                    {
                        $this->stderr->writeString($toPrint->value);
                    }
                    break;

                case "BREAK":
                    $this->stderr->writeString("Position in code: " . $instructionArray->current . "\n");
                    $this->stderr->writeString("Stack: " . print_r($this->stack, true) . "\n");
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
                ErrorHandler::ErrorAndExit("Wrong operand " . $symb->value, ReturnCode::INVALID_SOURCE_STRUCTURE);
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
        else if ($symb->type == "label")
        {
            return $symb;
        }
        else if ($symb->type == "type")
        {
            if (!in_array($symb->value, ["int", "string", "bool", "nil", "label", "type", "var"]))
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

    private function checkInt(Argument $symb) : void
    {
        if (!is_numeric($symb->value) || $symb->type != "int")
        {
            ErrorHandler::ErrorAndExit("Wrong operand " . $symb->value, ReturnCode::OPERAND_TYPE_ERROR);
        }
    }
}
