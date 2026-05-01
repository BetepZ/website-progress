<?php
// File ini untuk mengambil 11 tugas pada minggu tertentu saat form dropdown dipilih
header('Content-Type: application/json');
$db_file = __DIR__ . '/../database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Menangkap parameter minggu (default minggu 1 jika kosong)
    $week = isset($_GET['w']) ? (int)$_GET['w'] : 1;

    // KEAMANAN: Menggunakan Prepared Statement
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE week_number = :week ORDER BY category ASC, slot_number ASC");
    $stmt->execute([':week' => $week]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $tasks]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
