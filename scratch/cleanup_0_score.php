<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

header('Content-Type: text/plain; charset=utf-8');

echo "=== CLEANUP 0 SCORE ATTEMPTS ===\n";
// Find attempts with 0 score
$res = $db->query("SELECT id, quiz_id, student_id, score, total_questions FROM quiz_attempts WHERE score = 0");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo "Found 0-score attempt: ID {$r['id']}, Quiz {$r['quiz_id']}, Student {$r['student_id']}\n";
    }
}

// Delete attempt 38 and any score = 0 attempt for quiz 55
$del = $db->query("DELETE FROM quiz_attempts WHERE id = 38 OR (quiz_id = 55 AND score = 0)");
if ($del) {
    echo "Successfully cleared attempt 38 and 0-score attempts for Quiz 55! Affected rows: " . $db->affected_rows . "\n";
} else {
    echo "Delete failed: " . $db->error . "\n";
}

echo "Done!\n";
