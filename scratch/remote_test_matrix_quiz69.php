<?php
require_once '../config.php';
$db = getDB();

$qid = 69; // The quiz with 40 questions uploaded earlier
$stq = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY id ASC");
$stq->bind_param("i", $qid);
$stq->execute();
$base_qs = $stq->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($base_qs)) {
    // Find latest quiz
    $rq = $db->query("SELECT id, tieu_de FROM quizzes ORDER BY id DESC LIMIT 1");
    if ($rq && $row = $rq->fetch_assoc()) {
        $qid = $row['id'];
        $stq = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY id ASC");
        $stq->bind_param("i", $qid);
        $stq->execute();
        $base_qs = $stq->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

echo "Working on Quiz ID $qid: " . count($base_qs) . " questions found.\n";

// Regenerate 4 exam codes using new invariant logic
$so_ma_de = 4;
$ma_de_start = 101;
$letters = ['A','B','C','D'];
$db->query("DELETE FROM quiz_exam_codes WHERE quiz_id = $qid");

for ($i = 0; $i < $so_ma_de; $i++) {
    $ma_de = (string)($ma_de_start + $i);
    $qs = $base_qs;
    shuffle($qs);

    $matrix = [];
    foreach ($qs as $idx => $bq) {
        $orig_dung = strtoupper(trim($bq['dap_an_dung'] ?? 'A'));
        if (!in_array($orig_dung, ['A','B','C','D'])) $orig_dung = 'A';

        $opt_items = [
            ['orig_key' => 'A', 'text' => $bq['dap_an_a'] ?? '', 'is_correct' => ($orig_dung === 'A')],
            ['orig_key' => 'B', 'text' => $bq['dap_an_b'] ?? '', 'is_correct' => ($orig_dung === 'B')],
            ['orig_key' => 'C', 'text' => $bq['dap_an_c'] ?? '', 'is_correct' => ($orig_dung === 'C')],
            ['orig_key' => 'D', 'text' => $bq['dap_an_d'] ?? '', 'is_correct' => ($orig_dung === 'D')],
        ];
        shuffle($opt_items);

        $new_opts = [];
        $new_correct = 'A';
        foreach ($letters as $li => $lc) {
            $new_opts[$lc] = $opt_items[$li]['text'] ?? '';
            if (!empty($opt_items[$li]['is_correct'])) {
                $new_correct = $lc;
            }
        }

        $matrix[] = [
            'stt' => $idx + 1,
            'orig_id' => $bq['id'],
            'id' => $bq['id'],
            'cau_hoi' => $bq['cau_hoi'],
            'dap_an_a' => $new_opts['A'],
            'dap_an_b' => $new_opts['B'],
            'dap_an_c' => $new_opts['C'],
            'dap_an_d' => $new_opts['D'],
            'dap_an_dung' => $new_correct,
        ];
    }

    $json = json_encode($matrix, JSON_UNESCAPED_UNICODE);
    $ins = $db->prepare("INSERT INTO quiz_exam_codes (quiz_id, ma_de, matrix_data) VALUES (?, ?, ?)");
    $ins->bind_param("iss", $qid, $ma_de, $json);
    $ins->execute();
    echo "Generated Code $ma_de with " . count($matrix) . " questions.\n";
}

// Verification on DB
$check = $db->query("SELECT id, ma_de, LENGTH(matrix_data) as len FROM quiz_exam_codes WHERE quiz_id = $qid ORDER BY ma_de ASC");
while ($r = $check->fetch_assoc()) {
    echo "DB Code {$r['ma_de']}: ID={$r['id']}, Length={$r['len']} bytes.\n";
}
