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

$files_to_sync = [
    'student/ai_advisor.php',
    'includes/student_nav.php',
    'assets/student_dark_mode.css',
    'assets/img/curtain_scene_light.png',
    'assets/img/curtain_scene_dark.png'
];

$remote_bases = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb'
];

foreach ($remote_bases as $remote_base) {
    echo "\n=== Syncing to $remote_base ===\n";
    foreach ($files_to_sync as $rel) {
        $local = $local_base . '/' . $rel;
        $remote = $remote_base . '/' . $rel;
        if (!file_exists($local)) {
            echo "SKIP (Not found): $local\n";
            continue;
        }
        
        // Ensure remote directory exists
        $dir = dirname($remote);
        @ftp_mkdir($conn_id, $dir);

        if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
            echo "SUCCESS: $rel -> $remote\n";
        } else {
            echo "FAILED: $rel -> $remote\n";
        }
    }
}

ftp_close($conn_id);
echo "\nFinished sync script.\n";
