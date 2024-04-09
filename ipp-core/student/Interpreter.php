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
    private array $stack = array();
    /** @var array<int> $positon */
    private array $positon = array();

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
            $instruction->name = strtoupper($instruction->name);
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
                    if ($param != "symb" && $instruction->args[$index]->type != "var")
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
                    if (count($this->positon) == 0)
                    {
                        ErrorHandler::ErrorAndExit("Stack is empty", ReturnCode::VALUE_ERROR);
                    }
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
                    $a = $this->getSymb($instruction->args[1]);
                    $b = $this->getSymb($instruction->args[2]);
                    if ($a->type == "int" && $b->type == "int")
                    {
                        if (intval($a->value) < intval($b->value))
                        {
                            $result = "true";
                        }
                        else
                        {
                            $result = "false";
                        }
                    }
                    else if ($a->type == "string" && $b->type == "string")
                    {
                        if ($a->value < $b->value)
                        {
                            $result = "true";
                        }
                        else
                        {
                            $result = "false";
                        }
                    }
                    else if ($a->type == "bool" && $b->type == "bool")
                    {
                        if ($a->value == "false" && $b->value == "true")
                        {
                            $result = "true";
                        }
                        else
                        {
                            $result = "false";
                        }
                    }
                    else
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $a->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "bool", $result);
                    break;

                case "GT":
                    $a = $this->getSymb($instruction->args[1]);
                    $b = $this->getSymb($instruction->args[2]);
                    if ($a->type == "int" && $b->type == "int")
                    {
                        if (intval($a->value) > intval($b->value))
                        {
                            $result = "true";
                        }
                        else
                        {
                            $result = "false";
                        }
                    }
                    else if ($a->type == "string" && $b->type == "string")
                    {
                        if ($a->value > $b->value)
                        {
                            $result = "true";
                        }
                        else
                        {
                            $result = "false";
                        }
                    }
                    else if ($a->type == "bool" && $b->type == "bool")
                    {
                        if ($a->value == "true" && $b->value == "false")
                        {
                            $result = "true";
                        }
                        else
                        {
                            $result = "false";
                        }
                    }
                    else
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $a->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }

                    $this->frame->setVar($instruction->args[0]->value, "bool", $result);
                    break;

                case "EQ":
                    $a = $this->getSymb($instruction->args[1]);
                    $b = $this->getSymb($instruction->args[2]);
                    if ($a->type == "string" && $b->type == "string")
                    {
                        if ($a->value === $b->value)
                        {
                            $result = "true";
                        }
                        else
                        {
                            $result = "false";
                        }
                    }
                    else if ($a->type == "int" && $b->type == "int")
                    {
                        if (intval($a->value) === intval($b->value))
                        {
                            $result = "true";
                        }
                        else
                        {
                            $result = "false";
                        }
                    }
                    else if ($a->type == "bool" && $b->type == "bool")
                    {
                        if ($a->value === $b->value)
                        {
                            $result = "true";
                        }
                        else
                        {
                            $result = "false";
                        }
                    }
                    else if ($a->type == "nil" || $b->type == "nil")
                    {
                        if ($a->type == "nil" && $b->type == "nil")
                        {
                            $result = "true";
                        }
                        else
                        {
                            $result = "false";
                        }
                    }
                    else
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $a->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "bool", $result);
                    break;

                case "AND":
                    if ($this->getSymb($instruction->args[1])->type != "bool" || $this->getSymb($instruction->args[2])->type != "bool")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[1]->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    if ($this->getSymb($instruction->args[1])->value == "true" && $this->getSymb($instruction->args[2])->value == "true")
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "true");
                    }
                    else
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "false");
                    }
                    break;

                case "OR":
                    if ($this->getSymb($instruction->args[1])->type != "bool" || $this->getSymb($instruction->args[2])->type != "bool")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[1]->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    if ($this->getSymb($instruction->args[1])->value == "true" || $this->getSymb($instruction->args[2])->value == "true")
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "true");
                    }
                    else
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "false");
                    }
                    break;

                case "NOT":
                    if ($this->getSymb($instruction->args[1])->type != "bool")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[1]->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    if ($this->getSymb($instruction->args[1])->value == "true")
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "false");
                    }
                    else
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "true");
                    }
                    break;

                case "INT2CHAR":
                    if ($this->getSymb($instruction->args[1])->type != "int")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[1]->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    if ($this->getSymb($instruction->args[1])->value < 0 || $this->getSymb($instruction->args[1])->value > 255)
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[1]->value, ReturnCode::STRING_OPERATION_ERROR);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "string", chr(intval($this->getSymb($instruction->args[1])->value)));
                    break;

                case "STRI2INT":
                    if ($this->getSymb($instruction->args[1])->type != "string" || $this->getSymb($instruction->args[2])->type != "int")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[1]->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    if (intval($this->getSymb($instruction->args[2])->value) >= strlen($this->getSymb($instruction->args[1])->value))
                    {
                        ErrorHandler::ErrorAndExit("Index out of range", ReturnCode::STRING_OPERATION_ERROR);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(ord($this->getSymb($instruction->args[1])->value[intval($this->getSymb($instruction->args[2])->value)])));
                    break;

                case "READ":
                    if (!in_array($instruction->args[1]->value, ["int", "string", "bool"]))
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[1]->value, ReturnCode::INVALID_SOURCE_STRUCTURE);
                    }
                    switch ($instruction->args[1]->value)
                    {
                        case "int":
                            $input = $this->input->readInt();
                            break;

                        case "string":
                            $input = $this->input->readString();
                            break;

                        case "bool":
                            $input = $this->input->readBool();
                            if ($input == true)
                            {
                                $input = "true";
                            }
                            else if ($input == false)
                            {
                                $input = "false";
                            }
                            break;
                    }
                    if ($input == null)
                    {
                        $this->frame->setVar($instruction->args[0]->value, "nil", "nil");
                    }
                    else
                    {
                        $this->frame->setVar($instruction->args[0]->value, $instruction->args[1]->value, $input);
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
                        if ($toPrint->type == "string")
                        {
                            $printable = preg_replace_callback('/\\\(\d{3})/', function($matches)
                            {
                                return chr((int)$matches[1]);
                            },
                            $toPrint->value);
                        }
                        else
                        {
                            $printable = $toPrint->value;
                        }
                        $this->stdout->writeString($printable);
                    }
                    break;
                
                case "CONCAT":
                    if ($this->getSymb($instruction->args[1])->type != "string" || $this->getSymb($instruction->args[2])->type != "string")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[1]->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "string", $this->getSymb($instruction->args[1])->value . $this->getSymb($instruction->args[2])->value);
                    break;

                case "STRLEN":
                    if ($this->getSymb($instruction->args[1])->type != "string")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[1]->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(strlen($this->getSymb($instruction->args[1])->value)));
                    break;

                case "GETCHAR":
                    if ($this->getSymb($instruction->args[1])->type != "string")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[1]->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    if ($this->getSymb($instruction->args[2])->type != "int")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[2]->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    if (intval($this->getSymb($instruction->args[2])->value) >= strlen($this->getSymb($instruction->args[1])->value))
                    {
                        ErrorHandler::ErrorAndExit("Index out of range", ReturnCode::STRING_OPERATION_ERROR);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "string", $this->getSymb($instruction->args[1])->value[intval($this->getSymb($instruction->args[2])->value)]);
                    break;

                case "SETCHAR":
                    $a = $this->getSymb($instruction->args[0]);
                    $b = $this->getSymb($instruction->args[1]);
                    $c = $this->getSymb($instruction->args[2]);
                    if ($a->type != "string" || $b->type != "int" || $c->type != "string")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $instruction->args[0]->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    if ((intval($b->value) >= strlen($a->value)) || intval($b->value) < 0 || $c->value == null)
                    {
                        ErrorHandler::ErrorAndExit("Index out of range", ReturnCode::STRING_OPERATION_ERROR);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "string", substr_replace($a->value, $c->value, intval($b), 1));
                    break;

                case "TYPE":
                    $symb = $this->getSymb($instruction->args[1]);
                    if ($symb == null)
                    {
                        $this->frame->setVar($instruction->args[0]->value, "type", "");
                    }
                    else if ($symb->type == "var")
                    {
                        $this->frame->setVar($instruction->args[0]->value, "type", $this->frame->getVar($symb->value)->type);
                    }
                    else
                    {
                        $this->frame->setVar($instruction->args[0]->value, "type", $symb->type);
                    }
                    break;

                case "LABEL":
                    break;

                case "JUMP":
                    $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                    break;

                case "JUMPIFEQ":
                    $a = $this->getSymb($instruction->args[1]);
                    $b = $this->getSymb($instruction->args[2]);
                    if ($a->type == "int" && $b->type == "int")
                    {
                        if (intval($a->value) == intval($b->value))
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($a->type == "string" && $b->type == "string")
                    {
                        if ($a->value == $b->value)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($a->type == "bool" && $b->type == "bool")
                    {
                        if ($a->value == $b->value)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($a->type == "nil" || $b->type == "nil")
                    {
                        if ($a->value == null && $b->value == null)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $a->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    break;

                case "JUMPIFNEQ":
                    $a = $this->getSymb($instruction->args[1]);
                    $b = $this->getSymb($instruction->args[2]);
                    if ($a->type == "int" && $b->type == "int")
                    {
                        if (intval($a->value) != intval($b->value))
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($a->type == "string" && $b->type == "string")
                    {
                        if ($a->value != $b->value)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($a->type == "bool" && $b->type == "bool")
                    {
                        if ($a->value != $b->value)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($a->type == "nil" || $b->type == "nil")
                    {
                        if ($a->value != null && $b->value != null)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $a->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    break;

                case "EXIT":
                    $exit = $this->getSymb($instruction->args[0]);
                    if ($exit->type != "int")
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $exit->value, ReturnCode::OPERAND_TYPE_ERROR);
                    }
                    if (intval($exit->value) < 0 || intval($exit->value) > 9)
                    {
                        ErrorHandler::ErrorAndExit("Wrong operand " . $exit->value, ReturnCode::OPERAND_VALUE_ERROR);
                    }
                    exit(intval($exit->value));

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
        return $symb;
    }

    private function checkInt(Argument $symb) : void
    {
        if (!is_numeric($symb->value) || $symb->type != "int")
        {
            ErrorHandler::ErrorAndExit("Wrong operand " . $symb->value, ReturnCode::OPERAND_TYPE_ERROR);
        }
    }
}
