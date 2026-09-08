<?php
$html = file_get_contents(__DIR__ . '/dashboard_rendered.html');

// Check line numbers where .adm-dashboard-grid and .adm-topbar appear
$lines = explode("\n", $html);
foreach ($lines as $num => $line) {
    if (strpos($line, 'adm-topbar') !== false || strpos($line, 'adm-main-wrapper') !== false || strpos($line, 'main-content') !== false || strpos($line, 'adm-dashboard-grid') !== false || strpos($line, 'adm-dash-main-col') !== false) {
        echo ($num + 1) . ": " . trim($line) . "\n";
    }
}
