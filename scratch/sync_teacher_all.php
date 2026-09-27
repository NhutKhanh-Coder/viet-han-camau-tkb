<?php
set_time_limit(300);

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
        sleep(1);
    }
    return null;
}

$conn = getFtpConn($ftp_server, $ftp_user, $ftp_pass);
if (!$conn) {
    die("FATAL: Could not connect to FTP!\n");
}

$roots = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];

// Ensure remote directories exist
foreach ($roots as $root) {
    @ftp_mkdir($conn, $root . '/ai_engine');
    @ftp_mkdir($conn, $root . '/teacher');
}

// 1. Files in teacher/
$teacher_files = scandir('c:/xampp/htdocs/tkb/teacher');
$success = 0;
$failed = 0;

foreach ($teacher_files as $f) {
    if ($f === '.' || $f === '..' || !str_ends_with($f, '.php')) continue;
    $local = 'c:/xampp/htdocs/tkb/teacher/' . $f;
    foreach ($roots as $root) {
        $remote = $root . '/teacher/' . $f;
        if (@ftp_put($conn, $remote, $local, FTP_BINARY)) {
            echo "OK: $remote\n";
            $success++;
        } else {
            echo "FAIL: $remote\n";
            $failed++;
        }
    }
}

// 2. Files in ai_engine/
$ai_files = ['model_evaluation_results.json', 'predict_service.py', 'train_evaluate.py'];
foreach ($ai_files as $f) {
    $local = 'c:/xampp/htdocs/tkb/ai_engine/' . $f;
    if (!file_exists($local)) continue;
    foreach ($roots as $root) {
        $remote = $root . '/ai_engine/' . $f;
        if (@ftp_put($conn, $remote, $local, FTP_BINARY)) {
            echo "OK: $remote\n";
            $success++;
        } else {
            echo "FAIL: $remote\n";
            $failed++;
        }
    }
}

ftp_close($conn);
echo "Completed! Success: $success, Failed: $failed\n";
