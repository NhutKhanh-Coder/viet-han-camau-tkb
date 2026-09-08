window.globalDebounceTimer = window.globalDebounceTimer || null;

const templates = {
    python: `# Bài Tập Lập Trình Python - VKC TKB\ndef main():\n    print("Chào mừng bạn đến với Kho Lưu Trữ Code VKC!")\n    numbers = [10, 20, 30, 40, 50]\n    print("Tổng mảng:", sum(numbers))\n\nif __name__ == "__main__":\n    main()`,
    cpp: `// Bài Tập Lập Trình C++ - VKC TKB\n#include <iostream>\nusing namespace std;\n\nint main() {\n    cout << "Chào mừng bạn đến với Kho Lưu Trữ Code C++!" << endl;\n    return 0;\n}`,
    c: `// Bài Tập Lập Trình C - VKC TKB\n#include <stdio.h>\n\nint main() {\n    printf("Chào mừng bạn đến với Kho Lưu Trữ Code C!\\n");\n    return 0;\n}`,
    java: `// Bài Tập Lập Trình Java - VKC TKB\npublic class Main {\n    public static void main(String[] args) {\n        System.out.println("Chào mừng bạn đến với Kho Lưu Trữ Code Java!");\n    }\n}`,
    php: `<?php\n// Bài Tập Lập Trình PHP - VKC TKB\necho "Chào mừng bạn đến với Kho Lưu Trữ Code PHP!\\n";\n$numbers = [1, 2, 3, 4, 5];\necho "Tổng: " . array_sum($numbers);\n?>`,
    html: `<!DOCTYPE html>\n<html lang="vi">\n<head>\n    <meta charset="UTF-8">\n    <title>Dự Án Web Demo</title>\n    <style>\n        body { font-family: sans-serif; text-align: center; padding: 50px; background: #0f172a; color: #fff; }\n        h1 { color: #a855f7; }\n    </style>\n</head>\n<body>\n    <h1>🚀 Dự Án Web HTML/CSS/JS</h1>\n    <p>Viết code và deploy trực tiếp trên hệ thống VKC TKB</p>\n</body>\n</html>`
};

document.addEventListener("DOMContentLoaded", () => {
    const langFilter = document.getElementById('langFilter');
    const searchInput = document.getElementById('searchInput');
    if (langFilter) langFilter.value = 'all';
    if (searchInput) searchInput.value = '';

    // Always fetch fresh project list from API so newly uploaded files/folders appear instantly
    fetchProjects();

    if (window.postSuccessMsg) {
        const isError = window.postSuccessMsg.includes('❌');
        showToast(window.postSuccessMsg, !isError);
        alert(window.postSuccessMsg);
    }

    const textarea = document.getElementById('projectCode');
    if (textarea) {
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });
    }
});

function debounceFetch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchProjects, 300);
}

function fetchProjects() {
    // Nếu đang ở trang kho_code_cong_dong.php → dùng fetchPublicProjects thay thế
    if (window.location.pathname.includes('kho_code_cong_dong.php') && typeof fetchPublicProjects === 'function') {
        fetchPublicProjects();
        return;
    }
    const search = document.getElementById('searchInput') ? document.getElementById('searchInput').value : '';
    const lang = document.getElementById('langFilter') ? document.getElementById('langFilter').value : 'all';
    fetch(`/tkb/api/code_storage_api.php?action=list&ngon_ngu=${encodeURIComponent(lang)}&search=${encodeURIComponent(search)}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) { renderStats(res.stats); renderProjects(res.data); }
            else { renderProjects([]); }
        })
        .catch(err => { console.error(err); renderProjects([]); });
}

function readFileAsText(file) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = e => resolve(e.target.result);
        reader.onerror = () => resolve('');
        reader.readAsText(file);
    });
}

function utf8_to_b64(str) {
    if (!str) return '';
    try {
        return window.btoa(unescape(encodeURIComponent(str)));
    } catch(e) {
        try {
            let binary = '';
            const bytes = new TextEncoder().encode(str);
            const len = bytes.byteLength;
            for (let i = 0; i < len; i += 8192) {
                binary += String.fromCharCode.apply(null, bytes.subarray(i, Math.min(i + 8192, len)));
            }
            return window.btoa(binary);
        } catch(err) {
            return str;
        }
    }
}

async function autoSaveProject(title, lang, desc, code, editId) {
    showToast('Đang tải bài làm lên kho...', true);
    try {
        const params = new URLSearchParams();
        params.append('ten_du_an', utf8_to_b64(title));
        params.append('ngon_ngu', lang);
        params.append('mo_ta', utf8_to_b64(desc));
        params.append('ma_nguon', utf8_to_b64(code));
        params.append('is_base64', '1');
        params.append('la_cong_khai', '1');
        if (editId) params.append('id', editId);

        const res = await fetch('/tkb/api/code_storage_api.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });
        const data = await res.json();
        if (data.success) {
            showToast('✅ Đã lưu vào kho code thành công!', true);
            setTimeout(() => window.location.reload(), 800);
        } else {
            showToast('❌ Lỗi: ' + (data.message || 'Không lưu được!'), false);
        }
    } catch (err) {
        showToast('❌ Lỗi kết nối: ' + err.message, false);
    }
}

function readFileAsDataURL(file) {
    return new Promise(resolve => {
        const r = new FileReader();
        r.onload = e => resolve(e.target.result);
        r.onerror = () => resolve(null);
        r.readAsDataURL(file);
    });
}

function compressImageFile(file, maxWidth = 1200, quality = 0.75) {
    return new Promise(resolve => {
        if (!file.type || !file.type.startsWith('image/') || file.type.includes('svg')) {
            return readFileAsDataURL(file).then(resolve);
        }
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            URL.revokeObjectURL(url);
            let w = img.width, h = img.height;
            if (w > maxWidth) {
                h = Math.round((h * maxWidth) / w);
                w = maxWidth;
            }
            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, w, h);
            const dataUrl = canvas.toDataURL('image/jpeg', quality);
            resolve(dataUrl);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            readFileAsDataURL(file).then(resolve);
        };
        img.src = url;
    });
}

async function uploadFolderProject(e) {
    try {
        const files = e.target.files;
        if (!files || files.length === 0) return;
        showToast('Đang quét và tối ưu hóa thư mục dự án...', true);

        let folderName = files[0] && files[0].webkitRelativePath ? files[0].webkitRelativePath.split('/')[0] : 'Dự án';
        const textBundle = {};
        const allBatchFiles = [];
        let hasHtml = false;

        const textExts = ['html','htm','css','scss','less','js','jsx','ts','tsx','json','txt','md','py','php','c','cpp','h','hpp','cs','java','xml','csv','sql','sh','bat','yaml','yml','rb','go','rs','vue','dart','svg','env','htaccess','config','inc','tpl','twig','blade','properties','log','lock','gitignore','dockerfile'];
        const imgExts = ['jpg','jpeg','png','gif','webp','bmp','ico','avif','svg'];
        const mediaExts = ['mp3','wav','ogg','m4a','flac','mp4','webm','mkv','mov','avi','aac','wma'];

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            let relPath = file.webkitRelativePath ? file.webkitRelativePath.split('/').slice(1).join('/') : file.name;
            if (!relPath) relPath = file.name;
            const ext = relPath.split('.').pop().toLowerCase();
            if (['html','htm'].includes(ext)) hasHtml = true;

            // Bỏ qua các file rác của OS
            if (relPath.includes('.DS_Store') || relPath.includes('thumbs.db') || relPath.includes('desktop.ini')) continue;

            if (textExts.includes(ext)) {
                if (file.size > 50 * 1024 * 1024) continue; // 50MB
                const text = await readFileAsText(file);
                if (text !== null && text !== undefined) {
                    textBundle[relPath] = text;
                    allBatchFiles.push({ path: relPath, content: text, is_base64: 0 });
                }
            } else if (imgExts.includes(ext)) {
                if (file.size > 50 * 1024 * 1024) continue;
                const dataUrl = await compressImageFile(file, 2048, 0.85); // Nâng chất lượng ảnh
                if (dataUrl) {
                    allBatchFiles.push({ path: relPath, content: btoa(dataUrl), is_base64: 1 });
                }
            } else if (mediaExts.includes(ext)) {
                if (file.size > 100 * 1024 * 1024) continue; // 100MB cho video/audio
                const dataUrl = await readFileAsDataURL(file);
                if (dataUrl) {
                    allBatchFiles.push({ path: relPath, content: btoa(dataUrl), is_base64: 1 });
                }
            } else {
                // Tải mọi loại file khác (font, docs, zip, v.v...) - Không giới hạn file
                if (file.size > 100 * 1024 * 1024) continue;
                const dataUrl = await readFileAsDataURL(file);
                if (dataUrl) {
                    allBatchFiles.push({ path: relPath, content: btoa(dataUrl), is_base64: 1 });
                }
            }
        }

        const totalFiles = allBatchFiles.length;
        if (totalFiles === 0) { alert('Không có tệp hợp lệ trong thư mục!'); return; }

        const desc = `${totalFiles} tệp trong ${folderName}`;

        // Create light initial bundle (first 5 text files) for main table row
        const initialTextBundle = {};
        const textKeys = Object.keys(textBundle);
        textKeys.slice(0, 8).forEach(k => initialTextBundle[k] = textBundle[k]);
        const initialCodeJson = JSON.stringify(initialTextBundle, null, 2);

        // Step 1: Create project entry in CSDL
        showToast(`Đang khởi tạo dự án "${folderName}" (${totalFiles} tệp)...`, true);
        const formBody = new URLSearchParams();
        formBody.append('ten_du_an', utf8_to_b64('Dự án: ' + folderName));
        formBody.append('ngon_ngu', hasHtml ? 'html' : 'python');
        formBody.append('mo_ta', utf8_to_b64(desc));
        formBody.append('ma_nguon', utf8_to_b64(initialCodeJson));
        formBody.append('is_base64', '1');
        formBody.append('la_cong_khai', '0');

        const saveRes = await fetch('/tkb/api/code_storage_api.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formBody.toString()
        });
        if (!saveRes.ok) {
            const errText = await saveRes.text();
            alert('❌ Lỗi HTTP ' + saveRes.status + ':\n' + errText.substring(0, 400));
            return;
        }
        let saveData;
        try {
            saveData = await saveRes.json();
        } catch (jsonErr) {
            const rawText = await saveRes.clone().text().catch(() => '(empty)');
            alert('❌ Server trả về không phải JSON:\n' + rawText.substring(0, 400));
            return;
        }
        if (!saveData || !saveData.success || !saveData.id) {
            showToast('❌ Lỗi tạo dự án: ' + (saveData?.message || 'Không có ID'), false);
            return;
        }

        const storageId = saveData.id;

        // Step 2: Smart Batch Upload
        let currentBatch = [];
        let currentBatchSize = 0;
        let uploadedCount = 0;
        const maxPayloadSize = 2 * 1024 * 1024; // 2MB

        async function uploadBatchWithRetry(storageId, batch, total, offset) {
            const currentCount = offset + batch.length;
            const pct = Math.round((currentCount / total) * 100);
            showToast(`🚀 Đang tải lên ${currentCount}/${total} tệp (${pct}%)...`, true);

            for (let attempt = 1; attempt <= 3; attempt++) {
                try {
                    const res = await fetch('/tkb/api/code_storage_api.php?action=batch_save_files', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            storage_id: storageId,
                            files: batch
                        })
                    });
                    if (res.ok) return;
                } catch (e) {
                    console.warn('Batch upload error:', e);
                }
                if (attempt < 3) {
                    showToast(`⚠️ Lỗi mạng, đang thử lại ${currentCount} (${attempt}/3)...`, true);
                    await new Promise(r => setTimeout(r, 1500));
                }
            }
            showToast(`❌ Không thể tải lên một số tệp!`, false);
        }

        for (let i = 0; i < allBatchFiles.length; i++) {
            const file = allBatchFiles[i];
            const estSize = file.path.length + file.content.length + 100;
            
            if (currentBatch.length >= 100 || (currentBatchSize + estSize > maxPayloadSize && currentBatch.length > 0)) {
                await uploadBatchWithRetry(storageId, currentBatch, allBatchFiles.length, uploadedCount);
                uploadedCount += currentBatch.length;
                currentBatch = [];
                currentBatchSize = 0;
            }
            currentBatch.push(file);
            currentBatchSize += estSize;
        }
        
        if (currentBatch.length > 0) {
            await uploadBatchWithRetry(storageId, currentBatch, allBatchFiles.length, uploadedCount);
            uploadedCount += currentBatch.length;
        }

        showToast(`✅ Đã lưu thành công dự án lớn "${folderName}" (${totalFiles} tệp)!`, true);
        fetchProjects();
    } catch (err) {
        alert('Lỗi nạp thư mục: ' + err.message);
    } finally {
        if (e.target) e.target.value = '';
    }
}




let currentModalBanner = '';

async function uploadModalBanner(e) {
    const file = e.target.files[0];
    if (!file) return;
    showToast('Đang xử lý ảnh banner...', true);
    const dataUrl = await compressImageFile(file, 1000, 0.7);
    if (dataUrl) {
        currentModalBanner = dataUrl;
        document.getElementById('modalBannerPreviewImg').src = dataUrl;
        document.getElementById('modalBannerPreviewBox').style.display = 'block';
        showToast('Đã thêm ảnh banner!', true);
    }
}

function removeModalBanner() {
    currentModalBanner = '';
    const imgEl = document.getElementById('modalBannerPreviewImg');
    const boxEl = document.getElementById('modalBannerPreviewBox');
    if (imgEl) imgEl.src = '';
    if (boxEl) boxEl.style.display = 'none';
}

async function readModalFile(e) {
    const file = e.target.files[0];
    if (!file) return;
    const fileName = file.name;
    const ext = fileName.split('.').pop().toLowerCase();
    const textExts = ['html','htm','css','scss','less','js','jsx','ts','tsx','json','txt','md','py','php','c','cpp','h','hpp','cs','java','xml','csv','sql','sh','bat','yaml','yml','rb','go','rs','vue','dart','svg'];
    const imgExts = ['jpg','jpeg','png','gif','webp','bmp','ico'];
    const mediaExts = ['mp3','wav','ogg','m4a','flac','mp4','webm'];

    let lang = 'python';
    if (ext === 'py') lang = 'python';
    else if (['cpp','c','h','hpp'].includes(ext)) lang = 'cpp';
    else if (ext === 'java') lang = 'java';
    else if (ext === 'php') lang = 'php';
    else if (['html','htm','js','css'].includes(ext)) lang = 'html';

    document.getElementById('projectTitle').value = 'Tệp: ' + fileName;
    document.getElementById('projectLang').value = lang;

    if (imgExts.includes(ext) || mediaExts.includes(ext)) {
        showToast('Đang xử lý tệp ảnh/nhạc...', true);
        let dataUrl;
        if (imgExts.includes(ext)) {
            dataUrl = await compressImageFile(file, 1200, 0.75);
        } else {
            dataUrl = await readFileAsDataURL(file);
        }
        if (!dataUrl) { alert('Không đọc được tệp!'); return; }
        const bundle = {};
        bundle[fileName] = dataUrl;
        document.getElementById('projectCode').value = JSON.stringify(bundle, null, 4);
        document.getElementById('projectLang').value = 'html';
        showToast('Đã nạp file media ' + fileName, true);
    } else {
        const reader = new FileReader();
        reader.onload = evt => {
            document.getElementById('projectCode').value = evt.target.result;
            showToast('Đã nạp file ' + fileName, true);
        };
        reader.readAsText(file);
    }
}

async function readModalFolder(e) {
    try {
        const files = e.target.files;
        if (!files || files.length === 0) return;
        showToast('Đang nạp và tối ưu hóa các tệp trong thư mục...', true);
        let folderName = files[0] && files[0].webkitRelativePath ? files[0].webkitRelativePath.split('/')[0] : 'Dự án';
        const bundle = {};
        let hasHtml = false;
        const textExts = ['html','htm','css','scss','less','js','jsx','ts','tsx','json','txt','md','py','php','c','cpp','h','hpp','cs','java','xml','csv','sql','sh','bat','yaml','yml','rb','go','rs','vue','dart','svg'];
        const imgExts = ['jpg','jpeg','png','gif','webp','bmp','ico'];
        const mediaExts = ['mp3','wav','ogg','m4a','flac','mp4','webm'];

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            let relPath = file.webkitRelativePath ? file.webkitRelativePath.split('/').slice(1).join('/') : file.name;
            if (!relPath) relPath = file.name;
            const ext = relPath.split('.').pop().toLowerCase();
            if (['html','htm'].includes(ext)) hasHtml = true;

            if (textExts.includes(ext)) {
                if (file.size > 2 * 1024 * 1024) continue;
                const text = await readFileAsText(file);
                if (text !== null && text !== undefined) bundle[relPath] = text;
            } else if (imgExts.includes(ext)) {
                if (file.size > 15 * 1024 * 1024) continue;
                const dataUrl = await compressImageFile(file, 1200, 0.75);
                if (dataUrl) bundle[relPath] = dataUrl;
            } else if (mediaExts.includes(ext)) {
                if (file.size > 4 * 1024 * 1024) continue;
                const dataUrl = await readFileAsDataURL(file);
                if (dataUrl) bundle[relPath] = dataUrl;
            }
        }
        if (Object.keys(bundle).length === 0) { alert('Không có tệp hợp lệ!'); return; }
        const code = JSON.stringify(bundle, null, 4);
        document.getElementById('projectTitle').value = 'Dự án: ' + folderName;
        document.getElementById('projectLang').value = hasHtml ? 'html' : 'python';
        document.getElementById('projectDesc').value = `${Object.keys(bundle).length} tệp trong ${folderName}`;
        document.getElementById('projectCode').value = code;
        showToast(`Đã nạp ${Object.keys(bundle).length} tệp (kèm ảnh/nhạc) từ thư mục ${folderName}`, true);
    } catch(err) { alert('Lỗi nạp thư mục: ' + err.message); }
}

async function uploadAndRunCode(e) {
    try {
        const file = e.target.files[0];
        if (!file) return;
        const fileName = file.name;
        const ext = fileName.split('.').pop().toLowerCase();
        const textExts = ['html','htm','css','scss','less','js','jsx','ts','tsx','json','txt','md','py','php','c','cpp','h','hpp','cs','java','xml','csv','sql','sh','bat','yaml','yml','rb','go','rs','vue','dart','svg'];
        const imgExts = ['jpg','jpeg','png','gif','webp','bmp','ico'];
        const mediaExts = ['mp3','wav','ogg','m4a','flac','mp4','webm'];

        if (ext === 'zip') {
            showToast('Đang giải nén .ZIP...', true);
            try {
                const zip = await JSZip.loadAsync(file);
                const textBundle = {};
                const allBatchFiles = [];
                let hasHtml = false;

                const mimeMap = {
                    jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', gif: 'image/gif', webp: 'image/webp',
                    ico: 'image/x-icon', mp3: 'audio/mpeg', wav: 'audio/wav', ogg: 'audio/ogg', mp4: 'video/mp4'
                };

                for (const relPath in zip.files) {
                    const zipObj = zip.files[relPath];
                    if (zipObj.dir) continue;
                    const fe = relPath.split('.').pop().toLowerCase();
                    if (['html','htm'].includes(fe)) hasHtml = true;

                    const parts = relPath.split('/');
                    const cp = parts.length > 1 ? parts.slice(1).join('/') : relPath;
                    if (!cp) continue;

                    if (textExts.includes(fe)) {
                        const text = await zipObj.async('string');
                        textBundle[cp] = text;
                        allBatchFiles.push({ path: cp, content: text, is_base64: 0 });
                    } else if (imgExts.includes(fe) || mediaExts.includes(fe)) {
                        const b64 = await zipObj.async('base64');
                        const mime = mimeMap[fe] || 'application/octet-stream';
                        const dataUrl = `data:${mime};base64,${b64}`;
                        allBatchFiles.push({ path: cp, content: btoa(dataUrl), is_base64: 1 });
                    }
                }

                const totalFiles = allBatchFiles.length;
                if (totalFiles === 0) { alert('Không tìm thấy tệp hợp lệ trong .ZIP!'); return; }

                const projTitle = 'Dự án .ZIP: ' + fileName.replace(/\.zip$/i, '');
                const desc = `${totalFiles} tệp từ ${fileName}`;

                // Light initial bundle for main row
                const initialTextBundle = {};
                const textKeys = Object.keys(textBundle);
                textKeys.slice(0, 8).forEach(k => initialTextBundle[k] = textBundle[k]);
                const initialCodeJson = JSON.stringify(initialTextBundle, null, 2);

                // Step 1: Save project row
                showToast(`Đang nạp file ZIP "${fileName}" (${totalFiles} tệp)...`, true);
                const params = new URLSearchParams();
                params.append('ten_du_an', utf8_to_b64(projTitle));
                params.append('ngon_ngu', hasHtml ? 'html' : 'python');
                params.append('mo_ta', utf8_to_b64(desc));
                params.append('ma_nguon', utf8_to_b64(initialCodeJson));
                params.append('is_base64', '1');
                params.append('la_cong_khai', '0');

                const saveRes = await fetch('/tkb/api/code_storage_api.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
                const saveData = await saveRes.json();
                if (!saveData.success || !saveData.id) {
                    showToast('❌ Lỗi lưu ZIP: ' + (saveData.message || ''), false);
                    return;
                }

                const storageId = saveData.id;

                // Step 2: Upload all files in batch chunks of 5 files
                const batchSize = 5;
                for (let i = 0; i < allBatchFiles.length; i += batchSize) {
                    const batch = allBatchFiles.slice(i, i + batchSize);
                    const currentCount = Math.min(i + batchSize, allBatchFiles.length);
                    const pct = Math.round((currentCount / allBatchFiles.length) * 100);
                    showToast(`🚀 Đang giải nén & lưu tệp ${currentCount}/${allBatchFiles.length} (${pct}%)...`, true);

                    await fetch('/tkb/api/code_storage_api.php?action=batch_save_files', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            storage_id: storageId,
                            files: batch
                        })
                    }).catch(e => console.warn('⚠️ Batch ZIP save warning:', e));
                }

                showToast(`✅ Đã giải nén & lưu thành công ${totalFiles} tệp từ .ZIP!`, true);
                fetchProjects();
            } catch (err) {
                alert('Lỗi đọc ZIP: ' + err.message);
            } finally {
                if (e.target) e.target.value = '';
            }
            return;
        }

        // Single file upload (Image / Media / Code)
        let lang = 'python';
        if (ext === 'py') lang = 'python';
        else if (['cpp','c','h','hpp'].includes(ext)) lang = 'cpp';
        else if (ext === 'java') lang = 'java';
        else if (ext === 'php') lang = 'php';
        else if (['html','htm','js','css'].includes(ext)) lang = 'html';

        if (imgExts.includes(ext) || mediaExts.includes(ext)) {
            showToast('Đang xử lý tệp ảnh/nhạc...', true);
            let dataUrl;
            if (imgExts.includes(ext)) {
                dataUrl = await compressImageFile(file, 1200, 0.75);
            } else {
                dataUrl = await readFileAsDataURL(file);
            }
            if (!dataUrl) { alert('Không đọc được tệp!'); return; }

            const bundle = {};
            bundle[fileName] = dataUrl;
            autoSaveProject('Tệp media: ' + fileName, 'html', 'Tải lên từ máy tính', JSON.stringify(bundle, null, 2));
        } else {
            if (file.size > 5 * 1024 * 1024) { alert('File quá lớn (>5MB)!'); return; }
            const text = await readFileAsText(file);
            if (text !== null) {
                autoSaveProject('Tệp: ' + fileName, lang, 'Tải lên từ máy tính', text);
            }
        }
    } catch (err) {
        alert('Lỗi: ' + err.message);
    } finally {
        if (e.target) e.target.value = '';
    }
}

function renderStats(stats) {
    if (!stats) return;
    document.getElementById('statTotal').innerText = stats.total || 0;
    document.getElementById('statPython').innerText = stats.count_python || 0;
    document.getElementById('statCpp').innerText = (parseInt(stats.count_cpp)||0)+(parseInt(stats.count_java)||0);
    document.getElementById('statHtml').innerText = stats.count_html || 0;
}

function getLangBadgeClass(lang) {
    const l = (lang||'').toLowerCase();
    return { python:'lang-python', cpp:'lang-cpp', c:'lang-c', java:'lang-java', php:'lang-php', html:'lang-html' }[l] || 'lang-python';
}
function getLangName(lang) {
    const l = (lang||'').toLowerCase();
    return { python:'🐍 Python', cpp:'⚡ C++', c:'⚡ C', java:'☕ Java', php:'🐘 PHP', html:'🌐 Web HTML' }[l] || (lang||'').toUpperCase();
}

function renderProjects(projects) {
    const grid = document.getElementById('projectsGrid');
    if (!grid) return;
    window.cachedProjects = window.cachedProjects || {};
    if (Array.isArray(projects)) {
        projects.forEach(p => { if (p && p.id) window.cachedProjects[p.id] = p; });
    }
    if (!projects || !projects.length) {
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px;">
                <p style="color: #64748b; font-size: 14.5px; margin: 0 0 16px 0;">Kho lưu trữ chưa có bài làm code nào.</p>
                <button type="button" onclick="openCreateModal()" style="background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #ffffff; border: none; padding: 11px 24px; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-plus"></i> Tạo Bài Làm Mới
                </button>
            </div>
        `;
        return;
    }
    try {
        grid.innerHTML = projects.map(item => {
            if (!item) return '';
            const lang = item.ngon_ngu||'python', bc = getLangBadgeClass(lang), ll = getLangName(lang);
            let previewHtml = '';
            const fileNames = item.file_names || [];

            if (fileNames.length > 0) {
                const chips = fileNames.map(f => {
                    const ext = f.split('.').pop().toLowerCase();
                    let icon = '📄';
                    if (['html','htm'].includes(ext)) icon = '🌐';
                    else if (['css','scss'].includes(ext)) icon = '🎨';
                    else if (['js','ts'].includes(ext)) icon = '📜';
                    else if (['jpg','jpeg','png','gif','webp','ico','bmp'].includes(ext)) icon = '🖼️';
                    else if (['mp3','wav','ogg','m4a','flac'].includes(ext)) icon = '🎵';
                    else if (['mp4','webm'].includes(ext)) icon = '🎬';
                    else if (['py','php','c','cpp','java','cs'].includes(ext)) icon = '⚡';
                    return `<span style="display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,0.08);padding:3px 8px;border-radius:6px;font-size:11.5px;color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);">${icon} ${escapeHtml(f)}</span>`;
                }).join(' ');

                previewHtml = `<div style="margin-bottom:6px;font-size:12px;font-weight:700;color:#38bdf8;display:flex;align-items:center;gap:6px;"><i class="fa-solid fa-folder-tree"></i> Danh sách ${fileNames.length} tệp trong dự án:</div><div style="display:flex;flex-wrap:wrap;gap:6px;max-height:80px;overflow-y:auto;">${chips}</div>`;
            } else {
                let preview = escapeHtml(item.preview_code || '');
                const rawPreview = (item.preview_code || '').trim();
                if (rawPreview.startsWith('{')) {
                    try {
                        const obj = JSON.parse(rawPreview);
                        const mainFile = Object.keys(obj).find(k => k.toLowerCase() === 'index.html' || k.toLowerCase().endsWith('/index.html')) || Object.keys(obj)[0];
                        if (mainFile && obj[mainFile]) {
                            preview = `<div style="font-weight:700;color:#38bdf8;margin-bottom:4px;font-size:11px;"><i class="fa-regular fa-file-code"></i> Tệp: ${escapeHtml(mainFile)}</div>` + escapeHtml(obj[mainFile].substring(0, 250));
                        }
                    } catch(e) {}
                }
                previewHtml = preview;
            }

            const title = escapeHtml(item.ten_du_an||'Bài làm');
            const desc = item.mo_ta ? escapeHtml(item.mo_ta) : '';
            const updated = item.updated_at||item.created_at||'Vừa nạp', codeLen = item.code_length||0;
            const itemId = item.id||0, ghUrl = item.github_url||'';

            const gradientMap = {
                python: 'linear-gradient(135deg, #1e293b 0%, #0f172a 50%, #38bdf8 100%)',
                html: 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #a855f7 100%)',
                cpp: 'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0284c7 100%)',
                c: 'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0284c7 100%)',
                java: 'linear-gradient(135deg, #0f172a 0%, #312e81 50%, #ea580c 100%)',
                php: 'linear-gradient(135deg, #0f172a 0%, #2e1065 50%, #8b5cf6 100%)'
            };
            const gradBg = gradientMap[lang] || gradientMap.python;

            const thumbHtml = item.thumbnail ? `<div style="width:100%;height:130px;border-radius:14px;overflow:hidden;margin-bottom:12px;background:#0f172a;border:1px solid rgba(255,255,255,0.15);position:relative;box-shadow:0 6px 16px rgba(0,0,0,0.35);">
                <img src="${item.thumbnail}" style="width:100%;height:100%;object-fit:cover;display:block;" alt="Ảnh Banner Dự Án" onerror="this.style.display='none';if(this.nextElementSibling)this.nextElementSibling.style.display='flex';">
                <div style="width:100%;height:100%;display:none;background:${gradBg};align-items:center;justify-content:center;position:relative;">
                    <div style="font-size:36px;opacity:0.2;position:absolute;left:16px;bottom:-6px;"><i class="fa-solid fa-code"></i></div>
                    <div style="font-size:14px;font-weight:800;color:#f8fafc;z-index:2;letter-spacing:0.5px;text-align:center;padding:0 16px;">${ll} - ${title}</div>
                </div>
                <div style="position:absolute;top:8px;right:8px;background:rgba(15,23,42,0.85);backdrop-filter:blur(8px);padding:4px 10px;border-radius:8px;font-size:11px;color:#38bdf8;font-weight:700;border:1px solid rgba(56,189,248,0.3);">
                    <i class="fa-solid fa-image"></i> Banner Ảnh
                </div>
            </div>` : `<div style="width:100%;height:110px;border-radius:14px;overflow:hidden;margin-bottom:12px;background:${gradBg};border:1px solid rgba(255,255,255,0.12);position:relative;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.25);">
                <div style="font-size:36px;opacity:0.2;position:absolute;left:16px;bottom:-6px;"><i class="fa-solid fa-code"></i></div>
                <div style="font-size:14px;font-weight:800;color:#f8fafc;z-index:2;letter-spacing:0.5px;text-align:center;padding:0 16px;">
                    ${ll} - ${title}
                </div>
            </div>`;

            const isPublic = (item.la_cong_khai == 1 || item.la_cong_khai === undefined);
            const statusBadge = isPublic 
                ? `<span style="background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.3);padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;"><i class="fa-solid fa-globe"></i> Công khai</span>` 
                : `<span style="background:rgba(148,163,184,0.15);color:#94a3b8;border:1px solid rgba(148,163,184,0.3);padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;"><i class="fa-solid fa-lock"></i> Riêng tư</span>`;

            return `<div class="project-card"><div>
                <div class="card-top"><div class="project-title">${title}</div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                    ${statusBadge}
                    ${ghUrl?`<a href="${escapeHtml(ghUrl)}" target="_blank" class="lang-badge" style="background:#24292e;color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.2);"><i class="fa-brands fa-github"></i> GitHub</a>`:''}
                    <span class="lang-badge ${bc}">${ll}</span></div></div>
                ${desc?`<div class="project-desc">${desc}</div>`:''}
                ${thumbHtml}
                <div class="code-snippet-box">${previewHtml}</div></div>
            <div><div style="font-size:11.5px;color:#64748b;margin-bottom:10px;display:flex;justify-content:space-between;">
                <span><i class="fa-regular fa-clock"></i> ${updated}</span><span>(${codeLen} ký tự)</span></div>
            <div class="card-actions">
                <a href="/tkb/student/code_ide.php?storage_id=${itemId}" class="btn-act btn-run-ide"><i class="fa-solid fa-play"></i> IDE</a>
                <button class="btn-act" style="background:rgba(99,102,241,0.15);color:#818cf8;border:1px solid rgba(99,102,241,0.3);" onclick="copyProjectShareUrl(${itemId})" title="Sao chép liên kết chia sẻ"><i class="fa-solid fa-share-nodes"></i> Chia sẻ</button>
                <button class="btn-act" style="background:rgba(255,255,255,0.06);color:${isPublic?'#34d399':'#94a3b8'};border:1px solid rgba(255,255,255,0.1);" onclick="togglePublicStatus(${itemId})" title="Chuyển đổi Công khai / Riêng tư">${isPublic?'🌐':'🔒'}</button>
                <button class="btn-act btn-copy-code" onclick="copyCode(${itemId})" title="Copy mã nguồn"><i class="fa-regular fa-copy"></i></button>
                <button class="btn-act btn-edit-code" onclick="openEditModal(${itemId})" title="Sửa bài làm"><i class="fa-solid fa-pen"></i></button>
                <button class="btn-act btn-del-code" onclick="deleteProject(${itemId})" title="Xóa bài làm"><i class="fa-solid fa-trash"></i></button>
            </div></div></div>`;
        }).join('');
    } catch(err) { console.error("renderProjects error:", err); }
}

function copyProjectShareUrl(id) {
    const url = `${window.location.origin}/tkb/student/code_ide.php?storage_id=${id}`;
    navigator.clipboard.writeText(url).then(() => {
        showToast('Đã sao chép liên kết chia sẻ dự án vào khay nhớ tạm!');
    }).catch(() => {
        showToast('Không thể sao chép liên kết');
    });
}

async function togglePublicStatus(id) {
    showToast('Đang cập nhật trạng thái chia sẻ...', true);
    try {
        const res = await fetch(`/tkb/api/code_storage_api.php?action=toggle_public&id=${id}`, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            showToast('✅ ' + data.message, true);
            fetchProjects();
        } else {
            showToast('❌ ' + data.message, false);
        }
    } catch(e) {
        showToast('❌ Lỗi kết nối: ' + e.message, false);
    }
}

function openGitHubModal() {
    const urlInput = document.getElementById('githubRepoUrl');
    if (urlInput) urlInput.value = '';
    const titleInput = document.getElementById('githubCustomTitle');
    if (titleInput) titleInput.value = '';
    const modal = document.getElementById('githubModal');
    if (modal) { modal.style.display = 'flex'; modal.classList.add('show'); }
}

function closeGitHubModal() {
    const modal = document.getElementById('githubModal');
    if (modal) { modal.style.display = 'none'; modal.classList.remove('show'); }
}

async function saveGitHubProject() {
    let input = (document.getElementById('githubRepoUrl')||{}).value || '';
    input = input.trim();
    const customTitle = ((document.getElementById('githubCustomTitle')||{}).value||'').trim();
    if (!input) { showToast('Vui lòng nhập link GitHub!', false); return; }

    const btn = document.getElementById('btnSaveGh');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang tải...'; }
    showToast('⌛ Đang kết nối GitHub...', true);

    let cleaned = input.replace(/^https?:\/\/(www\.)?github\.com\//i,'').replace(/\/$/,'').replace(/\.git$/i,'');
    const parts = cleaned.split('/').filter(p=>p.length>0);
    let owner = parts[0]||'', repo = parts[1]||parts[0]||'repository';
    let ghTitle = customTitle||`GitHub: ${repo}`, ghLang = 'python';
    let ghDesc = `Dự án GitHub: ${input}`;
    let ghCode = `// GitHub Repository: https://github.com/${owner}/${repo}`;
    let ghUrl = `https://github.com/${owner}/${repo}`;

    if (parts.length >= 2) {
        try {
            const res = await fetch(`https://api.github.com/repos/${owner}/${repo}`);
            if (res.ok) {
                const data = await res.json();
                if (!customTitle) ghTitle = 'GitHub: ' + data.name;
                ghDesc = (data.description||'Dự án GitHub') + ` | ⭐ ${data.stargazers_count} stars | 🍴 ${data.forks_count} forks`;
                ghUrl = data.html_url;
                const lr = (data.language||'').toLowerCase();
                if (['html','javascript','typescript','css'].includes(lr)) ghLang='html';
                else if (['c++','c','cuda'].includes(lr)) ghLang='cpp';
                else if (lr==='java') ghLang='java';
                else if (lr==='php') ghLang='php';
                else if (lr==='python') ghLang='python';
            }
        } catch(err){ console.warn("GitHub API:",err); }

        // Fetch all files in repository contents
        try {
            const contentsRes = await fetch(`https://api.github.com/repos/${owner}/${repo}/contents`);
            if (contentsRes.ok) {
                const contentsData = await contentsRes.json();
                if (Array.isArray(contentsData) && contentsData.length > 0) {
                    const bundle = {};
                    let hasHtml = false;
                    for (const item of contentsData) {
                        if (item.type === 'file' && item.download_url) {
                            if (item.size > 2 * 1024 * 1024) continue;
                            const fileRes = await fetch(item.download_url);
                            if (fileRes.ok) {
                                const fileText = await fileRes.text();
                                bundle[item.name] = fileText;
                                const ext = item.name.split('.').pop().toLowerCase();
                                if (['html','htm'].includes(ext)) hasHtml = true;
                            }
                        }
                    }
                    if (Object.keys(bundle).length > 0) {
                        if (hasHtml) ghLang = 'html';
                        const nonReadmeKeys = Object.keys(bundle).filter(k => k.toLowerCase() !== 'readme.md');
                        const htmlKeys = nonReadmeKeys.filter(k => ['html','htm'].includes(k.split('.').pop().toLowerCase()));

                        if (htmlKeys.length > 0) {
                            if (nonReadmeKeys.length <= 2) {
                                ghCode = bundle[htmlKeys[0]];
                            } else {
                                ghCode = JSON.stringify(bundle, null, 2);
                            }
                        } else if (nonReadmeKeys.length === 1) {
                            ghCode = bundle[nonReadmeKeys[0]];
                        } else if (nonReadmeKeys.length > 1) {
                            ghCode = JSON.stringify(bundle, null, 2);
                        } else {
                            ghCode = bundle[Object.keys(bundle)[0]];
                        }
                    }
                }
            }
        } catch(e) { console.warn("GitHub contents error:", e); }

        // Support direct file link e.g. https://github.com/NhutKhanh-Coder/Share-code-/blob/main/NgayKyNiem.html
        const blobIdx = parts.indexOf('blob');
        if (blobIdx > 0 && parts.length > blobIdx + 2) {
            const branch = parts[blobIdx + 1];
            const filePath = parts.slice(blobIdx + 2).join('/');
            const rawUrl = `https://raw.githubusercontent.com/${owner}/${repo}/${branch}/${filePath}`;
            try {
                const rawRes = await fetch(rawUrl);
                if (rawRes.ok) {
                    ghCode = await rawRes.text();
                    const fileName = filePath.split('/').pop();
                    if (!customTitle) ghTitle = `Tệp: ${fileName}`;
                    const ext = fileName.split('.').pop().toLowerCase();
                    if (['html','htm','css','js'].includes(ext)) ghLang = 'html';
                    else if (['py'].includes(ext)) ghLang = 'python';
                    else if (['cpp','c'].includes(ext)) ghLang = 'cpp';
                }
            } catch(e) {}
        }
    }

    showToast('🚀 Đang lưu vào CSDL...', true);

    try {
        const params = new URLSearchParams();
        params.append('ten_du_an', utf8_to_b64(ghTitle));
        params.append('ngon_ngu', ghLang);
        params.append('mo_ta', utf8_to_b64(ghDesc));
        params.append('ma_nguon', utf8_to_b64(ghCode));
        params.append('github_url', utf8_to_b64(ghUrl));
        params.append('is_base64', '1');

        const resSave = await fetch('/tkb/api/code_storage_api.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });
        const resJson = await resSave.json();
        if (resJson.success) {
            showToast('🎉 Đã lưu dự án GitHub thành công!');
            if (btn) { btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-cloud-arrow-down"></i> Lưu Vào Kho Code Sinh Viên'; }
            closeGitHubModal();
            fetchProjects();
        } else {
            showToast('❌ Lỗi: ' + (resJson.message || 'Không thể lưu dự án GitHub'), false);
            if (btn) { btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-cloud-arrow-down"></i> Lưu Vào Kho Code Sinh Viên'; }
        }
    } catch (err) {
        showToast('❌ Lỗi kết nối: ' + err.message, false);
        if (btn) { btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-cloud-arrow-down"></i> Lưu Vào Kho Code Sinh Viên'; }
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;");
}

function onLanguageChange() {
    const lang = document.getElementById('projectLang').value;
    const codeArea = document.getElementById('projectCode');
    if (codeArea && (!codeArea.value || Object.values(templates).includes(codeArea.value.trim()))) {
        codeArea.value = templates[lang] || '';
    }
}

function openCreateModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus-circle" style="color:#a855f7;"></i> Tạo Bài Làm Code Mới';
    document.getElementById('projectId').value = 0;
    document.getElementById('projectTitle').value = '';
    document.getElementById('projectDesc').value = '';
    document.getElementById('projectLang').value = 'python';
    document.getElementById('projectCode').value = templates.python;
    const modal = document.getElementById('projectModal');
    if (modal) { modal.style.display='flex'; modal.classList.add('show'); }
}

function cleanCodeForTextarea(rawCode) {
    if (!rawCode) return '';
    const str = String(rawCode).trim();
    if (str.startsWith('{') || str.startsWith('"{')) {
        try {
            const obj = JSON.parse(str);
            if (typeof obj === 'object' && obj !== null) {
                const cleaned = {};
                for (const [key, val] of Object.entries(obj)) {
                    if (typeof val === 'string' && (val.startsWith('data:') || val.length > 50000)) {
                        const ext = key.split('.').pop().toLowerCase();
                        if (['mp3','wav','ogg','m4a','flac','mp4','webm'].includes(ext)) {
                            cleaned[key] = `[Tệp âm thanh/video: ${key} - Lưu trong kho]`;
                        } else if (['jpg','jpeg','png','gif','webp','bmp','ico'].includes(ext)) {
                            cleaned[key] = `[Tệp hình ảnh: ${key} - Lưu trong kho]`;
                        } else {
                            cleaned[key] = val;
                        }
                    } else {
                        cleaned[key] = val;
                    }
                }
                return JSON.stringify(cleaned, null, 4);
            }
        } catch(e) {}
    }
    return str;
}

function openEditModal(id) {
    const modal = document.getElementById('projectModal');
    if (modal) {
        document.body.appendChild(modal);
        modal.style.display = 'flex';
        modal.classList.add('show');
    }

    const cached = (window.cachedProjects && window.cachedProjects[id]) ? window.cachedProjects[id] : null;

    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color:#38bdf8;"></i> Chỉnh Sửa Kho Code';
    document.getElementById('projectId').value = id;

    if (cached) {
        document.getElementById('projectTitle').value = cached.ten_du_an || '';
        document.getElementById('projectDesc').value = cached.mo_ta || '';
        document.getElementById('projectLang').value = cached.ngon_ngu || 'python';
        document.getElementById('projectCode').value = cleanCodeForTextarea(cached.ma_nguon || cached.preview_code || '');
        const pubCb = document.getElementById('projectIsPublic');
        if (pubCb) pubCb.checked = (cached.la_cong_khai == 1 || cached.la_cong_khai === undefined);

        if (cached.banner_img || cached.thumbnail) {
            currentModalBanner = cached.banner_img || cached.thumbnail;
            document.getElementById('modalBannerPreviewImg').src = currentModalBanner;
            document.getElementById('modalBannerPreviewBox').style.display = 'block';
        } else {
            removeModalBanner();
        }
    } else {
        document.getElementById('projectTitle').value = 'Đang nạp...';
        document.getElementById('projectDesc').value = '';
        document.getElementById('projectCode').value = 'Đang nạp mã nguồn...';
    }

    // Silent background sync for full code if needed
    fetch(`/tkb/api/code_storage_api.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const fullItem = res.data;
                window.cachedProjects[id] = fullItem;
                document.getElementById('projectTitle').value = fullItem.ten_du_an || '';
                document.getElementById('projectDesc').value = fullItem.mo_ta || '';
                document.getElementById('projectLang').value = fullItem.ngon_ngu || 'python';
                document.getElementById('projectCode').value = cleanCodeForTextarea(fullItem.ma_nguon || '');
                const pubCb = document.getElementById('projectIsPublic');
                if (pubCb) pubCb.checked = (fullItem.la_cong_khai == 1 || fullItem.la_cong_khai === undefined);
                if (fullItem.banner_img) {
                    currentModalBanner = fullItem.banner_img;
                    document.getElementById('modalBannerPreviewImg').src = fullItem.banner_img;
                    document.getElementById('modalBannerPreviewBox').style.display = 'block';
                }
            }
        }).catch(err => {});
}

function closeModal() {
    const modal = document.getElementById('projectModal');
    if (modal){modal.style.display='none';modal.classList.remove('show');}
}

async function saveProject(e) {
    e.preventDefault();
    const id = parseInt(document.getElementById('projectId').value || 0);
    const title = document.getElementById('projectTitle').value.trim();
    const code = document.getElementById('projectCode').value;
    const lang = document.getElementById('projectLang').value;
    const desc = (document.getElementById('projectDesc').value || '').trim();
    const isPublic = document.getElementById('projectIsPublic') ? (document.getElementById('projectIsPublic').checked ? '1' : '0') : '1';
    if (!title) { showToast('Vui lòng nhập tên bài làm!', false); return; }
    showToast('🚀 Đang lưu bài làm...', true);
    try {
        const params = new URLSearchParams();
        params.append('ten_du_an', utf8_to_b64(title));
        params.append('ngon_ngu', lang);
        params.append('mo_ta', utf8_to_b64(desc));
        params.append('ma_nguon', utf8_to_b64(code));
        if (currentModalBanner) {
            if (currentModalBanner.startsWith('data:')) {
                params.append('banner_img', currentModalBanner);
            } else {
                params.append('banner_img', utf8_to_b64(currentModalBanner));
            }
        }
        params.append('is_base64', '1');
        params.append('la_cong_khai', isPublic);
        if (id > 0) params.append('id', id);

        const res = await fetch('/tkb/api/code_storage_api.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });
        const data = await res.json();
        if (data.success) {
            closeModal();
            showToast('✅ Đã lưu bài làm thành công!', true);
            setTimeout(() => fetchProjects(), 500);
        } else {
            showToast('❌ ' + (data.message || 'Không lưu được!'), false);
        }
    } catch (err) {
        showToast('❌ Lỗi kết nối: ' + err.message, false);
    }
}

async function saveAndRunProject(e) {
    if (e) e.preventDefault();
    const id = parseInt(document.getElementById('projectId').value || 0);
    const title = document.getElementById('projectTitle').value.trim();
    const code = document.getElementById('projectCode').value;
    const lang = document.getElementById('projectLang').value;
    const desc = (document.getElementById('projectDesc').value || '').trim();
    const isPublic = document.getElementById('projectIsPublic') ? (document.getElementById('projectIsPublic').checked ? '1' : '0') : '1';
    if (!title || !code) { showToast('Vui lòng nhập tên và code!', false); return; }
    showToast('Đang lưu và chuyển sang IDE...', true);
    try {
        const params = new URLSearchParams();
        params.append('ten_du_an', utf8_to_b64(title));
        params.append('ngon_ngu', lang);
        params.append('mo_ta', utf8_to_b64(desc));
        params.append('ma_nguon', utf8_to_b64(code));
        if (currentModalBanner) {
            if (currentModalBanner.startsWith('data:')) {
                params.append('banner_img', currentModalBanner);
            } else {
                params.append('banner_img', utf8_to_b64(currentModalBanner));
            }
        }
        params.append('is_base64', '1');
        params.append('la_cong_khai', isPublic);
        if (id > 0) params.append('id', id);

        const res = await fetch('/tkb/api/code_storage_api.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });
        const data = await res.json();
        if (data.success && data.id) {
            closeModal();
            window.location.href = `/tkb/student/code_ide.php?storage_id=${data.id}&autorun=1`;
        } else {
            showToast('❌ ' + (data.message || 'Lỗi lưu'), false);
        }
    } catch (err) {
        showToast('❌ Lỗi kết nối máy chủ', false);
    }
}

function copyCode(id) {
    fetch(`/tkb/api/code_storage_api.php?action=get&id=${id}`)
        .then(res=>res.json())
        .then(res=>{if(res.success&&res.data){navigator.clipboard.writeText(res.data.ma_nguon).then(()=>showToast('Đã sao chép code!')).catch(()=>showToast('Không thể sao chép',false));}});
}

function copyProjectShareUrl(id) {
    openSingleShareModal(id);
}

function openSingleShareModal(id) {
    const item = (window.cachedProjects && window.cachedProjects[id]) ? window.cachedProjects[id] : null;
    let modal = document.getElementById('singleShareModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'singleShareModal';
        modal.className = 'modal-backdrop';
        modal.innerHTML = `
            <div class="modal-content-box" style="max-width: 540px;">
                <div class="modal-header">
                    <h3 style="margin: 0; color: #f8fafc; font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-share-nodes" style="color:#a855f7;"></i> Chia Sẻ Bài Làm Code
                    </h3>
                    <button type="button" style="background:none; border:none; color:#94a3b8; font-size:22px; cursor:pointer;" onclick="closeSingleShareModal()">&times;</button>
                </div>
                <div class="modal-body" id="singleShareModalBody">
                </div>
                <div class="modal-footer">
                    <button class="btn-cancel" onclick="closeSingleShareModal()">Đóng</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    const shareUrl = `${window.location.origin}/tkb/student/code_ide.php?storage_id=${id}`;
    const title = item ? escapeHtml(item.ten_du_an || 'Bài làm') : 'Bài làm';
    const isPublic = item ? (item.la_cong_khai == 1 || item.la_cong_khai === undefined) : true;

    const body = document.getElementById('singleShareModalBody');
    if (body) {
        body.innerHTML = `
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(168, 85, 247, 0.15); color: #c084fc; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 12px;">
                    <i class="fa-solid fa-code"></i>
                </div>
                <h3 style="margin: 0 0 6px 0; color: #ffffff; font-size: 19px; font-weight: 800;">${title}</h3>
                <span style="font-size: 12px; font-weight: 700; color: ${isPublic ? '#34d399' : '#94a3b8'}; background: ${isPublic ? 'rgba(16,185,129,0.15)' : 'rgba(255,255,255,0.08)'}; padding: 3px 10px; border-radius: 8px; border: 1px solid ${isPublic ? 'rgba(16,185,129,0.3)' : 'rgba(255,255,255,0.1)'};">
                    ${isPublic ? '🌐 Đang Công Khai Trên Cộng Đồng' : '🔒 Đang Riêng Tư'}
                </span>
            </div>

            <div style="margin-bottom: 20px;">
                <label class="form-label-code">ĐƯỜNG DẪN CHIA SẺ TRỰC TIẾP</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" readonly value="${shareUrl}" id="shareUrlInput" class="modal-input" style="margin-bottom: 0; font-family: monospace; font-size: 13px;">
                    <button type="button" class="btn-save-project" style="white-space: nowrap; padding: 10px 16px;" onclick="copyInputUrl()">
                        <i class="fa-solid fa-copy"></i> Sao chép
                    </button>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button type="button" class="btn-act" style="padding: 13px; font-size: 14px; justify-content: center; background: ${isPublic ? 'rgba(239, 68, 68, 0.18)' : 'linear-gradient(135deg, #a855f7 0%, #7c3aed 100%)'}; color: #ffffff; border: 1px solid ${isPublic ? 'rgba(239, 68, 68, 0.4)' : 'none'}; box-shadow: ${isPublic ? 'none' : '0 6px 20px rgba(168, 85, 247, 0.4)'};" onclick="togglePublicFromSingleModal(${id})">
                    <i class="fa-solid ${isPublic ? 'fa-lock' : 'fa-globe'}"></i> ${isPublic ? '🔒 Chuyển Sang Riêng Tư (Thu Hồi Chia Sẻ)' : '🌐 Đăng & Chia Sẻ Lên Kho Cộng Đồng Ngay'}
                </button>

                <a href="/tkb/student/code_ide.php?storage_id=${id}" target="_blank" class="btn-act btn-run-ide" style="padding: 12px; font-size: 14px;">
                    <i class="fa-solid fa-play"></i> Mở Trực Tiếp Trên Trình Chạy IDE
                </a>
            </div>
        `;
    }

    modal.style.display = 'flex';
    modal.classList.add('show');
}

function closeSingleShareModal() {
    const modal = document.getElementById('singleShareModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
}

function copyInputUrl() {
    const input = document.getElementById('shareUrlInput');
    if (input) {
        input.select();
        navigator.clipboard.writeText(input.value).then(() => {
            showToast('Đã sao chép liên kết chia sẻ!');
        });
    }
}

async function togglePublicFromSingleModal(id) {
    showToast('Đang cập nhật trạng thái...', true);
    try {
        const res = await fetch(`/tkb/api/code_storage_api.php?action=toggle_public&id=${id}`, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            showToast('✅ ' + data.message, true);
            if (window.cachedProjects && window.cachedProjects[id]) {
                window.cachedProjects[id].la_cong_khai = data.la_cong_khai;
            }
            openSingleShareModal(id);
            if (typeof fetchProjects === 'function') fetchProjects();
            if (typeof fetchPublicProjects === 'function') fetchPublicProjects();
        } else {
            showToast('❌ ' + data.message, false);
        }
    } catch(e) {
        showToast('❌ Lỗi kết nối: ' + e.message, false);
    }
}

function deleteProject(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa bài làm này?')) return;
    fetch('/tkb/api/code_storage_api.php?action=delete',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`id=${id}`})
        .then(res=>res.json())
        .then(res=>{if(res.success){showToast(res.message||'Đã xóa!');fetchProjects();}else{showToast(res.message||'Lỗi xóa',false);}});
}

function showToast(msg, isSuccess = true) {
    const toast = document.getElementById('toastMsg');
    const toastText = document.getElementById('toastText');
    if (!toast || !toastText) {
        alert(msg);
        return;
    }
    toastText.innerText = msg;
    toast.style.background = isSuccess ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
    toast.style.display = 'flex';
    toast.classList.add('show');
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => {
        toast.classList.remove('show');
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(100px)';
        setTimeout(() => { toast.style.display = 'none'; }, 300);
    }, 3000);
}
