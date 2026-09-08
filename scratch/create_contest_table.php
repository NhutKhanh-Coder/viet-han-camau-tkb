<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<pre>\n=== CREATING TABLE mmo_contest_submissions ===\n";

$sql = "CREATE TABLE IF NOT EXISTS mmo_contest_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    student_code VARCHAR(50) NOT NULL,
    current_round INT DEFAULT 1,
    round1_answer VARCHAR(10) NULL,
    round1_score INT DEFAULT 0,
    round2_answer VARCHAR(10) NULL,
    round2_score INT DEFAULT 0,
    round3_code TEXT NULL,
    round3_score INT DEFAULT 0,
    total_score INT DEFAULT 0,
    rank_position INT DEFAULT 0,
    prize_awarded VARCHAR(255) NULL,
    prize_data TEXT NULL,
    prize_status ENUM('pending', 'awarded') DEFAULT 'pending',
    admin_comment TEXT NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    total_time_seconds INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (event_id),
    INDEX (student_id),
    INDEX (total_score),
    INDEX (rank_position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$ok = $db->query($sql);
echo $ok ? "SUCCESS: Created or table mmo_contest_submissions already exists.\n" : "ERROR: " . $db->error . "\n";

// Kiểm tra các cột
$res = $db->query("SHOW COLUMNS FROM mmo_contest_submissions");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo " - " . $r['Field'] . " (" . $r['Type'] . ")\n";
    }
}

echo "=== MIGRATION COMPLETE ===\n</pre>";
