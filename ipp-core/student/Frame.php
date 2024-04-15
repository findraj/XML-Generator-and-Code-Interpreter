<?php
/**
 * @author Jan Findra (xfindr01)
 */

namespace IPP\Student;

use IPP\Student\Argument;

/**
 * Class Frame represents a frame of variables in the IPPcode24 language.
 
 */
class Frame
{
    /** @var array<Variable> $GF */
    public array $GF;
    /** @var array<Variable> $TF */
    public array $TF;
    /** @var bool $TFexist */
    public bool $TFexist;
    /** @var array<array<Variable>> $frameStack */
    public array $frameStack;

    public function __construct()
    {
        $this->GF = array();
        $this->frameStack = array();
        $this->TFexist = false;
    }

    /**
     * Function creates a new temporary frame.
     */
    public function createFrame() : void
    {
        $this->TF = array();
        $this->TFexist = true;
    }

    /**
     * Function pushes the temporary frame to the frame stack, where it became local frame.
     */
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

    /**
     * Function pops the local frame from the frame stack.
     */
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

    /**
     * Function defines a new variable in the given frame.
     * @param string $frame Name of the frame where the variable should be defined.
     * @param string $name Name of the variable.
     */
    public function defVar(string $frame, string $name) : void
    {
        $var = new Variable($name);
        if ($frame == "GF") // check if the variable should be defined in GF
        {
            if (in_array($var, $this->GF)) // check if variable already exists
            {
                throw new SemanticException("Variable already exists");
            }
            $this->GF[] = $var;
        }
        else
        {
            if ($frame == "TF") // check if the variable should be defined in TF
            {
                if (!$this->TFexist) // check if TF exists
                {
                    throw new FrameAccessException("TF does not exists");
                }
                if (in_array($var, $this->TF)) // check if variable already exists
                {
                    throw new SemanticException("Variable already exists");
                }
                $this->TF[] = $var;
            }
            else // the variable should be defined in LF
            {
                $top = count($this->frameStack) - 1; // index of the top frame
                if ($top < 0)
                {
                    throw new FrameAccessException("LF does not exist");
                }
                if (in_array($var, $this->frameStack[$top])) // check if variable already exists
                {
                    throw new SemanticException("Variable already exists");
                }
                $this->frameStack[$top][] = $var;
            }
        }
    }

    /**
     * Function sets the value of the given variable.
     * @param string $variable Name of the variable.
     * @param string $type Type of the variable.
     * @param string $value Value of the variable.
     */
    public function setVar(string $variable, string $type, string $value) : void
    {
        $exploded = explode("@", $variable);
        if (count($exploded) != 2) // check if the variable is in the right format
        {
            throw new OperandValueException("Wrong var value");
        }

        $frame = $exploded[0];
        $name = $exploded[1];
        $defined = false;
        if (!in_array($frame, ["GF", "TF", "LF"])) // check if the variable location is correct
        {
            throw new InvalidSourceStructureException("Wrong var value");
        }
        if ($frame == "GF") // check if the variable is in GF
        {
            foreach ($this->GF as $var) // find the variable
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
            if ($frame == "TF") // check if the variable is in TF
            {
                if (!$this->TFexist) // check if TF exists
                {
                    throw new FrameAccessException("TF does not exists");
                }
                foreach ($this->TF as $var) // find the variable
                {
                    if ($var->name == $name)
                    {
                        $var->type = $type;
                        $var->value = $value;
                        $defined = true;
                    }
                }
            }
            else // the variable is in LF
            {
                $top = count($this->frameStack) - 1; // index of the top frame
                if ($top < 0) // check if there is at least 1 LF on the stack
                {
                    throw new FrameAccessException("LF does not exist");
                }
                foreach ( $this->frameStack[$top] as $var) // find the variable
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
        if (!$defined) // check if the variable was found
        {
            throw new VariableAccessException("Variable is not defined");
        }
    }

    /**
     * Function returns the value of the given variable.
     * @param string $variable Name of the variable.
     * @return Argument Value of the variable.
     */
    public function getVar(string $variable) : Argument
    {
        $result = new Argument();
        $defined = false;
        $exploded = explode("@", $variable);
        if (count($exploded) != 2) // check if the variable is in the right format
        {
            throw new OperandValueException("Wrong var value");
        }

        $frame = $exploded[0];
        $name = $exploded[1];
        $result->value = null;

        if (!in_array($frame, ["GF", "TF", "LF"])) // check if the variable location is correct
        {
            throw new OperandValueException("Wrong var value");
        }

        if ($frame == "GF") // check if the variable is in GF
        {
            foreach ($this->GF as $var) // find the variable
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
            if ($frame == "TF") // check if the variable is in TF
            {
                if (!$this->TFexist) // check if TF exists
                {
                    throw new FrameAccessException("TF does not exists");
                }
                foreach ($this->TF as $var) // find the variable
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
                foreach ( $this->frameStack[$top] as $var) // find the variable
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
        if (!$defined) // check if the variable was found
        {
            throw new VariableAccessException("Variable is not defined");
        }
        if ($result->value === null) // check if the variable has a value
        {
            throw new ValueException("Variable has no value");
        }

        return $result;
    }
}