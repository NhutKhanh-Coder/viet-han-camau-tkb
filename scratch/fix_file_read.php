<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$oldSelectHandlers = "
        function handleFolderSelect(input) {
            const files = Array.from(input.files);
            if (files.length === 0) return;
            
            let folderName = 'Thư mục dự án';
            if (files[0].webkitRelativePath) {
                folderName = files[0].webkitRelativePath.split('/')[0];
            }
            
            processIncomingFiles(files, folderName);
            input.value = '';
        }

        function handleFileSelect(input) {
            const files = Array.from(input.files);
            processIncomingFiles(files);
            input.value = '';
        }
";

$newSelectHandlers = "
        async function handleFolderSelect(input) {
            const files = Array.from(input.files);
            if (files.length === 0) return;
            
            let folderName = 'Thư mục dự án';
            if (files[0].webkitRelativePath) {
                folderName = files[0].webkitRelativePath.split('/')[0];
            }
            
            await processIncomingFiles(files, folderName);
            input.value = '';
        }

        async function handleFileSelect(input) {
            const files = Array.from(input.files);
            await processIncomingFiles(files);
            input.value = '';
        }
";

$aiPhp = str_replace(trim($oldSelectHandlers), trim($newSelectHandlers), $aiPhp);

// Now update processIncomingFiles to be async
$oldProcess = "function processIncomingFiles(files, fromFolderName = null) {
            if (!files || files.length === 0) return;

            const ignoredFolders = ['node_modules/', '.git/', '.vs/', '.idea/', 'vendor/', '__pycache__/'];

            Array.from(files).forEach(file => {";
            
$newProcess = "async function processIncomingFiles(files, fromFolderName = null) {
            if (!files || files.length === 0) return;

            const ignoredFolders = ['node_modules/', '.git/', '.vs/', '.idea/', 'vendor/', '__pycache__/'];

            for (const file of Array.from(files)) {
                const relPath = file.webkitRelativePath || file.relativePath || file.name;";

$aiPhp = str_replace($oldProcess, $newProcess, $aiPhp);

$oldReaderLogic = "                // If user uploads a ZIP project archive
                if (ext === '.zip') {
                    const reader = new FileReader();
                    reader.onload = async function(e) {
                        if (window.JSZip) {
                            try {
                                const zip = await JSZip.loadAsync(e.target.result);
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
                                        size: `${entrySizeKb} KB`
                                    });
                                }
                                renderPreviews();
                            } catch(err) {
                                console.error('Error parsing ZIP:', err);
                            }
                        }
                    };
                    reader.readAsArrayBuffer(file);
                    return;
                }

                const reader = new FileReader();
                if (file.type.startsWith('image/')) {
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: 'image',
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
                            type: 'text',
                            data: e.target.result,
                            ext: ext,
                            size: `${sizeKb} KB`
                        });
                        renderPreviews();
                    };
                    reader.readAsText(file);
                }
            });";

$newReaderLogic = "                // If user uploads a ZIP project archive
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
                                    size: `${entrySizeKb} KB`
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
                            size: `${sizeKb} KB`
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
                            size: `${sizeKb} KB`
                        });
                    }
                } catch (err) {
                    console.error('Error reading file:', file.name, err);
                }
            } // end for loop
            
            renderPreviews();
";

$aiPhp = str_replace($oldReaderLogic, $newReaderLogic, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Refactored processIncomingFiles to use async await!\n";
