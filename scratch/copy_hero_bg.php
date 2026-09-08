<?php
$source = "C:\\Users\\Lê Nhựt Khánh\\.gemini\\antigravity-ide\\brain\\c088f40e-482a-4a8a-a431-2d9b33733225\\admin_hero_banner_bg_1787146986487.jpg";
$target = __DIR__ . "/../assets/img/admin_hero_banner_bg.jpg";

if (!file_exists(dirname($target))) {
    mkdir(dirname($target), 0777, true);
}

if (copy($source, $target)) {
    echo "Successfully copied image to $target\n";
} else {
    echo "Failed to copy image!\n";
}
