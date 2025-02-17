# **IPP Project - XML Parser**

### **Author**
**Name:** Dinara Garipova  
**Login:** xgarip00  

## **Project Overview**  
This project is an implementation of a script that processes IPPCode23 code and converts it into an XML representation. The script is structured to parse input, check syntax, handle errors, and generate a well-formed XML output.

## **Script Structure**
1. **Error Codes and Constants:**  
   - The script starts with the definition of error return codes.

2. **Helper Functions:**  
   - Includes functions for handling command-line arguments, printing help messages, and error reporting.

3. **Parsing Logic:**  
   - Reads input from `stdin`, removes unnecessary whitespace and comments.  
   - Splits instructions into tokens for structured processing.

4. **Instruction Handling:**  
   - Instructions are categorized into 8 groups based on operands.  
   - Each group is validated for correct syntax and structure.

5. **XML Generation:**  
   - Uses the **SimpleXML** library to create structured XML output.

## **Execution**
To run the script, use:
```bash
php parse.php < input_file.txt > output.xml
```
where:

- input_file.txt contains IPPCode23 instructions.
- output.xml is the generated XML output.

## Error Handling
- The script detects missing headers, incorrect syntax, and invalid arguments.
- Regular expressions ensure correct formatting of constants and variables.
  
## Output Format
- The final output is an XML document representing the parsed code.
- The script ensures proper formatting before writing to standard output.

## Project Structure
```bash
.
├── tests/          # Test cases
├── tests_1/        # Additional test cases
├── Tests_2/        # More test cases
├── is_it_ok.sh     # Script for checking submission requirements
├── parse.php       # Main parsing script
├── readme1.pdf     # Documentation
├── simple_tag.out  # Sample output file
├── simple_tag.src  # Sample source file
├── test.php        # Additional test script
```
