<?php
// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = 'sql308.infinityfree.com';
$username = 'if0_41796593';
$password = 'T5v3vJeuvOxCI';
$dbname = 'if0_41796593_truong_caodang';

try {
    $pdo = new PDO("mysql:host=sql308.infinityfree.com;dbname=if0_41796593_truong_caodang;charset=utf8mb4", 'if0_41796593', 'T5v3vJeuvOxCI');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
            $f_h = "sql308.infinityfree.com";
            $f_u = "if0_41796593";
            $f_p = "T5v3vJeuvOxCI";
            $f_d = "if0_41796593_truong_caodang";
            $pdo = new PDO("mysql:host=$f_h;dbname=$f_d;charset=utf8mb4", $f_u, $f_p);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn = @new mysqli($f_h, $f_u, $f_p, $f_d);
        } catch(Throwable $ex){}
}

// Global helper functions

// Format price to VND, e.g. 580000 -> 580.000 đ
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' đ';
}

// Safely escape outputs
function sanitize($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit();
}

// Automatically extract direct image link from Bing/Google Image search URLs
function extractImageUrl($url) {
    $url = trim($url);
    if (empty($url)) return $url;
    
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        $parsed = parse_url($url);
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParts);
            
            // Bing Image Search detail page query param: mediaurl
            if (isset($queryParts['mediaurl'])) {
                return trim($queryParts['mediaurl']);
            }
            
            // Google Image Search detail page query param: imgurl
            if (isset($queryParts['imgurl'])) {
                return trim($queryParts['imgurl']);
            }
        }
    }
    return $url;
}
?>
