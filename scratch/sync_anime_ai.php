<?php
set_time_limit(180);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

echo "Connecting to FTP $ftp_server...\n";
$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP connection/login failed!\n");
}
@ftp_pasv($conn_id, true);

$local_base = 'c:/xampp/htdocs/tkb';
$files = [
    'assets/ai/vutru_ani_grok_idle.webp',
    'assets/ai/vutru_ani_grok_happy.webp',
    'assets/ai/vutru_ani_grok_idle.png',
    'assets/ai/vutru_ani_grok_happy.png',
    'assets/ai/vutru_ani_grok_circle.webp',
    'assets/ai/vutru_ani_grok_circle.png',
    'assets/ai/vutru_hikari_5d_idle.webp',
    'assets/ai/vutru_hikari_5d_happy.webp',
    'assets/ai/vutru_hikari_5d_idle.png',
    'assets/ai/vutru_hikari_5d_happy.png',
    'api/admin_ai_api.php',
    'admin/ai_studio.php'
];

$remote_bases = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb'
];

function ftp_ensure_dir($conn_id, $remote_dir) {
    $parts = explode('/', trim($remote_dir, '/'));
    $path = '';
    foreach ($parts as $p) {
        $path .= '/' . $p;
        @ftp_mkdir($conn_id, $path);
    }
}

foreach ($remote_bases as $remote_base) {
    echo "\n=== Syncing Anime Assistant to $remote_base ===\n";
    foreach ($files as $rel) {
        $local = $local_base . '/' . $rel;
        $remote = $remote_base . '/' . $rel;
        if (!file_exists($local)) {
            echo "SKIP (Not found): $local\n";
            continue;
        }
        $remote_dir = dirname($remote);
        ftp_ensure_dir($conn_id, $remote_dir);

        if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
            echo "✔ SUCCESS: $rel -> $remote (" . round(filesize($local)/1024, 1) . " KB)\n";
        } else {
            echo "✖ FAILED: $rel -> $remote\n";
        }
    }
}

@ftp_close($conn_id);
echo "\n★ ANIME AI ASSISTANT DEPLOYMENT COMPLETED! ★\n";
