<?php
$c = file_get_contents('c:/xampp/htdocs/tkb/student/ai.php');
echo 'File length: ' . strlen($c) . "\n";
echo 'Princess count: ' . substr_count($c, 'Princess') . "\n";
echo 'voiceModalOverlay count: ' . substr_count($c, 'voiceModalOverlay') . "\n";
echo 'voice-modal-box count: ' . substr_count($c, 'voice-modal-box') . "\n";
echo 'voice-modal-overlay in CSS: ' . substr_count($c, '.voice-modal-overlay') . "\n";
