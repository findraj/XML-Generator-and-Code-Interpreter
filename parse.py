import sys
import re
from xml.etree.ElementTree import Element, SubElement, tostring

# dictionary containing all instructions
instructions = {
    "MOVE": ["Var", "Symb"],
    "CREATEFRAME": [],
    "PUSHFRAME": [],
    "POPFRAME": [],
    "DEFVAR": ["Var"],
    "CALL": ["Label"],
    "RETURN": [],
    "PUSHS": ["Symb"],
    "POPS": ["Var"],
    "ADD": ["Var", "Symb", "Symb"],
    "SUB": ["Var", "Symb", "Symb"],
    "MUL": ["Var", "Symb", "Symb"],
    "IDIV": ["Var", "Symb", "Symb"],
    "LT": ["Var", "Symb", "Symb"],
    "GT": ["Var", "Symb", "Symb"],
    "EQ": ["Var", "Symb", "Symb"],
    "AND": ["Var", "Symb", "Symb"],
    "OR": ["Var", "Symb", "Symb"],
    "NOT": ["Var", "Symb"],
    "INT2CHAR": ["Var", "Symb"],
    "STRI2INT": ["Var", "Symb", "Symb"],
    "READ": ["Var", "Type"],
    "WRITE": ["Symb"],
    "CONCAT": ["Var", "Symb", "Symb"],
    "STRLEN": ["Var", "Symb"],
    "GETCHAR": ["Var", "Symb", "Symb"],
    "SETCHAR": ["Var", "Symb", "Symb"],
    "TYPE": ["Var", "Symb"],
    "LABEL": ["Label"],
    "JUMP": ["Label"],
    "JUMPIFEQ": ["Label", "Symb", "Symb"],
    "JUMPIFNEQ": ["Label", "Symb", "Symb"],
    "EXIT": ["Symb"],
    "DPRINT": ["Symb"],
    "BREAK": [],
}


def remove_comments(input_text: str):
    """
    Function to remove all comments and empty lines from source code.
    :param input_text: code to be prepared
    :return: prepared code
    """
    comment_pattern = r'#.*?$'
    text_without_comments = re.sub(comment_pattern, '', input_text, flags=re.MULTILINE)
    text_without_comments = re.sub(r'\s*\n+', '\n', text_without_comments).strip('\n')
    return text_without_comments


def check_operand(current_operand: str, expected_type: str):
    """
    Function to check if the operand is valid\n
    :param current_operand: operand to be checked
    :param expected_type: expected type of the operand
    :return: operand type, operand value
    """

    if re.search(r'@@', current_operand) is not None:
        sys.stderr.write("ERROR: Too much '@'")
        sys.exit(23)

    # skip check if the type is Label
    if expected_type == "Label":
        if re.match(r'.*@.*', current_operand) or re.match(r'[a-zA-Z_\-$&%*!?].*', current_operand) is None:
            sys.stderr.write("ERROR: Wrong type of operand")
            sys.exit(23)
        operand_type, operand_value = expected_type, current_operand

    # if the type is Type check if the type exists
    elif expected_type == "Type":

        if current_operand in ["int", "string", "float", "bool"]:
            operand_type, operand_value = expected_type, current_operand

        else:
            sys.stderr.write("ERROR: Type does not exist.")
            sys.exit(23)

    # otherwise find the type of operand
    else:

        # check if the operand is variable
        if re.match(r'([GLT])F@[a-zA-Z_\-$&%*!?].*', current_operand) is not None:
            operand_type, operand_value = "Var", current_operand

        # check if the operand is string
        elif re.match(r'string@*', current_operand) is not None:
            operand_type, operand_value = "String", '@'.join(current_operand.split('@')[1:])

            # if there is '\' it must be followed by 3 digits
            match = re.search(r'\\', operand_value)
            if match is not None:

                if re.search(r'^\\\d{3}', operand_value[match.start():]) is None:
                    sys.stderr.write("ERROR: This use of '\' is not supported ")
                    sys.exit(23)

        # check if the operand is integer in right format
        elif re.match(r'int@[+-]?(0x[0-9A-Fa-f]+|0b[01]+|0[0-7]*|0o[0-7]*|[1-9][0-9]*)\b', current_operand) is not None:
            operand_type, operand_value = "Int", current_operand.split('@')[1]

        # check if the operand is float
        elif re.match(r'float@*', current_operand) is not None:
            operand_type, operand_value = "Float", current_operand.split('@')[1]

        # check if the operand is bool
        elif re.match(r'bool@(true|false)', current_operand) is not None:
            operand_type, operand_value = "Bool", current_operand.split('@')[1]

        # check if the operand is nil
        elif re.match(r'nil@nil$', current_operand) is not None:
            operand_type, operand_value = "Nil", current_operand.split('@')[1]

        # otherwise throw error
        else:
            sys.stderr.write("ERROR: Unrecognized operand")
            sys.exit(23)

        # if the expected type is not 'Symb' check if the operand has right type
        if (expected_type != "Symb") & (operand_type != expected_type):
            sys.stderr.write("Wrong type on specific position")
            sys.exit(23)

    return operand_type.lower(), operand_value


def arrange_xml(elem, level=0):
    """
    Function to arrange XML elements into indent version.
    :param elem: root element of the XML document
    :param level: level of indentation
    :return: arranged XML document
    """

    indent = "    "
    i = "\n" + level * indent

    if len(elem):

        if not elem.text or not elem.text.strip():
            elem.text = i + indent

        if not elem.tail or not elem.tail.strip():
            elem.tail = i

        for elem in elem:
            arrange_xml(elem, level + 1)

        if not elem.tail or not elem.tail.strip():
            elem.tail = i

    else:
        if level and (not elem.tail or not elem.tail.strip()):
            elem.tail = i


# PROGRAM STARTS HERE

# if there is no argument pass
if len(sys.argv) == 1:
    pass

# if there is 1 argument, check if it is --help/-h
elif len(sys.argv) == 2:
    argument = sys.argv[1]

    if (argument == "--help") | (argument == "-h"):
        sys.stdout.write(
            "Skript typu filtr (parse.py v jazyce Python 3.10) načte ze standardního vstupu zdrojový kód v "
            "IPP-code24, zkontroluje lexikální a syntaktickou správnost kódu a vypíše na standardní výstup XML "
            "reprezentaci programu.\n")
    sys.stderr.write("Wrong argument")
    sys.exit(0)

# else throw error
else:
    sys.stderr.write("Wrong argument composition!\n")
    sys.exit(10)

# read lines from stdin
inputText = sys.stdin.read()
inputText = remove_comments(inputText)
inputText = inputText.split('\n')
outputText = str()

# check if the header is right
if re.match(r'^.IPPcode24\s*$', re.sub(r'\s', '', inputText[0])) is None:
    sys.stderr.write("Wrong header")
    sys.exit(21)

program = Element("program", language="IPPcode24")

for index in range(1, len(inputText)):
    lineToCheck = inputText[index]
    lineToCheck = re.sub(r'\s+', ' ', lineToCheck)
    lineToCheck = lineToCheck.strip().split(' ')
    nameOfInstruction = lineToCheck[0].upper()

    # check if the instruction exists
    if nameOfInstruction not in instructions:
        sys.stderr.write("Wrong instruction")
        sys.exit(23)

    instruction = SubElement(program, "instruction", order=str(index), opcode=nameOfInstruction)
    currentInstruction = instructions[nameOfInstruction]

    # check if instruction has right number of operands
    if len(lineToCheck) - 1 != len(currentInstruction):
        sys.stderr.write("ERROR: Wrong number of operands.")
        sys.exit(23)

    for operandIndex in range(len(currentInstruction)):

        operandType, operandValue = check_operand(lineToCheck[operandIndex + 1], currentInstruction[operandIndex])
        arg = SubElement(instruction, f"arg{operandIndex + 1}", type=operandType).text = operandValue

arrange_xml(program)
outputText = tostring(program, encoding="unicode")
sys.stdout.write(outputText)
