<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("FTP connection failed\n");
}
@ftp_pasv($conn_id, true);

$local_base = 'c:/xampp/htdocs/tkb';

// Check / create remote img directories
$remotes = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];

foreach ($remotes as $rem) {
    @ftp_mkdir($conn_id, $rem . '/assets');
    @ftp_mkdir($conn_id, $rem . '/assets/img');
    
    // Upload image
    $loc_img = $local_base . '/assets/img/admin_hero_laptop.png';
    $rem_img = $rem . '/assets/img/admin_hero_laptop.png';
    if (file_exists($loc_img)) {
        if (@ftp_put($conn_id, $rem_img, $loc_img, FTP_BINARY)) {
            echo "Uploaded image: $rem_img\n";
        } else {
            echo "Failed upload image: $rem_img\n";
        }
    }
    
    // Upload dashboard.php
    $loc_dash = $local_base . '/admin/dashboard.php';
    $rem_dash = $rem . '/admin/dashboard.php';
    if (file_exists($loc_dash)) {
        if (@ftp_put($conn_id, $rem_dash, $loc_dash, FTP_BINARY)) {
            echo "Uploaded dashboard: $rem_dash\n";
        } else {
            echo "Failed upload dashboard: $rem_dash\n";
        }
    }
}

ftp_close($conn_id);
echo "Synced 3D Cyber Laptop image and dashboard to FTP 100%!\n";
