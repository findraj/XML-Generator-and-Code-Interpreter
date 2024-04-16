<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Student\XMLParser;
use IPP\Student\Frame;

class Interpreter extends AbstractInterpreter
{
    /** @var Frame $frame */
    private Frame $frame;
    /** @var array<Argument> $stack */
    private array $stack = array();
    /** @var array<int> $positon */
    private array $positon = array();

    public function execute(): int
    {
        $dom = $this->source->getDOMDocument();
        $XMLparser = new XMLParser($dom);
        $instructionArray = $XMLparser->checkInstructions();
        $instructionArray->sort();
        $instructionArray->findLabels();

        $this->frame = new Frame();

        $instruction = $instructionArray->getNextInstruction();
        while ($instruction != null) // go through all instructions
        {
            $instruction->name = strtoupper($instruction->name);
            if (!array_key_exists($instruction->name, $instructionArray->instructionDictionary)) // check if the instruction exists
            {
                throw new InvalidSourceStructureException("Unknown instruction: " . $instruction->name);
            }
            else
            {
                $params = $instructionArray->instructionDictionary[$instruction->name];

                if (count($params) != count($instruction->args)) // check if the number of arguments is correct
                {
                    throw new InvalidSourceStructureException("Wrong number of arguments " . $instruction->name);
                }

                $index = 0;
                foreach ($params as $param) // check if the type of arguments is correct
                {
                    if ($param != "symb" && $instruction->args[$index]->type != "var")
                    {
                        if ($param != $instruction->args[$index]->type)
                        {
                            throw new OperandTypeException("Wrong operand " . $instruction->args[$index]->value);
                        }
                    }
                $index += 1;
                }
            }
            switch ($instruction->name) // execute the instruction
            {
                case "MOVE":
                    if ($instruction->args[1]->value == null) // if the value is empty
                    {
                        $instruction->args[1]->value = ''; // set it to an empty string
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
                    if (count($this->positon) == 0) // if the stack is empty
                    {
                        throw new ValueException("Stack is empty");
                    }
                    $instructionArray->current = array_pop($this->positon);
                    break;

                case "PUSHS":
                    $this->stack[] = $this->getSymb($instruction->args[0]);
                    break;

                case "POPS":
                    if (count($this->stack) == 0) // if the stack is empty
                    {
                        throw new ValueException("Stack is empty");
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
                    if (intval($this->getSymb($instruction->args[2])->value) == 0) // if the divisor is 0
                    {
                        throw new OperandValueException("Division by zero");
                    }
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(intval($this->getSymb($instruction->args[1])->value) / intval($this->getSymb($instruction->args[2])->value)));
                    break;

                case "LT":
                    $a = $this->getSymb($instruction->args[1]);
                    $b = $this->getSymb($instruction->args[2]);
                    $result = "false";
                    if ($a->type == "int" && $b->type == "int") // if both operands are integers
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
                    else if ($this->getType($a) == "string" && $this->getType($b) == "string") // if both operands are strings
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
                    else if ($a->type == "bool" && $b->type == "bool") // if both operands are boolean
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
                        throw new OperandTypeException("Wrong operand " . $a->value);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "bool", $result);
                    break;

                case "GT":
                    $a = $this->getSymb($instruction->args[1]);
                    $b = $this->getSymb($instruction->args[2]);
                    $result = "false";
                    if ($a->type == "int" && $b->type == "int") // if both operands are integers
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
                    else if ($this->getType($a) == "string" && $this->getType($b) == "string") // if both operands are strings
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
                    else if ($a->type == "bool" && $b->type == "bool") // if both operands are boolean
                    {
                        if ($a->value == "true" && $b->value == "false") // true > false
                        {
                            $result = "true";
                        }
                        else // false < true
                        {
                            $result = "false";
                        }
                    }
                    else
                    {
                        throw new OperandTypeException("Wrong operand " . $a->value);
                    }

                    $this->frame->setVar($instruction->args[0]->value, "bool", $result);
                    break;

                case "EQ":
                    $a = $this->getSymb($instruction->args[1]);
                    $b = $this->getSymb($instruction->args[2]);
                    $result = "false";
                    if ($this->getType($a) == "string" && $this->getType($b) == "string") // if both operands are strings
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
                    else if ($a->type == "int" && $b->type == "int") // if both operands are integers
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
                    else if ($a->type == "bool" && $b->type == "bool") // if both operands are boolean
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
                    else if ($a->type == "nil" || $b->type == "nil") // if one of the operands is nil
                    {
                        if ($a->type == "nil" && $b->type == "nil") // both operand are nil => true
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
                        throw new OperandTypeException("Wrong operand " . $a->value);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "bool", $result);
                    break;

                case "AND":
                    if ($this->getSymb($instruction->args[1])->type != "bool" || $this->getSymb($instruction->args[2])->type != "bool") // if the operands are not boolean
                    {
                        throw new OperandTypeException("Wrong operand " . $instruction->args[1]->value);
                    }
                    if ($this->getSymb($instruction->args[1])->value == "true" && $this->getSymb($instruction->args[2])->value == "true") // if both operands are true
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "true");
                    }
                    else // if at least one of the operands is false
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "false");
                    }
                    break;

                case "OR":
                    if ($this->getSymb($instruction->args[1])->type != "bool" || $this->getSymb($instruction->args[2])->type != "bool") // if the operands are not boolean
                    {
                        throw new OperandTypeException("Wrong operand " . $instruction->args[1]->value);
                    }
                    if ($this->getSymb($instruction->args[1])->value == "true" || $this->getSymb($instruction->args[2])->value == "true") // if at least one of the operands is true
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "true");
                    }
                    else // if both operands are false
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "false");
                    }
                    break;

                case "NOT":
                    if ($this->getSymb($instruction->args[1])->type != "bool") // if the operand is not boolean
                    {
                        throw new OperandTypeException("Wrong operand " . $instruction->args[1]->value);
                    }
                    if ($this->getSymb($instruction->args[1])->value == "true") // if the operand is true
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "false");
                    }
                    else // if the operand is false
                    {
                        $this->frame->setVar($instruction->args[0]->value, "bool", "true");
                    }
                    break;

                case "INT2CHAR":
                    if ($this->getSymb($instruction->args[1])->type != "int") // if the operand is not integer
                    {
                        throw new OperandTypeException("Wrong operand " . $instruction->args[1]->value);
                    }
                    if ($this->getSymb($instruction->args[1])->value < 0 || $this->getSymb($instruction->args[1])->value > 255) // if the integer is out of range
                    {
                        throw new StringOperationException("Wrong operand " . $instruction->args[1]->value);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "string", chr(intval($this->getSymb($instruction->args[1])->value)));
                    break;

                case "STRI2INT":
                    if ($this->getSymb($instruction->args[1])->type != "string" || $this->getSymb($instruction->args[2])->type != "int") // if the first operand is not string or the second operand is not integer
                    {
                        throw new OperandTypeException("Wrong operand " . $instruction->args[1]->value);
                    }
                    if (intval($this->getSymb($instruction->args[2])->value) >= strlen($this->getSymb($instruction->args[1])->value)) // if the index is out of range
                    {
                        throw new StringOperationException("Index out of range");
                    }
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(ord($this->getSymb($instruction->args[1])->value[intval($this->getSymb($instruction->args[2])->value)])));
                    break;

                case "READ":
                    $input = null;
                    if (!in_array($instruction->args[1]->value, ["int", "string", "bool"])) // check if the type is correct
                    {
                        throw new InvalidSourceStructureException("Wrong operand " . $instruction->args[1]->value);
                    }
                    switch ($instruction->args[1]->value) // read the input
                    {
                        case "int":
                            $input = $this->input->readInt();
                            break;

                        case "string":
                            $input = $this->input->readString();
                            break;

                        case "bool":
                            $input = $this->input->readBool();
                            if ($input == true) // convert the input to string
                            {
                                $input = "true";
                            }
                            else // convert the input to string
                            {
                                if ($input != null)
                                {
                                    $input = "false";
                                }
                            }
                            break;
                    }
                    if ($input == null) // if the input is empty
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
                    if ($toPrint->type == "nil") // if the operand is nil
                    {
                        $this->stdout->writeString("");
                    }
                    else
                    {
                        if ($toPrint->type == "string") // if the operand is string
                        {
                            $printable = preg_replace_callback('/\\\(\d{3})/', function($matches) // convert the escape sequences
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
                    if ($this->getSymb($instruction->args[1])->type != "string" || $this->getSymb($instruction->args[2])->type != "string") // if the operands are not strings
                    {
                        throw new OperandTypeException("Wrong operand " . $instruction->args[1]->value);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "string", $this->getSymb($instruction->args[1])->value . $this->getSymb($instruction->args[2])->value);
                    break;

                case "STRLEN":
                    if ($this->getSymb($instruction->args[1])->type != "string") // if the operand is not string
                    {
                        throw new OperandTypeException("Wrong operand " . $instruction->args[1]->value);
                    }
                    $this->frame->setVar($instruction->args[0]->value, "int", strval(strlen($this->getSymb($instruction->args[1])->value)));
                    break;

                case "GETCHAR":
                    if ($this->getSymb($instruction->args[1])->type != "string") // if the first operand is not string
                    {
                        throw new OperandTypeException("Wrong operand " . $instruction->args[1]->value);
                    }
                    if ($this->getSymb($instruction->args[2])->type != "int") // if the second operand is not integer
                    {
                        throw new OperandTypeException("Wrong operand " . $instruction->args[2]->value);
                    }
                    if (intval($this->getSymb($instruction->args[2])->value) >= strlen($this->getSymb($instruction->args[1])->value)) // if the index is out of range
                    {
                        throw new StringOperationException("Index out of range");
                    }
                    $this->frame->setVar($instruction->args[0]->value, "string", $this->getSymb($instruction->args[1])->value[intval($this->getSymb($instruction->args[2])->value)]);
                    break;

                case "SETCHAR":
                    $a = $this->getSymb($instruction->args[0]);
                    $b = $this->getSymb($instruction->args[1]);
                    $c = $this->getSymb($instruction->args[2]);
                    if ($a->type != "string" || $b->type != "int" || $c->type != "string") // check if the types are correct
                    {
                        throw new OperandTypeException("Wrong operand " . $a->value);
                    }
                    if ((intval($b->value) >= strlen($a->value)) || intval($b->value) < 0 || $c->value == null) // if the index is out of range
                    {
                        throw new StringOperationException("Index out of range");
                    }
                    $this->frame->setVar($instruction->args[0]->value, "string", substr_replace($a->value, $c->value, intval($b->value), 1));
                    break;

                case "TYPE":
                    $symb = $this->getSymb($instruction->args[1]);
                    if ($symb == null) // if the operand is empty
                    {
                        $this->frame->setVar($instruction->args[0]->value, "type", "");
                    }
                    else if ($symb->type == "var") // if the operand is a variable
                    {
                        $this->frame->setVar($instruction->args[0]->value, "type", $this->frame->getVar($symb->value)->type);
                    }
                    else // if the operand is a constant
                    {
                        $this->frame->setVar($instruction->args[0]->value, "type", $this->getType($symb));
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
                    if ($a->type == "int" && $b->type == "int") // if both operands are integers
                    {
                        if (intval($a->value) == intval($b->value))
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($this->getType($a) == "string" && $this->getType($b) == "string") // if both operands are strings
                    {
                        if ($a->value == $b->value)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($a->type == "bool" && $b->type == "bool") // if both operands are boolean
                    {
                        if ($a->value == $b->value)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($a->type == "nil" || $b->type == "nil") // if one of the operands is nil
                    {
                        if ($a->value == null && $b->value == null)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else
                    {
                        throw new OperandTypeException("Wrong operand " . $a->type . " " . $b->type);
                    }
                    break;

                case "JUMPIFNEQ":
                    $a = $this->getSymb($instruction->args[1]);
                    $b = $this->getSymb($instruction->args[2]);
                    if ($a->type == "int" && $b->type == "int") // if both operands are integers
                    {
                        if (intval($a->value) != intval($b->value))
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($this->getType($a) == "string" && $this->getType($b) == "string") // if both operands are strings
                    {
                        if ($a->value != $b->value)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($a->type == "bool" && $b->type == "bool") // if both operands are boolean
                    {
                        if ($a->value != $b->value)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else if ($a->type == "nil" || $b->type == "nil") // if one of the operands is nil
                    {
                        if ($a->value != null && $b->value != null)
                        {
                            $instructionArray->current = $instructionArray->getLabel($instruction->args[0]->value)->line;
                        }
                    }
                    else
                    {
                        throw new OperandTypeException("Wrong operand " . $a->value);
                    }
                    break;

                case "EXIT":
                    $exit = $this->getSymb($instruction->args[0]);
                    if ($exit->type != "int") // if the operand is not integer
                    {
                        throw new OperandTypeException("Wrong operand " . $exit->value);
                    }
                    if (intval($exit->value) < 0 || intval($exit->value) > 9) // if the exit code is out of range
                    {
                        throw new OperandValueException("Wrong operand " . $exit->value);
                    }
                    exit(intval($exit->value));

                case "DPRINT":
                    $toPrint = $this->getSymb($instruction->args[0]);
                    if ($toPrint->type == "nil") // if the operand is nil
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
                    throw new InvalidSourceStructureException("Unknown instruction: " . $instruction->name);
            }

            $instruction = $instructionArray->getNextInstruction();
        }

        return 0;
    }

    /**
     * Returns the value of the operand and also check if the operand is correct.
     * @param Argument $symb Operand to be checked.
     * @return Argument Operand.
     */
    public function getSymb(Argument $symb) : Argument
    {
        if ($symb->type == "var") // if the operand is a variable
        {
            return $this->frame->getVar($symb->value);
        }
        else if ($symb->type == "int") // if the operand is an integer
        {
            if (!is_numeric($symb->value))
            {
                throw new InvalidSourceStructureException("Wrong operand " . $symb->value);
            }
            return $symb;
        }
        else if ($symb->type == "string") // if the operand is a string
        {
            if (!is_string($symb->value))
            {
                throw new OperandValueException("Wrong operand " . $symb->value);
            }
            return $symb;
        }
        else if ($symb->type == "bool") // if the operand is a bool
        {
            if (!is_bool(boolval($symb->value)))
            {
                throw new OperandValueException("Wrong operand " . $symb->value);
            }
            return $symb;
        }
        else if ($symb->type == "label") // if the operand is a label
        {
            return $symb;
        }
        else if ($symb->type == "type") // if the operand is a type
        {
            if (!in_array($symb->value, ["int", "string", "bool", "nil", "label", "type", "var"]))
            {
                throw new OperandValueException("Wrong operand " . $symb->value);
            }
            return $symb;
        }
        else if ($symb->type == "nil") // if the operand is nil
        {
            if ($symb->value != "nil")
            {
                throw new OperandValueException("Wrong operand " . $symb->value);
            }
            return $symb;
        }
        else
        {
            throw new OperandValueException("Wrong operand " . $symb->value);
        }
    }

    /**
     * Checks if the integer operand is correct.
     * @param Argument $symb Operand to be checked.
     */
    private function checkInt(Argument $symb) : void
    {
        if (!is_numeric($symb->value) || $symb->type != "int")
        {
            throw new OperandTypeException("Wrong operand " . $symb->value);
        }
    }

    /**
     * Returns the type of the operand.
     * @param Argument $symb Operand.
     * @return string Type of the operand.
     */
    private function getType(Argument $symb) : string
    {
        if ($symb->type == "type" || $symb->type == "label")
        {
            return "string";
        }
        return $symb->type;
    }
}
