<?php
set_time_limit(300);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

echo "Connecting to FTP $ftp_server...\n";
$conn_id = @ftp_connect($ftp_server, 21, 60);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP connection/login failed!\n");
}
@ftp_pasv($conn_id, true);

$local_base = 'c:/xampp/htdocs/tkb';
$files = [
    'assets/ai/galaxy_hero.jpg',
    'assets/ai/ai_robot.jpg',
    'assets/ai/rocket_launch.jpg',
    'admin/ai_studio.php',
    'includes/admin_cosmic_bot.php',
];

$remote_bases = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb'
];

foreach ($remote_bases as $remote_base) {
    echo "=== Syncing to $remote_base ===\n";
    // Ensure assets/ai folder exists
    @ftp_mkdir($conn_id, $remote_base . '/assets');
    @ftp_mkdir($conn_id, $remote_base . '/assets/ai');

    foreach ($files as $rel) {
        $local = $local_base . '/' . $rel;
        $remote = $remote_base . '/' . $rel;
        if (!file_exists($local)) {
            echo "SKIP (Not found): $local\n";
            continue;
        }
        echo "Uploading $rel (" . round(filesize($local)/1024) . " KB)... ";
        if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
            echo "OK\n";
        } else {
            echo "FAIL: $rel -> $remote\n";
        }
    }
}
ftp_close($conn_id);
echo "\n★ COSMIC ASSETS & CODE SYNC COMPLETE!\n";
