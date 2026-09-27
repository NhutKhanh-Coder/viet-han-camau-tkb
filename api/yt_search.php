<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');
if (empty($query)) {
    echo json_encode([]);
    exit;
}

$url = "https://www.youtube.com/results?search_query=" . urlencode($query);
$html = false;

// 1. Try cURL first (Reliable on InfinityFree / Linux hosting)
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $html = curl_exec($ch);
    curl_close($ch);
}

// 2. Fallback to file_get_contents
if (!$html) {
    $opts = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\r\nAccept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7\r\n",
            "timeout" => 6
        ],
        "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
    ];
    $html = @file_get_contents($url, false, stream_context_create($opts));
}

$results = [];
if ($html) {
    $marker = 'ytInitialData';
    $pos = strpos($html, $marker);
    if ($pos !== false) {
        $bracePos = strpos($html, '{', $pos);
        if ($bracePos !== false) {
            $scriptEnd = strpos($html, '</script>', $bracePos);
            if ($scriptEnd !== false) {
                $jsonStr = substr($html, $bracePos, $scriptEnd - $bracePos);
                $jsonStr = rtrim(trim($jsonStr), ';');
                $data = json_decode($jsonStr, true);

                $sections = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];
                foreach ($sections as $sec) {
                    $items = $sec['itemSectionRenderer']['contents'] ?? [];
                    foreach ($items as $item) {
                        if (isset($item['videoRenderer'])) {
                            $v = $item['videoRenderer'];
                            $videoId = $v['videoId'] ?? '';
                            $title = $v['title']['runs'][0]['text'] ?? ($v['title']['simpleText'] ?? '');
                            $channel = $v['ownerText']['runs'][0]['text'] ?? ($v['longBylineText']['runs'][0]['text'] ?? 'YouTube Channel');
                            
                            $avatar = '';
                            if (!empty($v['avatar']['decoratedAvatarViewModel']['avatar']['avatarViewModel']['image']['sources'][0]['url'])) {
                                $avatar = $v['avatar']['decoratedAvatarViewModel']['avatar']['avatarViewModel']['image']['sources'][0]['url'];
                            } elseif (!empty($v['channelThumbnailSupportedRenderers']['channelThumbnailWithLinkRenderer']['thumbnail']['thumbnails'][0]['url'])) {
                                $avatar = $v['channelThumbnailSupportedRenderers']['channelThumbnailWithLinkRenderer']['thumbnail']['thumbnails'][0]['url'];
                            }
                            if (empty($avatar)) {
                                $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($channel) . '&background=ef4444&color=fff';
                            }

                            $views = $v['viewCountText']['simpleText'] ?? ($v['shortViewCountText']['simpleText'] ?? 'Lượt xem trực tiếp');
                            $time = $v['publishedTimeText']['simpleText'] ?? 'Gần đây';
                            $duration = $v['lengthText']['simpleText'] ?? 'Video';
                            $thumbnail = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";

                            $desc = '';
                            if (!empty($v['detailedMetadataSnippets'][0]['snippetText']['runs'])) {
                                foreach ($v['detailedMetadataSnippets'][0]['snippetText']['runs'] as $r) {
                                    $desc .= $r['text'] ?? '';
                                }
                            }
                            if (empty($desc)) {
                                $desc = 'Video YouTube được phát trực tuyến với độ phân giải cao.';
                            }

                            if ($videoId && $title) {
                                $results[] = [
                                    'id' => 'yt_live_' . $videoId,
                                    'yt_id' => $videoId,
                                    'title' => $title,
                                    'channel' => $channel,
                                    'avatar' => $avatar,
                                    'duration' => $duration,
                                    'views' => $views,
                                    'time' => $time,
                                    'desc' => $desc,
                                    'thumbnail' => $thumbnail,
                                    'subject' => 'YouTube'
                                ];
                            }
                        }
                        if (count($results) >= 24) break 2;
                    }
                }
            }
        }
    }
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);
