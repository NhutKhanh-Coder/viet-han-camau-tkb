<?php
require_once '../config.php';
require_once '../includes/db.php';
$db = getDB();
$res = $db->query("SELECT ma_nguon FROM student_code_storage WHERE id=54 LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $mn = $row['ma_nguon'];
    echo "Length: " . strlen($mn) . "\n";
    $b64 = base64_decode($mn);
    if ($b64) {
        echo "Is JSON: " . (strpos(trim($b64), '{') === 0 ? "YES\n" : "NO\n");
        $arr = json_decode($b64, true);
        if ($arr) {
            echo "Keys: " . implode(', ', array_keys($arr)) . "\n";
            foreach($arr as $k => $v) {
                echo "$k length: " . strlen($v) . ", preview: " . substr($v, 0, 50) . "\n";
            }
        } else {
            echo "JSON Decode Failed: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "Base64 Decode Failed\n";
    }
}
