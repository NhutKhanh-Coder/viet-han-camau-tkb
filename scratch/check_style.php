<?php
$conn = ftp_connect('ftpupload.net', 21, 15);
if (!ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    die("Login failed\n");
}
ftp_pasv($conn, true);
@ftp_get($conn, 'scratch/remote_style.css', '/htdocs/tkb/assets/style.css', FTP_BINARY);
ftp_close($conn);
if (file_exists('scratch/remote_style.css')) {
    echo "Remote style.css size: " . filesize('scratch/remote_style.css') . " bytes\n";
    echo "Local style.css size: " . filesize('assets/style.css') . " bytes\n";
}
