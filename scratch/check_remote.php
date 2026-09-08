<?php
$conn = ftp_connect('ftpupload.net', 21, 15);
if (!ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    die("Login failed\n");
}
ftp_pasv($conn, true);
@ftp_get($conn, 'scratch/remote_dashboard.php', '/htdocs/tkb/admin/dashboard.php', FTP_BINARY);
ftp_close($conn);
if (file_exists('scratch/remote_dashboard.php')) {
    echo "Downloaded remote dashboard: " . filesize('scratch/remote_dashboard.php') . " bytes\n";
    echo "Local dashboard: " . filesize('admin/dashboard.php') . " bytes\n";
} else {
    echo "Failed to download\n";
}
