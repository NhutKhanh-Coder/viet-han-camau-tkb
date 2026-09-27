<?php
require_once '../config.php';
requireTeacher();

$db = getDB();

// ══════════════════════════════════════════════════════════════════════════
// RESOLVE TEACHER ID SAFELY
// ══════════════════════════════════════════════════════════════════════════
$gv_id = (int)($_SESSION['giang_vien_id'] ?? 0);
if ($gv_id <= 0 && isset($_SESSION['user_id'])) {
    $u_id = (int)$_SESSION['user_id'];
    $chkGv = $db->query("SELECT id FROM giang_vien WHERE user_id = $u_id LIMIT 1");
    if ($chkGv && $rowGv = $chkGv->fetch_assoc()) {
        $gv_id = (int)$rowGv['id'];
        $_SESSION['giang_vien_id'] = $gv_id;
    }
}

$tab = $_GET['tab'] ?? 'list';
$quiz_id = (int)($_GET['quiz_id'] ?? $_POST['quiz_id'] ?? 0);
$msg = $_GET['msg'] ?? '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ══════════════════════════════════════════════════════════════════════════
// HELPER: Extract text from uploaded files (DOCX / PDF / TXT)
// ══════════════════════════════════════════════════════════════════════════
function extractTextFromUploadedFile($tmp_name, $ext) {
    if (!file_exists($tmp_name)) return '';
    try {
        if ($ext === 'docx') {
            $data = '';
            // Method 1: ZipArchive extension
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($tmp_name) === true) {
                    if (($index = $zip->locateName('word/document.xml')) !== false) {
                        $data = $zip->getFromIndex($index);
                    }
                    $zip->close();
                }
            }
            // Method 2: Pure PHP raw zip reader fallback (works on any hosting)
            if (empty($data)) {
                $rawZip = @file_get_contents($tmp_name);
                if (!empty($rawZip)) {
                    $pos = strpos($rawZip, "word/document.xml");
                    if ($pos !== false) {
                        $headerPos = strrpos(substr($rawZip, 0, $pos), "PK\x03\x04");
                        if ($headerPos !== false) {
                            $header = substr($rawZip, $headerPos, 30);
                            $info = unpack('vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vuncompressed_size/vfilename_len/vextra_len', substr($header, 4));
                            $dataStart = $headerPos + 30 + $info['filename_len'] + $info['extra_len'];
                            $compressed = substr($rawZip, $dataStart, $info['compressed_size']);
                            if ($info['compression'] == 8) {
                                $data = @gzinflate($compressed);
                            } elseif ($info['compression'] == 0) {
                                $data = $compressed;
                            }
                        }
                    }
                }
            }

            if (!empty($data)) {
                // 1. Mark bold, underline, highlight runs with [BOLD] tags
                $data = preg_replace('/<w:r\b[^>]*>(?:(?!<\/w:r>).)*?<w:(?:b|bCs|u|highlight|color)\b(?:(?!<\/w:r>).)*?<w:t\b[^>]*>(.*?)<\/w:t>(?:(?!<\/w:r>).)*?<\/w:r>/isu', ' [BOLD]$1[/BOLD] ', $data);
                
                // 2. Replace tabs with spaces to preserve horizontal option separation
                $data = str_replace(['<w:tab/>', '<w:tab>'], '    ', $data);
                
                // 3. Replace paragraph, cell, and line break tags with newlines
                $data = str_replace(['</w:p>', '</w:tc>', '<w:br/>', '<w:br>', '</w:tr>'], "\n", $data);
                $data = str_replace(["\xc2\xa0", "&nbsp;", "\u{00A0}"], " ", $data);
                
                return html_entity_decode(strip_tags($data), ENT_QUOTES, 'UTF-8');
            }

            // Fallback for docx: extract all text between <w:t> tags from raw file
            $raw = @file_get_contents($tmp_name);
            if (preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/is', $raw, $matches)) {
                return implode(' ', $matches[1]);
            }
            return preg_replace('/[^\x20-\x7E\x{0080}-\x{FFFF}\n]/u', ' ', strip_tags($raw ?: ''));
        } elseif ($ext === 'pdf') {
            $content = @file_get_contents($tmp_name, false, null, 0, 2000000);
            if (empty($content)) return '';
            $text = '';
            if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
                $cnt = 0;
                foreach ($matches[1] as $stream) {
                    if ($cnt++ > 100) break;
                    $dec = @gzuncompress($stream);
                    if ($dec === false) $dec = @gzinflate(substr($stream, 2, -4));
                    if ($dec !== false && preg_match_all('/\((.*?)\)/s', $dec, $txt)) {
                        $text .= implode(' ', $txt[1]) . "\n";
                    }
                }
            }
            if (empty(trim($text)) && preg_match_all('/\((.*?)\)/s', $content, $txt)) {
                $text = implode(' ', $txt[1]);
            }
            return trim(str_replace(['\\(', '\\)', '\\\\', '\\r', '\\t'], ['(', ')', '\\', '', ' '], $text));
        } else {
            return @file_get_contents($tmp_name, false, null, 0, 1000000) ?: '';
        }
    } catch (Throwable $e) {
        return @file_get_contents($tmp_name, false, null, 0, 1000000) ?: '';
    }
}

// ══════════════════════════════════════════════════════════════════════════
// HELPER: Reconnect DB if MySQL dropped connection during long AI calls
// ══════════════════════════════════════════════════════════════════════════
function ensureDbConnection(&$db) {
    if (!$db || !@$db->ping()) {
        try {
            @$db->close();
        } catch (Throwable $t) {}
        $db = getDB();
    }
}

// ══════════════════════════════════════════════════════════════════════════
// HELPER: AI Direct Document Question Extractor & Solver (Multi-Chunk for 40+ questions)
// ══════════════════════════════════════════════════════════════════════════
function extractQuestionsDirectlyViaAI($rawText) {
    if (empty(trim($rawText))) return [];

    $AI_KEY = 'sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48';
    $models = ['mistralai/mistral-large-2512', 'deepseek/deepseek-chat-v3.1'];

    $text = trim($rawText);
    $totalLen = mb_strlen($text);

    // Split large text into clean chunks of ~8,000 characters by paragraphs/lines
    $chunkSize = 8000;
    $rawChunks = [];

    if ($totalLen <= $chunkSize) {
        $rawChunks[] = $text;
    } else {
        $lines = explode("\n", $text);
        $curr = '';
        foreach ($lines as $line) {
            if (mb_strlen($curr . "\n" . $line) > $chunkSize && !empty(trim($curr))) {
                $rawChunks[] = trim($curr);
                $curr = $line;
            } else {
                $curr .= ($curr === '' ? '' : "\n") . $line;
            }
        }
        if (!empty(trim($curr))) {
            $rawChunks[] = trim($curr);
        }
    }

    $allQuestions = [];
    $globalStt = 1;

    foreach ($rawChunks as $cIdx => $chunkText) {
        $prompt = "Bạn là chuyên gia thẩm định đề thi trắc nghiệm. Dưới đây là nội dung văn bản trích xuất từ tài liệu đề thi (phần " . ($cIdx + 1) . "/" . count($rawChunks) . ").
Nhiệm vụ:
1. Đọc và bóc tách TẤT CẢ các câu hỏi trắc nghiệm cùng 4 phương án A, B, C, D trong đoạn văn bản này (không bỏ sót bất kỳ câu nào).
2. TỰ ĐỘNG SUY LUẬN VÀ GIẢI ĐÁP ÁN ĐÚNG (A, B, C, hoặc D) cho từng câu hỏi (nếu trong đề có đánh dấu đáp án thì giữ nguyên, nếu chưa có thì giải để chọn đáp án chính xác nhất).
3. BẮT BUỘC: Trả về DUY NHẤT một mảng JSON thuần túy (Array of Objects), KHÔNG viết bất kỳ lời dẫn hay markdown nào.

Cấu trúc mỗi object:
{
  \"cau_hoi\": \"Nội dung câu hỏi (ngắn gọn, chính xác)\",
  \"dap_an_a\": \"Nội dung lựa chọn A\",
  \"dap_an_b\": \"Nội dung lựa chọn B\",
  \"dap_an_c\": \"Nội dung lựa chọn C\",
  \"dap_an_d\": \"Nội dung lựa chọn D\",
  \"dap_an_dung\": \"Ký tự đáp án đúng (A, B, C, hoặc D)\"
}

Văn bản cần xử lý:
\"\"\"
$chunkText
\"\"\"";

        foreach ($models as $model) {
            $ch = curl_init('https://api.xkiro.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => $model,
                    'messages'    => [
                        ['role' => 'system', 'content' => 'Bạn là chuyên gia bóc tách và giải đề thi trắc nghiệm. Luôn trả về đúng 1 mảng JSON thuần túy.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.1,
                    'max_tokens'  => 4000
                ]),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $AI_KEY
                ],
                CURLOPT_TIMEOUT        => 45,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($res)) {
                $json = json_decode($res, true);
                $content = $json['choices'][0]['message']['content'] ?? '';
                $s = strpos($content, '[');
                $e = strrpos($content, ']');
                if ($s !== false && $e !== false) {
                    $parsed = json_decode(substr($content, $s, $e - $s + 1), true);
                    if (is_array($parsed) && count($parsed) > 0) {
                        foreach ($parsed as $item) {
                            $qText = trim($item['cau_hoi'] ?? '');
                            $optA  = trim($item['dap_an_a'] ?? '');
                            $optB  = trim($item['dap_an_b'] ?? '');
                            $optC  = trim($item['dap_an_c'] ?? '');
                            $optD  = trim($item['dap_an_d'] ?? '');
                            $ans   = strtoupper(trim($item['dap_an_dung'] ?? 'A'));
                            if (!in_array($ans, ['A','B','C','D'])) $ans = 'A';

                            if (!empty($qText) && !empty($optA)) {
                                $allQuestions[] = [
                                    'stt'         => $globalStt++,
                                    'cau_hoi'     => $qText,
                                    'dap_an_a'    => $optA,
                                    'dap_an_b'    => $optB ?: 'Lựa chọn B',
                                    'dap_an_c'    => $optC ?: 'Lựa chọn C',
                                    'dap_an_d'    => $optD ?: 'Lựa chọn D',
                                    'dap_an_dung' => $ans,
                                    'ai_analyzed' => true
                                ];
                            }
                        }
                        break; // Success on this chunk, move to next chunk
                    }
                }
            }
        }
    }

    return $allQuestions;
}

// ══════════════════════════════════════════════════════════════════════════
// HELPER: Parse questions from raw text (Ultra-resilient & lossless for 40+ questions)
// ══════════════════════════════════════════════════════════════════════════
function parseQuestionsFromTextContent($text) {
    if (empty(trim($text))) return [];
    
    // 1. Normalize line breaks and non-breaking spaces
    $t = str_replace(["\r\n", "\r", "\xc2\xa0", "\u{00A0}", "&nbsp;"], ["\n", "\n", " ", " ", " "], $text);
    
    // 2. Extract global answer key map if present (e.g. "BẢNG ĐÁP ÁN: 1.C 2.A ...")
    $ansMap = [];
    if (preg_match('/(?:BẢNG\s*ĐÁP\s*ÁN|ĐÁP\s*ÁN|HƯỚNG\s*DẪN\s*CHẤM|ANSWER\s*KEY|KEY|Đ\/A|ĐA)\s*[:\n\r](.*?)$/isu', $t, $sec)) {
        if (preg_match_all('/(?:Câu\s*|\b)(\d{1,3})\s*[\.:\)\/\-\s]*\s*([A-Da-d])\b/u', $sec[1], $p, PREG_SET_ORDER)) {
            foreach ($p as $m) $ansMap[(int)$m[1]] = strtoupper($m[2]);
        }
    }
    if (count($ansMap) < 5) {
        if (preg_match_all('/(?:^|\n|\s)(\d{1,3})\s*[\.:\-\s]+\s*([A-Da-d])(?=\s|\n|$)/u', $t, $grid, PREG_SET_ORDER)) {
            $gridMap = [];
            foreach ($grid as $g) $gridMap[(int)$g[1]] = strtoupper($g[2]);
            if (count($gridMap) >= 5) $ansMap = array_merge($ansMap, $gridMap);
        }
    }

    // 3. Normalize question headers regardless of [BOLD], spaces, tags (supports Word, PDF, pasted text)
    $t = preg_replace_callback('/(?:\[\/?BOLD\]|\*|\s)*(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)(?:\[\/?BOLD\]|\*|\s)*(\d{1,3})(?:\[\/?BOLD\]|\*|\s)*[\.:\)\-]/iu', function($m) {
        return "\n\n[Q_HEAD_" . $m[1] . "]\n";
    }, $t);

    // Also handle standalone "1. ", "2. " at start of lines if not already tagged
    $t = preg_replace('/(?<=\n)\s*(?=(?:\[BOLD\]|\*)*\s*\b\d{1,3}[\.:\)\/]\s+[A-ZÀ-Ỹa-zà-ỹ\*\"])/u', "\n", $t);

    // Question block regex
    $qPat = '/(?:^|\n)\[Q_HEAD_(\d+)\]\s*\n?(.*?)(?=(?:\n\[Q_HEAD_\d+\])|\z)/siu';
    
    if (!preg_match_all($qPat, $t, $matches, PREG_SET_ORDER)) {
        $qPatFallback = '/(?:^|\n)\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*(\d+)|\b(\d{1,3})[\.:\)\/]\s+)\s*[:\.\)\-\s]*(.*?)(?=(?:\n\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}[\.:\)\/]\s+))|\z)/siu';
        if (!preg_match_all($qPatFallback, $t, $matches, PREG_SET_ORDER)) {
            return [];
        }
    }

    $questions = [];
    $autoNum = 1;

    foreach ($matches as $m) {
        $qNum = (int)($m[1] ?: $autoNum);
        $block = trim($m[2] ?? $m[3] ?? '');
        if (mb_strlen($block) < 3) continue;

        // Clean stray tags at end of block without catastrophic backtracking
        $block = preg_replace('/[\s\*\t\r\n]*(?:\[\/?BOLD\])*[\s\*\t\r\n]*(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*$/iu', '', $block);

        // Normalize options A, B, C, D (handles bold, asterisks, tabs, multiple spaces, brackets)
        $norm = $block;
        foreach (['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'] as $upper => $lower) {
            $norm = preg_replace('/(?:^|\n|\s{2,}|\t|\s+)(?:\[BOLD\]|\*)*\s*(?:' . $upper . '[\.:\)\/\-]|' . $lower . '[\.:\)\/\-]|\[' . $upper . '\]|\(' . $upper . '\)|\[' . $lower . '\]|\(' . $lower . '\))\s*(?:\[\/BOLD\]|\*)*/u', "\n[OPT_$upper] ", $norm);
        }

        $posA = strpos($norm, '[OPT_A]');
        $posB = strpos($norm, '[OPT_B]');
        $posC = strpos($norm, '[OPT_C]');
        $posD = strpos($norm, '[OPT_D]');

        $qTitle = '';
        $optA = $optB = $optC = $optD = '';

        if ($posA !== false && $posB !== false) {
            $qTitle = trim(substr($norm, 0, $posA)) ?: "Câu hỏi $qNum";
            $optA = trim(substr($norm, $posA + 7, $posB - ($posA + 7)));
            if ($posC !== false) {
                $optB = trim(substr($norm, $posB + 7, $posC - ($posB + 7)));
                if ($posD !== false) {
                    $optC = trim(substr($norm, $posC + 7, $posD - ($posC + 7)));
                    $optD = trim(substr($norm, $posD + 7));
                } else {
                    $optC = trim(substr($norm, $posC + 7));
                    $optD = '';
                }
            } else {
                $optB = trim(substr($norm, $posB + 7));
                $optC = $optD = '';
            }
        } else {
            // Fallback: Check if block has multiple non-empty lines
            $lines = array_values(array_filter(array_map('trim', explode("\n", $block))));
            if (count($lines) >= 3) {
                $qTitle = $lines[0];
                $optA = $lines[1] ?? 'Lựa chọn A';
                $optB = $lines[2] ?? 'Lựa chọn B';
                $optC = $lines[3] ?? 'Lựa chọn C';
                $optD = $lines[4] ?? 'Lựa chọn D';
            } else {
                continue;
            }
        }

        // Correct Answer Detection
        $correct = $ansMap[$qNum] ?? '';

        // 1. Inline answer string: "Đáp án: C", "Đ/A: C", "Key: C", "Chọn C", "=> C", "-> C"
        if (empty($correct) && preg_match('/(?:Đáp\s*án\s*đúng(?:\s*là)?|Đáp\s*án|Answer|ĐA|Đ\/A|Key|Đáp\s*số|Chọn|Phương\s*án|=>|->)\s*[:\.\-]?\s*([A-Da-d])\b/iu', $block, $mk)) {
            $correct = strtoupper($mk[1]);
        }

        // 2. Bold markers in options (e.g. **C.** or [BOLD]C.[/BOLD] or [BOLD]Answer C[/BOLD])
        if (empty($correct)) {
            $boldedOptions = [];
            $optRaw = ['A' => $optA, 'B' => $optB, 'C' => $optC, 'D' => $optD];

            // Check if option text contains bold formatting
            foreach ($optRaw as $letter => $rawContent) {
                $hasBoldText = false;
                if (preg_match_all('/\[BOLD\](.*?)\[\/BOLD\]/isu', $rawContent, $allBolds)) {
                    foreach ($allBolds[1] as $bt) {
                        if (mb_strlen(trim(strip_tags($bt))) > 0) {
                            $hasBoldText = true;
                            break;
                        }
                    }
                }
                if ($hasBoldText) {
                    $boldedOptions[] = $letter;
                }
            }

            // Check if option letter was bolded in block: [BOLD]b)[/BOLD] or **b)**
            if (empty($boldedOptions)) {
                foreach (['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'] as $upper => $lower) {
                    if (preg_match('/(?:\[BOLD\]|\*\*)\s*(?:' . $upper . '|' . $lower . ')[\.:\)\/\-]/iu', $block)) {
                        $boldedOptions[] = $upper;
                    }
                }
            }

            // If exactly 1 option is bold, select it as the explicit correct answer
            if (count($boldedOptions) === 1) {
                $correct = $boldedOptions[0];
            }
        }

        // 3. Asterisk markers: *A. or A.*
        if (empty($correct)) {
            foreach (['A', 'B', 'C', 'D'] as $letter) {
                $lc = strtolower($letter);
                if (preg_match('/(?:\*\s*[' . $letter . $lc . '][\.:\)]|[' . $letter . $lc . '][\.:\)]\s*\*)/u', $block)) {
                    $correct = $letter;
                    break;
                }
            }
        }

        // Clean answer tags & bold markers & internal option tags out of texts
        $stripPat = '/\s*(?:Đáp\s*án\s*đúng(?:\s*là)?|Đáp\s*án|Answer|ĐA|Đ\/A|Key|Đáp\s*số|Chọn|Phương\s*án|=>|->)\s*[:\.\-]?\s*[A-Da-d].*$/isu';
        $clean = function($s) use ($stripPat) {
            $s = preg_replace($stripPat, '', $s);
            $s = str_replace(['[BOLD]', '[/BOLD]', '**', '*', '[OPT_A]', '[OPT_B]', '[OPT_C]', '[OPT_D]'], '', $s);
            $s = preg_replace('/(?:\s+|\n)*(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*$/iu', '', $s);
            return trim(preg_replace('/\s+/', ' ', $s));
        };

        $hasExplicit = (!empty($correct) && in_array($correct, ['A','B','C','D']));
        if (!$hasExplicit) $correct = 'A';

        $questions[] = [
            'stt' => $qNum,
            'cau_hoi' => $clean($qTitle),
            'dap_an_a' => $clean($optA) ?: 'Lựa chọn A',
            'dap_an_b' => $clean($optB) ?: 'Lựa chọn B',
            'dap_an_c' => $clean($optC) ?: 'Lựa chọn C',
            'dap_an_d' => $clean($optD) ?: 'Lựa chọn D',
            'dap_an_dung' => $correct,
            '_need_ai_answer' => !$hasExplicit,
        ];
        $autoNum++;
    }

    return $questions;
}

// ══════════════════════════════════════════════════════════════════════════
// HELPER: AI Auto-Analyze Correct Answers Engine (Batch & Resilient with Chain-of-Thought)
// ══════════════════════════════════════════════════════════════════════════
function analyzeQuestionsAnswersViaAI(&$questions, $mon_hoc_name = '', $tieu_de = '', $forceSolveAll = false) {
    if (empty($questions)) return 0;

    $missingIndexes = [];
    if ($forceSolveAll) {
        foreach ($questions as $idx => $q) {
            $missingIndexes[] = $idx;
        }
    } else {
        foreach ($questions as $idx => $q) {
            if (!empty($q['_need_ai_answer']) || empty($q['dap_an_dung']) || !in_array($q['dap_an_dung'], ['A','B','C','D'])) {
                $missingIndexes[] = $idx;
            }
        }

        // If no explicit missing flag, check if all questions are default 'A' (likely un-answered)
        if (empty($missingIndexes) && count($questions) > 1) {
            $allA = true;
            foreach ($questions as $q) {
                if (($q['dap_an_dung'] ?? '') !== 'A') {
                    $allA = false;
                    break;
                }
            }
            if ($allA) {
                foreach ($questions as $idx => $q) $missingIndexes[] = $idx;
            }
        }
    }

    if (empty($missingIndexes)) return 0;

    $xkiroKey = 'sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48';
    $googleKey = 'AQ.Ab8RN6KhYYAj9hQMSKIADsz9qPRHc1THSWrXDBOq6m9UlhvAJQ';

    $context = !empty($mon_hoc_name) ? "môn học / lĩnh vực: $mon_hoc_name" : "kỳ thi cao đẳng & đại học";
    if (!empty($tieu_de)) $context .= " (Chuyên đề: $tieu_de)";

    // 10 questions per batch: ensures fast response (~3s), no curl timeout, and thorough CoT reasoning
    $batchSize = 10;
    $chunks = array_chunk($missingIndexes, $batchSize);
    $solvedCount = 0;

    foreach ($chunks as $chunk) {
        $itemsToSolve = [];
        foreach ($chunk as $idx) {
            $q = $questions[$idx];
            $itemsToSolve[] = [
                'id' => $idx,
                'cau_hoi' => $q['cau_hoi'] ?? '',
                'A' => $q['dap_an_a'] ?? '',
                'B' => $q['dap_an_b'] ?? '',
                'C' => $q['dap_an_c'] ?? '',
                'D' => $q['dap_an_d'] ?? ''
            ];
        }

        $prompt = "Bạn là chuyên gia thẩm định đề thi hàng đầu Việt Nam thuộc $context.
Hãy phân tích cẩn trọng từng câu hỏi trắc nghiệm và 4 phương án lựa chọn (A, B, C, D) dưới đây để chỉ ra phương án đúng nhất tuyệt đối.
QUY TẮC BẮT BUỘC:
1. Đọc kỹ câu hỏi, đối chiếu các định nghĩa chuẩn, cú pháp lập trình, quy tắc chỉ số mảng (trong hầu hết ngôn ngữ lập trình hiện đại như C, C++, C#, Java, Python, VB.Net, JavaScript... mảng đều là 0-based indexing, phần tử thứ k có chỉ số là k-1), tính tương thích phiên bản phần mềm, thứ tự toán tử.
2. Với mỗi câu hỏi, BẮT BUỘC đưa ra một câu phân tích ngắn gọn cơ sở lý thuyết (trường \"ly_do\") trước khi kết luận đáp án đúng để đảm bảo độ chính xác 100%.
3. Trả về DUY NHẤT một mảng JSON thuần túy (Array of Objects), KHÔNG viết bất kỳ chữ nào khác bên ngoài khối JSON.
Định dạng JSON:
[
  {
    \"id\": <id câu hỏi>,
    \"ly_do\": \"<phân tích ngắn gọn cơ sở lý thuyết>\",
    \"dap_an_dung\": \"A\" hoặc \"B\" hoặc \"C\" hoặc \"D\"
  }
]

Danh sách câu hỏi cần giải:
" . json_encode($itemsToSolve, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $solvedMap = [];

        // 1. Primary: mistralai/codestral-2508 via xkiro (Fast ~3s, top-tier coding & reasoning)
        $ch = curl_init('https://api.xkiro.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'       => 'mistralai/codestral-2508',
                'messages'    => [
                    ['role' => 'system', 'content' => 'Bạn là chuyên gia thẩm định đáp án trắc nghiệm. Luôn trả về đúng 1 mảng JSON thuần túy.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1,
                'max_tokens'  => 2500
            ]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $xkiroKey
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && !empty($res)) {
            $jsonRes = json_decode($res, true);
            $content = $jsonRes['choices'][0]['message']['content'] ?? '';
            $s = strpos($content, '[');
            $e = strrpos($content, ']');
            if ($s !== false && $e !== false) {
                $parsed = json_decode(substr($content, $s, $e - $s + 1), true);
                if (is_array($parsed)) {
                    foreach ($parsed as $p) {
                        $pId = (int)($p['id'] ?? -1);
                        $pAns = strtoupper(trim($p['dap_an_dung'] ?? ''));
                        if (in_array($pAns, ['A','B','C','D'])) {
                            $solvedMap[$pId] = $pAns;
                        }
                    }
                }
            }
        }

        // 2. Fallback 1: mistralai/mistral-large-2512 via xkiro
        if (empty($solvedMap)) {
            $chM = curl_init('https://api.xkiro.com/v1/chat/completions');
            curl_setopt_array($chM, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => 'mistralai/mistral-large-2512',
                    'messages'    => [
                        ['role' => 'system', 'content' => 'Bạn là chuyên gia thẩm định đáp án trắc nghiệm. Luôn trả về đúng 1 mảng JSON thuần túy.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.1,
                    'max_tokens'  => 2500
                ]),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $xkiroKey
                ],
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $resM = curl_exec($chM);
            $codeM = curl_getinfo($chM, CURLINFO_HTTP_CODE);
            curl_close($chM);

            if ($codeM === 200 && !empty($resM)) {
                $jsonM = json_decode($resM, true);
                $contentM = $jsonM['choices'][0]['message']['content'] ?? '';
                $s = strpos($contentM, '[');
                $e = strrpos($contentM, ']');
                if ($s !== false && $e !== false) {
                    $parsedM = json_decode(substr($contentM, $s, $e - $s + 1), true);
                    if (is_array($parsedM)) {
                        foreach ($parsedM as $p) {
                            $pId = (int)($p['id'] ?? -1);
                            $pAns = strtoupper(trim($p['dap_an_dung'] ?? ''));
                            if (in_array($pAns, ['A','B','C','D'])) {
                                $solvedMap[$pId] = $pAns;
                            }
                        }
                    }
                }
            }
        }

        // 3. Fallback 2: DeepSeek Chat via xkiro
        if (empty($solvedMap)) {
            $chD = curl_init('https://api.xkiro.com/v1/chat/completions');
            curl_setopt_array($chD, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => 'deepseek/deepseek-chat-v3.1',
                    'messages'    => [
                        ['role' => 'system', 'content' => 'Bạn là chuyên gia thẩm định đáp án trắc nghiệm. Luôn trả về đúng 1 mảng JSON thuần túy.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.1,
                    'max_tokens'  => 2500
                ]),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $xkiroKey
                ],
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $resD = curl_exec($chD);
            $codeD = curl_getinfo($chD, CURLINFO_HTTP_CODE);
            curl_close($chD);

            if ($codeD === 200 && !empty($resD)) {
                $jsonD = json_decode($resD, true);
                $contentD = $jsonD['choices'][0]['message']['content'] ?? '';
                $s = strpos($contentD, '[');
                $e = strrpos($contentD, ']');
                if ($s !== false && $e !== false) {
                    $parsedD = json_decode(substr($contentD, $s, $e - $s + 1), true);
                    if (is_array($parsedD)) {
                        foreach ($parsedD as $p) {
                            $pId = (int)($p['id'] ?? -1);
                            $pAns = strtoupper(trim($p['dap_an_dung'] ?? ''));
                            if (in_array($pAns, ['A','B','C','D'])) {
                                $solvedMap[$pId] = $pAns;
                            }
                        }
                    }
                }
            }
        }

        // 4. Fallback 3: Google Gemini
        if (empty($solvedMap)) {
            $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . urlencode($googleKey);
            $chG = curl_init($geminiUrl);
            curl_setopt_array($chG, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json'
                    ]
                ]),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $resG = curl_exec($chG);
            $codeG = curl_getinfo($chG, CURLINFO_HTTP_CODE);
            curl_close($chG);

            if ($codeG === 200 && !empty($resG)) {
                $jsonG = json_decode($resG, true);
                $contentG = $jsonG['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $parsedG = json_decode($contentG, true);
                if (is_array($parsedG)) {
                    foreach ($parsedG as $p) {
                        $pId = (int)($p['id'] ?? -1);
                        $pAns = strtoupper(trim($p['dap_an_dung'] ?? ''));
                        if (in_array($pAns, ['A','B','C','D'])) {
                            $solvedMap[$pId] = $pAns;
                        }
                    }
                }
            }
        }

        // Apply solved answers
        foreach ($chunk as $idx) {
            if (isset($solvedMap[$idx])) {
                $questions[$idx]['dap_an_dung'] = $solvedMap[$idx];
                $questions[$idx]['ai_analyzed'] = true;
                unset($questions[$idx]['_need_ai_answer']);
                $solvedCount++;
            }
        }
    }

    return $solvedCount;
}

// ══════════════════════════════════════════════════════════════════════════
// HELPER: AI Single Question Solver (Chain-of-Thought Powered)
// ══════════════════════════════════════════════════════════════════════════
function singleQuestionSolveViaAI($cau_hoi, $optA, $optB, $optC, $optD, $mon_hoc_name = '') {
    $xkiroKey = 'sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48';
    $googleKey = 'AQ.Ab8RN6KhYYAj9hQMSKIADsz9qPRHc1THSWrXDBOq6m9UlhvAJQ';

    $context = !empty($mon_hoc_name) ? "thuộc môn học / lĩnh vực: $mon_hoc_name" : "giáo dục & khảo thí đại học";

    $prompt = "Bạn là chuyên gia thẩm định đề thi hàng đầu Việt Nam $context.
Hãy phân tích cẩn trọng câu hỏi và 4 phương án dưới đây để chỉ ra phương án đúng nhất tuyệt đối (A, B, C, hoặc D).
QUY TẮC:
- Dựa trên chuẩn kiến thức, tài liệu chính thức, quy tắc chỉ số mảng 0-based (phần tử thứ k có index k-1), thứ tự ưu tiên toán tử.
- BẮT BUỘC trả về đúng 1 object JSON thuần túy (không có markdown hay chữ bên ngoài):
{
  \"ly_do\": \"phân tích ngắn gọn cơ sở lý thuyết\",
  \"dap_an_dung\": \"A\" hoặc \"B\" hoặc \"C\" hoặc \"D\"
}

Câu hỏi: $cau_hoi
A. $optA
B. $optB
C. $optC
D. $optD";

    // 1. Try xkiro Codestral 2508
    $ch = curl_init('https://api.xkiro.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model'       => 'mistralai/codestral-2508',
            'messages'    => [
                ['role' => 'system', 'content' => 'Bạn là chuyên gia thẩm định đáp án trắc nghiệm. Luôn trả về đúng 1 object JSON thuần túy.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.1,
            'max_tokens'  => 500
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $xkiroKey
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && !empty($res)) {
        $json = json_decode($res, true);
        $content = $json['choices'][0]['message']['content'] ?? '';
        if (preg_match('/"dap_an_dung"\s*:\s*"([A-D])"/i', $content, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/\b([A-D])\b/i', $content, $m)) {
            return strtoupper($m[1]);
        }
    }

    // 2. Fallback Mistral Large
    $chM = curl_init('https://api.xkiro.com/v1/chat/completions');
    curl_setopt_array($chM, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model'       => 'mistralai/mistral-large-2512',
            'messages'    => [
                ['role' => 'system', 'content' => 'Bạn là chuyên gia thẩm định đáp án trắc nghiệm. Luôn trả về đúng 1 object JSON thuần túy.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.1,
            'max_tokens'  => 500
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $xkiroKey
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $resM = curl_exec($chM);
    $codeM = curl_getinfo($chM, CURLINFO_HTTP_CODE);
    curl_close($chM);

    if ($codeM === 200 && !empty($resM)) {
        $jsonM = json_decode($resM, true);
        $contentM = $jsonM['choices'][0]['message']['content'] ?? '';
        if (preg_match('/"dap_an_dung"\s*:\s*"([A-D])"/i', $contentM, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/\b([A-D])\b/i', $contentM, $m)) {
            return strtoupper($m[1]);
        }
    }

    return 'A';
}

// ══════════════════════════════════════════════════════════════════════════
// HELPER: AI Quiz Generator Core Engine
// ══════════════════════════════════════════════════════════════════════════
function generateQuizViaAI($topic, $so_cau = 10, $level = 'mixed', $source_content = '') {
    $AI_KEY = 'sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48';
    $models = ['deepseek/deepseek-chat-v3.1', 'mistralai/mistral-large-2512'];
    
    $levelDesc = "Phân bổ độ khó: 40% Nhận biết, 40% Thông hiểu, 20% Vận dụng phân tích.";
    if ($level === 'easy') $levelDesc = "Mức độ: Dễ / Nhận biết và thông hiểu kiến thức căn bản.";
    elseif ($level === 'hard') $levelDesc = "Mức độ: Khó & Nâng cao / Vận dụng thực tế và tư duy chuyên sâu.";

    $sourcePrompt = "";
    if (!empty(trim($source_content))) {
        $sourcePrompt = "\nNỘI DUNG TÀI LIỆU NGUỒN (Bắt buộc biên soạn câu hỏi bám sát 100% nội dung sau):\n\"\"\"\n" . mb_substr(trim($source_content), 0, 4500) . "\n\"\"\"\n";
    }

    $prompt = "Bạn là chuyên gia sư phạm và khảo thí giáo dục đại học. Hãy tạo đúng $so_cau câu hỏi trắc nghiệm tiếng Việt chất lượng cao về chủ đề: \"$topic\".
$levelDesc$sourcePrompt
QUY TẮC BẮT BUỘC:
1. Trả về DUY NHẤT 1 mảng JSON thuần túy (Array of Objects), KHÔNG viết bất kỳ chữ giải thích nào, KHÔNG bọc trong markdown ```json ```.
2. Mỗi object đại diện cho 1 câu hỏi và phải có đúng 6 key sau:
   - \"cau_hoi\": Nội dung câu hỏi (ngắn gọn, mạch lạc, rõ ràng)
   - \"dap_an_a\": Nội dung phương án A
   - \"dap_an_b\": Nội dung phương án B
   - \"dap_an_c\": Nội dung phương án C
   - \"dap_an_d\": Nội dung phương án D
   - \"dap_an_dung\": Ký tự đáp án đúng (chỉ nhận đúng 1 trong 4 chữ cái: \"A\", \"B\", \"C\", hoặc \"D\")
3. CÂN BẰNG ĐÁP ÁN: Phân bổ ngẫu nhiên và đồng đều các đáp án đúng (A, B, C, D). Tuyệt đối KHÔNG ĐƯỢC để tất cả là A hoặc B.
4. Các phương án sai (nhiễu) phải hợp lý, mang tính phân loại tốt.";

    foreach ($models as $model) {
        $ch = curl_init('https://api.xkiro.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'       => $model,
                'messages'    => [
                    ['role' => 'system', 'content' => 'Bạn là chuyên gia soạn thảo đề thi trắc nghiệm. Luôn luôn trả về đúng 1 mảng JSON thuần túy, hợp lệ.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.45,
                'max_tokens'  => max(3500, $so_cau * 220)
            ]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $AI_KEY
            ],
            CURLOPT_TIMEOUT        => 80,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && !empty($res)) {
            $jsonRes = json_decode($res, true);
            $content = $jsonRes['choices'][0]['message']['content'] ?? '';
            
            $s = strpos($content, '[');
            $e = strrpos($content, ']');
            if ($s !== false && $e !== false) {
                $rawArray = substr($content, $s, $e - $s + 1);
                $parsed = json_decode($rawArray, true);
                if (is_array($parsed) && count($parsed) > 0) {
                    $cleaned = [];
                    foreach ($parsed as $item) {
                        $qText = trim($item['cau_hoi'] ?? '');
                        $optA  = trim($item['dap_an_a'] ?? '');
                        $optB  = trim($item['dap_an_b'] ?? '');
                        $optC  = trim($item['dap_an_c'] ?? '');
                        $optD  = trim($item['dap_an_d'] ?? '');
                        $ans   = strtoupper(trim($item['dap_an_dung'] ?? 'A'));
                        if (!in_array($ans, ['A','B','C','D'])) $ans = 'A';
                        
                        if (!empty($qText) && !empty($optA) && !empty($optB)) {
                            $cleaned[] = [
                                'cau_hoi'     => $qText,
                                'dap_an_a'    => $optA,
                                'dap_an_b'    => $optB,
                                'dap_an_c'    => $optC ?: 'Không có đáp án phù hợp',
                                'dap_an_d'    => $optD ?: 'Tất cả đều đúng',
                                'dap_an_dung' => $ans
                            ];
                        }
                    }
                    if (count($cleaned) > 0) {
                        return [
                            'success'   => true,
                            'model'     => $model,
                            'count'     => count($cleaned),
                            'questions' => $cleaned
                        ];
                    }
                }
            }
        }
    }
    
    return [
        'success' => false,
        'error'   => 'Không thể kết nối đến máy chủ AI hoặc phản hồi không đúng định dạng.'
    ];
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION HANDLERS
// ══════════════════════════════════════════════════════════════════════════

// 1. AJAX: GENERATE AI QUIZ QUESTIONS (FOR LIVE PREVIEW)
if ($action === 'ajax_ai_generate') {
    header('Content-Type: application/json');
    $topic = trim($_POST['topic'] ?? '');
    $so_cau = max(3, min(50, (int)($_POST['so_cau'] ?? 10)));
    $level = trim($_POST['level'] ?? 'mixed');
    $source_content = trim($_POST['source_content'] ?? '');

    if (empty($topic) && empty($source_content)) {
        echo json_encode(['success' => false, 'error' => 'Vui lòng nhập chủ đề hoặc dán tài liệu nguồn cho AI.']);
        exit;
    }

    if (empty($topic)) $topic = "Nội dung tài liệu đính kèm";

    $result = generateQuizViaAI($topic, $so_cau, $level, $source_content);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. AJAX / FORM: SAVE AI GENERATED QUIZ (FROM LIVE PREVIEW EDITOR)
elseif ($action === 'save_ai_quiz') {
    try {
        $mon_hoc_id = (int)($_POST['mon_hoc_id'] ?? 0);
        $tieu_de = trim($_POST['tieu_de'] ?? '') ?: "Đề trắc nghiệm AI " . date('d/m/Y H:i');
        $mo_ta = trim($_POST['mo_ta'] ?? '');
        $lop = trim($_POST['lop'] ?? '');
        $thoi_gian_lam_bai = max(5, (int)($_POST['thoi_gian_lam_bai'] ?? 15));
        $questions_json = $_POST['questions_json'] ?? '';
        $questions = json_decode($questions_json, true) ?: [];

        if (!$mon_hoc_id) {
            $msg = "error:Vui lòng chọn môn học cho bộ đề Quiz.";
        } elseif (empty($questions)) {
            $msg = "error:Chưa có câu hỏi nào để lưu.";
        } else {
            $eff_gv = $gv_id;
            if ($eff_gv <= 0 && isset($_SESSION['user_id'])) {
                $r = $db->query("SELECT id FROM giang_vien WHERE user_id = " . (int)$_SESSION['user_id'] . " LIMIT 1");
                if ($r && $row = $r->fetch_assoc()) $eff_gv = (int)$row['id'];
            }

            @$db->query("ALTER TABLE quizzes ADD COLUMN `mo_ta` TEXT DEFAULT NULL");
            @$db->query("ALTER TABLE quizzes ADD COLUMN `thoi_gian_lam_bai` INT(11) DEFAULT 15");

            $stmt = $db->prepare("INSERT INTO quizzes (giang_vien_id, mon_hoc_id, tieu_de, mo_ta, thoi_gian_lam_bai) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iissi", $eff_gv, $mon_hoc_id, $tieu_de, $mo_ta, $thoi_gian_lam_bai);
                $stmt->execute();
                $new_quiz_id = $db->insert_id;
            } else {
                $stmt2 = $db->prepare("INSERT INTO quizzes (giang_vien_id, mon_hoc_id, tieu_de) VALUES (?, ?, ?)");
                $stmt2->bind_param("iis", $eff_gv, $mon_hoc_id, $tieu_de);
                $stmt2->execute();
                $new_quiz_id = $db->insert_id;
            }

            $inserted = 0;
            $stq = $db->prepare("INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($questions as $q) {
                $c = trim($q['cau_hoi'] ?? '');
                $a = trim($q['dap_an_a'] ?? '');
                $b = trim($q['dap_an_b'] ?? '');
                $cc = trim($q['dap_an_c'] ?? '');
                $d = trim($q['dap_an_d'] ?? '');
                $dung = strtoupper(trim($q['dap_an_dung'] ?? 'A'));
                if (!in_array($dung, ['A','B','C','D'])) $dung = 'A';
                if (!empty($c) && !empty($a)) {
                    $stq->bind_param("issssss", $new_quiz_id, $c, $a, $b, $cc, $d, $dung);
                    if ($stq->execute()) $inserted++;
                }
            }

            if (!empty($lop)) {
                $deadline = date('Y-m-d 23:59:59', strtotime('+7 days'));
                $sa = $db->prepare("INSERT INTO assignments (giang_vien_id, mon_hoc_id, tieu_de, mo_ta, han_nop, lop, quiz_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($sa) {
                    $sa->bind_param("iissssi", $eff_gv, $mon_hoc_id, $tieu_de, $mo_ta, $deadline, $lop, $new_quiz_id);
                    $sa->execute();
                }
            }

            $msg = "success:AI đã tạo thành công bộ Quiz \"$tieu_de\" ($inserted câu hỏi)!";
            header("Location: /tkb/teacher/quiz.php?tab=questions&quiz_id=$new_quiz_id&msg=" . urlencode($msg));
            exit;
        }
    } catch (Throwable $e) {
        $msg = "error:Lỗi lưu Quiz: " . $e->getMessage();
    }
}

// 3. AJAX: AI GENERATE & ADD MORE QUESTIONS TO EXISTING QUIZ
elseif ($action === 'ajax_ai_add_more') {
    header('Content-Type: application/json');
    $qid = (int)($_POST['quiz_id'] ?? 0);
    $topic = trim($_POST['topic'] ?? '');
    $so_cau = max(1, min(30, (int)($_POST['so_cau'] ?? 5)));
    $level = trim($_POST['level'] ?? 'mixed');

    if (!$qid) {
        echo json_encode(['success' => false, 'error' => 'Thiếu Quiz ID.']);
        exit;
    }

    if (empty($topic)) {
        $chk = $db->query("SELECT tieu_de FROM quizzes WHERE id = $qid LIMIT 1");
        if ($chk && $r = $chk->fetch_assoc()) $topic = $r['tieu_de'];
        else $topic = "Kiến thức chuyên ngành";
    }

    $aiRes = generateQuizViaAI($topic, $so_cau, $level);
    if ($aiRes['success'] && !empty($aiRes['questions'])) {
        $stq = $db->prepare("INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $cnt = 0;
        foreach ($aiRes['questions'] as $q) {
            $stq->bind_param("issssss", $qid, $q['cau_hoi'], $q['dap_an_a'], $q['dap_an_b'], $q['dap_an_c'], $q['dap_an_d'], $q['dap_an_dung']);
            if ($stq->execute()) $cnt++;
        }
        echo json_encode(['success' => true, 'added_count' => $cnt]);
    } else {
        echo json_encode(['success' => false, 'error' => $aiRes['error'] ?? 'Lỗi tạo câu hỏi AI.']);
    }
    exit;
}

// 4. CREATE QUIZ VIA STANDARD FORM (TEXT PASTE, FILE UPLOAD, MANUAL)
elseif ($action === 'create_quiz') {
    try {
        $mon_hoc_id = (int)($_POST['mon_hoc_id'] ?? 0);
        $tieu_de = trim($_POST['tieu_de'] ?? '') ?: "Đề trắc nghiệm " . date('d/m/Y H:i');
        $mo_ta = trim($_POST['mo_ta'] ?? '');
        $lop = trim($_POST['lop'] ?? '');
        $thoi_gian_lam_bai = max(5, (int)($_POST['thoi_gian_lam_bai'] ?? 15));
        $source_mode = $_POST['source_mode'] ?? 'manual';

        if (!$mon_hoc_id) {
            $msg = "error:Vui lòng chọn môn học.";
        } else {
            $questions_to_insert = [];

            // 1. Parse / AI Extract Questions FIRST before DB write
            if ($source_mode === 'text' && !empty($_POST['raw_text'])) {
                $raw = trim($_POST['raw_text']);
                $questions_to_insert = parseQuestionsFromTextContent($raw);
                if (count($questions_to_insert) < 2) {
                    $aiExtracted = extractQuestionsDirectlyViaAI($raw);
                    if (count($aiExtracted) > count($questions_to_insert)) {
                        $questions_to_insert = $aiExtracted;
                    }
                }
            } elseif ($source_mode === 'file' && isset($_FILES['quiz_file']) && $_FILES['quiz_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['quiz_file']['name'], PATHINFO_EXTENSION));
                $fc = extractTextFromUploadedFile($_FILES['quiz_file']['tmp_name'], $ext);
                if (!empty($fc)) {
                    $questions_to_insert = parseQuestionsFromTextContent($fc);
                    if (count($questions_to_insert) < 2) {
                        $aiExtracted = extractQuestionsDirectlyViaAI($fc);
                        if (count($aiExtracted) > count($questions_to_insert)) {
                            $questions_to_insert = $aiExtracted;
                        }
                    }
                }
            }

            // AI Automatically analyzes and determines correct answers
            $mon_hoc_name = '';
            if ($mon_hoc_id > 0) {
                $rm = $db->query("SELECT ten_mon FROM mon_hoc WHERE id = $mon_hoc_id LIMIT 1");
                if ($rm && $rowm = $rm->fetch_assoc()) $mon_hoc_name = $rowm['ten_mon'] ?? '';
            }
            $forceSolve = !empty($_POST['force_ai_solve']);
            if (!empty($questions_to_insert)) {
                analyzeQuestionsAnswersViaAI($questions_to_insert, $mon_hoc_name, $tieu_de, $forceSolve);
            }

            // 2. Re-establish MySQL connection after long AI requests
            ensureDbConnection($db);

            $eff_gv = $gv_id;
            if ($eff_gv <= 0 && isset($_SESSION['user_id'])) {
                $r = $db->query("SELECT id FROM giang_vien WHERE user_id = " . (int)$_SESSION['user_id'] . " LIMIT 1");
                if ($r && $row = $r->fetch_assoc()) $eff_gv = (int)$row['id'];
            }

            @$db->query("ALTER TABLE quizzes ADD COLUMN `mo_ta` TEXT DEFAULT NULL");
            @$db->query("ALTER TABLE quizzes ADD COLUMN `thoi_gian_lam_bai` INT(11) DEFAULT 15");

            $stmt = $db->prepare("INSERT INTO quizzes (giang_vien_id, mon_hoc_id, tieu_de, mo_ta, thoi_gian_lam_bai) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iissi", $eff_gv, $mon_hoc_id, $tieu_de, $mo_ta, $thoi_gian_lam_bai);
                $stmt->execute();
                $new_quiz_id = $db->insert_id;
                $stmt->close();
            } else {
                $t_esc = $db->real_escape_string($tieu_de);
                $m_esc = $db->real_escape_string($mo_ta);
                $db->query("INSERT INTO quizzes (giang_vien_id, mon_hoc_id, tieu_de, mo_ta, thoi_gian_lam_bai) VALUES ($eff_gv, $mon_hoc_id, '$t_esc', '$m_esc', $thoi_gian_lam_bai)");
                $new_quiz_id = $db->insert_id;
            }

            $inserted = 0;
            if (!empty($questions_to_insert) && $new_quiz_id > 0) {
                $stq = $db->prepare("INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stq) {
                    foreach ($questions_to_insert as $q) {
                        $c = trim($q['cau_hoi'] ?? '');
                        $a = trim($q['dap_an_a'] ?? '');
                        $b = trim($q['dap_an_b'] ?? '');
                        $cc = trim($q['dap_an_c'] ?? '');
                        $d = trim($q['dap_an_d'] ?? '');
                        $dung = strtoupper(trim($q['dap_an_dung'] ?? 'A'));
                        if (!in_array($dung, ['A','B','C','D'])) $dung = 'A';
                        if (!empty($c)) {
                            $stq->bind_param("issssss", $new_quiz_id, $c, $a, $b, $cc, $d, $dung);
                            if ($stq->execute()) $inserted++;
                        }
                    }
                    $stq->close();
                } else {
                    foreach ($questions_to_insert as $q) {
                        $c = $db->real_escape_string(trim($q['cau_hoi'] ?? ''));
                        $a = $db->real_escape_string(trim($q['dap_an_a'] ?? ''));
                        $b = $db->real_escape_string(trim($q['dap_an_b'] ?? ''));
                        $cc = $db->real_escape_string(trim($q['dap_an_c'] ?? ''));
                        $d = $db->real_escape_string(trim($q['dap_an_d'] ?? ''));
                        $dung = strtoupper(trim($q['dap_an_dung'] ?? 'A'));
                        if (!in_array($dung, ['A','B','C','D'])) $dung = 'A';
                        if (!empty($c)) {
                            if ($db->query("INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES ($new_quiz_id, '$c', '$a', '$b', '$cc', '$d', '$dung')")) {
                                $inserted++;
                            }
                        }
                    }
                }
            }

            if (!empty($lop) && $new_quiz_id > 0) {
                $deadline = date('Y-m-d 23:59:59', strtotime('+7 days'));
                $sa = $db->prepare("INSERT INTO assignments (giang_vien_id, mon_hoc_id, tieu_de, mo_ta, han_nop, lop, quiz_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($sa) {
                    $sa->bind_param("iissssi", $eff_gv, $mon_hoc_id, $tieu_de, $mo_ta, $deadline, $lop, $new_quiz_id);
                    $sa->execute();
                    $sa->close();
                }
            }

            if ($inserted > 0) {
                $msg = "success:Đã tạo thành công bộ Quiz \"$tieu_de\" ($inserted câu hỏi — AI đã phân tích & chọn đáp án)!";
            } else {
                $msg = "success:Đã tạo khung bộ Quiz \"$tieu_de\"!";
            }
            header("Location: /tkb/teacher/quiz.php?tab=questions&quiz_id=$new_quiz_id&msg=" . urlencode($msg));
            exit;
        }
    } catch (Throwable $e) {
        $msg = "error:Lỗi tạo Quiz: " . $e->getMessage();
    }
}

// 5. ADD SINGLE QUESTION
elseif ($action === 'add_question') {
    $qid = (int)$_POST['quiz_id'];
    $cau_hoi = trim($_POST['cau_hoi'] ?? '');
    $da = trim($_POST['dap_an_a'] ?? '');
    $db_ = trim($_POST['dap_an_b'] ?? '');
    $dc = trim($_POST['dap_an_c'] ?? '');
    $dd = trim($_POST['dap_an_d'] ?? '');
    $dung = strtoupper(trim($_POST['dap_an_dung'] ?? ''));

    if (empty($dung) || !in_array($dung, ['A','B','C','D'])) {
        $dung = singleQuestionSolveViaAI($cau_hoi, $da, $db_, $dc, $dd);
    }

    ensureDbConnection($db);
    if ($qid && $cau_hoi && $da && $db_) {
        $stmt = $db->prepare("INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issssss", $qid, $cau_hoi, $da, $db_, $dc, $dd, $dung);
            $msg = $stmt->execute() ? "success:Đã thêm câu hỏi mới!" : "error:Lỗi: " . $db->error;
            $stmt->close();
        } else {
            $c = $db->real_escape_string($cau_hoi);
            $a = $db->real_escape_string($da);
            $b = $db->real_escape_string($db_);
            $cc = $db->real_escape_string($dc);
            $d = $db->real_escape_string($dd);
            $msg = $db->query("INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES ($qid, '$c', '$a', '$b', '$cc', '$d', '$dung')") ? "success:Đã thêm câu hỏi mới!" : "error:Lỗi lưu câu hỏi.";
        }
    } else {
        $msg = "error:Vui lòng nhập câu hỏi và ít nhất đáp án A, B.";
    }
    header("Location: /tkb/teacher/quiz.php?tab=questions&quiz_id=$qid&msg=" . urlencode($msg));
    exit;
}

// 6. BULK ADD QUESTIONS (TEXT PASTE & FILE UPLOAD)
elseif ($action === 'bulk_add_questions') {
    $qid = (int)$_POST['quiz_id'];
    $parsed = [];
    
    if (isset($_FILES['quiz_file']) && $_FILES['quiz_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['quiz_file']['name'], PATHINFO_EXTENSION));
        $fc = extractTextFromUploadedFile($_FILES['quiz_file']['tmp_name'], $ext);
        if (!empty($fc)) {
            $parsed = parseQuestionsFromTextContent($fc);
            if (count($parsed) < 2) {
                $aiExtracted = extractQuestionsDirectlyViaAI($fc);
                if (count($aiExtracted) > count($parsed)) {
                    $parsed = $aiExtracted;
                }
            }
        }
    } elseif (!empty($_POST['raw_text'])) {
        $raw = trim($_POST['raw_text']);
        $parsed = parseQuestionsFromTextContent($raw);
        if (count($parsed) < 2) {
            $aiExtracted = extractQuestionsDirectlyViaAI($raw);
            if (count($aiExtracted) > count($parsed)) {
                $parsed = $aiExtracted;
            }
        }
    }
    
    $mon_hoc_name = '';
    $quiz_title = '';
    if ($qid > 0) {
        $rq = $db->query("SELECT q.tieu_de, m.ten_mon FROM quizzes q LEFT JOIN mon_hoc m ON q.mon_hoc_id = m.id WHERE q.id = $qid LIMIT 1");
        if ($rq && $rowq = $rq->fetch_assoc()) {
            $mon_hoc_name = $rowq['ten_mon'] ?? '';
            $quiz_title = $rowq['tieu_de'] ?? '';
        }
    }
    $forceSolve = !empty($_POST['force_ai_solve']);
    if (!empty($parsed)) {
        analyzeQuestionsAnswersViaAI($parsed, $mon_hoc_name, $quiz_title, $forceSolve);
    }

    ensureDbConnection($db);

    if ($qid && !empty($parsed)) {
        $cnt = 0;
        $stmt = $db->prepare("INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            foreach ($parsed as $q) {
                $stmt->bind_param("issssss", $qid, $q['cau_hoi'], $q['dap_an_a'], $q['dap_an_b'], $q['dap_an_c'], $q['dap_an_d'], $q['dap_an_dung']);
                if ($stmt->execute()) $cnt++;
            }
            $stmt->close();
        } else {
            foreach ($parsed as $q) {
                $c = $db->real_escape_string(trim($q['cau_hoi'] ?? ''));
                $a = $db->real_escape_string(trim($q['dap_an_a'] ?? ''));
                $b = $db->real_escape_string(trim($q['dap_an_b'] ?? ''));
                $cc = $db->real_escape_string(trim($q['dap_an_c'] ?? ''));
                $d = $db->real_escape_string(trim($q['dap_an_d'] ?? ''));
                $dung = strtoupper(trim($q['dap_an_dung'] ?? 'A'));
                if (!in_array($dung, ['A','B','C','D'])) $dung = 'A';
                if (!empty($c)) {
                    if ($db->query("INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES ($qid, '$c', '$a', '$b', '$cc', '$d', '$dung')")) {
                        $cnt++;
                    }
                }
            }
        }
        $msg = "success:Đã nạp thành công $cnt câu hỏi (AI đã tự động phân tích & gán đáp án chính xác)!";
    } else {
        $msg = "error:Không nhận diện được câu hỏi từ tệp hoặc văn bản. Vui lòng kiểm tra lại!";
    }
    header("Location: /tkb/teacher/quiz.php?tab=questions&quiz_id=$qid&msg=" . urlencode($msg));
    exit;
}

// 7. EDIT QUESTION
elseif ($action === 'edit_question') {
    $qid = (int)$_POST['quiz_id'];
    $question_id = (int)$_POST['question_id'];
    $cau_hoi = trim($_POST['cau_hoi'] ?? '');
    $da = trim($_POST['dap_an_a'] ?? '');
    $db_ = trim($_POST['dap_an_b'] ?? '');
    $dc = trim($_POST['dap_an_c'] ?? '');
    $dd = trim($_POST['dap_an_d'] ?? '');
    $dung = strtoupper(trim($_POST['dap_an_dung'] ?? 'A'));

    if ($question_id && $cau_hoi) {
        $stmt = $db->prepare("UPDATE quiz_questions SET cau_hoi=?, dap_an_a=?, dap_an_b=?, dap_an_c=?, dap_an_d=?, dap_an_dung=? WHERE id=?");
        $stmt->bind_param("ssssssi", $cau_hoi, $da, $db_, $dc, $dd, $dung, $question_id);
        $msg = $stmt->execute() ? "success:Đã cập nhật câu hỏi!" : "error:Lỗi cập nhật.";
    }
    header("Location: /tkb/teacher/quiz.php?tab=questions&quiz_id=$qid&msg=" . urlencode($msg));
    exit;
}

// 8. DELETE QUESTION
elseif ($action === 'delete_question') {
    $qid = (int)$_GET['quiz_id'];
    $question_id = (int)$_GET['question_id'];
    if ($question_id) {
        $stmt = $db->prepare("DELETE FROM quiz_questions WHERE id=?");
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $msg = "success:Đã xóa câu hỏi!";
    }
    header("Location: /tkb/teacher/quiz.php?tab=questions&quiz_id=$qid&msg=" . urlencode($msg));
    exit;
}

// 9. DELETE QUIZ
elseif ($action === 'delete_quiz') {
    $qid = (int)$_GET['quiz_id'];
    if ($qid) {
        $db->query("DELETE FROM quiz_questions WHERE quiz_id = $qid");
        $db->query("DELETE FROM quiz_exam_codes WHERE quiz_id = $qid");
        $db->query("DELETE FROM quiz_attempts WHERE quiz_id = $qid");
        $db->query("DELETE FROM assignments WHERE quiz_id = $qid");
        $db->query("DELETE FROM quizzes WHERE id = $qid");
        $msg = "success:Đã xóa bộ Quiz và dữ liệu liên quan!";
    }
    header("Location: /tkb/teacher/quiz.php?tab=list&msg=" . urlencode($msg));
    exit;
}

// 10. AJAX: Quick set correct answer
elseif ($action === 'quick_set_correct') {
    header('Content-Type: application/json');
    $question_id = (int)($_POST['question_id'] ?? 0);
    $ans = strtoupper(trim($_POST['answer'] ?? ''));
    if ($question_id && in_array($ans, ['A','B','C','D'])) {
        $stmt = $db->prepare("UPDATE quiz_questions SET dap_an_dung=? WHERE id=?");
        $stmt->bind_param("si", $ans, $question_id);
        echo json_encode(['success' => $stmt->execute(), 'answer' => $ans]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
    }
    exit;
}

// 10B. AJAX: AI Solve Single Question
elseif ($action === 'ajax_ai_solve_question') {
    header('Content-Type: application/json');
    $question_id = (int)($_POST['question_id'] ?? 0);
    $cau_hoi = trim($_POST['cau_hoi'] ?? '');
    $da = trim($_POST['dap_an_a'] ?? '');
    $db_ = trim($_POST['dap_an_b'] ?? '');
    $dc = trim($_POST['dap_an_c'] ?? '');
    $dd = trim($_POST['dap_an_d'] ?? '');

    if ($question_id > 0) {
        $res = $db->query("SELECT * FROM quiz_questions WHERE id = $question_id LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $cau_hoi = $row['cau_hoi'];
            $da = $row['dap_an_a'];
            $db_ = $row['dap_an_b'];
            $dc = $row['dap_an_c'];
            $dd = $row['dap_an_d'];
        }
    }

    if (empty($cau_hoi) || empty($da)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu dữ liệu câu hỏi.']);
        exit;
    }

    $mon_hoc_name = '';
    if ($question_id > 0) {
        $rm = $db->query("SELECT m.ten_mon FROM quiz_questions qq JOIN quizzes q ON qq.quiz_id = q.id LEFT JOIN mon_hoc m ON q.mon_hoc_id = m.id WHERE qq.id = $question_id LIMIT 1");
        if ($rm && $rowm = $rm->fetch_assoc()) $mon_hoc_name = $rowm['ten_mon'] ?? '';
    }

    $solved = singleQuestionSolveViaAI($cau_hoi, $da, $db_, $dc, $dd, $mon_hoc_name);
    if ($question_id > 0 && in_array($solved, ['A','B','C','D'])) {
        $stmt = $db->prepare("UPDATE quiz_questions SET dap_an_dung=? WHERE id=?");
        $stmt->bind_param("si", $solved, $question_id);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'answer' => $solved, 'question_id' => $question_id]);
    exit;
}

// 10C. AJAX: AI Solve All Questions In Quiz
elseif ($action === 'ajax_ai_solve_all') {
    header('Content-Type: application/json');
    $qid = (int)($_POST['quiz_id'] ?? 0);
    if (!$qid) {
        echo json_encode(['success' => false, 'error' => 'Thiếu Quiz ID']);
        exit;
    }

    $mon_hoc_name = '';
    $quiz_title = '';
    $rq = $db->query("SELECT q.tieu_de, m.ten_mon FROM quizzes q LEFT JOIN mon_hoc m ON q.mon_hoc_id = m.id WHERE q.id = $qid LIMIT 1");
    if ($rq && $rowq = $rq->fetch_assoc()) {
        $mon_hoc_name = $rowq['ten_mon'] ?? '';
        $quiz_title = $rowq['tieu_de'] ?? '';
    }

    $res = $db->query("SELECT * FROM quiz_questions WHERE quiz_id = $qid ORDER BY id ASC");
    $list = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $r['_need_ai_answer'] = true;
            $list[] = $r;
        }
    }

    if (empty($list)) {
        echo json_encode(['success' => false, 'error' => 'Chưa có câu hỏi nào trong đề.']);
        exit;
    }

    $solvedCount = analyzeQuestionsAnswersViaAI($list, $mon_hoc_name, $quiz_title, true);
    $upStmt = $db->prepare("UPDATE quiz_questions SET dap_an_dung=? WHERE id=?");
    foreach ($list as $item) {
        $ans = $item['dap_an_dung'] ?? 'A';
        $upStmt->bind_param("si", $ans, $item['id']);
        $upStmt->execute();
    }

    echo json_encode(['success' => true, 'count' => count($list), 'solved_count' => $solvedCount]);
    exit;
}

// 11. GENERATE SHUFFLED EXAM CODES (100% INVARIANT ACCURACY)
elseif ($action === 'generate_matrix_codes') {
    $qid = (int)$_POST['quiz_id'];
    $so_ma_de = max(1, min(100, (int)($_POST['so_ma_de'] ?? 4)));
    $ma_de_start = max(101, (int)($_POST['ma_de_start'] ?? 101));
    $tron_cau = isset($_POST['tron_cau_hoi']);
    $tron_da = isset($_POST['tron_dap_an']);

    $stq = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY id ASC");
    $stq->bind_param("i", $qid);
    $stq->execute();
    $base_qs = $stq->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($base_qs)) {
        $msg = "error:Chưa có câu hỏi nào để trộn.";
    } else {
        $db->query("DELETE FROM quiz_exam_codes WHERE quiz_id = $qid");
        $letters = ['A','B','C','D'];

        for ($i = 0; $i < $so_ma_de; $i++) {
            $ma_de = (string)($ma_de_start + $i);
            $qs = $base_qs;
            if ($tron_cau) shuffle($qs);

            $matrix = [];
            foreach ($qs as $idx => $bq) {
                $orig_dung = strtoupper(trim($bq['dap_an_dung'] ?? 'A'));
                if (!in_array($orig_dung, ['A','B','C','D'])) $orig_dung = 'A';

                // Map options with boolean correct tracker (100% invariant through shuffle)
                $opt_items = [
                    ['orig_key' => 'A', 'text' => $bq['dap_an_a'] ?? '', 'is_correct' => ($orig_dung === 'A')],
                    ['orig_key' => 'B', 'text' => $bq['dap_an_b'] ?? '', 'is_correct' => ($orig_dung === 'B')],
                    ['orig_key' => 'C', 'text' => $bq['dap_an_c'] ?? '', 'is_correct' => ($orig_dung === 'C')],
                    ['orig_key' => 'D', 'text' => $bq['dap_an_d'] ?? '', 'is_correct' => ($orig_dung === 'D')],
                ];

                if ($tron_da) {
                    shuffle($opt_items);
                }

                $new_opts = [];
                $new_correct = 'A';
                foreach ($letters as $li => $lc) {
                    $new_opts[$lc] = $opt_items[$li]['text'] ?? '';
                    if (!empty($opt_items[$li]['is_correct'])) {
                        $new_correct = $lc;
                    }
                }

                $matrix[] = [
                    'stt' => $idx + 1,
                    'orig_id' => $bq['id'],
                    'id' => $bq['id'],
                    'cau_hoi' => $bq['cau_hoi'],
                    'dap_an_a' => $new_opts['A'],
                    'dap_an_b' => $new_opts['B'],
                    'dap_an_c' => $new_opts['C'],
                    'dap_an_d' => $new_opts['D'],
                    'dap_an_dung' => $new_correct,
                ];
            }

            $json = json_encode($matrix, JSON_UNESCAPED_UNICODE);
            $ins = $db->prepare("INSERT INTO quiz_exam_codes (quiz_id, ma_de, matrix_data) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $qid, $ma_de, $json);
            $ins->execute();
        }
        $msg = "success:Đã tạo $so_ma_de mã đề thi và bảo toàn chính xác 100% đáp án đúng!";
    }
    header("Location: /tkb/teacher/quiz.php?tab=matrix&quiz_id=$qid&msg=" . urlencode($msg));
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// FETCH DATA
// ══════════════════════════════════════════════════════════════════════════
$res_m = $db->query("SELECT id, ten_mon, ma_mon FROM mon_hoc ORDER BY ten_mon");
$monList = $res_m ? $res_m->fetch_all(MYSQLI_ASSOC) : [];

$sql = "SELECT q.*, m.ten_mon,
        COALESCE(g.ho_ten, u.ho_ten, 'Giáo Viên') as ten_giang_vien,
        (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) as q_count,
        (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
        (SELECT COUNT(*) FROM quiz_exam_codes WHERE quiz_id = q.id) as code_count
    FROM quizzes q
    LEFT JOIN mon_hoc m ON q.mon_hoc_id = m.id
    LEFT JOIN giang_vien g ON q.giang_vien_id = g.id
    LEFT JOIN users u ON q.giang_vien_id = u.id WHERE 1=1";
if (!isAdmin() && $gv_id > 0) $sql .= " AND (q.giang_vien_id = $gv_id OR q.giang_vien_id = 0 OR q.giang_vien_id IS NULL)";
$sql .= " ORDER BY q.id DESC";
$quizzes = ($r = $db->query($sql)) ? $r->fetch_all(MYSQLI_ASSOC) : [];

$current_quiz = null; $quiz_questions = []; $exam_codes = []; $student_results = [];

if ($quiz_id > 0) {
    $stcq = $db->prepare("SELECT q.*, m.ten_mon, COALESCE(g.ho_ten,'Giáo Viên') as ten_giang_vien FROM quizzes q LEFT JOIN mon_hoc m ON q.mon_hoc_id=m.id LEFT JOIN giang_vien g ON q.giang_vien_id=g.id WHERE q.id=?");
    $stcq->bind_param("i", $quiz_id);
    $stcq->execute();
    $current_quiz = $stcq->get_result()->fetch_assoc();

    if ($current_quiz) {
        $quiz_questions = ($r = $db->query("SELECT * FROM quiz_questions WHERE quiz_id=$quiz_id ORDER BY id ASC")) ? $r->fetch_all(MYSQLI_ASSOC) : [];
        $exam_codes = ($r = $db->query("SELECT * FROM quiz_exam_codes WHERE quiz_id=$quiz_id ORDER BY ma_de ASC")) ? $r->fetch_all(MYSQLI_ASSOC) : [];
        $student_results = ($r = $db->query("SELECT qa.*, s.ho_ten as student_name, s.ma_sv, s.lop FROM quiz_attempts qa LEFT JOIN students s ON qa.student_id=s.id WHERE qa.quiz_id=$quiz_id ORDER BY qa.attempted_at DESC")) ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
}

// KPI
$total_quizzes = count($quizzes);
$total_questions = $total_attempts = 0;
foreach ($quizzes as $qz) { $total_questions += (int)$qz['q_count']; $total_attempts += (int)$qz['attempt_count']; }

$msgType = $msgText = '';
if ($msg) { $parts = explode(':', $msg, 2); $msgType = $parts[0]; $msgText = $parts[1] ?? ''; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quiz Trắc Nghiệm — Hệ Thống Soạn Đề AI Thông Minh</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
:root {
    /* ══════════════════════════════════════════════════════════════
       DEFAULT: DEEP COSMIC LOFI DARK THEME (Matches Admin/Teacher Portal)
       ══════════════════════════════════════════════════════════════ */
    --quiz-bg: #090514;
    --quiz-card: #140d27;
    --quiz-card-sub: rgba(255, 255, 255, 0.05);
    --quiz-border: rgba(168, 85, 247, 0.2);
    --quiz-border-hover: rgba(192, 132, 252, 0.45);
    --quiz-purple: #a855f7;
    --quiz-purple-light: rgba(147, 51, 234, 0.2);
    --quiz-blue: #38bdf8;
    --quiz-blue-light: rgba(2, 132, 199, 0.2);
    --quiz-green: #34d399;
    --quiz-green-light: rgba(5, 150, 105, 0.2);
    --quiz-red: #f87171;
    --quiz-red-light: rgba(220, 38, 38, 0.2);
    --quiz-yellow: #fbbf24;
    --quiz-text: #f3e8ff;
    --quiz-muted: #a79bb7;
    --quiz-input: #0c0717;
    --quiz-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
    --quiz-opt-bg: rgba(255, 255, 255, 0.04);
    --quiz-opt-border: rgba(168, 85, 247, 0.25);
    --quiz-table-th: #0f0a1e;
    --quiz-table-hover: rgba(168, 85, 247, 0.08);
}

body.adm-light-mode {
    /* ══════════════════════════════════════════════════════════════
       LIGHT THEME (Crisp Modern White & Slate)
       ══════════════════════════════════════════════════════════════ */
    --quiz-bg: #f8fafc;
    --quiz-card: #ffffff;
    --quiz-card-sub: #f1f5f9;
    --quiz-border: #e2e8f0;
    --quiz-border-hover: #cbd5e1;
    --quiz-purple: #7c3aed;
    --quiz-purple-light: #f5f3ff;
    --quiz-blue: #0284c7;
    --quiz-blue-light: #f0f9ff;
    --quiz-green: #059669;
    --quiz-green-light: #ecfdf5;
    --quiz-red: #dc2626;
    --quiz-red-light: #fef2f2;
    --quiz-yellow: #d97706;
    --quiz-text: #0f172a;
    --quiz-muted: #64748b;
    --quiz-input: #ffffff;
    --quiz-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    --quiz-opt-bg: #f8fafc;
    --quiz-opt-border: #e2e8f0;
    --quiz-table-th: #f8fafc;
    --quiz-table-hover: #f8fafc;
}

body {
    background: var(--quiz-bg) !important;
    color: var(--quiz-text) !important;
    font-family: 'Plus Jakarta Sans', sans-serif;
    transition: background 0.3s ease, color 0.3s ease;
}
.main-content {
    background: var(--quiz-bg) !important;
    color: var(--quiz-text) !important;
    transition: background 0.3s ease, color 0.3s ease;
}

/* Page Header */
.page-title {
    color: var(--quiz-text) !important;
    font-weight: 800;
}
.page-sub {
    color: var(--quiz-muted) !important;
    font-size: 13.5px;
    margin-top: 4px;
}

/* Tab Navigation */
.qz-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
.qz-tab {
    padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 13px;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    border: 1px solid var(--quiz-border); background: var(--quiz-card);
    color: var(--quiz-muted); transition: all 0.25s cubic-bezier(.4,0,.2,1);
    box-shadow: var(--quiz-shadow);
}
.qz-tab:hover { background: var(--quiz-purple-light); color: var(--quiz-text); border-color: var(--quiz-border-hover); transform: translateY(-1px); }
.qz-tab.active {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff;
    border-color: #7c3aed; box-shadow: 0 4px 16px rgba(124, 58, 237, 0.38);
}

/* KPI Cards */
.qz-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.qz-kpi {
    background: var(--quiz-card); border: 1px solid var(--quiz-border); border-radius: 16px;
    padding: 22px; position: relative; overflow: hidden;
    box-shadow: var(--quiz-shadow);
    transition: all 0.25s ease;
}
.qz-kpi:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.15); border-color: var(--quiz-border-hover); }
.qz-kpi::after {
    content: ''; position: absolute; top: 0; right: 0; width: 90px; height: 90px;
    border-radius: 50%; filter: blur(40px); opacity: 0.15; pointer-events: none;
}
.qz-kpi:nth-child(1)::after { background: #8b5cf6; }
.qz-kpi:nth-child(2)::after { background: #0284c7; }
.qz-kpi:nth-child(3)::after { background: #10b981; }
.qz-kpi-label { font-size: 12px; font-weight: 700; color: var(--quiz-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.qz-kpi-value { font-size: 32px; font-weight: 900; margin-top: 6px; }

/* Quiz Cards Grid */
.qz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
.qz-card {
    background: var(--quiz-card); border: 1px solid var(--quiz-border); border-radius: 18px;
    padding: 24px; transition: all 0.3s cubic-bezier(.4,0,.2,1); position: relative;
    box-shadow: var(--quiz-shadow);
}
.qz-card:hover { border-color: var(--quiz-border-hover); transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.25); }
.qz-card-title { font-size: 16px; font-weight: 800; color: var(--quiz-text); line-height: 1.45; margin-bottom: 10px; }
.qz-badge {
    display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700;
    padding: 4px 10px; border-radius: 8px;
}
.qz-badge-purple { background: var(--quiz-purple-light); color: var(--quiz-purple); border: 1px solid var(--quiz-border); }
.qz-badge-blue { background: var(--quiz-blue-light); color: var(--quiz-blue); border: 1px solid rgba(56, 189, 248, 0.3); }
.qz-badge-green { background: var(--quiz-green-light); color: var(--quiz-green); border: 1px solid rgba(16, 185, 129, 0.3); }
.qz-badge-red { background: var(--quiz-red-light); color: var(--quiz-red); border: 1px solid rgba(239, 68, 68, 0.3); }

/* Action Buttons */
.qz-card-actions {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
    border-top: 1px solid var(--quiz-border); padding-top: 16px; margin-top: 14px;
}
.qz-action-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 8px 12px; border-radius: 10px; font-size: 12px; font-weight: 700;
    text-decoration: none; transition: all 0.2s ease; cursor: pointer; border: 1px solid transparent;
}
.qz-action-btn:hover { transform: translateY(-1px); }
.qz-btn-edit { background: var(--quiz-purple-light); color: var(--quiz-purple); border-color: var(--quiz-border); }
.qz-btn-edit:hover { background: rgba(147, 51, 234, 0.32); color: #fff; }
.qz-btn-shuffle { background: var(--quiz-blue-light); color: var(--quiz-blue); border-color: rgba(56, 189, 248, 0.3); }
.qz-btn-shuffle:hover { background: rgba(2, 132, 199, 0.32); color: #fff; }
.qz-btn-scores { background: var(--quiz-green-light); color: var(--quiz-green); border-color: rgba(16, 185, 129, 0.3); }
.qz-btn-scores:hover { background: rgba(5, 150, 105, 0.32); color: #fff; }
.qz-btn-delete { background: var(--quiz-red-light); color: var(--quiz-red); border-color: rgba(239, 68, 68, 0.3); }
.qz-btn-delete:hover { background: rgba(220, 38, 38, 0.32); color: #fff; }

/* Form Inputs */
.qz-input, .qz-select, .qz-textarea {
    width: 100%; background: var(--quiz-input); border: 1px solid var(--quiz-border);
    color: var(--quiz-text); border-radius: 10px; padding: 10px 14px; font-size: 13.5px;
    font-family: 'Plus Jakarta Sans', sans-serif; transition: all 0.2s;
    box-sizing: border-box;
}
.qz-input:focus, .qz-select:focus, .qz-textarea:focus {
    border-color: #a855f7; outline: none;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.25);
}
.qz-textarea { font-family: 'JetBrains Mono', 'Fira Code', monospace; resize: vertical; }
.qz-label { display: block; font-size: 12.5px; font-weight: 700; color: var(--quiz-text); margin-bottom: 6px; }

/* Mode Tabs */
.qz-mode-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
.qz-mode-tab {
    padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 700;
    cursor: pointer; border: 1px solid var(--quiz-border);
    background: var(--quiz-card); color: var(--quiz-muted); transition: all 0.2s;
    box-shadow: var(--quiz-shadow);
}
.qz-mode-tab.active {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: #fff; border-color: #7c3aed; box-shadow: 0 4px 16px rgba(124, 58, 237, 0.35);
}
.qz-mode-tab:hover:not(.active) { background: var(--quiz-purple-light); color: var(--quiz-text); border-color: var(--quiz-border-hover); }

/* AI Studio Container */
.ai-studio-box {
    background: var(--quiz-card);
    border: 1px solid var(--quiz-border); border-radius: 20px; padding: 26px;
    margin-bottom: 22px; position: relative; overflow: hidden;
    box-shadow: var(--quiz-shadow);
}
.ai-studio-box::before {
    content: ''; position: absolute; top: -60px; right: -60px; width: 160px; height: 160px;
    background: #c084fc; filter: blur(60px); opacity: 0.12; pointer-events: none;
}
.ai-chip {
    padding: 6px 13px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
    cursor: pointer; background: var(--quiz-card-sub); border: 1px solid var(--quiz-border);
    color: var(--quiz-text); transition: all 0.2s; user-select: none; display: inline-block; margin: 3px 2px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.ai-chip:hover { background: #7c3aed; color: #fff; border-color: #7c3aed; }

/* AI Question Preview Card */
.ai-preview-card {
    background: var(--quiz-card); border: 1px solid var(--quiz-border);
    border-radius: 16px; padding: 18px; margin-bottom: 14px; position: relative;
    transition: all 0.2s; box-shadow: var(--quiz-shadow);
}
.ai-preview-card:hover { border-color: var(--quiz-border-hover); box-shadow: 0 6px 20px rgba(139,92,246,0.15); }
.ai-preview-num {
    font-size: 13px; font-weight: 800; color: var(--quiz-purple); display: flex;
    align-items: center; justify-content: space-between; margin-bottom: 8px;
}

/* Question Cards in Manager */
.qz-question-card {
    background: var(--quiz-card); border: 1px solid var(--quiz-border); border-radius: 16px;
    padding: 22px; margin-bottom: 14px; transition: border-color 0.2s, box-shadow 0.2s;
    box-shadow: var(--quiz-shadow);
}
.qz-question-card:hover { border-color: var(--quiz-border-hover); box-shadow: 0 6px 18px rgba(0,0,0,0.1); }
.qz-question-title { font-size: 14.5px; font-weight: 800; color: var(--quiz-text); line-height: 1.5; margin-bottom: 12px; }
.qz-question-num { color: var(--quiz-purple); margin-right: 6px; font-size: 15px; font-weight: 800; }

/* Option boxes */
.qz-opts { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.qz-opt {
    background: var(--quiz-opt-bg); border: 1.5px solid var(--quiz-opt-border); border-radius: 10px;
    padding: 11px 15px; font-size: 13px; color: var(--quiz-text); display: flex; align-items: center; gap: 8px;
    cursor: pointer; transition: all 0.2s; position: relative; user-select: none;
}
.qz-opt:hover { border-color: #a855f7; background: var(--quiz-purple-light); color: var(--quiz-text); }
.qz-opt.correct {
    border-color: #10b981; background: var(--quiz-green-light);
    color: #34d399; font-weight: 700;
}
body.adm-light-mode .qz-opt.correct {
    color: #065f46;
}
.qz-opt.correct::after {
    content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
    margin-left: auto; font-size: 12px; color: #10b981;
}
.qz-opt-letter { font-weight: 800; min-width: 22px; color: var(--quiz-muted); }
.qz-opt.correct .qz-opt-letter { color: #34d399; }
body.adm-light-mode .qz-opt.correct .qz-opt-letter { color: #065f46; }

/* Answer Key Box */
.qz-answer-key-box {
    margin-top: 16px; background: var(--quiz-green-light); border: 1px dashed rgba(16, 185, 129, 0.4);
    border-radius: 14px; padding: 16px;
}
.qz-answer-key-label {
    display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 13px;
    color: var(--quiz-green); margin-bottom: 8px;
}

/* Submit Button */
.qz-submit-btn {
    width: 100%; padding: 14px; border: none; border-radius: 14px; font-size: 15px;
    font-weight: 800; cursor: pointer; transition: all 0.25s;
    background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    font-family: 'Plus Jakarta Sans', sans-serif; text-decoration: none;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.25);
}
.qz-submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(124, 58, 237, 0.38); }

.qz-ai-btn {
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    box-shadow: 0 4px 16px rgba(236,72,153,0.25);
}
.qz-ai-btn:hover {
    box-shadow: 0 8px 26px rgba(236,72,153,0.4);
}

/* Empty State */
.qz-empty {
    background: var(--quiz-card); border: 1px dashed var(--quiz-border); border-radius: 18px;
    padding: 60px 30px; text-align: center;
    box-shadow: var(--quiz-shadow);
}
.qz-empty i { font-size: 48px; color: var(--quiz-purple); margin-bottom: 16px; }
.qz-empty h3 { font-size: 18px; font-weight: 800; color: var(--quiz-text); margin-bottom: 6px; }
.qz-empty p { color: var(--quiz-muted); font-size: 13.5px; margin-bottom: 20px; }

/* Table */
.qz-table { width: 100%; border-collapse: collapse; font-size: 13px; background: var(--quiz-card); }
.qz-table th {
    padding: 14px 12px; text-align: left; color: var(--quiz-muted); font-weight: 700;
    background: var(--quiz-table-th); border-bottom: 2px solid var(--quiz-border);
}
.qz-table td {
    padding: 12px; border-bottom: 1px solid var(--quiz-border); color: var(--quiz-text);
}
.qz-table tr:hover td { background: var(--quiz-table-hover); }

/* Modal */
.qz-modal-overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(6px);
    z-index: 9999; align-items: center; justify-content: center;
}
.qz-modal-overlay.show { display: flex; }
.qz-modal {
    background: var(--quiz-card); border: 1px solid var(--quiz-border); border-radius: 20px;
    padding: 28px; width: 95%; max-width: 620px; max-height: 90vh; overflow-y: auto;
    box-shadow: var(--quiz-shadow);
}
.qz-modal-title { font-size: 18px; font-weight: 800; color: var(--quiz-text); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

/* Section panel */
.qz-panel {
    background: var(--quiz-card); border: 1px solid var(--quiz-border); border-radius: 18px; padding: 26px;
    box-shadow: var(--quiz-shadow);
}

/* Spinner / Animation */
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }
@keyframes pulseGlow { 0%, 100% { opacity: 0.6; transform: scale(1); } 50% { opacity: 1; transform: scale(1.05); } }
.pulse-glow { animation: pulseGlow 2s ease-in-out infinite; }

/* Responsive */
@media (max-width: 768px) {
    .qz-opts { grid-template-columns: 1fr; }
    .qz-card-actions { grid-template-columns: 1fr; }
    .qz-grid { grid-template-columns: 1fr; }
    .qz-two-col { grid-template-columns: 1fr !important; }
}
</style>
</head>
<body class="<?= isAdmin() ? 'admin-portal' : '' ?>">

<?php if (isAdmin()): ?>
    <?php include '../includes/admin_nav.php'; ?>
    <div class="main-content">
<?php else: ?>
    <?php include '../includes/teacher_nav.php'; ?>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header" style="margin-bottom: 22px;">
    <div>
        <h1 class="page-title" style="display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-brain" style="color:var(--quiz-purple);"></i> Quiz Trắc Nghiệm Thông Minh
        </h1>
        <p class="page-sub">Trí tuệ nhân tạo AI tạo đề tự động • Trích xuất file Word/PDF • Trộn mã đề thi xáo trộn</p>
    </div>
    <a href="?tab=create" class="qz-submit-btn" style="width:auto;padding:10px 22px;font-size:13px;border-radius:12px;">
        <i class="fa-solid fa-plus"></i> Tạo Bộ Đề Mới
    </a>
</div>

<!-- Alert -->
<?php if ($msgText): ?>
<div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px;">
    <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
    <?= htmlspecialchars($msgText) ?>
</div>
<?php endif; ?>

<!-- Navigation Tabs -->
<div class="qz-tabs">
    <a href="?tab=list" class="qz-tab <?= $tab==='list' ? 'active' : '' ?>">
        <i class="fa-solid fa-layer-group"></i> Danh Sách (<?= $total_quizzes ?>)
    </a>
    <a href="?tab=create" class="qz-tab <?= $tab==='create' ? 'active' : '' ?>">
        <i class="fa-solid fa-wand-magic-sparkles"></i> Tạo Đề Mới (AI / Tệp / Dán)
    </a>
    <?php if ($current_quiz): ?>
    <a href="?tab=questions&quiz_id=<?= $quiz_id ?>" class="qz-tab <?= $tab==='questions' ? 'active' : '' ?>">
        <i class="fa-solid fa-pen-to-square"></i> Soạn Câu Hỏi (<?= count($quiz_questions) ?>)
    </a>
    <a href="?tab=matrix&quiz_id=<?= $quiz_id ?>" class="qz-tab <?= $tab==='matrix' ? 'active' : '' ?>">
        <i class="fa-solid fa-shuffle"></i> Trộn Mã Đề (<?= count($exam_codes) ?>)
    </a>
    <a href="?tab=results&quiz_id=<?= $quiz_id ?>" class="qz-tab <?= $tab==='results' ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-column"></i> Kết Quả (<?= count($student_results) ?>)
    </a>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- TAB 1: DANH SÁCH BỘ ĐỀ QUIZ -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<?php if ($tab === 'list'): ?>

    <!-- KPI -->
    <div class="qz-kpi-grid">
        <div class="qz-kpi">
            <div class="qz-kpi-label">Tổng bộ đề Quiz</div>
            <div class="qz-kpi-value" style="color:var(--quiz-text);"><?= $total_quizzes ?></div>
        </div>
        <div class="qz-kpi">
            <div class="qz-kpi-label">Tổng câu hỏi trắc nghiệm</div>
            <div class="qz-kpi-value" style="color:var(--quiz-blue);"><?= $total_questions ?></div>
        </div>
        <div class="qz-kpi">
            <div class="qz-kpi-label">Lượt thi của sinh viên</div>
            <div class="qz-kpi-value" style="color:var(--quiz-green);"><?= $total_attempts ?></div>
        </div>
    </div>

    <?php if (empty($quizzes)): ?>
        <div class="qz-empty">
            <i class="fa-solid fa-brain"></i>
            <h3>Chưa có bộ đề Quiz nào</h3>
            <p>Tạo đề trắc nghiệm cực nhanh bằng Trí tuệ nhân tạo AI, dán Word hoặc tài liệu PDF.</p>
            <a href="?tab=create" class="qz-submit-btn" style="width:auto;display:inline-flex;padding:10px 28px;font-size:14px;">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Tạo Đề Bằng AI Ngay
            </a>
        </div>
    <?php else: ?>
        <div class="qz-grid">
            <?php foreach ($quizzes as $qz): ?>
            <div class="qz-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <span class="qz-badge qz-badge-blue"><i class="fa-solid fa-book"></i> <?= htmlspecialchars($qz['ten_mon'] ?: 'Chung') ?></span>
                    <span class="qz-badge qz-badge-purple"><i class="fa-solid fa-circle-question"></i> <?= $qz['q_count'] ?> câu</span>
                </div>
                <div class="qz-card-title"><?= htmlspecialchars($qz['tieu_de']) ?></div>
                <div style="font-size:12px;color:var(--quiz-muted);display:flex;gap:12px;flex-wrap:wrap;margin-bottom:4px;">
                    <span><i class="fa-solid fa-clock"></i> <?= (int)($qz['thoi_gian_lam_bai'] ?? 15) ?> phút</span>
                    <span><i class="fa-solid fa-shuffle"></i> <?= (int)$qz['code_count'] ?> mã đề</span>
                    <span><i class="fa-solid fa-user-check"></i> <?= (int)$qz['attempt_count'] ?> lượt thi</span>
                </div>
                <div class="qz-card-actions">
                    <a href="?tab=questions&quiz_id=<?= $qz['id'] ?>" class="qz-action-btn qz-btn-edit"><i class="fa-solid fa-pen-to-square"></i> Soạn Câu Hỏi</a>
                    <a href="?tab=matrix&quiz_id=<?= $qz['id'] ?>" class="qz-action-btn qz-btn-shuffle"><i class="fa-solid fa-shuffle"></i> Trộn Mã Đề</a>
                    <a href="?tab=results&quiz_id=<?= $qz['id'] ?>" class="qz-action-btn qz-btn-scores"><i class="fa-solid fa-chart-column"></i> Xem Điểm</a>
                    <a href="?action=delete_quiz&quiz_id=<?= $qz['id'] ?>" onclick="return confirm('Xóa bộ Quiz này cùng toàn bộ câu hỏi và bài nộp?')" class="qz-action-btn qz-btn-delete"><i class="fa-solid fa-trash"></i> Xóa Đề</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- TAB 2: TẠO ĐỀ MỚI (AI STUDIO & FILE & TEXT) -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'create'): ?>

    <div class="qz-panel" style="max-width:920px;margin:0 auto;">
        <div style="font-size:19px;font-weight:800;color:var(--quiz-text);margin-bottom:22px;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--quiz-purple);"></i> Tạo Bộ Đề Quiz Trắc Nghiệm Mới
        </div>

        <!-- Mode Selector -->
        <label class="qz-label" style="font-size:13px;color:var(--quiz-text);font-weight:800;margin-bottom:10px;">
            Chọn Phương Thức Tạo Đề:
        </label>
        <div class="qz-mode-tabs">
            <button type="button" class="qz-mode-tab active" onclick="switchMode('ai',this)">
                <i class="fa-solid fa-robot" style="color:#f472b6;"></i> 1. Trí Tuệ Nhân Tạo AI Tạo Tự Động
            </button>
            <button type="button" class="qz-mode-tab" onclick="switchMode('text',this)">
                <i class="fa-solid fa-paste"></i> 2. Dán Văn Bản / Word
            </button>
            <button type="button" class="qz-mode-tab" onclick="switchMode('file',this)">
                <i class="fa-solid fa-file-arrow-up"></i> 3. Tải Lên Tệp Word (.docx)/PDF
            </button>
            <button type="button" class="qz-mode-tab" onclick="switchMode('manual',this)">
                <i class="fa-solid fa-pen"></i> 4. Tạo Khung Đề Rỗng
            </button>
        </div>

        <!-- ══ MODE 1: AI STUDIO ══ -->
        <div id="mode_ai" style="display:block;">
            <div class="ai-studio-box">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#ec4899,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div>
                        <div style="font-size:16px;font-weight:800;color:var(--quiz-text);">AI Quiz Studio — Sinh Đề Thi Tự Động</div>
                        <div style="font-size:12px;color:var(--quiz-muted);">Chỉ cần nhập chủ đề hoặc dán giáo trình, AI sẽ tự động ra đề và phân bổ đều đáp án A, B, C, D</div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                    <div>
                        <label class="qz-label">Chọn Môn Học *</label>
                        <select id="ai_mon_hoc_id" class="qz-select" required>
                            <option value="">-- Chọn môn học --</option>
                            <?php foreach ($monList as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['ten_mon']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="qz-label">Tên Bộ Đề Quiz *</label>
                        <input type="text" id="ai_tieu_de" class="qz-input" placeholder="VD: Kiểm tra trắc nghiệm Chương 1">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label class="qz-label">Số Lượng Câu Hỏi</label>
                        <select id="ai_so_cau" class="qz-select" onchange="updateAiDuration(this.value)">
                            <option value="5">5 câu (Nhanh)</option>
                            <option value="10" selected>10 câu (Tiêu chuẩn)</option>
                            <option value="15">15 câu</option>
                            <option value="20">20 câu</option>
                            <option value="30">30 câu</option>
                            <option value="40">40 câu (Đề thi chuẩn)</option>
                        </select>
                    </div>
                    <div>
                        <label class="qz-label">Mức Độ Phân Khúc</label>
                        <select id="ai_level" class="qz-select">
                            <option value="mixed" selected>Hỗn hợp (40% Dễ - 40% Vừa - 20% Khó)</option>
                            <option value="easy">Cơ bản (Nhận biết & Thông hiểu)</option>
                            <option value="hard">Nâng cao (Vận dụng & Phân tích sâu)</option>
                        </select>
                    </div>
                    <div>
                        <label class="qz-label">Thời Gian Làm Bài (Phút)</label>
                        <input type="number" id="ai_thoi_gian" value="15" min="5" max="180" class="qz-input">
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="qz-label">Chủ Đề Cần Ra Đề *</label>
                    <input type="text" id="ai_topic" class="qz-input" placeholder="VD: Lập trình Web PHP cơ bản và xử lý Form, Cơ sở dữ liệu MySQL, Mạng máy tính...">
                    <div style="margin-top:8px;">
                        <span style="font-size:11px;color:#64748b;margin-right:6px;">Gợi ý nhanh:</span>
                        <span class="ai-chip" onclick="setAiTopic('Lập trình Web PHP và Kết nối MySQL')">PHP & MySQL</span>
                        <span class="ai-chip" onclick="setAiTopic('Lập trình Hướng đối tượng OOP trong C#/VB.Net')">OOP C#/VB.Net</span>
                        <span class="ai-chip" onclick="setAiTopic('Cơ sở dữ liệu và Truy vấn SQL Server')">SQL Database</span>
                        <span class="ai-chip" onclick="setAiTopic('Cấu trúc dữ liệu và Giải thuật căn bản')">CTDL & Giải thuật</span>
                        <span class="ai-chip" onclick="setAiTopic('Mạng máy tính và Mô hình OSI/TCP-IP')">Mạng máy tính</span>
                        <span class="ai-chip" onclick="setAiTopic('Tin học văn phòng Word, Excel, PowerPoint')">Tin học văn phòng</span>
                    </div>
                </div>

                <div style="margin-bottom:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label class="qz-label" style="margin:0;">Tài Liệu / Giáo Trình Nguồn (Tùy chọn)</label>
                        <span style="font-size:11px;color:#64748b;">AI sẽ ra đề bám sát 100% nội dung này</span>
                    </div>
                    <textarea id="ai_source_content" rows="3" class="qz-textarea" placeholder="Dán nội dung bài học, tóm tắt giáo trình, slide bài giảng vào đây nếu muốn AI ra đề thi bám sát tài liệu của bạn..."></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
                    <div>
                        <label class="qz-label">Giao Cho Lớp (Tùy chọn)</label>
                        <input type="text" id="ai_lop" class="qz-input" placeholder="VD: CNTT24A (để trống nếu không giao)">
                    </div>
                    <div>
                        <label class="qz-label">Ghi Chú / Mô Tả (Tùy chọn)</label>
                        <input type="text" id="ai_mo_ta" class="qz-input" placeholder="VD: Kiểm tra 15 phút đầu giờ">
                    </div>
                </div>

                <button type="button" onclick="startAiGeneration()" class="qz-submit-btn qz-ai-btn" id="btn_generate_ai" style="padding:15px;font-size:16px;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Bắt Đầu Sinh Đề Thi Bằng AI
                </button>
            </div>

            <!-- AI Progress / Loading Box -->
            <div id="ai_loading_box" style="display:none;background:var(--quiz-card);border:1px solid var(--quiz-border);border-radius:18px;padding:30px;text-align:center;margin-bottom:20px;box-shadow:var(--quiz-shadow);">
                <div style="font-size:42px;color:#ec4899;margin-bottom:14px;"><i class="fa-solid fa-atom spin"></i></div>
                <div style="font-size:18px;font-weight:800;color:var(--quiz-text);margin-bottom:6px;" id="ai_loading_text">Đang kết nối siêu trí tuệ AI...</div>
                <div style="font-size:13px;color:var(--quiz-muted);margin-bottom:18px;" id="ai_loading_sub">Hệ thống đang biên soạn câu hỏi, tạo phương án nhiễu & phân bổ đáp án...</div>
                <div style="width:100%;height:6px;background:var(--quiz-card-sub);border-radius:10px;overflow:hidden;max-width:380px;margin:0 auto;">
                    <div id="ai_progress_bar" style="width:20%;height:100%;background:linear-gradient(90deg,#ec4899,#8b5cf6);transition:width 0.4s ease;"></div>
                </div>
            </div>

            <!-- AI Live Preview & Editor Area -->
            <div id="ai_preview_area" style="display:none;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <div style="font-size:17px;font-weight:800;color:var(--quiz-text);display:flex;align-items:center;gap:8px;">
                            <i class="fa-solid fa-list-check" style="color:var(--quiz-green);"></i>
                            Xem Trước &amp; Chỉnh Sửa Câu Hỏi AI Vừa Tạo (<span id="ai_preview_count">0</span> câu)
                        </div>
                        <div style="font-size:12px;color:var(--quiz-muted);">Bạn có thể bấm trực tiếp vào đáp án A/B/C/D để đổi đáp án đúng hoặc sửa nội dung trước khi lưu.</div>
                    </div>
                    <button type="button" onclick="saveAiQuizToDb()" class="qz-submit-btn" style="width:auto;padding:10px 22px;background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 4px 16px rgba(16,185,129,0.3);">
                        <i class="fa-solid fa-circle-check"></i> Lưu Bộ Đề Này Vào Hệ Thống
                    </button>
                </div>

                <div id="ai_questions_list"></div>

                <button type="button" onclick="saveAiQuizToDb()" class="qz-submit-btn" style="margin-top:14px;padding:14px;background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 4px 16px rgba(16,185,129,0.3);">
                    <i class="fa-solid fa-circle-check"></i> Hoàn Tất &amp; Lưu Vào Cơ Sở Dữ Liệu
                </button>
            </div>
        </div>

        <!-- ══ MODE 2, 3, 4: STANDARD FORM ══ -->
        <form method="POST" action="?action=create_quiz" enctype="multipart/form-data" id="standard_create_form" style="display:none;">
            <input type="hidden" name="source_mode" id="selected_source_mode" value="text">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label class="qz-label">Chọn Môn Học *</label>
                    <select name="mon_hoc_id" class="qz-select" required>
                        <option value="">-- Chọn môn học --</option>
                        <?php foreach ($monList as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['ten_mon']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="qz-label">Tên Bộ Đề Quiz *</label>
                    <input type="text" name="tieu_de" class="qz-input" placeholder="Ví dụ: Kiểm tra Chương 1" required>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                <div>
                    <label class="qz-label">Giao Cho Lớp (Tùy chọn)</label>
                    <input type="text" name="lop" class="qz-input" placeholder="VD: CNTT24A (để trống nếu không giao)">
                </div>
                <div>
                    <label class="qz-label">Thời Gian Làm Bài (Phút)</label>
                    <input type="number" name="thoi_gian_lam_bai" value="15" min="5" max="180" class="qz-input">
                </div>
            </div>

            <!-- Mode 2: Paste Text -->
            <div id="mode_text" style="display:none;margin-bottom:18px;">
                <div style="background:var(--quiz-purple-light);border:1px solid var(--quiz-border);border-radius:12px;padding:12px 16px;margin-bottom:12px;display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#a855f7,#6366f1);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div style="font-size:12.5px;color:var(--quiz-text);line-height:1.5;">
                        <strong style="color:var(--quiz-purple);">AI Tự Động Phân Tích Đáp Án:</strong> Nếu đề thi không có sẵn đáp án (không in đậm, không ghi "Đáp án: ..."), hệ thống sẽ <strong>tự động dùng AI phân tích câu hỏi &amp; chọn đáp án chính xác nhất (A, B, C, D)</strong> cho bạn.
                    </div>
                </div>
                <label class="qz-label" style="color:var(--quiz-muted);font-size:11.5px;">Dán nội dung câu hỏi trắc nghiệm (Hệ thống tự tách Câu 1, A, B, C, D):</label>
                <textarea name="raw_text" rows="8" class="qz-textarea" placeholder="Câu 1: Visual Studio .Net là:
a) Hệ điều hành mới của Microsoft
b) Trình soạn thảo văn bản
c) Môi trường phát triển tích hợp (IDE)
d) Trình duyệt web

Câu 2: Phiên bản nào hỗ trợ đa nền tảng?
A. Visual Studio 2010
B. Visual Studio 2013
C. Visual Studio 2017
D. Visual Studio 2015"></textarea>
            </div>

            <!-- Mode 3: File Upload -->
            <div id="mode_file" style="display:none;margin-bottom:18px;">
                <div style="background:var(--quiz-purple-light);border:1px solid var(--quiz-border);border-radius:12px;padding:12px 16px;margin-bottom:12px;display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#a855f7,#6366f1);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div style="font-size:12.5px;color:var(--quiz-text);line-height:1.5;">
                        <strong style="color:var(--quiz-purple);">AI Tự Động Phân Tích Đáp Án:</strong> Tải lên tệp đề thi Word hoặc PDF. Nếu tệp không có sẵn đáp án, AI sẽ tự động phân tích và gán đáp án chính xác cho từng câu hỏi.
                    </div>
                </div>
                <label class="qz-label" style="color:var(--quiz-muted);font-size:11.5px;">Chọn tệp Word (.docx), PDF (.pdf) hoặc Text (.txt):</label>
                <input type="file" name="quiz_file" class="qz-input" accept=".docx,.pdf,.txt">
            </div>

            <!-- Mode 4: Manual -->
            <div id="mode_manual" style="display:none;margin-bottom:18px;color:var(--quiz-muted);font-size:13px;padding:14px;background:var(--quiz-card);border:1px solid var(--quiz-border);border-radius:10px;">
                <i class="fa-solid fa-circle-info" style="color:var(--quiz-blue);"></i>
                Bộ Quiz rỗng sẽ được tạo trước, sau đó bạn thêm từng câu hỏi theo ý muốn.
            </div>

            <div id="ai_solve_toggle_box" style="margin-bottom:18px;padding:12px 14px;background:var(--quiz-card-sub);border:1px solid var(--quiz-border);border-radius:10px;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;color:var(--quiz-text);font-weight:700;">
                    <input type="checkbox" name="force_ai_solve" value="1" checked style="width:17px;height:17px;accent-color:#7c3aed;">
                    <span><i class="fa-solid fa-wand-magic-sparkles" style="color:#7c3aed;"></i> Bật AI Thẩm Định &amp; Tự Giải Toàn Bộ Đáp Án (Đảm bảo chuẩn xác 100%)</span>
                </label>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="qz-submit-btn">
                    <i class="fa-solid fa-circle-check"></i> Hoàn Tất &amp; Khởi Tạo Bộ Quiz
                </button>
            </div>
        </form>
    </div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- TAB 3: SOẠN & QUẢN LÝ CÂU HỎI TRONG ĐỀ -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'questions' && $current_quiz): ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="?tab=list" class="qz-action-btn" style="background:var(--quiz-card);color:var(--quiz-text);border-color:var(--quiz-border);">
                <i class="fa-solid fa-arrow-left"></i> Quay lại
            </a>
            <h2 style="font-size:17px;font-weight:800;color:var(--quiz-text);margin:0;">
                <?= htmlspecialchars($current_quiz['tieu_de']) ?>
                <span class="qz-badge qz-badge-purple" style="margin-left:8px;"><?= count($quiz_questions) ?> câu hỏi</span>
            </h2>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" onclick="openImportModal()" class="qz-action-btn" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;" title="Tải tệp Word/PDF hoặc dán đề để AI tự động giải">
                <i class="fa-solid fa-file-arrow-up"></i> Nạp Tệp / Dán Đề (AI Tự Giải)
            </button>
            <button type="button" onclick="openAiAddModal()" class="qz-action-btn qz-ai-btn" style="color:#fff;border-radius:10px;padding:8px 14px;">
                <i class="fa-solid fa-wand-magic-sparkles"></i> AI Sinh Thêm Câu Hỏi
            </button>
            <button type="button" onclick="aiSolveAllQuestions(<?= $quiz_id ?>)" id="btn_ai_solve_all" class="qz-action-btn" style="background:#fdf2f8;color:#db2777;border-color:#fbcfe8;" title="Dùng AI tự động giải & phân tích đáp án cho toàn bộ câu hỏi trong đề">
                <i class="fa-solid fa-brain"></i> AI Giải Toàn Đề
            </button>
            <a href="?tab=matrix&quiz_id=<?= $quiz_id ?>" class="qz-action-btn qz-btn-shuffle"><i class="fa-solid fa-shuffle"></i> Trộn Mã Đề</a>
            <a href="/tkb/teacher/in_de_thi.php?quiz_id=<?= $quiz_id ?>" target="_blank" class="qz-action-btn qz-btn-scores"><i class="fa-solid fa-print"></i> In Đề Thi</a>
        </div>
    </div>

    <!-- Questions List in Quiz (Full Width) -->
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="font-size:15px;font-weight:800;color:var(--quiz-text);">
                <i class="fa-solid fa-list-ol" style="color:var(--quiz-purple);"></i> Danh Sách Câu Hỏi Trong Đề (<?= count($quiz_questions) ?>)
            </div>
            <?php if (!empty($quiz_questions)): ?>
            <div style="font-size:12px;color:var(--quiz-muted);">
                <i class="fa-solid fa-circle-check" style="color:var(--quiz-green);"></i> Bấm vào phương án để đổi đáp án đúng &bull; Bấm <strong>AI Giải</strong> để AI tự tìm đáp án
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($quiz_questions)): ?>
            <div class="qz-empty" style="padding:60px 20px;text-align:center;background:var(--quiz-card);border:1px dashed var(--quiz-border);border-radius:16px;">
                <div style="width:70px;height:70px;border-radius:20px;background:var(--quiz-purple-light);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;color:var(--quiz-purple);font-size:32px;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <h3 style="font-size:18px;font-weight:800;color:var(--quiz-text);margin-bottom:8px;">Chưa có câu hỏi nào trong bộ đề</h3>
                <p style="color:var(--quiz-muted);max-width:520px;margin:0 auto 24px auto;font-size:13.5px;line-height:1.6;">
                    Hãy tải lên tệp đề thi Word / PDF hoặc dán văn bản câu hỏi. AI sẽ tự động đọc hiểu, bóc tách câu hỏi và gán đáp án chính xác nhất!
                </p>
                <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <button type="button" onclick="openImportModal()" class="qz-submit-btn" style="width:auto;padding:12px 24px;border-radius:12px;font-size:14px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);">
                        <i class="fa-solid fa-file-arrow-up"></i> Tải Lên Tệp Đề Thi Word/PDF (AI Tự Giải)
                    </button>
                    <button type="button" onclick="openAiAddModal()" class="qz-action-btn qz-ai-btn" style="color:#fff;border-radius:12px;padding:12px 20px;font-size:14px;">
                        <i class="fa-solid fa-atom"></i> AI Tự Soạn Câu Hỏi Mới
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:1fr;gap:16px;">
            <?php foreach ($quiz_questions as $idx => $q): ?>
            <div class="qz-question-card" id="qz_card_<?= $q['id'] ?>" style="margin-bottom:0;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:10px;">
                    <div class="qz-question-title" style="font-size:15px;line-height:1.5;">
                        <span class="qz-question-num">Câu <?= $idx + 1 ?>:</span>
                        <?= htmlspecialchars($q['cau_hoi']) ?>
                    </div>
                    <div style="display:flex;gap:6px;flex-shrink:0;">
                        <button type="button" onclick="quickAiSolveQuestion(<?= $q['id'] ?>, this)" class="qz-action-btn" style="padding:6px 12px;font-size:12px;font-weight:700;background:#fdf2f8;color:#db2777;border-color:#fbcfe8;" title="Dùng AI phân tích đáp án cho câu này">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> AI Giải
                        </button>
                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($q, JSON_UNESCAPED_UNICODE)) ?>, <?= $quiz_id ?>)" class="qz-action-btn" style="padding:6px 10px;font-size:12px;background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe;" title="Sửa câu hỏi">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <a href="?action=delete_question&quiz_id=<?= $quiz_id ?>&question_id=<?= $q['id'] ?>" onclick="return confirm('Xóa câu hỏi này?')" class="qz-action-btn" style="padding:6px 10px;font-size:12px;background:#fef2f2;color:var(--quiz-red);border-color:#fecaca;" title="Xóa">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                </div>
                <div class="qz-opts">
                    <?php foreach (['A','B','C','D'] as $l):
                        $key = 'dap_an_' . strtolower($l);
                        if (empty($q[$key])) continue;
                        $isCorrect = $q['dap_an_dung'] === $l;
                    ?>
                    <div class="qz-opt <?= $isCorrect ? 'correct' : '' ?>" data-letter="<?= $l ?>" onclick="quickSetAnswer(<?= $q['id'] ?>,'<?= $l ?>',this)" title="Bấm để chọn <?= $l ?> là đáp án đúng">
                        <span class="qz-opt-letter"><?= $l ?>.</span>
                        <?= htmlspecialchars($q[$key]) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Import / Upload Questions Modal -->
    <div class="qz-modal-overlay" id="importModal">
        <div class="qz-modal" style="max-width:620px;">
            <div class="qz-modal-title">
                <i class="fa-solid fa-file-arrow-up" style="color:#0284c7;"></i> Nạp Thêm Đề Thi (AI Tự Giải Đáp Án)
                <button onclick="closeImportModal()" style="margin-left:auto;background:none;border:none;color:#94a3b8;font-size:20px;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="?action=bulk_add_questions" enctype="multipart/form-data">
                <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
                
                <div style="background:var(--quiz-purple-light);border:1px solid var(--quiz-border);border-radius:12px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#a855f7,#6366f1);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div style="font-size:12.5px;color:var(--quiz-text);line-height:1.5;">
                        <strong style="color:var(--quiz-purple);">AI Tự Động Phân Tích &amp; Giải Đề:</strong> Bạn chỉ cần tải tệp Word/PDF hoặc dán nội dung. AI sẽ tự động đọc câu hỏi, bóc tách phương án và chọn đáp án chính xác nhất!
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="qz-label">Cách 1: Chọn Tệp Word (.docx), PDF (.pdf) hoặc Text (.txt)</label>
                    <input type="file" name="quiz_file" class="qz-input" accept=".docx,.pdf,.txt">
                </div>

                <div style="text-align:center;color:var(--quiz-muted);font-size:12px;margin:12px 0;font-weight:700;">--- HOẶC ---</div>

                <div style="margin-bottom:18px;">
                    <label class="qz-label">Cách 2: Dán Văn Bản Câu Hỏi Trắc Nghiệm</label>
                    <textarea name="raw_text" rows="5" class="qz-textarea" placeholder="Câu 1: ... A. ... B. ... C. ... D. ..."></textarea>
                </div>

                <div style="margin-bottom:16px;padding:10px 12px;background:var(--quiz-card-sub);border:1px solid var(--quiz-border);border-radius:10px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:12.5px;color:var(--quiz-text);font-weight:700;">
                        <input type="checkbox" name="force_ai_solve" value="1" checked style="width:16px;height:16px;accent-color:#7c3aed;">
                        <span><i class="fa-solid fa-wand-magic-sparkles" style="color:#7c3aed;"></i> Bật AI Thẩm Định &amp; Tự Giải Toàn Bộ Đáp Án (Chuẩn xác 100%)</span>
                    </label>
                </div>

                <button type="submit" class="qz-submit-btn" style="padding:12px;font-size:14px;border-radius:10px;">
                    <i class="fa-solid fa-bolt"></i> Nạp Vào Bộ Đề &amp; AI Tự Giải Ngay
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Question Modal -->
    <div class="qz-modal-overlay" id="editModal">
        <div class="qz-modal">
            <div class="qz-modal-title">
                <i class="fa-solid fa-pen-to-square" style="color:var(--quiz-purple);"></i> Sửa Câu Hỏi
                <button onclick="closeEditModal()" style="margin-left:auto;background:none;border:none;color:#94a3b8;font-size:20px;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="?action=edit_question" id="editForm">
                <input type="hidden" name="quiz_id" id="edit_quiz_id">
                <input type="hidden" name="question_id" id="edit_question_id">
                <div style="margin-bottom:12px;">
                    <label class="qz-label">Nội dung câu hỏi</label>
                    <textarea name="cau_hoi" id="edit_cau_hoi" rows="3" class="qz-textarea" style="font-family:'Plus Jakarta Sans',sans-serif;" required></textarea>
                </div>
                <?php foreach (['A','B','C','D'] as $l): ?>
                <div style="margin-bottom:10px;">
                    <label class="qz-label">Lựa chọn <?= $l ?></label>
                    <input type="text" name="dap_an_<?= strtolower($l) ?>" id="edit_dap_an_<?= strtolower($l) ?>" class="qz-input">
                </div>
                <?php endforeach; ?>
                <div style="margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label class="qz-label" style="color:var(--quiz-green);margin:0;"><i class="fa-solid fa-check-circle"></i> Đáp Án Đúng</label>
                        <button type="button" onclick="aiSolveInEditModal()" class="qz-action-btn qz-ai-btn" id="btn_ai_solve_modal" style="font-size:11px;padding:4px 10px;color:#fff;border-radius:8px;">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> AI Tìm Đáp Án
                        </button>
                    </div>
                    <select name="dap_an_dung" id="edit_dap_an_dung" class="qz-select" style="border-color:#a7f3d0;color:var(--quiz-green);font-weight:800;">
                        <option value="A">A</option><option value="B">B</option>
                        <option value="C">C</option><option value="D">D</option>
                    </select>
                </div>
                <button type="submit" class="qz-submit-btn" style="font-size:14px;padding:12px;">
                    <i class="fa-solid fa-save"></i> Lưu Thay Đổi
                </button>
            </form>
        </div>
    </div>

    <!-- AI Add More Modal -->
    <div class="qz-modal-overlay" id="aiAddModal">
        <div class="qz-modal">
            <div class="qz-modal-title">
                <i class="fa-solid fa-wand-magic-sparkles" style="color:#f472b6;"></i> AI Sinh Thêm Câu Hỏi Vào Đề
                <button onclick="closeAiAddModal()" style="margin-left:auto;background:none;border:none;color:#94a3b8;font-size:20px;cursor:pointer;">&times;</button>
            </div>
            <div style="margin-bottom:14px;">
                <label class="qz-label">Chủ đề cần tạo thêm câu hỏi:</label>
                <input type="text" id="ai_add_topic" class="qz-input" value="<?= htmlspecialchars($current_quiz['tieu_de']) ?>" placeholder="VD: Tìm hiểu sâu hơn về mảng và chuỗi trong PHP...">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
                <div>
                    <label class="qz-label">Số lượng câu thêm:</label>
                    <select id="ai_add_count" class="qz-select">
                        <option value="3">3 câu</option>
                        <option value="5" selected>5 câu</option>
                        <option value="10">10 câu</option>
                        <option value="15">15 câu</option>
                    </select>
                </div>
                <div>
                    <label class="qz-label">Mức độ:</label>
                    <select id="ai_add_level" class="qz-select">
                        <option value="mixed">Hỗn hợp</option>
                        <option value="easy">Cơ bản</option>
                        <option value="hard">Nâng cao</option>
                    </select>
                </div>
            </div>
            <div id="ai_add_loading" style="display:none;text-align:center;padding:15px;color:#7c3aed;font-weight:700;">
                <i class="fa-solid fa-atom spin" style="font-size:24px;margin-bottom:6px;display:block;"></i>
                AI đang biên soạn câu hỏi... Vui lòng chờ vài giây!
            </div>
            <button type="button" onclick="submitAiAddMore(<?= $quiz_id ?>)" id="btn_submit_ai_add" class="qz-submit-btn qz-ai-btn" style="padding:12px;font-size:14px;">
                <i class="fa-solid fa-bolt"></i> Sinh &amp; Thêm Vào Bộ Đề Ngay
            </button>
        </div>
    </div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- TAB 4: TRỘN MÃ ĐỀ & MA TRẬN ĐÁP ÁN -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'matrix' && $current_quiz): ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="?tab=list" class="qz-action-btn" style="background:var(--quiz-card);color:var(--quiz-text);border-color:var(--quiz-border);"><i class="fa-solid fa-arrow-left"></i></a>
            <h2 style="font-size:17px;font-weight:800;color:var(--quiz-text);margin:0;">
                Trộn Mã Đề: <?= htmlspecialchars($current_quiz['tieu_de']) ?>
            </h2>
        </div>
        <a href="/tkb/teacher/in_de_thi.php?quiz_id=<?= $quiz_id ?>" target="_blank" class="qz-submit-btn" style="width:auto;padding:10px 20px;font-size:13px;border-radius:12px;">
            <i class="fa-solid fa-print"></i> In Tất Cả Mã Đề
        </a>
    </div>

    <!-- Generator Form -->
    <div class="qz-panel" style="margin-bottom:24px;">
        <div style="font-size:15px;font-weight:800;color:var(--quiz-text);margin-bottom:16px;">
            <i class="fa-solid fa-shuffle" style="color:var(--quiz-blue);"></i> Cấu Hình Trộn Đề
        </div>
        <form method="POST" action="?action=generate_matrix_codes" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr)) 160px;gap:14px;align-items:end;">
            <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
            <div>
                <label class="qz-label">Số Mã Đề:</label>
                <input type="number" name="so_ma_de" value="4" min="1" max="50" class="qz-input">
            </div>
            <div>
                <label class="qz-label">Mã Đề Bắt Đầu:</label>
                <input type="number" name="ma_de_start" value="101" min="101" class="qz-input">
            </div>
            <div style="padding-bottom:6px;">
                <label style="display:flex;align-items:center;gap:8px;color:var(--quiz-text);font-size:13px;font-weight:700;cursor:pointer;">
                    <input type="checkbox" name="tron_cau_hoi" checked value="1"> Trộn thứ tự câu hỏi
                </label>
                <label style="display:flex;align-items:center;gap:8px;color:var(--quiz-text);font-size:13px;font-weight:700;cursor:pointer;margin-top:4px;">
                    <input type="checkbox" name="tron_dap_an" checked value="1"> Trộn thứ tự đáp án
                </label>
            </div>
            <div>
                <button type="submit" class="qz-submit-btn" style="font-size:13px;padding:11px;border-radius:10px;">
                    <i class="fa-solid fa-bolt"></i> Trộn Ngay
                </button>
            </div>
        </form>
    </div>

    <!-- Exam Codes Table -->
    <?php if (empty($exam_codes)): ?>
        <div class="qz-empty" style="padding:40px;">
            <i class="fa-solid fa-shuffle" style="font-size:38px;color:var(--quiz-blue);"></i>
            <h3>Chưa tạo mã đề xáo trộn</h3>
            <p>Nhấn nút "Trộn Ngay" ở trên để tạo các mã đề thi xáo trộn cho sinh viên.</p>
        </div>
    <?php else: ?>
        <div class="qz-panel">
            <div style="font-size:15px;font-weight:800;color:var(--quiz-text);margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
                <span><i class="fa-solid fa-table-cells" style="color:var(--quiz-green);"></i> Bảng Ma Trận Đáp Án</span>
                <span class="qz-badge qz-badge-green"><?= count($exam_codes) ?> mã đề</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="qz-table">
                    <thead>
                        <tr>
                            <th>Mã Đề</th><th>Số Câu</th><th>Chuỗi Đáp Án</th><th style="text-align:center;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exam_codes as $ec):
                            $md = json_decode($ec['matrix_data'], true) ?: [];
                            $ans_str = '';
                            foreach ($md as $i => $mq) $ans_str .= ($i+1) . $mq['dap_an_dung'] . '  ';
                        ?>
                        <tr>
                            <td style="font-weight:800;color:var(--quiz-blue);font-size:15px;">Mã <?= htmlspecialchars($ec['ma_de']) ?></td>
                            <td><?= count($md) ?> câu</td>
                            <td style="font-family:monospace;color:var(--quiz-green);font-weight:700;word-break:break-all;font-size:12px;"><?= htmlspecialchars($ans_str) ?></td>
                            <td style="text-align:center;">
                                <a href="/tkb/teacher/in_de_thi.php?quiz_id=<?= $quiz_id ?>&code_id=<?= $ec['id'] ?>" target="_blank" class="qz-action-btn qz-btn-shuffle" style="font-size:11px;padding:5px 10px;">
                                    <i class="fa-solid fa-print"></i> In Mã Này
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- TAB 5: BẢNG ĐIỂM SINH VIÊN -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'results' && $current_quiz): ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="?tab=list" class="qz-action-btn" style="background:var(--quiz-card);color:var(--quiz-text);border-color:var(--quiz-border);"><i class="fa-solid fa-arrow-left"></i></a>
            <h2 style="font-size:17px;font-weight:800;color:var(--quiz-text);margin:0;">
                Bảng Điểm: <?= htmlspecialchars($current_quiz['tieu_de']) ?>
            </h2>
        </div>
    </div>

    <?php if (empty($student_results)): ?>
        <div class="qz-empty" style="padding:50px 20px;">
            <i class="fa-solid fa-user-clock" style="font-size:42px;"></i>
            <h3>Chưa có sinh viên nào nộp bài</h3>
            <p>Khi sinh viên hoàn thành bài thi trắc nghiệm, điểm số sẽ hiển thị tại đây.</p>
        </div>
    <?php else: ?>
        <?php
            $totalScore = 0; $passed = 0;
            foreach ($student_results as $sr) {
                $totalScore += (float)$sr['diem'];
                if ((float)$sr['diem'] >= 5) $passed++;
            }
            $avg = count($student_results) > 0 ? $totalScore / count($student_results) : 0;
        ?>
        <div class="qz-kpi-grid" style="margin-bottom:18px;">
            <div class="qz-kpi">
                <div class="qz-kpi-label">Số lượt thi</div>
                <div class="qz-kpi-value" style="color:var(--quiz-blue);font-size:26px;"><?= count($student_results) ?></div>
            </div>
            <div class="qz-kpi">
                <div class="qz-kpi-label">Điểm trung bình</div>
                <div class="qz-kpi-value" style="color:var(--quiz-yellow);font-size:26px;"><?= number_format($avg, 1) ?></div>
            </div>
            <div class="qz-kpi">
                <div class="qz-kpi-label">Tỉ lệ đạt (≥ 5.0)</div>
                <div class="qz-kpi-value" style="color:var(--quiz-green);font-size:26px;"><?= count($student_results) > 0 ? round($passed / count($student_results) * 100) : 0 ?>%</div>
            </div>
        </div>

        <div class="qz-panel">
            <div style="overflow-x:auto;">
                <table class="qz-table">
                    <thead>
                        <tr>
                            <th>MSSV</th><th>Họ và Tên</th><th>Lớp</th><th>Mã Đề</th>
                            <th>Số Đúng</th><th>Điểm Số</th><th>Thời Gian Nộp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($student_results as $sr): ?>
                        <tr>
                            <td style="font-weight:700;color:var(--quiz-blue);"><?= htmlspecialchars($sr['ma_sv'] ?? 'N/A') ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($sr['student_name'] ?? 'SV') ?></td>
                            <td style="color:var(--quiz-muted);"><?= htmlspecialchars($sr['lop'] ?? 'N/A') ?></td>
                            <td><span class="qz-badge qz-badge-blue"><?= htmlspecialchars($sr['ma_de'] ?? '101') ?></span></td>
                            <td><?= (int)$sr['so_cau_dung'] ?> / <?= (int)$sr['tong_cau'] ?></td>
                            <td style="font-weight:900;font-size:15px;color:<?= (float)$sr['diem'] >= 5 ? 'var(--quiz-green)' : 'var(--quiz-red)' ?>;">
                                <?= number_format((float)$sr['diem'], 1) ?>
                            </td>
                            <td style="color:var(--quiz-muted);font-size:12px;"><?= date('d/m/Y H:i', strtotime($sr['attempted_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

</div>

<!-- Hidden Form for Saving AI Quiz -->
<form id="ai_save_form" method="POST" action="?action=save_ai_quiz" style="display:none;">
    <input type="hidden" name="mon_hoc_id" id="save_mon_hoc_id">
    <input type="hidden" name="tieu_de" id="save_tieu_de">
    <input type="hidden" name="lop" id="save_lop">
    <input type="hidden" name="mo_ta" id="save_mo_ta">
    <input type="hidden" name="thoi_gian_lam_bai" id="save_thoi_gian_lam_bai">
    <input type="hidden" name="questions_json" id="save_questions_json">
</form>

<script>
// Store generated questions in memory for live editing before saving
let generatedAiQuestions = [];

// Quick topic setter
function setAiTopic(topic) {
    document.getElementById('ai_topic').value = topic;
    const titleInput = document.getElementById('ai_tieu_de');
    if (!titleInput.value || titleInput.value.startsWith('Kiểm tra trắc nghiệm')) {
        titleInput.value = 'Trắc nghiệm: ' + topic;
    }
}

// Update duration based on question count
function updateAiDuration(cnt) {
    cnt = parseInt(cnt) || 10;
    document.getElementById('ai_thoi_gian').value = Math.max(5, Math.round(cnt * 1.5));
}

// Switch create mode
function switchMode(mode, btn) {
    document.querySelectorAll('.qz-mode-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const stdForm = document.getElementById('standard_create_form');
    const aiMode = document.getElementById('mode_ai');

    if (mode === 'ai') {
        if (aiMode) aiMode.style.display = 'block';
        if (stdForm) stdForm.style.display = 'none';
    } else {
        if (aiMode) aiMode.style.display = 'none';
        if (stdForm) stdForm.style.display = 'block';
        document.getElementById('selected_source_mode').value = mode;

        ['text','file','manual'].forEach(m => {
            const el = document.getElementById('mode_' + m);
            if (el) el.style.display = (m === mode) ? 'block' : 'none';
        });
    }
}

// ══ AI GENERATION TRIGGER ══
function startAiGeneration() {
    const topic = document.getElementById('ai_topic').value.trim();
    const sourceContent = document.getElementById('ai_source_content').value.trim();
    const soCau = document.getElementById('ai_so_cau').value;
    const level = document.getElementById('ai_level').value;
    const monHocId = document.getElementById('ai_mon_hoc_id').value;

    if (!topic && !sourceContent) {
        alert('Vui lòng nhập chủ đề câu hỏi hoặc dán tài liệu nguồn cho AI!');
        document.getElementById('ai_topic').focus();
        return;
    }
    if (!monHocId) {
        alert('Vui lòng chọn môn học cho bộ đề!');
        document.getElementById('ai_mon_hoc_id').focus();
        return;
    }

    const btn = document.getElementById('btn_generate_ai');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-atom spin"></i> AI Đang Biên Soạn Đề Thi...';

    const loadingBox = document.getElementById('ai_loading_box');
    const previewArea = document.getElementById('ai_preview_area');
    loadingBox.style.display = 'block';
    previewArea.style.display = 'none';

    // Simulated progress steps
    const progressBar = document.getElementById('ai_progress_bar');
    const loadingText = document.getElementById('ai_loading_text');
    const loadingSub = document.getElementById('ai_loading_sub');

    progressBar.style.width = '25%';
    loadingText.innerText = 'Đang phân tích chủ đề: "' + (topic || 'Tài liệu nguồn') + '"...';
    loadingSub.innerText = 'Bước 1/3: Khởi tạo mô hình AI & xây dựng cấu trúc đề thi';

    setTimeout(() => {
        progressBar.style.width = '60%';
        loadingText.innerText = 'Đang biên soạn ' + soCau + ' câu hỏi trắc nghiệm...';
        loadingSub.innerText = 'Bước 2/3: Soạn câu hỏi & 4 phương án A, B, C, D';
    }, 2500);

    setTimeout(() => {
        progressBar.style.width = '85%';
        loadingText.innerText = 'Đang kiểm duyệt và phân bổ đáp án...';
        loadingSub.innerText = 'Bước 3/3: Kiểm tra tính logic và hoàn tất';
    }, 6000);

    const fd = new FormData();
    fd.append('action', 'ajax_ai_generate');
    fd.append('topic', topic);
    fd.append('source_content', sourceContent);
    fd.append('so_cau', soCau);
    fd.append('level', level);

    fetch('/tkb/teacher/quiz.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        progressBar.style.width = '100%';
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Tạo Lại Đề Thi Bằng AI';
        loadingBox.style.display = 'none';

        if (data.success && data.questions && data.questions.length > 0) {
            generatedAiQuestions = data.questions;
            renderAiPreview(generatedAiQuestions);
            previewArea.style.display = 'block';
            previewArea.scrollIntoView({ behavior: 'smooth' });
        } else {
            alert('Lỗi: ' + (data.error || 'AI không thể phản hồi đúng định dạng. Vui lòng thử lại!'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Bắt Đầu Sinh Đề Thi Bằng AI';
        loadingBox.style.display = 'none';
        alert('Lỗi kết nối máy chủ AI: ' + err.message + '. Vui lòng thử lại!');
    });
}

// ══ RENDER AI PREVIEW QUESTIONS ══
function renderAiPreview(questions) {
    document.getElementById('ai_preview_count').innerText = questions.length;
    const container = document.getElementById('ai_questions_list');
    container.innerHTML = '';

    questions.forEach((q, idx) => {
        const card = document.createElement('div');
        card.className = 'ai-preview-card';
        card.id = 'ai_q_card_' + idx;

        let optsHtml = '';
        ['A','B','C','D'].forEach(letter => {
            const key = 'dap_an_' + letter.toLowerCase();
            const val = q[key] || '';
            const isCorrect = (q.dap_an_dung === letter);
            optsHtml += `
                <div class="qz-opt ${isCorrect ? 'correct' : ''}" onclick="toggleAiAnswer(${idx}, '${letter}')" title="Bấm để chọn ${letter} là đáp án đúng">
                    <span class="qz-opt-letter">${letter}.</span>
                    <span id="ai_opt_text_${idx}_${letter}">${escapeHtml(val)}</span>
                </div>
            `;
        });

        card.innerHTML = `
            <div class="ai-preview-num">
                <span><i class="fa-solid fa-circle-question" style="color:#7c3aed;"></i> Câu ${idx + 1}</span>
                <div style="display:flex;gap:6px;">
                    <span class="qz-badge qz-badge-green" id="ai_badge_ans_${idx}">Đáp án đúng: ${q.dap_an_dung}</span>
                    <button type="button" onclick="deleteAiQuestion(${idx})" class="qz-action-btn" style="padding:4px 8px;font-size:11px;background:#fef2f2;color:var(--quiz-red);border-color:#fecaca;" title="Xóa câu này">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            <div style="font-size:14.5px;font-weight:700;color:var(--quiz-text);margin-bottom:10px;line-height:1.45;">
                ${escapeHtml(q.cau_hoi)}
            </div>
            <div class="qz-opts">${optsHtml}</div>
        `;

        container.appendChild(card);
    });
}

// Toggle AI question correct answer
function toggleAiAnswer(idx, letter) {
    if (!generatedAiQuestions[idx]) return;
    generatedAiQuestions[idx].dap_an_dung = letter;

    const card = document.getElementById('ai_q_card_' + idx);
    if (card) {
        card.querySelectorAll('.qz-opt').forEach(o => o.classList.remove('correct'));
        const badge = document.getElementById('ai_badge_ans_' + idx);
        if (badge) badge.innerText = 'Đáp án đúng: ' + letter;
    }
    renderAiPreview(generatedAiQuestions);
}

// Delete question from preview
function deleteAiQuestion(idx) {
    if (confirm('Bạn muốn xóa câu hỏi này khỏi đề?')) {
        generatedAiQuestions.splice(idx, 1);
        renderAiPreview(generatedAiQuestions);
    }
}

// Save AI Quiz to Database
function saveAiQuizToDb() {
    if (generatedAiQuestions.length === 0) {
        alert('Không có câu hỏi nào để lưu!');
        return;
    }

    const monHocId = document.getElementById('ai_mon_hoc_id').value;
    const tieuDe = document.getElementById('ai_tieu_de').value.trim() || document.getElementById('ai_topic').value.trim() || 'Đề trắc nghiệm AI';
    const lop = document.getElementById('ai_lop').value.trim();
    const moTa = document.getElementById('ai_mo_ta').value.trim();
    const thoiGian = document.getElementById('ai_thoi_gian').value;

    document.getElementById('save_mon_hoc_id').value = monHocId;
    document.getElementById('save_tieu_de').value = tieuDe;
    document.getElementById('save_lop').value = lop;
    document.getElementById('save_mo_ta').value = moTa;
    document.getElementById('save_thoi_gian_lam_bai').value = thoiGian;
    document.getElementById('save_questions_json').value = JSON.stringify(generatedAiQuestions);

    document.getElementById('ai_save_form').submit();
}

// ══ AI ADD MORE MODAL (TAB 3) ══
function openAiAddModal() {
    document.getElementById('aiAddModal').classList.add('show');
}
function closeAiAddModal() {
    document.getElementById('aiAddModal').classList.remove('show');
}
function submitAiAddMore(quizId) {
    const topic = document.getElementById('ai_add_topic').value.trim();
    const soCau = document.getElementById('ai_add_count').value;
    const level = document.getElementById('ai_add_level').value;

    const btn = document.getElementById('btn_submit_ai_add');
    const loading = document.getElementById('ai_add_loading');
    btn.disabled = true;
    loading.style.display = 'block';

    const fd = new FormData();
    fd.append('action', 'ajax_ai_add_more');
    fd.append('quiz_id', quizId);
    fd.append('topic', topic);
    fd.append('so_cau', soCau);
    fd.append('level', level);

    fetch('/tkb/teacher/quiz.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        loading.style.display = 'none';
        if (data.success) {
            alert('Đã thêm thành công ' + data.added_count + ' câu hỏi vào bộ Quiz!');
            location.reload();
        } else {
            alert('Lỗi: ' + (data.error || 'Không thể tạo thêm câu hỏi.'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        loading.style.display = 'none';
        alert('Lỗi: ' + err.message);
    });
}

// Quick set answer (AJAX in question manager)
function quickSetAnswer(qId, ans, el) {
    const card = el.closest('.qz-question-card');
    card.querySelectorAll('.qz-opt').forEach(o => o.classList.remove('correct'));
    el.classList.add('correct');

    const fd = new FormData();
    fd.append('action', 'quick_set_correct');
    fd.append('question_id', qId);
    fd.append('answer', ans);
    fetch('/tkb/teacher/quiz.php', { method: 'POST', body: fd }).catch(e => console.error(e));
}

// Quick AI solve single question on card
function quickAiSolveQuestion(questionId, btn) {
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-atom spin"></i> Đang giải...';

    const fd = new FormData();
    fd.append('action', 'ajax_ai_solve_question');
    fd.append('question_id', questionId);

    fetch('/tkb/teacher/quiz.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.success && data.answer) {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Đã chọn ' + data.answer;
            btn.style.color = 'var(--quiz-green)';
            btn.style.borderColor = 'rgba(52,211,153,0.4)';
            
            // Highlight matching option on card
            const card = document.getElementById('qz_card_' + questionId);
            if (card) {
                card.querySelectorAll('.qz-opt').forEach(opt => {
                    if (opt.getAttribute('data-letter') === data.answer) {
                        opt.classList.add('correct');
                    } else {
                        opt.classList.remove('correct');
                    }
                });
            }

            setTimeout(() => {
                btn.innerHTML = origHtml;
                btn.style.color = '';
                btn.style.borderColor = '';
            }, 3000);
        } else {
            btn.innerHTML = origHtml;
            alert('Lỗi: ' + (data.error || 'AI không thể phân tích câu này.'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        alert('Lỗi: ' + err.message);
    });
}

// AI Solve all questions in current quiz
function aiSolveAllQuestions(quizId) {
    if (!confirm('Bạn có chắc chắn muốn AI tự động giải và phân tích lại toàn bộ đáp án cho tất cả các câu hỏi trong bộ đề này?')) {
        return;
    }

    const btn = document.getElementById('btn_ai_solve_all');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-atom spin"></i> AI Đang Giải Toàn Bộ Đề...';

    const fd = new FormData();
    fd.append('action', 'ajax_ai_solve_all');
    fd.append('quiz_id', quizId);

    fetch('/tkb/teacher/quiz.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        if (data.success) {
            alert('AI đã giải và cập nhật thành công đáp án chính xác cho ' + (data.solved_count || data.count) + ' câu hỏi!');
            location.reload();
        } else {
            alert('Lỗi: ' + (data.error || 'Không thể giải đề.'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        alert('Lỗi kết nối AI: ' + err.message);
    });
}

// AI Solve in Edit Modal
function aiSolveInEditModal() {
    const qText = document.getElementById('edit_cau_hoi').value.trim();
    const optA = document.getElementById('edit_dap_an_a').value.trim();
    const optB = document.getElementById('edit_dap_an_b').value.trim();
    const optC = document.getElementById('edit_dap_an_c').value.trim();
    const optD = document.getElementById('edit_dap_an_d').value.trim();
    const qId = document.getElementById('edit_question_id').value;

    if (!qText || !optA) {
        alert('Vui lòng nhập câu hỏi và ít nhất phương án A!');
        return;
    }

    const btn = document.getElementById('btn_ai_solve_modal');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-atom spin"></i> Đang giải...';

    const fd = new FormData();
    fd.append('action', 'ajax_ai_solve_question');
    fd.append('question_id', qId);
    fd.append('cau_hoi', qText);
    fd.append('dap_an_a', optA);
    fd.append('dap_an_b', optB);
    fd.append('dap_an_c', optC);
    fd.append('dap_an_d', optD);

    fetch('/tkb/teacher/quiz.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        if (data.success && data.answer) {
            const select = document.getElementById('edit_dap_an_dung');
            select.value = data.answer;
            select.style.boxShadow = '0 0 12px rgba(52,211,153,0.8)';
            setTimeout(() => { select.style.boxShadow = ''; }, 2000);
        } else {
            alert('Lỗi: ' + (data.error || 'Không thể phân tích đáp án.'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        alert('Lỗi: ' + err.message);
    });
}

// Edit Modal
function openEditModal(q, quizId) {
    document.getElementById('edit_quiz_id').value = quizId;
    document.getElementById('edit_question_id').value = q.id;
    document.getElementById('edit_cau_hoi').value = q.cau_hoi;
    document.getElementById('edit_dap_an_a').value = q.dap_an_a;
    document.getElementById('edit_dap_an_b').value = q.dap_an_b;
    document.getElementById('edit_dap_an_c').value = q.dap_an_c || '';
    document.getElementById('edit_dap_an_d').value = q.dap_an_d || '';
    document.getElementById('edit_dap_an_dung').value = q.dap_an_dung;
    document.getElementById('editModal').classList.add('show');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

function openImportModal() {
    document.getElementById('importModal').classList.add('show');
}
function closeImportModal() {
    document.getElementById('importModal').classList.remove('show');
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

document.getElementById('editModal')?.addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
document.getElementById('aiAddModal')?.addEventListener('click', function(e) { if (e.target === this) closeAiAddModal(); });
document.getElementById('importModal')?.addEventListener('click', function(e) { if (e.target === this) closeImportModal(); });
</script>

</body>
</html>
<?php $db->close(); ?>
