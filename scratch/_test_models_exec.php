<?php
session_start();
$_SESSION["user_id"] = 1;
$_SESSION["username"] = "admin";
$_SESSION["role"] = "admin";
$_SESSION["ho_ten"] = "Lê Nhựt Khánh";

ob_start();
try {
    include __DIR__ . "/admin/quanly_ai_models.php";
    $output = ob_get_clean();
    echo "SUCCESS: Length " . strlen($output) . "\n";
    echo "Snippet: " . substr(strip_tags($output), 0, 200) . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
