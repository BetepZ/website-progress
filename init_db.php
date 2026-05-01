<?php
// Script ini dijalankan 1x saja untuk setup awal database SQLite.

try {
    // Membuat atau menghubungkan ke file database SQLite
    $db_file = __DIR__ . '/database.sqlite';
    $pdo = new PDO('sqlite:' . $db_file);

    // Set error mode ke Exception agar mudah mendeteksi error (Praktik Keamanan & Debugging)
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

    // 2. Membuat Indexing (Sangat penting untuk efisiensi dan mencegah query lambat)
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_week ON tasks(week_number)");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_week_cat_slot ON tasks(week_number, category, slot_number)");

    // 3. Pengecekan Data (Mencegah duplikasi data jika script ter-run dua kali)
    $stmt = $pdo->query("SELECT COUNT(*) FROM tasks");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo "Tabel kosong. Memulai proses seeding data...<br>";

        // Menggunakan Prepared Statement untuk efisiensi dan keamanan
        $insertStmt = $pdo->prepare("INSERT INTO tasks (week_number, category, slot_number) VALUES (:week, :category, :slot)");

        // Memulai transaksi agar insert 143 baris berjalan sangat cepat dalam 1 proses
        $pdo->beginTransaction();

        // Looping untuk 13 Minggu
        for ($w = 1; $w <= 13; $w++) {

            // 3 Artikel
            for ($i = 1; $i <= 3; $i++) {
                $insertStmt->execute([':week' => $w, ':category' => 'article', ':slot' => $i]);
            }

            // 6 Instagram
            for ($i = 1; $i <= 6; $i++) {
                $insertStmt->execute([':week' => $w, ':category' => 'instagram', ':slot' => $i]);
            }

            // 2 YouTube
            for ($i = 1; $i <= 2; $i++) {
                $insertStmt->execute([':week' => $w, ':category' => 'youtube', ':slot' => $i]);
            }
        }

        // Commit transaksi
        $pdo->commit();
        echo "✅ Sukses! Database SQLite telah dibuat dan 143 baris tugas awal berhasil disiapkan.";
    } else {
        echo "⚠️ Database sudah memiliki $count baris data. Proses seeding dibatalkan agar data aman.";
    }
} catch (PDOException $e) {
    // Rollback jika ada error saat transaksi
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Terjadi kesalahan Database: " . htmlspecialchars($e->getMessage());
}
