<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 30);
if (!$conn || !@ftp_login($conn, $ftp_user, $ftp_pass)) {
    die("FTP Connection failed\n");
}
@ftp_pasv($conn, true);
echo "FTP Connected successfully!\n";

$candidates = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb',
    '/htdocs',
    '/viethan.free.nf/htdocs'
];

$local_teacher_quiz = 'c:/xampp/htdocs/tkb/teacher/quiz.php';
$local_populator    = 'c:/xampp/htdocs/tkb/scratch/populate_quiz57_40q.php';

// 1. Upload teacher/quiz.php
foreach ($candidates as $cand) {
    $remote_teacher = $cand . '/teacher/quiz.php';
    $chk = @ftp_size($conn, $remote_teacher);
    if ($chk !== -1) {
        echo "Found: $remote_teacher (size: $chk bytes)\n";
        if (@ftp_put($conn, $remote_teacher, $local_teacher_quiz, FTP_BINARY)) {
            echo ">>> SUCCESS: Uploaded teacher/quiz.php to: $remote_teacher (" . filesize($local_teacher_quiz) . " bytes)\n";
        } else {
            echo ">>> FAILED to upload to: $remote_teacher\n";
        }
    }
}

// 2. Upload populator runner to student/
$uploaded_runner = false;
foreach ($candidates as $cand) {
    $remote_runner = $cand . '/student/populate_quiz57_40q.php';
    if (@ftp_put($conn, $remote_runner, $local_populator, FTP_BINARY)) {
        echo ">>> Uploaded populator runner to: $remote_runner\n";
        $uploaded_runner = true;
    }
}

ftp_close($conn);

// 3. Execute the populator runner via HTTP
echo "\n--- Executing runner via HTTP ---\n";
$url = 'https://viethan.free.nf/tkb/student/populate_quiz57_40q.php';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT        => 30
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $code\n";
echo "Response:\n" . $res . "\n";

// 4. Remove temporary runner from FTP
$conn = @ftp_connect($ftp_server, 21, 30);
if ($conn && @ftp_login($conn, $ftp_user, $ftp_pass)) {
    @ftp_pasv($conn, true);
    foreach ($candidates as $cand) {
        @ftp_delete($conn, $cand . '/student/populate_quiz57_40q.php');
        @ftp_delete($conn, $cand . '/student/cleanup_0_score.php');
    }
    ftp_close($conn);
    echo "\nCleaned up remote runner script successfully.\n";
}

echo "Deployment and Database update finished!\n";
