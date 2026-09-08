<?php
$lines = file('c:/xampp/htdocs/tkb/student/ai.php');
foreach ($lines as $num => $line) {
    if (stripos($line, 'voice-modal-overlay') !== false || stripos($line, 'voiceModalOverlay') !== false) {
        echo "Line " . ($num + 1) . ": " . trim($line) . "\n";
    }
}
