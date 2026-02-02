<?php
require_once 'config.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="export_' . date('Y-m-d_His') . '.xls"');
header('Cache-Control: max-age=0');

$type = $_GET['type'] ?? 'donasi';

function exportToExcel($data, $headers, $filename = '') {
    echo '<table border="1">';
    echo '<tr>';
    foreach($headers as $header) {
        echo '<th style="background-color:#4CAF50;color:white;font-weight:bold;padding:8px;">' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    
    foreach($data as $row) {
        echo '<tr>';
        foreach($row as $cell) {
            echo '<td style="padding:5px;">' . htmlspecialchars($cell ?? '') . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
}

switch($type) {
    case 'donasi':
        $data = $pdo->query("
            SELECT d.tanggal, d.nama_donatur, d.jumlah, d.kategori, d.program, d.metode_pembayaran, d.status, d.keterangan
            FROM csr_donations d
            ORDER BY d.tanggal DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['Tanggal', 'Nama Donatur', 'Jumlah', 'Kategori', 'Program', 'Metode Pembayaran', 'Status', 'Keterangan'];
        $rows = [];
        foreach($data as $d) {
            $rows[] = [
                date('d/m/Y', strtotime($d['tanggal'])),
                $d['nama_donatur'],
                number_format($d['jumlah'], 0, ',', '.'),
                $d['kategori'],
                $d['program'] ?? '-',
                $d['metode_pembayaran'],
                $d['status'],
                $d['keterangan'] ?? '-'
            ];
        }
        exportToExcel($rows, $headers);
        break;
        
    case 'donatur':
        $data = $pdo->query("
            SELECT nama, email, no_hp, tipe, kategori, status, alamat
            FROM donatur
            ORDER BY nama
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['Nama', 'Email', 'No HP', 'Tipe', 'Kategori', 'Status', 'Alamat'];
        $rows = [];
        foreach($data as $d) {
            $rows[] = [
                $d['nama'],
                $d['email'] ?? '-',
                $d['no_hp'] ?? '-',
                $d['tipe'],
                $d['kategori'],
                $d['status'],
                $d['alamat'] ?? '-'
            ];
        }
        exportToExcel($rows, $headers);
        break;
        
    case 'program':
        $data = $pdo->query("
            SELECT p.nama_program, p.kategori, p.lokasi, p.tanggal_mulai, p.tanggal_selesai, p.budget, p.status, u.nama_lengkap as pic
            FROM program_csr p
            LEFT JOIN users u ON p.pic = u.id
            ORDER BY p.tanggal_mulai DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['Nama Program', 'Kategori', 'Lokasi', 'Tanggal Mulai', 'Tanggal Selesai', 'Budget', 'Status', 'PIC'];
        $rows = [];
        foreach($data as $d) {
            $rows[] = [
                $d['nama_program'],
                $d['kategori'],
                $d['lokasi'] ?? '-',
                $d['tanggal_mulai'] ? date('d/m/Y', strtotime($d['tanggal_mulai'])) : '-',
                $d['tanggal_selesai'] ? date('d/m/Y', strtotime($d['tanggal_selesai'])) : '-',
                number_format($d['budget'] ?? 0, 0, ',', '.'),
                $d['status'],
                $d['pic'] ?? '-'
            ];
        }
        exportToExcel($rows, $headers);
        break;
        
    case 'karyawan':
        try {
            $data = $pdo->query("
                SELECT nip, nama_lengkap, email, no_hp, jabatan, departemen, status_karyawan, gaji_pokok, status
                FROM karyawan
                ORDER BY nama_lengkap
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = ['NIP', 'Nama Lengkap', 'Email', 'No HP', 'Jabatan', 'Departemen', 'Status Karyawan', 'Gaji Pokok', 'Status'];
            $rows = [];
            foreach($data as $d) {
                $rows[] = [
                    $d['nip'],
                    $d['nama_lengkap'],
                    $d['email'] ?? '-',
                    $d['no_hp'] ?? '-',
                    $d['jabatan'] ?? '-',
                    $d['departemen'] ?? '-',
                    $d['status_karyawan'],
                    number_format($d['gaji_pokok'] ?? 0, 0, ',', '.'),
                    $d['status']
                ];
            }
            exportToExcel($rows, $headers);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        break;
        
    case 'keuangan':
        $subtype = $_GET['subtype'] ?? 'pemasukan';
        
        if($subtype == 'pemasukan') {
            $data = $pdo->query("
                SELECT tanggal, kategori, sumber, jumlah, metode_pembayaran, status, keterangan
                FROM pemasukan
                ORDER BY tanggal DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = ['Tanggal', 'Kategori', 'Sumber', 'Jumlah', 'Metode Pembayaran', 'Status', 'Keterangan'];
            $rows = [];
            foreach($data as $d) {
                $rows[] = [
                    date('d/m/Y', strtotime($d['tanggal'])),
                    $d['kategori'],
                    $d['sumber'],
                    number_format($d['jumlah'], 0, ',', '.'),
                    $d['metode_pembayaran'] ?? '-',
                    $d['status'],
                    $d['keterangan'] ?? '-'
                ];
            }
            exportToExcel($rows, $headers);
        } else {
            $data = $pdo->query("
                SELECT tanggal, kategori, nama_program, vendor, jumlah, metode_pembayaran, status, keterangan
                FROM pengeluaran
                ORDER BY tanggal DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = ['Tanggal', 'Kategori', 'Program', 'Vendor', 'Jumlah', 'Metode Pembayaran', 'Status', 'Keterangan'];
            $rows = [];
            foreach($data as $d) {
                $rows[] = [
                    date('d/m/Y', strtotime($d['tanggal'])),
                    $d['kategori'],
                    $d['nama_program'] ?? '-',
                    $d['vendor'] ?? '-',
                    number_format($d['jumlah'], 0, ',', '.'),
                    $d['metode_pembayaran'] ?? '-',
                    $d['status'],
                    $d['keterangan'] ?? '-'
                ];
            }
            exportToExcel($rows, $headers);
        }
        break;
        
    default:
        echo "Type tidak valid";
}
?>

