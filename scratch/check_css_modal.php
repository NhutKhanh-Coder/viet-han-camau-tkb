<?php
$css = file_get_contents(__DIR__ . '/../assets/style.css');
preg_match_all('/\.modal-overlay[^{]*\{[^}]*\}/', $css, $m);
print_r($m[0]);
preg_match_all('/\.modal[^{]*\{[^}]*\}/', $css, $m2);
print_r(array_slice($m2[0], 0, 10));
