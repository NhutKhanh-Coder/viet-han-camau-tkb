<?php
set_time_limit(300);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

echo "Connecting to $ftp_server...\n";
$conn_id = @ftp_connect($ftp_server, 21, 30);

if (!$conn_id) {
    die("ERROR: Could not connect to FTP server $ftp_server\n");
}

if (!@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP login failed for user $ftp_user\n");
}

@ftp_pasv($conn_id, true);
echo "FTP connected successfully!\n";

function ftp_mkd_recursively($conn_id, $path) {
    $parts = explode('/', trim($path, '/'));
    $current = '';
    foreach ($parts as $part) {
        $current .= '/' . $part;
        @ftp_mkdir($conn_id, $current);
    }
}

$local_base = 'c:/xampp/htdocs/tkb';

$files_to_upload = [
    'teacher/baocao_tien_do.php',
    'teacher/thongbao.php',
    'teacher/dashboard.php',
    'teacher/quanly_nhac.php',
    'teacher/quanlylop.php',
    'includes/teacher_nav.php',
    'student/dashboard.php',
    'config.php',
    'assets/teacher_portal.css',
];

$remote_bases = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb'
];

echo "Total target files to upload: " . count($files_to_upload) . "\n";

foreach ($remote_bases as $remote_base) {
    echo "=== Uploading TARGET FILES to base: $remote_base ===\n";
    $count = 0;
    foreach ($files_to_upload as $rel_path) {
        $count++;
        $local_file = $local_base . '/' . $rel_path;
        $remote_file = $remote_base . '/' . $rel_path;

        $dir = dirname($remote_file);
        ftp_mkd_recursively($conn_id, $dir);
        
        if (@ftp_put($conn_id, $remote_file, $local_file, FTP_BINARY)) {
            echo "[$count/" . count($files_to_upload) . "] SUCCESS: $rel_path\n";
        } else {
            echo "[$count/" . count($files_to_upload) . "] FAILED: $rel_path\n";
        }
    }
}

ftp_close($conn_id);
echo "FTP Target Files Sync Completed 100%!\n";
