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
    'config.php',
    'login.php',
    'api/login.php',
    'register.php',
    'clear_banners.php',
    'api/upload_video.php',
    'api/get_student_grades.php',
    'api/couple_api.php',
    'student/dashboard.php',
    'student/quiz.php',
    'includes/admin_nav.php',
    'includes/teacher_nav.php',
    'admin/dashboard.php',
    'admin/students.php',
    'admin/diem.php',
    'admin/baitap.php',
    'admin/tailieu.php',
    'admin/giangvien.php',
    'admin/lop.php',
    'admin/monhoc.php',
    'admin/phanquyen.php',
    'admin/nhatky.php',
    'admin/backup.php',
    'admin/caidat.php',
    'admin/profile.php',
    'teacher/baitap.php',
    'teacher/tailieu.php',
    'teacher/quiz.php',
    'teacher/doan.php',
    'teacher/quanly_thuchanh.php',
    'teacher/cham_code.php',
    'teacher/baocao_tien_do.php',
    'teacher/diemdanh.php',
    'teacher/quanly_sinhvien.php',
    'assets/style.css',
    'assets/img/lofi_night_sky.jpg',
    'assets/img/phuong_anh_avatar.png',
    'assets/img/phuong_anh_1.png',
    'assets/img/phuong_anh_2.png',
    'assets/img/phuong_anh_3.png',
    'assets/img/phuong_anh_4.png',
    'assets/img/anime_video_thumb.png',
    'assets/img/sakura_corner.png'
];

$remote_bases = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb'
];

foreach ($remote_bases as $remote_base) {
    echo "=== Syncing to $remote_base ===\n";
    foreach ($files as $rel) {
        $local = $local_base . '/' . $rel;
        $remote = $remote_base . '/' . $rel;
        if (!file_exists($local)) {
            echo "SKIP (Not found): $local\n";
            continue;
        }
        if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
            echo "SUCCESS: $rel -> $remote\n";
        } else {
            echo "FAILED: $rel -> $remote\n";
        }
    }
}
ftp_close($conn_id);
echo "Sync Admin feature to InfinityFree finished 100%!\n";
