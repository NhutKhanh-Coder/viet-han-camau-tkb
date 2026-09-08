<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$oldProcess = "async function processIncomingFiles(files, fromFolderName = null) {
            if (!files || files.length === 0) return;

            const ignoredFolders = ['node_modules/', '.git/', '.vs/', '.idea/', 'vendor/', '__pycache__/'];

            for (const file of Array.from(files)) {
                const relPath = file.webkitRelativePath || file.relativePath || file.name;";

$newProcess = "async function processIncomingFiles(files, fromFolderName = null) {
            if (!files || files.length === 0) return;

            const ignoredFolders = ['node_modules/', '.git/', '.vs/', '.idea/', 'vendor/', '__pycache__/'];
            
            // Show loading state
            const loadingIndicator = document.getElementById('typingIndicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'block';
                loadingIndicator.querySelector('span').textContent = 'Đang đọc thư mục...';
            }

            for (const file of Array.from(files)) {
                const relPath = file.webkitRelativePath || file.relativePath || file.name;";

$aiPhp = str_replace($oldProcess, $newProcess, $aiPhp);

$oldReaderLogic = '                // If user uploads a ZIP project archive
                if (ext === \'.zip\') {
                    if (window.JSZip) {
                        try {
                            const arrayBuffer = await file.arrayBuffer();
                            const zip = await JSZip.loadAsync(arrayBuffer);
                            const zipFolder = file.name.replace(/\.zip$/i, \'\');
                            const entries = Object.keys(zip.files);
                            for (let filename of entries) {
                                const entry = zip.files[filename];
                                if (entry.dir) continue;
                                if (ignoredFolders.some(ig => filename.includes(ig))) continue;
                                
                                const content = await entry.async(\'text\');
                                const entryExt = filename.substring(filename.lastIndexOf(\'.\')).toLowerCase();
                                const entrySizeKb = (content.length / 1024).toFixed(1);
                                
                                attachedFiles.push({
                                    name: filename.split(\'/\').pop(),
                                    path: filename,
                                    folder: zipFolder,
                                    type: \'text\',
                                    data: content,
                                    ext: entryExt,
                                    size: `${} KB`
                                });
                            }
                        } catch(err) {
                            console.error(\'Error parsing ZIP:\', err);
                        }
                    }
                    continue;
                }

                try {
                    const type = file.type || \'\';
                    if (type.startsWith(\'image/\')) {
                        const reader = new FileReader();
                        const dataUrl = await new Promise((resolve, reject) => {
                            reader.onload = e => resolve(e.target.result);
                            reader.onerror = e => reject(e);
                            reader.readAsDataURL(file);
                        });
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: \'image\',
                            data: dataUrl,
                            size: `${} KB`
                        });
                    } else {
                        const text = await file.text();
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: \'text\',
                            data: text,
                            ext: ext,
                            size: `${} KB`
                        });
                    }
                } catch (err) {
                    console.error(\'Error reading file:\', file.name, err);
                }
            } // end for loop
            
            renderPreviews();
';

// Because the variables got interpolated as empty strings, the text in student/ai.php is `${} KB`
$wrongText = '${} KB';

$aiPhp = str_replace(
    ['size: `${} KB`', 'size: `${} KB`', 'size: `${} KB`'],
    ['size: `${entrySizeKb} KB`', 'size: `${sizeKb} KB`', 'size: `${sizeKb} KB`'],
    $aiPhp
);

$aiPhp = str_replace(
    'renderPreviews();',
    "renderPreviews();\n            if (loadingIndicator) { loadingIndicator.style.display = 'none'; loadingIndicator.querySelector('span').textContent = 'AI đang suy nghĩ...'; }",
    $aiPhp
);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Fixed interpolation bugs!\n";
