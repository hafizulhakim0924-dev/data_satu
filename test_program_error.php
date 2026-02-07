<?php
// Simple error test page for program.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Error Test - Program.php</h1>";
echo "<pre>";

// Test config
echo "1. Testing config.php...\n";
try {
    require_once 'config.php';
    echo "✓ config.php loaded successfully\n";
    echo "✓ Database connection: OK\n";
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    die();
}

// Test tables
echo "\n2. Testing required tables...\n";
$tables = ['program_csr', 'users', 'csr_donations', 'program_penyaluran', 'program_dampak', 'lokasi_strategis'];
foreach($tables as $table) {
    try {
        $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        echo "✓ Table '$table': EXISTS\n";
    } catch(PDOException $e) {
        echo "✗ Table '$table': NOT FOUND - " . $e->getMessage() . "\n";
    }
}

// Test program_csr query
echo "\n3. Testing program_csr query...\n";
try {
    $result = $pdo->query("SELECT COUNT(*) as total FROM program_csr")->fetch();
    echo "✓ Total programs: " . ($result['total'] ?? 0) . "\n";
} catch(PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test program_csr with subquery
echo "\n4. Testing program_csr with subquery...\n";
try {
    $result = $pdo->query("
        SELECT p.*, 
            u.nama_lengkap as pic_name,
            COALESCE((SELECT SUM(jumlah) FROM csr_donations WHERE program=p.nama_program), 0) as total_donasi
        FROM program_csr p 
        LEFT JOIN users u ON p.pic=u.id 
        LIMIT 1
    ")->fetch();
    echo "✓ Query with subquery: OK\n";
} catch(PDOException $e) {
    echo "✗ Error with subquery: " . $e->getMessage() . "\n";
    echo "  This might be the cause of HTTP 500 error\n";
}

// Test program_penyaluran
echo "\n5. Testing program_penyaluran table...\n";
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'program_penyaluran'")->fetch();
    if ($table_check) {
        $result = $pdo->query("SELECT COUNT(*) as total FROM program_penyaluran")->fetch();
        echo "✓ Table exists, total records: " . ($result['total'] ?? 0) . "\n";
    } else {
        echo "⚠ Table 'program_penyaluran' does not exist\n";
    }
} catch(PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test program_dampak
echo "\n6. Testing program_dampak table...\n";
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'program_dampak'")->fetch();
    if ($table_check) {
        $result = $pdo->query("SELECT COUNT(*) as total FROM program_dampak")->fetch();
        echo "✓ Table exists, total records: " . ($result['total'] ?? 0) . "\n";
    } else {
        echo "⚠ Table 'program_dampak' does not exist\n";
    }
} catch(PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test lokasi_strategis
echo "\n7. Testing lokasi_strategis table...\n";
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'lokasi_strategis'")->fetch();
    if ($table_check) {
        $result = $pdo->query("SELECT COUNT(*) as total FROM lokasi_strategis")->fetch();
        echo "✓ Table exists, total records: " . ($result['total'] ?? 0) . "\n";
    } else {
        echo "⚠ Table 'lokasi_strategis' does not exist\n";
    }
} catch(PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "</pre>";

echo "<h2>Next Steps:</h2>";
echo "<ul>";
echo "<li>Jika ada tabel yang tidak ada, jalankan script SQL yang sesuai</li>";
echo "<li>Jika ada error query, periksa struktur tabel</li>";
echo "<li>Buka program.php?debug=1 untuk melihat error detail</li>";
echo "</ul>";

?>

