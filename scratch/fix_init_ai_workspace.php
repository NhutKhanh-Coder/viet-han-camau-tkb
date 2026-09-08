<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$initFunc = '
        function initAIWorkspace() {
            if (typeof renderSessions === \'function\') renderSessions();
            if (typeof renderModelsList === \'function\') renderModelsList(ALL_MODELS);
            if (typeof updateTriggerButtons === \'function\') updateTriggerButtons();
            if (typeof renderVoiceCards === \'function\') renderVoiceCards();
            if (typeof renderTtsHistory === \'function\') renderTtsHistory();
        }
';

$aiPhp = str_replace('// Initialize on page load', $initFunc . "\n        // Initialize on page load", $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully added initAIWorkspace definition in student/ai.php!\n";
