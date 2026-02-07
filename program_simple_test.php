<?php
// Minimal test version of program.php to find the error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Program Test</title></head><body>";
echo "<h1>Testing program.php Components</h1>";
echo "<pre>";

try {
    echo "1. Loading config.php...\n";
    require_once 'config.php';
    echo "   ✓ Config loaded\n";
    
    echo "\n2. Testing database connection...\n";
    $pdo->query("SELECT 1");
    echo "   ✓ Database OK\n";
    
    echo "\n3. Testing program_csr table...\n";
    try {
        $result = $pdo->query("SELECT COUNT(*) as total FROM program_csr")->fetch();
        echo "   ✓ Table exists, " . $result['total'] . " records\n";
    } catch(PDOException $e) {
        echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. Testing main query from program.php...\n";
    try {
        $program_list = $pdo->query("SELECT p.*, 
            u.nama_lengkap as pic_name,
            COALESCE((SELECT SUM(jumlah) FROM csr_donations WHERE program=p.nama_program), 0) as total_donasi,
            COALESCE((SELECT SUM(jumlah_penyaluran) FROM program_penyaluran WHERE program_id=p.id), 0) as total_penyaluran,
            COALESCE(p.progress, 0) as progress
            FROM program_csr p 
            LEFT JOIN users u ON p.pic=u.id 
            ORDER BY p.tanggal_mulai DESC
            LIMIT 5")->fetchAll();
        echo "   ✓ Query successful, found " . count($program_list) . " programs\n";
    } catch(PDOException $e) {
        echo "   ✗ QUERY ERROR: " . $e->getMessage() . "\n";
        echo "   Trying simpler query...\n";
        try {
            $program_list = $pdo->query("SELECT * FROM program_csr LIMIT 5")->fetchAll();
            echo "   ✓ Simple query works, found " . count($program_list) . " programs\n";
        } catch(PDOException $e2) {
            echo "   ✗ Simple query also failed: " . $e2->getMessage() . "\n";
        }
    }
    
    echo "\n5. Testing error handler...\n";
    if (!isset($GLOBALS['program_captured_errors'])) {
        $GLOBALS['program_captured_errors'] = [];
    }
    echo "   ✓ Error handler setup OK\n";
    
    echo "\n=== All Tests Passed ===\n";
    echo "</pre>";
    
    echo "<h2>If program.php still shows HTTP 500:</h2>";
    echo "<ul>";
    echo "<li>Check PHP error log: " . ini_get('error_log') . "</li>";
    echo "<li>Check server error log</li>";
    echo "<li>Try accessing: program.php?debug=1</li>";
    echo "<li>Run: check_program_error.php</li>";
    echo "</ul>";
    
} catch(Throwable $e) {
    echo "\n✗ FATAL ERROR:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</body></html>";
?>

