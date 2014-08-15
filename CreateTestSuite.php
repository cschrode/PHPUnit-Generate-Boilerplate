<?php

/**
 * Parses and Generates Test Suite for one function signature
 *
 * @param   String  $function  Function prototype
 * @param   String  $test_dir  The directory to place the test
 */
function generateTestSuite($function, $test_dir)
{
    $function_array = parseFunctionPrototype($function);
    produceTestSuite($function_array, $test_dir);
}

/**
 * Ensures no spaces before or after array items
 *
 * For use in array_walk()
 *
 * @param String &$value Value to transform
 *
 * @return  void Value is trimmed of spaces
 */
function trimValue(&$value)
{
    $value = trim($value);
}

/**
 * Splits a function up into its name and its arguments
 *
 * @param   String $function Function prototype
 *
 * @return  Array  [0] Name of function, [n] Arguments
 */
function parseFunctionPrototype($function)
{
    $function_name = substr($function, 0, strpos($function, "("));

    // Check to see if there's any arguments
    if (!strpos($function, "$")) {
        return array($function_name);
    }

    // Get entire argument signature
    $arguments = substr(
        $function,
        $start = ( strpos($function, "(") + 1 ),
        $length = ( strpos($function, ")") - strpos($function, "(") - 1 )
    );

    // Check to see if there's only one argument
    if (!strpos($arguments, ",")) {
        return array($function_name, $arguments);
    }

    // Turn arguments into an array
    $args   = explode(',', $arguments);
    $result = array();

    // First argument should be function's name
    // The rest should be arguments
    $result[0] = $function_name;
    $result    = array_merge($result, $args);

    // Ensures there's no spaces before or after the values
    array_walk($result, 'trimValue');

    return $result;
}

/**
 * Writes test suite to a file as a standalone test group
 *
 * @param Array  $signature Function prototype
 * @param String $test_dir  The directory to place the test
 *
 * @return  void Produces a PHP file boilerplated to handle unit tests
 */
function produceTestSuite(Array $signature, $test_dir)
{
    // Don't create a suite for functions with no arguments
    if (isset($signature) && count($signature) < 2) {
        return false;
    }

    // Pop the function's name off the front
    $function    = array_shift($signature);
    $uc_function = ucfirst($function);

    // Regroup arguments into a string
    $args = join(", ", $signature);

    // Stubs are substituted arguments
    // Mocks dynamically use the stubs, we need the array index though
    $stubs  = "";
    $mocks  = "";
    $ubound = count($signature) - 1;
    for ($i=0; $i < $ubound; $i++) {
        // Concrete arguments
        $stubs .= '0, ';

        // References to concrete arguments
        $mocks .= ", \$expectResults[".($i+1)."]";
    }

    // Finally create file into the Test Directory parameter the user passed
    $fp = fopen($test_dir.'/'.$uc_function.'Test.php', 'w');
    fwrite(
        $fp, "<?php

class ".$uc_function."Test extends PHPUnit_Framework_TestCase
{
    // Function under testing
    private function ".$function."(".$args.")
    {
        return \"abc\";
    }

    // Test Driver function, don't call $function() directly
    // Usage: \$actual_result = run_{$function}(array($args, \$expected_result));
    private function run_".$function."(Array \$expectResults)
    {
        \$actualResult = \$this->".$function."(\$expectResults[0]{$mocks});

        return array(\$actualResult, end(\$expectResults));
    }

    // Use Cases
    ///////////////////////////////////////////////////////////////////////////

    // Heuristic Cases
    ///////////////////////////////////////////////////////////////////////////

    // Boundary Case: MAX_INT
    ///////////////////////////////////////////////////////////////////////////
    public function test".$uc_function."_MaxInt()
    {
        // Act
        \$results = \$this->run_".$function."(array(32767, {$stubs}32767));

        // Assert
        \$this->assertEquals(\$results[0], \$results[1]);
    }

    // Boundary Case: MIN_INT
    ///////////////////////////////////////////////////////////////////////////
    public function test".$uc_function."_MinInt()
    {
        // Act
        \$results = \$this->run_".$function."(array(-32768, {$stubs}-32768));

        // Assert
        \$this->assertEquals(\$results[0], \$results[1]);
    }


    // Boundary Case: NULL Value
    ///////////////////////////////////////////////////////////////////////////
    public function test".$uc_function."_NullValue()
    {
        // Act
        \$results = \$this->run_".$function."(array(NULL, {$stubs}NULL));

        // Assert
        \$this->assertEquals(\$results[0], \$results[1]);
    }


    // Boundary Case: Zero
    ///////////////////////////////////////////////////////////////////////////
    public function test".$uc_function."_Zero()
    {
        // Act
        \$results = \$this->run_".$function."(array(0, {$stubs}0));

        // Assert
        \$this->assertEquals(\$results[0], \$results[1]);
    }


    // Boundary Case: Negative Value
    ///////////////////////////////////////////////////////////////////////////
    public function test".$uc_function."_NegativeValue()
    {
        // Act
        \$results = \$this->run_".$function."(array(-1, {$stubs}-1));

        // Assert
        \$this->assertEquals(\$results[0], \$results[1]);
    }


    // Boundary Case: Empty String value
    ///////////////////////////////////////////////////////////////////////////
    public function test".$uc_function."_StringValue()
    {
        // Act
        \$results = \$this->run_".$function."(array(\"\", {$stubs}\"\"));

        // Assert
        \$this->assertEquals(\$results[0], \$results[1]);
    }
};"
    );

    fclose($fp);
}

// Usage:
// CreateTests.php 'doExample($argc, $argv)' /var/www/project/mysite/tests
generateTestSuite($argv[1], $argv[2]);