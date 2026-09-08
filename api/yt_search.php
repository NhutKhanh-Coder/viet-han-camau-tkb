<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');
if (empty($query)) {
    echo json_encode([]);
    exit;
}

$url = "https://www.youtube.com/results?search_query=" . urlencode($query);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$html = curl_exec($ch);
curl_close($ch);

$results = [];
$pos = strpos($html, 'ytInitialData = ');
if ($pos !== false) {
    $start = $pos + strlen('ytInitialData = ');
    $end = strpos($html, ';</script>', $start);
    if ($end !== false) {
        $jsonStr = substr($html, $start, $end - $start);
        $data = json_decode($jsonStr, true);
        $sections = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];

        foreach ($sections as $sec) {
            $items = $sec['itemSectionRenderer']['contents'] ?? [];
            foreach ($items as $item) {
                if (isset($item['videoRenderer'])) {
                    $v = $item['videoRenderer'];
                    $videoId = $v['videoId'] ?? '';
                    $title = $v['title']['runs'][0]['text'] ?? '';
                    $channel = $v['ownerText']['runs'][0]['text'] ?? 'YouTube Channel';
                    $views = $v['viewCountText']['simpleText'] ?? ($v['shortViewCountText']['simpleText'] ?? '');
                    $duration = $v['lengthText']['simpleText'] ?? '';
                    $thumbnail = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";

                    if ($videoId && $title) {
                        $results[] = [
                            'id' => $videoId,
                            'title' => $title,
                            'channel' => $channel,
                            'duration' => $duration,
                            'views' => $views,
                            'thumbnail' => $thumbnail
                        ];
                    }
                }
                if (count($results) >= 15) break 2;
            }
        }
    }
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);
