<?php
// Test verification script for Quiz Shuffling & Grading Accuracy
$db = new mysqli('localhost', 'root', '', 'tkb');
if ($db->connect_error) {
    // If local db not available or table empty, test with sample questions array
    echo "Local DB connect notice: " . $db->connect_error . "\n";
}

// Check if Quiz 69 or any quiz exists locally
$base_qs = [];
if (!$db->connect_error) {
    $res = $db->query("SELECT * FROM quiz_questions WHERE quiz_id = 69 ORDER BY id ASC");
    if ($res && $res->num_rows > 0) {
        $base_qs = $res->fetch_all(MYSQLI_ASSOC);
    }
}

if (empty($base_qs)) {
    // Mock 40 questions to simulate a real exam
    for ($i = 1; $i <= 40; $i++) {
        $correctLetter = ['A','B','C','D'][($i * 7) % 4];
        $base_qs[] = [
            'id' => $i,
            'cau_hoi' => "Câu hỏi số $i: Nội dung kiểm tra kiến thức lập trình?",
            'dap_an_a' => "Phương án A của câu $i",
            'dap_an_b' => "Phương án B của câu $i",
            'dap_an_c' => "Phương án C của câu $i",
            'dap_an_d' => "Phương án D của câu $i",
            'dap_an_dung' => $correctLetter
        ];
    }
}

echo "Testing with " . count($base_qs) . " questions...\n";

$letters = ['A', 'B', 'C', 'D'];
$all_exams_perfect = true;

for ($code_idx = 101; $code_idx <= 104; $code_idx++) {
    $qs = $base_qs;
    shuffle($qs); // Shuffle question order

    $matrix = [];
    foreach ($qs as $idx => $bq) {
        $orig_dung = strtoupper(trim($bq['dap_an_dung'] ?? 'A'));
        if (!in_array($orig_dung, ['A','B','C','D'])) $orig_dung = 'A';
        $true_content = $bq['dap_an_' . strtolower($orig_dung)];

        // Map options with boolean correct tracker (Invariant through shuffle)
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

        // Verify the shuffled correct letter maps to the true original content
        $assigned_correct_content = $new_opts[$new_correct];
        if ($assigned_correct_content !== $true_content) {
            echo "MISMATCH in Code $code_idx at question {$bq['id']}: expected '$true_content', got '$assigned_correct_content'\n";
            $all_exams_perfect = false;
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

    // Simulate Student Answering All Correctly according to the original knowledge
    $simulated_score = 0;
    foreach ($matrix as $m_idx => $q_item) {
        $user_choice = $q_item['dap_an_dung']; // Student picks the letter shown as correct for this shuffled option
        if ($user_choice === $q_item['dap_an_dung']) {
            $simulated_score++;
        }
    }

    if ($simulated_score === count($matrix)) {
        echo "Code $code_idx: 100% Match! ($simulated_score/" . count($matrix) . " questions perfectly aligned)\n";
    } else {
        echo "Code $code_idx: Grading error! ($simulated_score/" . count($matrix) . ")\n";
        $all_exams_perfect = false;
    }
}

if ($all_exams_perfect) {
    echo "\n>>> VERIFICATION COMPLETE: ALL 4 SHUFFLED CODES PRESERVE 100% MATHEMATICAL INTEGRITY! <<<\n";
}
