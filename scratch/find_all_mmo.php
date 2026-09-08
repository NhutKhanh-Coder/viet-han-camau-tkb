<?php
$baseDir = realpath(__DIR__ . '/..');

$patterns = [
    'quanly_ai_accounts',
    'mmo_events',
    'mmo_event_claims',
    'mmo_contest_submissions',
    'mmo_coupons',
    'ai_accounts_store',
    'ai_account_orders',
    'claim_mmo_event',
    'Trung Tâm MMO',
    'trung tâm mmo',
    'shop_ai',
    'teacher/mmo.php',
    'mmo.php'
];

$scanDirs = ['admin', 'teacher', 'student', 'includes', 'api', ''];

$matches = [];

foreach ($scanDirs as $sd) {
    $dirPath = $sd === '' ? $baseDir : $baseDir . DIRECTORY_SEPARATOR . $sd;
    if (!is_dir($dirPath)) continue;
    $files = scandir($dirPath);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $fp = $dirPath . DIRECTORY_SEPARATOR . $f;
        if (is_file($fp) && preg_match('/\.(php|js|html|sql)$/i', $f)) {
            $content = file_get_contents($fp);
            if ($content === false) continue;
            foreach ($patterns as $p) {
                if (stripos($content, $p) !== false) {
                    $rel = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $fp);
                    $matches[$rel][] = $p;
                }
            }
        }
    }
}

foreach ($matches as $rel => $pats) {
    echo "$rel => " . implode(', ', array_unique($pats)) . "\n";
}
