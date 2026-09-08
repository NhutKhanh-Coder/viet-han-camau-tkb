<?php
$aiPhp = file_get_contents('c:/xampp/htdocs/tkb/student/ai.php');

$pattern = '/<p class="ai-hero-sub">.*?<\/p>\s*<!-- Messages container -->/s';
$replacement = '<p class="ai-hero-sub">Chọn mô hình và bắt đầu chat — hoặc chọn một gợi ý bên dưới.</p>
                    </div>

                    <!-- Messages container -->';

$aiPhp = preg_replace($pattern, $replacement, $aiPhp);

file_put_contents('c:/xampp/htdocs/tkb/student/ai.php', $aiPhp);
echo "Restored missing </div> for ai-hero-welcome!\n";
