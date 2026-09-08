<?php
set_time_limit(300);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 30);
if (!$conn || !@ftp_login($conn, $ftp_user, $ftp_pass)) {
    die("FTP Connection failed\n");
}
@ftp_pasv($conn, true);
echo "FTP Connected!\n";

function ftp_mkd_recursively($conn_id, $path) {
    $parts = explode('/', trim($path, '/'));
    $current = '';
    foreach ($parts as $part) {
        $current .= '/' . $part;
        @ftp_mkdir($conn_id, $current);
    }
}

// Find all locations where student/ai.php exists
$candidates = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb',
    '/htdocs',
    '/viethan.free.nf/htdocs'
];

$valid_bases = [];
foreach ($candidates as $cand) {
    $chk = @ftp_size($conn, $cand . '/student/ai.php');
    if ($chk !== -1) {
        echo "FOUND student/ai.php in: $cand (size: $chk)\n";
        $valid_bases[] = $cand;
    } else {
        echo "Not found in $cand\n";
    }
}

if (empty($valid_bases)) {
    $valid_bases = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];
}

$local_base = 'c:/xampp/htdocs/tkb';
$files_to_upload = [
    'chatbot/index.php',
    'chatbot/index.html',
    'chatbot/env.php',
    'chatbot/setup.sql',
    'chatbot/.env',
    'chatbot/.env.example',
    'chatbot/README.md',
    'chatbot/api/chat.php',
    'chatbot/api/conversations.php',
    'chatbot/api/messages.php',
    'chatbot/assets/style.css',
    'chatbot/assets/app.js',
    'student/ai.php',
    'api/workspace_files.php',
    'api/coding_agent.php',
    'api/login.php',
];

// Create chatbot/index.html as fallback / redirect to index.php
file_put_contents($local_base . '/chatbot/index.html', file_get_contents($local_base . '/chatbot/index.php'));

foreach ($valid_bases as $remote_base) {
    echo "\n=== Uploading to: $remote_base ===\n";
    foreach ($files_to_upload as $rel) {
        $local = $local_base . '/' . $rel;
        $remote = $remote_base . '/' . $rel;
        if (!file_exists($local)) continue;
        
        $dir = dirname($remote);
        ftp_mkd_recursively($conn, $dir);
        
        if (@ftp_put($conn, $remote, $local, FTP_BINARY)) {
            echo "OK: $remote\n";
        } else {
            echo "FAIL: $remote\n";
        }
    }
}

ftp_close($conn);
echo "\n=== SYNC COMPLETE ===\n";
