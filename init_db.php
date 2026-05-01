<?php
// Script ini dijalankan 1x saja untuk setup awal database SQLite.

try {

    $db_file = __DIR__ . '/database.sqlite';
    $pdo = new PDO('sqlite:' . $db_file);


    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Membuat Tabel Tasks
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            week_number INTEGER NOT NULL,
            category TEXT NOT NULL,
            slot_number INTEGER NOT NULL,
            url TEXT,
            is_important INTEGER DEFAULT 0,
            notes TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createTableQuery);


    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_week ON tasks(week_number)");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_week_cat_slot ON tasks(week_number, category, slot_number)");


    $stmt = $pdo->query("SELECT COUNT(*) FROM tasks");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo "Tabel kosong. Memulai proses seeding data...<br>";


        $insertStmt = $pdo->prepare("INSERT INTO tasks (week_number, category, slot_number) VALUES (:week, :category, :slot)");


        $pdo->beginTransaction();


        for ($w = 1; $w <= 13; $w++) {


            for ($i = 1; $i <= 3; $i++) {
                $insertStmt->execute([':week' => $w, ':category' => 'article', ':slot' => $i]);
            }


            for ($i = 1; $i <= 6; $i++) {
                $insertStmt->execute([':week' => $w, ':category' => 'instagram', ':slot' => $i]);
            }


            for ($i = 1; $i <= 3; $i++) {
                $insertStmt->execute([':week' => $w, ':category' => 'youtube', ':slot' => $i]);
            }
        }


        $pdo->commit();
        echo "✅ Sukses! Database SQLite telah dibuat dan 143 baris tugas awal berhasil disiapkan.";
    } else {
        echo "⚠️ Database sudah memiliki $count baris data. Proses seeding dibatalkan agar data aman.";
    }
} catch (PDOException $e) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo " Terjadi kesalahan Database: " . htmlspecialchars($e->getMessage());
}
