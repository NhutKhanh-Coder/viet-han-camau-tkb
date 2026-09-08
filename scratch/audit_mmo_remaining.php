<?php
$rootDir = realpath(__DIR__ . '/..');
$skipDirs = ['.git', 'scratch', 'temp_runs', 'uploads', 'assets', 'vendor', 'node_modules'];

function scanDirRecursive($dir, &$results, $skipDirs, $rootDir) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $dir . '/' . $item;
        if (is_dir($fullPath)) {
            if (in_array($item, $skipDirs)) continue;
            scanDirRecursive($fullPath, $results, $skipDirs, $rootDir);
        } elseif (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($fullPath);
            $relPath = str_replace($rootDir . '/', '', str_replace('\\', '/', $fullPath));
            
            // Check for MMO references
            $patterns = [
                'mmo_events' => '/\bmmo_events\b/i',
                'quanly_ai_accounts' => '/quanly_ai_accounts/i',
                'shop_ai.php' => '/shop_ai\.php/i',
                'events.php' => '/student\/events\.php/i',
                'ai_accounts_store' => '/ai_accounts_store/i',
                'ai_account_orders' => '/ai_account_orders/i',
                'mmo_coupons' => '/mmo_coupons/i',
                'mmo_event_claims' => '/mmo_event_claims/i',
                'Chợ MMO' => '/Chợ MMO/iu',
            ];
            
            foreach ($patterns as $name => $pattern) {
                if (preg_match($pattern, $content)) {
                    $results[] = [
                        'file' => $relPath,
                        'pattern' => $name
                    ];
                }
            }
        }
    }
}

$results = [];
scanDirRecursive($rootDir, $results, $skipDirs, $rootDir);

echo "Total matching references found: " . count($results) . "\n";
foreach ($results as $r) {
    echo "- File: {$r['file']} (matched: {$r['pattern']})\n";
}
