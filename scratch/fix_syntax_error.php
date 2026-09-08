<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$pattern = '/async function processIncomingFiles\(files.*?^\s*\}\n/ms';

$newFunction = "async function processIncomingFiles(files, fromFolderName = null) {
            if (!files || files.length === 0) return;

            const ignoredFolders = ['node_modules/', '.git/', '.vs/', '.idea/', 'vendor/', '__pycache__/'];
            
            const loadingIndicator = document.getElementById('typingIndicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'block';
                loadingIndicator.querySelector('span').textContent = 'Đang đọc tệp tin...';
            }

            for (const file of Array.from(files)) {
                const relPath = file.webkitRelativePath || file.relativePath || file.name;
                
                if (ignoredFolders.some(ig => relPath.includes(ig))) continue;

                const ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
                const sizeKb = (file.size / 1024).toFixed(1);

                // ZIP extraction
                if (ext === '.zip') {
                    if (window.JSZip) {
                        try {
                            const arrayBuffer = await file.arrayBuffer();
                            const zip = await JSZip.loadAsync(arrayBuffer);
                            const zipFolder = file.name.replace(/\.zip$/i, '');
                            const entries = Object.keys(zip.files);
                            for (let filename of entries) {
                                const entry = zip.files[filename];
                                if (entry.dir) continue;
                                if (ignoredFolders.some(ig => filename.includes(ig))) continue;
                                
                                const content = await entry.async('text');
                                const entryExt = filename.substring(filename.lastIndexOf('.')).toLowerCase();
                                const entrySizeKb = (content.length / 1024).toFixed(1);
                                
                                attachedFiles.push({
                                    name: filename.split('/').pop(),
                                    path: filename,
                                    folder: zipFolder,
                                    type: 'text',
                                    data: content,
                                    ext: entryExt,
                                    size: entrySizeKb + ' KB'
                                });
                            }
                        } catch(err) {
                            console.error('Error parsing ZIP:', err);
                        }
                    }
                    continue;
                }

                try {
                    const type = file.type || '';
                    if (type.startsWith('image/')) {
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
                            type: 'image',
                            data: dataUrl,
                            size: sizeKb + ' KB'
                        });
                    } else {
                        const text = await file.text();
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: 'text',
                            data: text,
                            ext: ext,
                            size: sizeKb + ' KB'
                        });
                    }
                } catch (err) {
                    console.error('Error reading file:', file.name, err);
                }
            } // end loop

            renderPreviews();
            if (loadingIndicator) { 
                loadingIndicator.style.display = 'none'; 
                loadingIndicator.querySelector('span').textContent = 'AI đang suy nghĩ...'; 
            }
        }
";

// Use preg_replace to wipe out the old broken function and replace it cleanly.
// Since the old function spans multiple lines and ends with a closing brace at the root indent level,
// we use a regex that matches `async function processIncomingFiles...` down to the first `    }`
$replaced = preg_replace('/async function processIncomingFiles\(files.*?^\s{8}\}/ms', $newFunction, $aiPhp, 1);

if ($replaced === null || $replaced === $aiPhp) {
    echo "Regex replacement failed. Trying fallback manual substring replacement.\n";
    // Fallback if regex fails: search string explicitly up to line 2150
    $startIdx = strpos($aiPhp, "async function processIncomingFiles(files, fromFolderName = null)");
    $endIdx = strpos($aiPhp, "// Setup Drag & Drop on Chat Body");
    if ($startIdx !== false && $endIdx !== false) {
        $replaced = substr($aiPhp, 0, $startIdx) . $newFunction . "\n        " . substr($aiPhp, $endIdx);
    }
}

file_put_contents(__DIR__ . '/../student/ai.php', $replaced);
echo "processIncomingFiles fully restored!\n";
