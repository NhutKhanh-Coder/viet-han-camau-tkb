<?php
if ($argc < 2) die("Usage: php scratch/quick_upload.php <rel_path>\n");
$rel = $argv[1];
$local = 'c:/xampp/htdocs/tkb/' . $rel;
if (!file_exists($local)) die("File not found: $local\n");

$conn = @ftp_connect('ftpupload.net', 21, 30);
if (!$conn || !@ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI')) die("FTP fail\n");
@ftp_pasv($conn, true);

foreach (['/htdocs/tkb/', '/viethan.free.nf/htdocs/tkb/'] as $prefix) {
    $remote = $prefix . $rel;
    $remote_dir = dirname($remote);
    $parts = array_filter(explode('/', str_replace('\\', '/', $remote_dir)));
    $p = '';
    foreach ($parts as $part) {
        $p .= '/' . $part;
        @ftp_mkdir($conn, $p);
    }
    if (@ftp_put($conn, $remote, $local, FTP_BINARY)) {
        echo "Uploaded to $remote\n";
    } else {
        echo "Failed $remote\n";
    }
}
@ftp_close($conn);
