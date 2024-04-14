<?php

namespace IPP\Student;

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
            throw new FrameAccessException("There is no TF to push");
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
            throw new FrameAccessException("There is no LF to pop");
        }
    }

    public function defVar(string $frame, string $name) : void
    {
        $var = new Variable($name);
        if ($frame == "GF")
        {
            if (in_array($var, $this->GF))
            {
                throw new SemanticException("Variable already exists");
            }
            $this->GF[] = $var;
        }
        else
        {
            if ($frame == "TF")
            {
                if (!$this->TFexist) // check if TF exists
                {
                    throw new FrameAccessException("TF does not exists");
                }
                if (in_array($var, $this->TF))
                {
                    throw new SemanticException("Variable already exists");
                }
                $this->TF[] = $var;
            }
            else
            {
                $top = count($this->frameStack) - 1; // index of the top frame
                if ($top < 0)
                {
                    throw new FrameAccessException("LF does not exist");
                }
                if (in_array($var, $this->frameStack[$top]))
                {
                    throw new SemanticException("Variable already exists");
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
            throw new OperandValueException("Wrong var value");
        }

        $frame = $exploded[0];
        $name = $exploded[1];
        $defined = false;
        if (!in_array($frame, ["GF", "TF", "LF"]))
        {
            throw new InvalidSourceStructureException("Wrong var value");
        }
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
                    throw new FrameAccessException("TF does not exists");
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
                    throw new FrameAccessException("LF does not exist");
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
            throw new VariableAccessException("Variable is not defined");
        }
    }

    public function getVar(string $variable) : Argument
    {
        $result = new Argument();
        $defined = false;
        $exploded = explode("@", $variable);
        if (count($exploded) != 2)
        {
            throw new OperandValueException("Wrong var value");
        }

        $frame = $exploded[0];
        $name = $exploded[1];
        $result->value = null;

        if (!in_array($frame, ["GF", "TF", "LF"]))
        {
            throw new OperandValueException("Wrong var value");
        }

        if ($frame == "GF")
        {
            foreach ($this->GF as $var)
            {
                if ($var->name == $name)
                {
                    $defined = true;
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
                    throw new FrameAccessException("TF does not exists");
                }
                foreach ($this->TF as $var)
                {
                    if ($var->name == $name)
                    {
                        $defined = true;
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
                    throw new FrameAccessException("LF does not exist");
                }
                foreach ( $this->frameStack[$top] as $var)
                {
                    if ($var->name == $name)
                    {
                        $defined = true;
                        $result->value = $var->value;
                        $result->type = $var->type;
                    }
                }
            }
        }
        if (!$defined)
        {
            throw new VariableAccessException("Variable is not defined");
        }
        if ($result->value === null)
        {
            throw new ValueException("Variable has no value");
        }

        return $result;
    }
}