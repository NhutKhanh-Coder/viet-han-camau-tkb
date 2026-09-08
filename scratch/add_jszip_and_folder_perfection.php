<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Add JSZip CDN in <head>
if (strpos($aiPhp, 'jszip.min.js') === false) {
    $aiPhp = str_replace('</head>', '<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>' . "\n</head>", $aiPhp);
}

// 2. Enhance processIncomingFiles to handle .zip extraction and all folders
$oldProcessIncoming = 'function processIncomingFiles(files, fromFolderName = null) {
            if (!files || files.length === 0) return;

            // Exclude huge binary / cache folders like node_modules, .git, vendor if too large
            const ignoredFolders = [\'node_modules/\', \'.git/\', \'.vs/\', \'.idea/\'];

            files.forEach(file => {
                const relPath = file.webkitRelativePath || file.relativePath || file.name;
                
                // Skip ignored directories
                if (ignoredFolders.some(ig => relPath.includes(ig))) return;

                const ext = file.name.substring(file.name.lastIndexOf(\'.\')).toLowerCase();
                const sizeKb = (file.size / 1024).toFixed(1);
                const reader = new FileReader();

                if (file.type.startsWith(\'image/\')) {
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: \'image\',
                            data: e.target.result,
                            size: `${sizeKb} KB`
                        });
                        renderPreviews();
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Read all files as text (Code, markdown, config, json, sql, txt, etc.)
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: \'text\',
                            data: e.target.result,
                            ext: ext,
                            size: `${sizeKb} KB`
                        });
                        renderPreviews();
                    };
                    reader.readAsText(file);
                }
            });
        }';

$newProcessIncoming = 'function processIncomingFiles(files, fromFolderName = null) {
            if (!files || files.length === 0) return;

            const ignoredFolders = [\'node_modules/\', \'.git/\', \'.vs/\', \'.idea/\', \'vendor/\', \'__pycache__/\'];

            Array.from(files).forEach(file => {
                const relPath = file.webkitRelativePath || file.relativePath || file.name;
                
                if (ignoredFolders.some(ig => relPath.includes(ig))) return;

                const ext = file.name.substring(file.name.lastIndexOf(\'.\')).toLowerCase();
                const sizeKb = (file.size / 1024).toFixed(1);

                // If user uploads a ZIP project archive
                if (ext === \'.zip\') {
                    const reader = new FileReader();
                    reader.onload = async function(e) {
                        if (window.JSZip) {
                            try {
                                const zip = await JSZip.loadAsync(e.target.result);
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
                                        size: `${entrySizeKb} KB`
                                    });
                                }
                                renderPreviews();
                            } catch(err) {
                                console.error(\'Error parsing ZIP:\', err);
                            }
                        }
                    };
                    reader.readAsArrayBuffer(file);
                    return;
                }

                const reader = new FileReader();
                if (file.type.startsWith(\'image/\')) {
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: \'image\',
                            data: e.target.result,
                            size: `${sizeKb} KB`
                        });
                        renderPreviews();
                    };
                    reader.readAsDataURL(file);
                } else {
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: \'text\',
                            data: e.target.result,
                            ext: ext,
                            size: `${sizeKb} KB`
                        });
                        renderPreviews();
                    };
                    reader.readAsText(file);
                }
            });
        }';

$aiPhp = str_replace($oldProcessIncoming, $newProcessIncoming, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully integrated JSZip and improved Folder/File ingestion in student/ai.php!\n";
