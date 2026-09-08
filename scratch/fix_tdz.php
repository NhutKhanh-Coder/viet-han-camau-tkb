<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// Replace top variable
$aiPhp = str_replace('let currentVoice = ALL_VOICES[0];', 'let currentVoice = null;', $aiPhp);

// Initialize right after ALL_VOICES
$oldAllVoicesEnd = "            { id: 'meme_anime', name: 'Anime Chan', lang: 'ja', gender: 'female', category: ['all', 'meme', 'character'], sub: 'Kawaii High-Pitch Anime', pitch: 1.6, rate: 1.2, isTrending: false, tags: ['vip'], gradient: 'linear-gradient(135deg, #f472b6, #c084fc)', icon: 'fa-heart' }\n        ];";

$newAllVoicesEnd = $oldAllVoicesEnd . "\n        currentVoice = ALL_VOICES[0];";

$aiPhp = str_replace($oldAllVoicesEnd, $newAllVoicesEnd, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Fixed TDZ bug for ALL_VOICES!\n";
