<?php
// Simple error checker for program.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Error Checker</title></head><body>";
echo "<h1>Checking program.php for Errors</h1>";
echo "<pre>";

// Test 1: Check config
echo "1. Testing config.php...\n";
try {
    require_once 'config.php';
    echo "   ✓ config.php OK\n";
} catch(Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    die("</pre></body></html>");
}

// Test 2: Check if we can read program.php
echo "\n2. Reading program.php file...\n";
$file_content = @file_get_contents('program.php');
if ($file_content === false) {
    echo "   ✗ Cannot read program.php\n";
    die("</pre></body></html>");
}
echo "   ✓ File readable (" . strlen($file_content) . " bytes)\n";

// Test 3: Check PHP syntax
echo "\n3. Checking PHP syntax...\n";
$output = [];
$return_var = 0;
exec('php -l program.php 2>&1', $output, $return_var);
if ($return_var === 0) {
    echo "   ✓ No syntax errors\n";
} else {
    echo "   ✗ SYNTAX ERRORS FOUND:\n";
    foreach($output as $line) {
        echo "   " . $line . "\n";
    }
}

// Test 4: Try to include program.php with error capture
echo "\n4. Attempting to execute program.php...\n";
ob_start();
$old_error_handler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $errors;
    if (!isset($errors)) $errors = [];
    $errors[] = [
        'type' => $errno,
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ];
    echo "   ⚠ ERROR: [$errno] $errstr in " . basename($errfile) . ":$errline\n";
    return false;
});

$errors = [];

try {
    // Try to include just the PHP part
    $php_start = strpos($file_content, '<?php');
    if ($php_start !== false) {
        $php_code = substr($file_content, $php_start + 5);
        // Remove HTML part
        $html_start = strpos($php_code, '<!DOCTYPE');
        if ($html_start !== false) {
            $php_code = substr($php_code, 0, $html_start);
        }
        
        // Try to eval just the initialization part
        $init_code = '';
        $lines = explode("\n", $php_code);
        $line_count = 0;
        foreach($lines as $line) {
            $line_count++;
            if ($line_count > 100) break; // Only check first 100 lines
            if (strpos($line, 'require_once') !== false || 
                strpos($line, 'if ($_SERVER') !== false ||
                strpos($line, 'try {') !== false) {
                $init_code .= $line . "\n";
            }
        }
        
        // Test database connection part
        echo "   Testing database queries...\n";
        try {
            $pdo->query("SELECT 1");
            echo "   ✓ Database connection OK\n";
        } catch(PDOException $e) {
            echo "   ✗ Database error: " . $e->getMessage() . "\n";
        }
        
        // Test table checks
        $tables = ['program_csr', 'users', 'csr_donations', 'program_penyaluran', 'program_dampak', 'lokasi_strategis'];
        foreach($tables as $table) {
            try {
                $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
                echo "   ✓ Table '$table' exists\n";
            } catch(PDOException $e) {
                echo "   ✗ Table '$table' ERROR: " . $e->getMessage() . "\n";
            }
        }
        
    }
} catch(Throwable $e) {
    echo "   ✗ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();
if (!empty($output)) {
    echo $output;
}

if (!empty($errors)) {
    echo "\n   ⚠ Total errors captured: " . count($errors) . "\n";
}

restore_error_handler();

echo "\n=== Check Complete ===\n";
echo "</pre>";

echo "<h2>Next Steps:</h2>";
echo "<ul>";
echo "<li>If syntax errors found, fix them first</li>";
echo "<li>If table errors found, run fix_all_program_tables.sql</li>";
echo "<li>Check php_errors.log for detailed errors</li>";
echo "</ul>";

echo "</body></html>";
?>

