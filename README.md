# XML Generator and Code Interpreter

**Project Name**: IPPCode Processor

## Overview

The IPPCode Processor is a two-part project designed to process and interpret IPPcode24 instructions. The first part generates XML from IPPcode24 source code, while the second part interprets the generated XML to execute the instructions.

## Part 1: IPPcode24 to XML Converter

### Implementation

#### Argument Handling

The program starts by checking command-line arguments. Only zero or one argument is allowed, with valid options being `-h` or `--help` to display help information.

#### Input Reading

Input is read from the standard input. The `remove_comments` function strips comments and empty lines, and the input is then split by newline characters (`\n`).

#### Language Identifier Validation

The first line must contain `.IPPcode24`, optionally preceded by any number of spaces.

#### Instruction Processing

Each line of the input is processed as follows:

1. **Whitespace Handling**: Redundant spaces are removed, and the line is split into tokens.
2. **Instruction Validation**: The first token (instruction name) is converted to uppercase and checked against a predefined dictionary of valid instructions (`instructions`), which includes their expected parameters.
3. **Operand Checking**: The `check_operand` function validates operands based on their expected types using regular expressions. Special cases include:
   - Escaped sequences (e.g., `\123`).
   - Integers in various formats (decimal, hexadecimal, etc.).
   - The `Symb` type, which checks for basic data types.  
     The function returns the operand type and its value.
4. **XML Generation**: Valid instructions are converted into XML elements with attributes for type and value.

#### Output

The `arrange_xml` function structures the final XML output, which is printed to the standard output.

---

## Part 2: IPPcode24 Interpreter

### Design

#### UML Diagram

The project's structure is visualized in the included UML diagram (`UML.png`).

![UML diagram](./ipp-core/student/UML.png)

#### Repository Structure

Key files include:

- `Argument.php`: Handles argument validation.
- `Frame.php`: Manages frame operations (global, local, temporary).
- `Instruction.php` and `InstructionArray.php`: Represent and organize instructions.
- `Interpreter.php`: The main execution logic.
- `Label.php`: Manages labels for jumps.
- `XMLParser.php`: Parses the input XML file.

#### Exceptions

Custom exceptions handle errors such as invalid frame access, incorrect operand types, and semantic violations.

### Execution Flow

1. **Input Parsing**: The XML file is parsed and validated for correct structure and instruction names.
2. **Instruction Preparation**: Instructions are loaded as objects of the `Instruction` class and stored in an `InstructionArray`. Labels are indexed for jump operations.
3. **Instruction Execution**:
   - The interpreter checks argument counts and types against a predefined instruction dictionary.
   - For `Symb` operands, the `getSymb` function validates the argument and fetches variable values from the appropriate frame.
   - A switch statement executes the logic for each supported instruction.
4. **Frame and Variable Management**:
   - The `Frame` class handles operations on global, local, and temporary frames.
   - Temporary frames are pushed to/popped from a stack, with a flag (`TFexist`) tracking their availability.

### Testing

Testing was performed manually using reference inputs and outputs. Additionally, test suites from peers were used, achieving the following results:

```
PASSED: 361
FAILED: 2
```

### Known Issues

1. **Uninitialized Variable Type**: The interpreter terminates with exit code 56 when accessing the type of an uninitialized variable, instead of returning an empty string as specified.

---

## Conclusion

The IPPCode Processor efficiently converts IPPcode24 source to XML and interprets it with robust error handling. Future improvements could address the known issue and expand test coverage.
