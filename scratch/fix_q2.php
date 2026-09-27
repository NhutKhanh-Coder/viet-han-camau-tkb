<?php
ini_set("display_errors", 1);
require_once __DIR__ . "/config.php";
$db = getDB();
$db->query("UPDATE quiz_questions SET dap_an_dung = 'C' WHERE id = 1432 AND quiz_id = 69");
echo "Updated Q2 to C. Affected: " . $db->affected_rows . "\n";
