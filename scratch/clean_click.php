<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$oldFunc1 = '
        function openFolderDirectly(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            const pop = document.getElementById("attachMenuPopover");
            if (pop) pop.style.display = "none";

            // Luôn dùng input webkitdirectory vì showDirectoryPicker bị lỗi trên hosting free
            const fi = document.getElementById("folderInput");
            if (fi) {
                // Fix lỗi cache thuộc tính của trình duyệt
                fi.webkitdirectory = true;
                fi.setAttribute("webkitdirectory", "");
                fi.setAttribute("directory", "");
                fi.click();
            }
        }
';
$newFunc1 = '
        function openFolderDirectly(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            const pop = document.getElementById("attachMenuPopover");
            if (pop) pop.style.display = "none";
            const fi = document.getElementById("folderInput");
            if (fi) {
                fi.click();
            }
        }
';

$oldFunc2 = '
        function openNativeFolderPicker() {
            // Luôn dùng input webkitdirectory vì showDirectoryPicker bị lỗi trên hosting free
            const fi = document.getElementById("folderInput");
            if (fi) {
                // Fix lỗi cache thuộc tính của trình duyệt
                fi.webkitdirectory = true;
                fi.setAttribute("webkitdirectory", "");
                fi.setAttribute("directory", "");
                fi.click();
            }
        }
';
$newFunc2 = '
        function openNativeFolderPicker() {
            const fi = document.getElementById("folderInput");
            if (fi) {
                fi.click();
            }
        }
';

$aiPhp = str_replace(trim($oldFunc1), trim($newFunc1), $aiPhp);
$aiPhp = str_replace(trim($oldFunc2), trim($newFunc2), $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Cleaned up folder input clickers!\n";
