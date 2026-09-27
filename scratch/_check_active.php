<?php
session_start();
$_SESSION["user_id"] = 1;
$_SESSION["username"] = "admin";
$_SESSION["role"] = "admin";
$_SESSION["ho_ten"] = "Lê Nhựt Khánh";

$_SERVER["PHP_SELF"] = "/tkb/admin/quanly_ai_models.php";
$_SERVER["SCRIPT_NAME"] = "/tkb/admin/quanly_ai_models.php";

ob_start();
include __DIR__ . "/admin/quanly_ai_models.php";
$html = ob_get_clean();

preg_match_all("/<li class=\"adm-nav-item\s*([^\"*]*)\"[^>]*>[\s\S]*?<span>(.*?)<\/span>/i", $html, $matches);
for ($i = 0; $i < count($matches[0]); $i++) {
    if (strpos($matches[1][$i], "active") !== false) {
        echo "ACTIVE ITEM: " . $matches[2][$i] . " (class: " . $matches[1][$i] . ")\n";
    }
}
unlink(__FILE__);
