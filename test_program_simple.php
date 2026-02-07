<?php
// Simple test to check if program.php can be loaded
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Testing program.php Loading</h1>";
echo "<pre>";

// Test 1: Check if config loads
echo "1. Testing config.php...\n";
try {
    require_once 'config.php';
    echo "   ✓ config.php loaded\n";
} catch(Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    die();
}

// Test 2: Try to include program.php with output buffering
echo "\n2. Testing program.php syntax...\n";
ob_start();
$old_error_handler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $errors;
    $errors[] = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    return false;
});

$errors = [];

// Try to parse the file
$file_content = file_get_contents('program.php');
if ($file_content === false) {
    echo "   ✗ Cannot read program.php file\n";
    die();
}

// Check for common syntax errors
if (strpos($file_content, '<?php') === false) {
    echo "   ⚠ Warning: No opening PHP tag found\n";
}

// Count opening and closing braces
$open_braces = substr_count($file_content, '{');
$close_braces = substr_count($file_content, '}');
if ($open_braces != $close_braces) {
    echo "   ⚠ Warning: Mismatched braces (open: $open_braces, close: $close_braces)\n";
}

// Count opening and closing parentheses
$open_parens = substr_count($file_content, '(');
$close_parens = substr_count($file_content, ')');
if ($open_parens != $close_parens) {
    echo "   ⚠ Warning: Mismatched parentheses (open: $open_parens, close: $close_parens)\n";
}

// Try to check PHP syntax
$syntax_check = shell_exec('php -l program.php 2>&1');
if ($syntax_check) {
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "   ✓ No PHP syntax errors detected\n";
    } else {
        echo "   ✗ PHP syntax errors found:\n";
        echo "   " . str_replace("\n", "\n   ", $syntax_check) . "\n";
    }
}

// Test 3: Try to execute a minimal version
echo "\n3. Testing database queries from program.php...\n";
try {
    // Test the main query
    $table_check = $pdo->query("SHOW TABLES LIKE 'program_csr'")->fetch();
    if ($table_check) {
        echo "   ✓ Table 'program_csr' exists\n";
        
        // Test the main query
        $test_query = $pdo->query("SELECT COUNT(*) as total FROM program_csr")->fetch();
        echo "   ✓ Query test successful (found " . $test_query['total'] . " programs)\n";
    } else {
        echo "   ✗ Table 'program_csr' does not exist\n";
    }
} catch(PDOException $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

// Test 4: Check for undefined variables
echo "\n4. Checking for potential undefined variables...\n";
$undefined_vars = [];
preg_match_all('/\$(\w+)/', $file_content, $matches);
$all_vars = array_unique($matches[1]);

// Check for common undefined variables that might cause errors
$critical_vars = ['lokasi_list', 'penyaluran_list', 'dampak_list', 'stats_lokasi', 'penyaluran_stats', 'dampak_stats'];
foreach ($critical_vars as $var) {
    // Check if variable is initialized before use
    $var_pattern = '/\$' . $var . '\s*=/';
    if (!preg_match($var_pattern, $file_content)) {
        echo "   ⚠ Warning: Variable \$$var might not be initialized\n";
    } else {
        echo "   ✓ Variable \$$var is initialized\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "</pre>";

echo "<h2>Recommendations:</h2>";
echo "<ul>";
echo "<li>If syntax errors found, fix them first</li>";
echo "<li>If undefined variables found, initialize them at the top of the file</li>";
echo "<li>Try accessing program.php?debug=1 to see runtime errors</li>";
echo "<li>Check PHP error log for detailed error messages</li>";
echo "</ul>";

?>

