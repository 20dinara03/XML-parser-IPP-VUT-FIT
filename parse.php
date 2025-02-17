<?php
/**
 * Project: IPP project, part 1
 * @file parse.php
 * 
 * @brief Parser for source code IPPcode23
 * @author Dinara Garipova, xgarip00
 */

ini_set('display_errors', 'stderr');

/*Definition of error codes*/
define("RESULT_OK", 0);
define("ERROR_MISSING_SCRIPT_PARAMETER", 10);
define("ERROR_OPENING_INPUT_FILES", 11);
define("ERROR_OPENING_OUTPUT_FILES", 12);
define("INTERNAL_ERROR", 99);
define("HEADER_ERROR", 21);
define("UNKNOWN_OR_INCORRECT_OPERATING_CODE", 22);
define("LEXICAL_OR_SYNTAXIS_ERROR", 23);

/*Function for print help*/ 
function help()
{
    echo"=========================================HELP INFORMATION============================================\n";
    echo"A filter type script (parse.php in PHP 8.1) reads the source code in IPPcode23  from the standard input,\n";
    echo "checks the lexical and syntactic correctness of the code and writes it to standard XML output\n";
    echo"To run the program use the following command:\n";
    echo "php8.1 parse.php [options] <inputfile >outputfile\n";
    echo "Where \"options\" can be --help or --source=file or --source=\"file\"\n";
}

/**
 * Function for print errors messages
 *
 * @param int $line number of row
 * @param int $exit_code code for finishing program
 * @param string $message error message
 * @return void
 */ 
function error($line,$exit_code,$message)
{
    $stderr = fopen('php://stderr', 'w');
    if($line != -1){
        fwrite($stderr, "Error in line " . ($line + 1) . ":\n");
    }
    fwrite($stderr, $message);
    fclose($stderr);
    exit($exit_code);
}

/**
 * Function for checking arguments of the program
 *
 * @param int $argc the number of arguments passed to script
 * @param array $argv array of arguments passed to script
 * @return void
 */
function check_arguments($argc, $argv) 
{
    // Check if --help command is provided as the only argument
    if ($argc == 2 && strcmp("--help", $argv[1]) == 0)
    {
        // If --help command is provided, call the help function and exit with a success code
        help();
        exit(RESULT_OK);
    }
    // Check if there are any other arguments provided
    elseif ($argc > 1)
    {
        // If there are other arguments, call the error function with an appropriate error code and message
        error(0,ERROR_MISSING_SCRIPT_PARAMETER, "A lot of arguments, to view more information use --help command.");
    }
}


/**
 * Function for removing comments and not needed whitespaces
 *
 * @param string $line one line from file
 * @return string
 */
function remove_comments_and_whitespace($line) 
{
     // remove comments starting with #
     $line = preg_replace('/\s*#.*$/m', '', $line);
     // trim leading and trailing spaces
     $line = trim($line);
     return $line;
}

/**
 * Function for separating lines
 *
 * @param string $line one line from file
 * @return array
 */
function separate($line)
{
    $tokens = preg_split('/\s+/', $line);
    return $tokens;
}

/**
 * Function for checking header
 *
 * @param string $token first world in file
 * @return bool
 */
function correct_header($token)
{
 $token = strtoupper($token);
 if ($token == ".IPPCODE23"){return true;}
 else{return false;}
}

/**
 * Function for printing instruction on stdot
 *
 * @param SimpleXml $xml_previous previous xml attribute
 * @param int $order number of output instruction
 * @param string $opcode instruction
 * @return SimpleXml
 */
function print_instruction($xml_previous,$order,$opcode)
{
    $instruction = $xml_previous->addChild('instruction');
    $instruction->addAttribute('order', $order);
    $instruction->addAttribute('opcode', $opcode);  
    return $instruction; 
}

/**
 * Function for printing operands to stdout
 *
 * @param SimpleXml $xml_previous previous xml attribute
 * @param string $text text of argument
 * @param string $arg_n number of argument in case "arg1"
 * @param string $type type of argument
 * @return void
 */
function print_operand($xml_previous, $text, $arg_n, $type)
{
    $arg_element = $xml_previous->addChild($arg_n, $text);
    $arg_element->addAttribute('type', $type);
}

/**
 * Check if a given variable matches a specific data type.
 *
 * @param string $type The expected data type of the variable.
 * @param mixed $variable The variable to check.
 *
 * @return bool True if the variable matches the expected data type, false otherwise.
 */
function check_const($type, $variable)
{
    switch($type)
    {
        // If the expected data type is an integer, check if the variable is a valid integer in decimal, hexadecimal or octal format.
        case "int":
            return (preg_match('/^[+-]?(0|[1-9][0-9]*|0x[0-9a-fA-F]+|0[oO]?[0-7]+)$/', $variable));
        break;
        // If the expected data type is a boolean, check if the variable is either "true" or "false".
        case "bool":
            return ($variable == "true" || $variable == "false");
        break;
        // If the expected data type is nil, check if the variable is "nil".
        case "nil":
            return ($variable == "nil");
        break;
        // If the expected data type is a string, check if the variable contains only non-backslash characters or backslashes that are properly escaped.
        case "string":
            // Remove backslashes followed by three digits, which represent an ASCII character code, to properly check the string format.
            // if the variable is valid, replace some special characters and print the operand with the xml attribute
            $variable = preg_replace('/([\\\][\d]{3})/','',$variable);
            return(preg_match('/^[^\\\]+$/',$variable) || $variable == '');
        break;
    }
}


/**
 * Function for checking lexical errors
 *
 * @param string $argument text of instruction's argument
 * @param string $name expected type of argument
 * @param int $number number of argument from 1 to 3
 * @param SimpleXML $xml_previous previous xml attribute
 * @param int $line number of code line
 * @return void
 */
function chek_and_print_arguments($argument,$name,$number,$xml_previous,$line)
{
    // define a string with "arg" and the current argument number
    $arg_number = "arg" . $number;
    switch($name)
    {
        case "var":
            // check if the variable is valid
            if(preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][\w\-$&%*!?]*$/", $argument))
            {
                // if the variable is valid, replace some special characters and print the operand with the xml attribute
                $argument = str_replace("&", "&amp;", $argument);
                print_operand($xml_previous, $argument, $arg_number, $name);
            }
            // if the variable is invalid, print an error
            else{error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid variable!\n");}
        break;
        case "label":
            // check if the label is valid
            if (preg_match("/^[a-zA-Z_\-\$&%\*!\?][\w\-\$&%\*!\?]*$/", $argument))
            {
                // if the label is valid, replace some special characters and print the operand with the xml attribute
                $special_symb = array("<",">","&");
                $replacement = array("&lt;", "&gt;", "&amp;");
                $argument = str_replace($special_symb, $replacement, $argument);
                // if the label is valid, print the operand with the xml attribute
                print_operand($xml_previous, $argument, $arg_number, $name);
            }
            else{error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid variable!\n");}
        break;
        case "symb":
            // split the argument by @ to check its type
            $array_argument = explode("@", $argument);
            // if the symbol is a variable
            if($array_argument[0] == "GF" || $array_argument[0] == "LF" || $array_argument[0] == "TF")
            {
                // check if the variable is valid and print the operand with the xml attribute
                if(preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][\w\-$&%*!?]*$/", $argument))
                {
                    print_operand($xml_previous, $argument, $arg_number, "var");
                }
            }
            // if the symbol is a constant
            elseif ( $array_argument[0] == "nil" || $array_argument[0] == "bool" || $array_argument[0] == "int" || $array_argument[0] == "string")
            {
                // if the symbol is a string, split it again by @ to get the actual string value
               if ($array_argument[0] == "string")
               {
                 $array_argument = explode("@", $argument,2);
                 $array_argument[1] = str_replace("&", "&amp;", $array_argument[1]);
               }
               if(check_const($array_argument[0], $array_argument[1]))
               {
                    print_operand($xml_previous, $array_argument[1], $arg_number, $array_argument[0]);
               }
               else{error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid variable!\n");}
            }
            else{error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid variable!\n");}
        break;
        case "type":
            if (preg_match('/^(nil|bool|int|string)$/', $argument))
            {
                print_operand($xml_previous, $argument, $arg_number, $name);
            }
            else{error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid variable!\n");}
        break;
    }
}


/**
 * Function for parsing all instructions
 *
 * @param string $opcode instruktion from file
 * @param array $args array of all instruction's arguments
 * @param SimpleXml $xml_previous previous xml attribute
 * @param int $order number of output instruction
 * @param int $line number of code line
 * @return void
 */ 
function parse($opcode,$args,$xml_previous,$order,$line)
{
    // Convert the opcode to uppercase
    $opcode = strtoupper($opcode);
    switch($opcode)
    {
        //Instructions with no arguments
        case "CREATEFRAME":
        case "PUSHFRAME":
        case "POPFRAME":
        case "RETURN":
        case "BREAK":
            // If the instruction has no arguments, print the XML representation.
            if (count($args) == 0)
            {
                print_instruction($xml_previous,$order,$opcode);
            }
             // If the instruction has arguments, throw an error.
            else{error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid instruction operand count");}
        break;
        // Instructions with one argument <var>
        case "DEFVAR":
        case "POPS":
            if (count($args) != 1){error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid instruction operand count");}
            else 
            {
                $xml_previous = print_instruction($xml_previous,$order,$opcode);
                chek_and_print_arguments($args[0], "var", "1", $xml_previous, $line);
            }
        break;
        //<label>
        case "CALL":
        case "LABEL":
        case "JUMP":
            if (count($args) != 1){error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid instruction operand count");}
            else 
            {
                $xml_previous = print_instruction($xml_previous,$order,$opcode);
                chek_and_print_arguments($args[0], "label", "1", $xml_previous, $line);
            }
        break;
        //<symb>
        case "PUSHS":
        case "WRITE":
        case "EXIT":
        case "DPRINT":
            if (count($args) != 1){error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid instruction operand count");}
            else 
            {
                $xml_previous = print_instruction($xml_previous,$order,$opcode);
                chek_and_print_arguments($args[0], "symb", "1", $xml_previous, $line);
            }
        break;
        // Instructions with two arguments <var> <symb>
        case "NOT":
        case "MOVE":
        case "INT2CHAR":
        case "STRLEN":
        case "TYPE":
            if (count($args) != 2){error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid instruction operand count");}
            else 
            {
                $xml_previous = print_instruction($xml_previous,$order,$opcode);
                chek_and_print_arguments($args[0], "var", "1", $xml_previous, $line);
                chek_and_print_arguments($args[1], "symb", "2", $xml_previous, $line);
            }
        break;
        //<var> <type>
        case "READ":
            if (count($args) != 2){error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid instruction operand count");}
            else 
            {
                $xml_previous = print_instruction($xml_previous,$order,$opcode);
                chek_and_print_arguments($args[0], "var", "1", $xml_previous, $line);
                chek_and_print_arguments($args[1], "type", "2", $xml_previous, $line);
            }
        break;
        //Instructions with three arguments <var> <symb> <symb>
        case "ADD":
        case "SUB":
        case "MUL":
        case "IDIV":
        case "LT":
        case "GT":
        case "EQ":
        case "AND":
        case "OR":
        case "STRI2INT":
        case "GETCHAR":
        case "SETCHAR":
        case "CONCAT":
            if (count($args) != 3){error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid instruction operand count");}
            else 
            {
                $xml_previous = print_instruction($xml_previous,$order,$opcode);
                chek_and_print_arguments($args[0], "var", "1", $xml_previous, $line);
                chek_and_print_arguments($args[1], "symb", "2", $xml_previous, $line);
                chek_and_print_arguments($args[2], "symb", "3", $xml_previous, $line);
            }
        break;
        // <label><symb><symb>
        case "JUMPIFEQ":
        case "JUMPIFNEQ":
            if (count($args) != 3){error($line,LEXICAL_OR_SYNTAXIS_ERROR,"Invalid instruction operand count");}
            else 
            {
                $xml_previous = print_instruction($xml_previous,$order,$opcode);
                chek_and_print_arguments($args[0], "label", "1", $xml_previous, $line);
                chek_and_print_arguments($args[1], "symb", "2", $xml_previous, $line);
                chek_and_print_arguments($args[2], "symb", "3", $xml_previous, $line);
            }
        break;
        default:
        error($line,UNKNOWN_OR_INCORRECT_OPERATING_CODE,"Invalid or missing instruction!");
    }
}

############################################################## MAIN PROGRAM ####################################################################
// Create the SimpleXML object
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><program></program>');
$xml->addAttribute('language', 'IPPcode23');

check_arguments($argc,$argv);
$lines = file('php://stdin');
$tokens = array(); // declare empty array
$line_tokens = array();
$is_header_correct = false;
$order = 0;
for ($i = 0; $i < count($lines); $i++)
{
    $line_tokens = separate(remove_comments_and_whitespace($lines[$i]));
    for ($j = 0; $j < count($line_tokens); $j++) 
    {
        $token = $line_tokens[$j];
        if (strlen($token) > 0) { // check if token is not empty
            array_push($tokens, $token); // append to $tokens array
        }
    }
    // Check if the header is correct and there are tokens
    if (!$is_header_correct && count($tokens) != 0)
    {
        // Check if the first token is the correct header
        if (correct_header($tokens[0]))
        {
            // Check if there is only one token in the line
            if(count($line_tokens) == 1){$is_header_correct = true;}
            else{error($i,HEADER_ERROR,"At the beginning, instead of the instruction,has to be ONLY the language identifier\n");}
        }
        // Error if the first token is not the correct header
        else{error($i,HEADER_ERROR,"At the beginning, instead of the instruction,expected the language identifier\n");}
    }
    elseif($is_header_correct && $line_tokens[0] != "")
    {
        $order++;
        $args = array_slice($line_tokens,1,count($line_tokens)-1);
        // Parse the instruction and its arguments
        parse($line_tokens[0],$args,$xml,$order,$i);
    }
}
if ($lines == NULL || $tokens == NULL ){error(0,HEADER_ERROR,"At the beginning expected the language identifier\n");}
// Format the XML output and print it
$dom = dom_import_simplexml($xml)->ownerDocument;
$dom->formatOutput = true;
echo $dom->saveXML();
?>