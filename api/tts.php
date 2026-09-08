<?php
/**
 * Viet Han Ca Mau AI Workspace - High Quality Text-to-Speech (TTS) API
 * Supports multi-voice neural synthesis, caching, and fallback.
 */
ob_start();
require_once __DIR__ . '/../config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$cacheDir = __DIR__ . '/../uploads/tts_cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}

// 1. Extract and sanitize input parameters
$text = '';
$voiceId = 'vi_thuytien';
$pitch = 1.0;
$rate = 1.0;
$volume = 1.0;
$returnJson = false;
$isDownload = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $json = json_decode($rawInput, true);
    if (is_array($json)) {
        $text = trim($json['text'] ?? '');
        $voiceId = trim($json['voice'] ?? ($json['voice_id'] ?? 'vi_thuytien'));
        $pitch = floatval($json['pitch'] ?? 1.0);
        $rate = floatval($json['rate'] ?? ($json['speed'] ?? 1.0));
        $volume = floatval($json['volume'] ?? 1.0);
        $returnJson = !empty($json['json']);
        $isDownload = !empty($json['download']);
    } else {
        $text = trim($_POST['text'] ?? '');
        $voiceId = trim($_POST['voice'] ?? ($_POST['voice_id'] ?? 'vi_thuytien'));
        $pitch = floatval($_POST['pitch'] ?? 1.0);
        $rate = floatval($_POST['rate'] ?? ($_POST['speed'] ?? 1.0));
        $volume = floatval($_POST['volume'] ?? 1.0);
        $returnJson = isset($_POST['json']);
        $isDownload = isset($_POST['download']);
    }
} else {
    $text = trim($_GET['text'] ?? '');
    $voiceId = trim($_GET['voice'] ?? ($_GET['voice_id'] ?? 'vi_thuytien'));
    $pitch = floatval($_GET['pitch'] ?? 1.0);
    $rate = floatval($_GET['rate'] ?? ($_GET['speed'] ?? 1.0));
    $volume = floatval($_GET['volume'] ?? 1.0);
    $returnJson = isset($_GET['json']);
    $isDownload = isset($_GET['download']);
}

if (!$text) {
    $text = 'Xin chào, đây là giọng đọc thử nghiệm của hệ thống AI.';
}

// Limit text length to 4000 characters
$text = mb_substr($text, 0, 4000, 'UTF-8');
$pitch = max(0.2, min(2.0, $pitch));
$rate = max(0.4, min(2.5, $rate));

// 2. Generate cache filename based on hash of parameters
$cacheKey = md5("{$text}_{$voiceId}_{$pitch}_{$rate}");
$cacheFile = $cacheDir . '/' . $cacheKey . '.mp3';
$cacheRelUrl = '/tkb/uploads/tts_cache/' . $cacheKey . '.mp3';

// 3. Check if cached audio already exists and is valid
$generated = false;
if (file_exists($cacheFile) && filesize($cacheFile) > 500) {
    $generated = true;
} else {
    // Generate audio using python edge-tts engine
    $possiblePythonPaths = [
        'C:\\Users\\Lê Nhựt Khánh\\AppData\\Local\\Programs\\Python\\Python312\\python.exe',
        'C:\\Python312\\python.exe',
        'C:\\Program Files\\Python312\\python.exe',
        'python.exe',
        'python'
    ];
    $pythonCmd = 'python';
    foreach ($possiblePythonPaths as $p) {
        if (file_exists($p)) {
            $pythonCmd = '"' . $p . '"';
            break;
        }
    }
    $scriptPath = __DIR__ . '/tts_engine.py';
    
    $cmd = sprintf(
        '%s %s --text %s --voice %s --pitch %s --rate %s --output %s 2>&1',
        $pythonCmd,
        escapeshellarg($scriptPath),
        escapeshellarg($text),
        escapeshellarg($voiceId),
        escapeshellarg((string)$pitch),
        escapeshellarg((string)$rate),
        escapeshellarg($cacheFile)
    );

    $output = @shell_exec($cmd);
    
    if (file_exists($cacheFile) && filesize($cacheFile) > 500) {
        $generated = true;
    } else {
        // Fallback: Google Translate TTS or ResponsiveVoice
        $lang = (strpos($voiceId, 'en') === 0) ? 'en' : ((strpos($voiceId, 'ja') === 0) ? 'ja' : 'vi');
        $fallbackUrl = 'https://translate.google.com/translate_tts?ie=UTF-8&q=' . urlencode(mb_substr($text, 0, 500, 'UTF-8')) . '&tl=' . urlencode($lang) . '&client=gtx';
        
        $ch = curl_init($fallbackUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15
        ]);
        $fbData = curl_exec($ch);
        $fbCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($fbCode === 200 && strlen($fbData) > 500) {
            @file_put_contents($cacheFile, $fbData);
            $generated = true;
        }
    }
}

if (!$generated || !file_exists($cacheFile)) {
    if ($returnJson) {
        if (ob_get_length()) @ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Không thể tạo âm thanh giọng nói.',
            'voice_id' => $voiceId
        ]);
        exit();
    }
    http_response_code(500);
    echo "Lỗi xử lý âm thanh Text-to-Speech!";
    exit();
}

// 4. Return Output (JSON metadata or Direct Audio Stream)
if ($returnJson) {
    if (ob_get_length()) @ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'audio_url' => $cacheRelUrl,
        'voice_id' => $voiceId,
        'pitch' => $pitch,
        'rate' => $rate,
        'filesize' => filesize($cacheFile),
        'timestamp' => time()
    ]);
    exit();
}

// Direct Audio Stream Response
if (ob_get_length()) @ob_clean();
header('Content-Type: audio/mpeg');
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=86400');
if ($isDownload) {
    $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $voiceId) . '_' . time() . '.mp3';
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
}
header('Content-Length: ' . filesize($cacheFile));
readfile($cacheFile);
exit();
