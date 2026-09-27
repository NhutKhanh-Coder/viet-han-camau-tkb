<?php
ini_set("display_errors", 1);
require_once __DIR__ . "/config.php";
header("Content-Type: text/plain; charset=utf-8");
$db = getDB();

$targetQuizId = 69;
$res = $db->query("SELECT id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung FROM quiz_questions WHERE quiz_id = $targetQuizId ORDER BY id ASC");
if ($res) {
    echo "Total: " . $res->num_rows . "\n";
    $i = 1;
    while ($r = $res->fetch_assoc()) {
        echo "Q$i (ID {$r["id"]}): {$r["cau_hoi"]}\n";
        echo "   [{$r["dap_an_dung"]}] A: {$r["dap_an_a"]} | B: {$r["dap_an_b"]} | C: {$r["dap_an_c"]} | D: {$r["dap_an_d"]}\n";
        $i++;
    }
} else {
    echo "Error: " . $db->error . "\n";
}
