<?php
$html = file_get_contents('c:/xampp/htdocs/tkb/student/ai.php');
if (preg_match('/<script>(.*?)<\/script>/s', $html, $matches)) {
    file_put_contents('c:/xampp/htdocs/tkb/scratch/check.js', $matches[1]);
    echo "Extracted script to scratch/check.js";
} else {
    echo "Could not find <script> tag.";
}
