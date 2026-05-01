<?php

header('Content-Type: application/json');
$db_file = __DIR__ . '/../database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $week = isset($_GET['w']) ? (int)$_GET['w'] : 1;


    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE week_number = :week ORDER BY category ASC, slot_number ASC");
    $stmt->execute([':week' => $week]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $tasks]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
