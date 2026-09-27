<?php
set_time_limit(600);

$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

function getFtpConn($server, $user, $pass) {
    for ($i = 0; $i < 3; $i++) {
        $conn = @ftp_connect($server, 21, 30);
        if ($conn && @ftp_login($conn, $user, $pass)) {
            @ftp_pasv($conn, true);
            return $conn;
        }
        if ($conn) @ftp_close($conn);
        sleep(2);
    }
    return null;
}

$local_base = 'c:/xampp/htdocs/tkb';

$files_to_sync = [
    'includes/admin_nav.php',
    'includes/ai_models_list.php',
    'admin/quanly_ai_models.php',
    'admin/ai_studio.php',
    'api/admin_ai_api.php',
    'api/admin_chat_api.php',
    'assets/sakura_fall.js',
    'admin/dashboard.php',
    'admin/diem.php',
    'admin/giangvien.php',
    'admin/lop.php',
    'admin/monhoc.php',
    'admin/nhatky.php',
    'admin/phanquyen.php',
    'admin/profile.php',
    'admin/students.php',
    'admin/tailieu.php',
    'admin/youtube.php',
    'teacher/baocao_tien_do.php'
];

$roots = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];

$conn = getFtpConn($ftp_server, $ftp_user, $ftp_pass);
if (!$conn) {
    die("FATAL: Could not connect to FTP!\n");
}

$success = 0;
$failed = 0;

foreach ($files_to_sync as $rel) {
    $local_path = $local_base . '/' . $rel;
    if (!file_exists($local_path)) {
        echo "NOT FOUND: $rel\n";
        continue;
    }

    foreach ($roots as $root) {
        $remote_path = $root . '/' . $rel;
        
        // Ensure remote parent dir exists
        $dir = dirname($remote_path);
        
        $uploaded = false;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            // Check if connection alive
            if (!@ftp_nlist($conn, '.')) {
                @ftp_close($conn);
                $conn = getFtpConn($ftp_server, $ftp_user, $ftp_pass);
                if (!$conn) continue;
            }

            if (@ftp_put($conn, $remote_path, $local_path, FTP_BINARY)) {
                $uploaded = true;
                break;
            } else {
                // Try to reconnect
                @ftp_close($conn);
                $conn = getFtpConn($ftp_server, $ftp_user, $ftp_pass);
                if (!$conn) break;
            }
        }

        if ($uploaded) {
            echo "SUCCESS: $remote_path\n";
            $success++;
        } else {
            echo "FAILED after 3 attempts: $remote_path\n";
            $failed++;
        }
    }
}

if ($conn) @ftp_close($conn);
echo "\n==== FINISHED ====\nSuccess: $success, Failed: $failed\n";
