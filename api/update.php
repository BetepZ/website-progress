<?php

header('Content-Type: application/json');
$db_file = __DIR__ . '/../database.sqlite';


$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (!$input || !isset($input['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    exit;
}

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $stmt = $pdo->prepare("
        UPDATE tasks 
        SET url = :url, 
            is_important = :is_important, 
            notes = :notes,
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = :id
    ");

    $stmt->execute([
        ':url' => isset($input['url']) && trim($input['url']) !== '' ? trim($input['url']) : null,
        ':is_important' => isset($input['is_important']) ? (int)$input['is_important'] : 0,
        ':notes' => isset($input['notes']) ? trim($input['notes']) : null,
        ':id' => $input['id']
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Data berhasil diupdate']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
