<?php
$file = __DIR__ . '/api/db_sync_data.sql';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $content = preg_replace("/'banner_[^']+'/", 'NULL', $content);
    $content = preg_replace("/'video_[^']+'/", 'NULL', $content);
    $content = preg_replace("/'avatar_[^']+'/", 'NULL', $content);
    file_put_contents($file, $content);
    echo "OK";
}
