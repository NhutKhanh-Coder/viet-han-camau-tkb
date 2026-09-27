<?php
// Load official 40 questions from populate_quiz57_40q.php
$content = file_get_contents('c:/xampp/htdocs/tkb/scratch/populate_quiz57_40q.php');
$start = strpos($content, '$questions = array (');
$end = strpos($content, ');', $start) + 2;
eval(substr($content, $start, $end - $start));
$official = $questions;

// Now let's fetch all 40 questions from remote Quiz 69
$phpCode = '<?php
ini_set("display_errors", 1);
require_once __DIR__ . "/config.php";
header("Content-Type: application/json; charset=utf-8");
$db = getDB();
$res = $db->query("SELECT id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung FROM quiz_questions WHERE quiz_id = 69 ORDER BY id ASC");
$out = [];
if ($res) {
    while ($r = $res->fetch_assoc()) $out[] = $r;
}
echo json_encode($out);
';

file_put_contents('c:/xampp/htdocs/tkb/scratch/get_q69_json.php', $phpCode);

$conn_id = @ftp_connect('ftpupload.net', 21, 30);
if ($conn_id && @ftp_login($conn_id, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    @ftp_put($conn_id, '/viethan.free.nf/htdocs/tkb/get_q69_json.php', 'c:/xampp/htdocs/tkb/scratch/get_q69_json.php', FTP_BINARY);
    @ftp_close($conn_id);
}

$url = 'https://viethan.free.nf/tkb/get_q69_json.php';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$html = curl_exec($ch);
curl_close($ch);

$cookieVal = null;
if (preg_match('/toNumbers\("([a-f0-9]+)"\),b=toNumbers\("([a-f0-9]+)"\),c=toNumbers\("([a-f0-9]+)"\)/i', $html, $m)) {
    $key = hex2bin($m[1]);
    $iv  = hex2bin($m[2]);
    $ct  = hex2bin($m[3]);
    $dec = openssl_decrypt($ct, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    $cookieVal = bin2hex($dec);
}

$remoteQuestions = [];
if ($cookieVal) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    curl_setopt($ch, CURLOPT_COOKIE, "__test=$cookieVal; path=/");
    $res = curl_exec($ch);
    curl_close($ch);
    $remoteQuestions = json_decode($res, true) ?: [];
}

$conn_id = @ftp_connect('ftpupload.net', 21, 30);
if ($conn_id && @ftp_login($conn_id, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    @ftp_delete($conn_id, '/viethan.free.nf/htdocs/tkb/get_q69_json.php');
    @ftp_close($conn_id);
}

echo "=== COMPARISON: QUIZ 69 vs OFFICIAL KEY ===\n";
$diffs = [];
for ($i = 0; $i < 40; $i++) {
    $rq = $remoteQuestions[$i] ?? null;
    $oq = $official[$i] ?? null;
    $num = $i + 1;
    if (!$rq || !$oq) {
        echo "Missing question $num\n";
        continue;
    }
    $rAns = $rq['dap_an_dung'];
    $oAns = $oq['dap_an_dung'];
    $match = ($rAns === $oAns) ? "MATCH [OK]" : "DIFF [!]";
    if ($rAns !== $oAns) {
        $diffs[] = [
            'num' => $num,
            'id' => $rq['id'],
            'q' => $rq['cau_hoi'],
            'curr' => $rAns . ' (' . $rq['dap_an_' . strtolower($rAns)] . ')',
            'official' => $oAns . ' (' . $oq['dap_an_' . strtolower($oAns)] . ')'
        ];
    }
    echo "Q$num: Current: $rAns | Official: $oAns -> $match\n";
}

echo "\nTOTAL DIFFERENCES: " . count($diffs) . "\n";
foreach ($diffs as $d) {
    echo "Câu {$d['num']} (ID {$d['id']}): {$d['q']}\n";
    echo "   Hiện tại: {$d['curr']}\n";
    echo "   Chuẩn đề: {$d['official']}\n\n";
}
