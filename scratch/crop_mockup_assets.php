<?php
$src_path = 'C:/Users/Lê Nhựt Khánh/.gemini/antigravity-ide/brain/65c4c695-5d33-499d-82da-55838622944a/.user_uploaded/media_1789810845493.jpg';
if (!file_exists($src_path)) {
    die("File not found\n");
}
$info = getimagesize($src_path);
echo "Image dimensions: " . $info[0] . "x" . $info[1] . " (Mime: " . $info['mime'] . ")\n";

$src = imagecreatefromjpeg($src_path);
$W = $info[0];
$H = $info[1];

// 1. Crop Hero Banner (top area around x=175 to W, y=70 to ~270)
// Let's create high-resolution crops
@mkdir('c:/xampp/htdocs/tkb/assets/ai', 0777, true);

// Save full mockup for reference
copy($src_path, 'c:/xampp/htdocs/tkb/assets/ai/vutru_mockup.jpg');
echo "Saved vutru_mockup.jpg\n";
