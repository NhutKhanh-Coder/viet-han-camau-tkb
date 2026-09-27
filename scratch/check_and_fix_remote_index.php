<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 20);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("FTP connection failed.\n");
}
ftp_pasv($conn_id, true);

$targets = [
    '/viethan.free.nf/htdocs/tkb/index.html',
    '/htdocs/tkb/index.html'
];

foreach ($targets as $target) {
    // Download sample first
    $h = fopen('php://temp', 'r+');
    if (@ftp_fget($conn_id, $h, $target, FTP_BINARY)) {
        rewind($h);
        $content = stream_get_contents($h);
        echo "Found: $target (Size: " . strlen($content) . " bytes)\n";
        echo "Preview:\n" . substr($content, 0, 200) . "\n---\n";
        
        // Backup before deleting
        file_put_contents(__DIR__ . '/backup_remote_index.html', $content);
        
        // Delete index.html so Apache serves index.php
        if (ftp_delete($conn_id, $target)) {
            echo "SUCCESSFULLY DELETED: $target\n";
        } else {
            echo "FAILED TO DELETE: $target\n";
        }
    } else {
        echo "Not found: $target\n";
    }
}

ftp_close($conn_id);
echo "Done checking and fixing remote index.\n";
