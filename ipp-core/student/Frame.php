<?php

namespace IPP\Student;

use IPP\Core\ReturnCode;
use IPP\Student\Argument;

class Frame
{
    /** @var array<Variable> $GF */
    public array $GF;
    /** @var array<Variable> $TF */
    public array $TF;
    public bool $TFexist;
    /** @var array<array<Variable>> $frameStack */
    public array $frameStack;

    public function __construct()
    {
        $this->GF = array();
        $this->frameStack = array();
        $this->TFexist = false;
    }

    public function createFrame() : void
    {
        $this->TF = array();
        $this->TFexist = true;
    }

    public function pushFrame() : void
    {
        if ($this->TFexist) // check if TF exists
        {
            $this->frameStack[] = $this->TF;
            $this->TFexist = false;
        }
        else
        {
            ErrorHandler::ErrorAndExit("There is no TF to push", ReturnCode::FRAME_ACCESS_ERROR);
        }
    }

    public function popFrame() : void
    {
        if (count($this->frameStack) > 0) // check if there is at least 1 LF on the stack
        {
            $this->TF = array_pop($this->frameStack);
            $this->TFexist = true;
        }
        else
        {
            ErrorHandler::ErrorAndExit("There is nothing in the frame stack to pop", ReturnCode::FRAME_ACCESS_ERROR);
        }
    }

    public function defVar(string $frame, string $name) : void
    {
        $var = new Variable($name);
        if ($frame == "GF")
        {
            $this->GF[] = $var;
        }
        else
        {
            if ($frame == "TF")
            {
                if (!$this->TFexist) // check if TF exists
                {
                    ErrorHandler::ErrorAndExit("TF does not exists", ReturnCode::FRAME_ACCESS_ERROR);
                }
                $this->TF[] = $var;
            }
            else
            {
                $top = count($this->frameStack) - 1; // index of the top frame
                if ($top < 0)
                {
                    ErrorHandler::ErrorAndExit("LF does not exist", ReturnCode::FRAME_ACCESS_ERROR);
                }
                $this->frameStack[$top][] = $var;
            }
        }
    }

    public function setVar(string $variable, string $type, string $value) : void
    {
        $exploded = explode("@", $variable);
        if (count($exploded) != 2)
        {
            ErrorHandler::ErrorAndExit("Wrong var value", ReturnCode::OPERAND_VALUE_ERROR);
        }

        $frame = $exploded[0];
        $name = $exploded[1];
        $defined = false;
        if ($frame == "GF")
        {
            foreach ($this->GF as $var)
            {
                if ($var->name == $name)
                {
                    $var->type = $type;
                    $var->value = $value;
                    $defined = true;
                }
            }
        }
        else
        {
            if ($frame == "TF")
            {
                if (!$this->TFexist) // check if TF exists
                {
                    ErrorHandler::ErrorAndExit("TF does not exists", ReturnCode::FRAME_ACCESS_ERROR);
                }
                foreach ($this->TF as $var)
                {
                    if ($var->name == $name)
                    {
                        $var->type = $type;
                        $var->value = $value;
                        $defined = true;
                    }
                }
            }
            else
            {
                $top = count($this->frameStack) - 1; // index of the top frame
                if ($top < 0)
                {
                    ErrorHandler::ErrorAndExit("LF does not exist", ReturnCode::FRAME_ACCESS_ERROR);
                }
                foreach ( $this->frameStack[$top] as $var)
                {
                    if ($var->name == $name)
                    {
                        $var->type = $type;
                        $var->value = $value;
                        $defined = true;
                    }
                }
            }
        }
        if (!$defined)
        {
            ErrorHandler::ErrorAndExit("Variable is not defined", ReturnCode::VALUE_ERROR);
        }
    }

    public function getVar(string $variable) : Argument
    {
        $result = new Argument();
        $exploded = explode("@", $variable);
        if (count($exploded) != 2)
        {
            ErrorHandler::ErrorAndExit("Wrong var value", ReturnCode::OPERAND_VALUE_ERROR);
        }

        $frame = $exploded[0];
        $name = $exploded[1];
        $result->value = null;

        if (!in_array($frame, ["GF", "TF", "LF"]))
        {
            ErrorHandler::ErrorAndExit("Wrong var value", ReturnCode::OPERAND_VALUE_ERROR);
        }

        if ($frame == "GF")
        {
            foreach ($this->GF as $var)
            {
                if ($var->name == $name)
                {
                    $result->value = $var->value;
                    if ($var->type == null)
                    {
                        $result->type = "";
                    }
                    else
                    {
                        $result->type = $var->type;
                    }
                }
            }
        }
        else
        {
            if ($frame == "TF")
            {
                if (!$this->TFexist) // check if TF exists
                {
                    ErrorHandler::ErrorAndExit("TF does not exists", ReturnCode::FRAME_ACCESS_ERROR);
                }
                foreach ($this->TF as $var)
                {
                    if ($var->name == $name)
                    {
                        $result->value = $var->value;
                        $result->type = $var->type;
                    }
                }
            }
            else
            {
                $top = count($this->frameStack) - 1; // index of the top frame
                if ($top < 0)
                {
                    ErrorHandler::ErrorAndExit("LF does not exist", ReturnCode::FRAME_ACCESS_ERROR);
                }
                foreach ( $this->frameStack[$top] as $var)
                {
                    if ($var->name == $name)
                    {
                        $result->value = $var->value;
                        $result->type = $var->type;
                    }
                }
            }
        }
        if ($result->value === null)
        {
            ErrorHandler::ErrorAndExit("Variable is not defined", ReturnCode::VALUE_ERROR);
        }

        return $result;
    }
}