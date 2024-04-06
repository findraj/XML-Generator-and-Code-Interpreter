<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;
use IPP\Core\Settings;

class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {
        // TODO: Start your code here
        // php interpret.php --source=./student/supplementary-test/interpret/read_test.src --input=./student/supplementary-test/interpret/read_test.in
        // Check \IPP\Core\AbstractInterpreter for predefined I/O objects:
        // $this->stdout->writeString("stdout");
        // $this->stderr->writeString("stderr");
        // throw new NotImplementedException;

        $dom = $this->source->getDOMDocument();
        $val = $this->input->readString();
        $this->stdout->writeString($val);

        return 0;
    }
}
