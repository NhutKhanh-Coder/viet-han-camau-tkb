<?php
require_once '../config.php';
requireStudent();
header('Location: /tkb/student/lam_bai_tap.php');
exit;

// Fetch student info
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $sv_id);
$stmt->execute();
$sv = $stmt->get_result()->fetch_assoc();
$lop = $sv['lop'] ?? '';

$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'submit' || $action === 'save_submission') {
    $aid = (int)$_POST['assignment_id'];
    $text = trim($_POST['submission_content'] ?? '');
    
    // File Upload handling
    $file_url = '';
    if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/submissions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = time() . '_' . basename($_FILES['attachment_file']['name']);
        $target_file = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['attachment_file']['tmp_name'], $target_file)) {
            $file_url = '/tkb/assets/uploads/submissions/' . $filename;
        } else {
            $msg = "error:Lỗi không thể tải lên file đính kèm.";
        }
    }
    
    if (!$msg) {
        // Check if already submitted
        $stmt_check = $db->prepare("SELECT id FROM submissions WHERE student_id = ? AND assignment_id = ?");
        $stmt_check->bind_param("ii", $sv_id, $aid);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        
        if ($res_check->num_rows > 0) {
            // Update submission
            if ($file_url !== '') {
                $stmt_up = $db->prepare("UPDATE submissions SET submission_text = ?, file_path = ?, submitted_at = CURRENT_TIMESTAMP WHERE student_id = ? AND assignment_id = ?");
                $stmt_up->bind_param("ssii", $text, $file_url, $sv_id, $aid);
            } else {
                $stmt_up = $db->prepare("UPDATE submissions SET submission_text = ?, submitted_at = CURRENT_TIMESTAMP WHERE student_id = ? AND assignment_id = ?");
                $stmt_up->bind_param("sii", $text, $sv_id, $aid);
            }
            if ($stmt_up->execute()) {
                $msg = "success:Cập nhật bài nộp thành công!";
            } else {
                $msg = "error:Lỗi cập nhật bài nộp: " . $db->error;
            }
        } else {
            // Create new submission
            $stmt_ins = $db->prepare("INSERT INTO submissions (assignment_id, student_id, submission_text, file_path) VALUES (?, ?, ?, ?)");
            $stmt_ins->bind_param("iiss", $aid, $sv_id, $text, $file_url);
            if ($stmt_ins->execute()) {
                $msg = "success:Nộp bài tập thành công!";
            } else {
                $msg = "error:Lỗi nộp bài tập: " . $db->error;
            }
        }
    }
}

// Fetch all assignments for the student's class
$assignments = [];
if ($lop) {
    $stmt_assign = $db->prepare("
        SELECT a.id, a.mon_hoc_id, a.giang_vien_id, a.lop, a.tieu_de, a.mo_ta, a.han_nop, a.created_at, a.file_path as assign_file_path,
               m.ten_mon, g.ho_ten as gv_name,
               sub.id as submission_id, sub.file_path as sub_file_path, sub.submission_text, sub.grade, sub.feedback, sub.submitted_at
        FROM assignments a
        JOIN mon_hoc m ON a.mon_hoc_id = m.id
        LEFT JOIN giang_vien g ON a.giang_vien_id = g.id
        LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
        WHERE a.lop = ?
        ORDER BY a.id DESC
    ");
    $stmt_assign->bind_param("is", $sv_id, $lop);
    $stmt_assign->execute();
    $assignments = $stmt_assign->get_result()->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nộp bài tập - Cổng sinh viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <style>
        .form-control {
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 15px;
            border-radius: 8px;
            width: 100%;
            font-size: 14px;
            outline: none;
            margin-bottom: 15px;
        }
        .btn-submit {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.15s;
        }
        .btn-submit:hover {
            opacity: 0.9;
        }
        .assign-card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }
        .dropzone {
            border: 2px dashed var(--border);
            background: var(--bg3);
            border-radius: 12px;
            padding: 25px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            margin-top: 8px;
            user-select: none;
        }
        .dropzone:hover, .dropzone.dragover {
            border-color: var(--accent);
            background: rgba(217, 27, 67, 0.02);
        }
        .dropzone-icon {
            font-size: 28px;
            color: var(--text2);
            margin-bottom: 10px;
            transition: transform 0.2s ease;
        }
        .dropzone:hover .dropzone-icon, .dropzone.dragover .dropzone-icon {
            color: var(--accent);
            transform: translateY(-2px);
        }
        .dropzone-text {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 5px 0;
        }
        .dropzone-text span {
            color: var(--accent);
            text-decoration: underline;
        }
        .dropzone-sub {
            font-size: 11px;
            color: var(--text2);
            margin: 0;
        }
        .dropzone-file-name {
            display: none;
            margin-top: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #10b981;
            background: rgba(16, 185, 129, 0.06);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 6px 12px;
            border-radius: 6px;
            word-break: break-all;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <?php include '../includes/student_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-pen-to-square" style="color:var(--accent)"></i> Nộp Bài Tập Về Nhà</h1>
            <p style="color: var(--text2); margin-top: 5px;">Xem danh sách bài tập được giao cho lớp <?= htmlspecialchars($lop) ?>, nộp sản phẩm thực hành và xem điểm</p>
        </div>
    </div>

    <!-- Alert message -->
    <?php if ($msg): 
        $parts = explode(':', $msg);
        $type = $parts[0];
        $text = $parts[1];
    ?>
        <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="display: block; margin-bottom: 20px;">
            <?= htmlspecialchars($text) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($assignments)): ?>
        <div class="card" style="padding: 60px; text-align: center; color: var(--text2);">
            <i class="fa-solid fa-box-open" style="font-size: 40px; color: var(--accent); margin-bottom: 20px; display: block;"></i>
            Tuyệt vời! Hiện tại lớp của bạn không có bài tập nào cần hoàn thành.
        </div>
    <?php else: foreach ($assignments as $as): 
        $has_submitted = ($as['submission_id'] !== null);
        $is_expired = $as['han_nop'] && (strtotime($as['han_nop']) < time());
    ?>
        <div class="assign-card">
            <div style="display:flex; justify-content:space-between; align-items:start; flex-wrap:wrap; gap:10px; border-bottom: 1px solid var(--border); padding-bottom:15px; margin-bottom:15px;">
                <div>
                    <span class="badge" style="background: rgba(225, 29, 72, 0.08); color: var(--accent); font-weight:700; font-size:11px;"><?= htmlspecialchars($as['ten_mon']) ?></span>
                    <h2 style="font-size:18px; font-weight:800; color:var(--text); margin-top:8px;"><?= htmlspecialchars($as['tieu_de']) ?></h2>
                    <div style="font-size:12px; color:var(--text2); margin-top:4px;"><i class="fa-solid fa-user-tie"></i> GV giao: <?= htmlspecialchars($as['gv_name'] ?? 'TBA') ?></div>
                </div>
                
                <div style="text-align: right;">
                    <div style="font-size:12px; color: <?= $is_expired ? '#ef4444' : '#10b981' ?>; font-weight:700; margin-bottom:5px;">
                        <i class="fa-solid fa-clock"></i> Hạn nộp: <?= $as['han_nop'] ? date('d/m/Y H:i', strtotime($as['han_nop'])) : 'Không giới hạn' ?>
                    </div>
                    <?php if ($has_submitted): ?>
                        <span class="badge" style="background: rgba(16, 185, 129, 0.1); color:#10b981; border: 1px solid rgba(16,185,129,0.2); font-weight:700; font-size:11px;"><i class="fa-solid fa-circle-check"></i> Đã nộp bài</span>
                    <?php else: ?>
                        <span class="badge" style="background: rgba(239, 68, 68, 0.1); color:#ef4444; border: 1px solid rgba(239,68,68,0.2); font-weight:700; font-size:11px;"><i class="fa-solid fa-circle-xmark"></i> Chưa nộp</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="margin-bottom:20px;">
                <h4 style="font-size:13.5px; color:var(--text); font-weight:700; margin-bottom:5px;"><i class="fa-solid fa-file-contract"></i> Yêu cầu đề bài:</h4>
                <p style="font-size:13.5px; color:var(--text2); line-height:1.6; white-space:pre-wrap; margin-bottom:15px;"><?= htmlspecialchars($as['mo_ta']) ?></p>
                <?php if (!empty($as['assign_file_path'])): ?>
                    <div style="font-size:12.5px; background: rgba(56, 189, 248, 0.03); border: 1px solid rgba(56, 189, 248, 0.2); padding: 8px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-paperclip" style="color:#38bdf8;"></i>
                        <span style="color:var(--text2);">Tài liệu học liệu đính kèm:</span>
                        <a href="<?= htmlspecialchars($as['assign_file_path']) ?>" target="_blank" style="color:#38bdf8; font-weight:700; text-decoration:none;">Xem / Tải tài liệu</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Grade Card Section if graded -->
            <?php if ($has_submitted && $as['grade'] !== null): ?>
                <div style="background: rgba(16, 185, 129, 0.03); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 15px; margin-bottom: 20px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                    <div style="text-align: center; border-right: 1px solid rgba(16,185,129,0.2); padding-right: 20px;">
                        <span style="font-size: 11px; color:#10b981; text-transform:uppercase; font-weight:700; display:block; margin-bottom:5px;">Điểm số</span>
                        <span style="font-size:28px; font-weight:900; color:#10b981;"><?= htmlspecialchars($as['grade']) ?></span>
                    </div>
                    <div style="flex:1;">
                        <strong style="font-size:13px; color:#0f172a; display:block; margin-bottom:3px;"><i class="fa-solid fa-comment-dots"></i> Giảng viên nhận xét:</strong>
                        <p style="font-size:13px; color:#334155; margin:0; line-height:1.5;"><?= htmlspecialchars($as['feedback'] ?: 'Bài làm tốt!') ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Submission Form -->
            <div style="background:var(--bg3); border-radius:12px; padding:20px; border: 1px solid var(--border);">
                <h4 style="font-size:13.5px; color:var(--text); font-weight:800; margin-bottom:12px;">
                    <?= $has_submitted ? '<i class="fa-solid fa-square-pen"></i> Chỉnh sửa bài làm đã nộp' : '<i class="fa-solid fa-cloud-arrow-up"></i> Nộp bài làm của bạn' ?>
                </h4>
                
                <form method="POST" action="baitap.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_submission">
                    <input type="hidden" name="assignment_id" value="<?= $as['id'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label" style="font-size:12.5px;">Bài viết / Câu trả lời / Link Repo sản phẩm</label>
                        <textarea name="submission_content" class="form-control" rows="4" placeholder="Dán link GitHub, mô tả bài nộp hoặc nhập câu trả lời trực tiếp tại đây..."><?= htmlspecialchars($as['submission_text'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label" style="font-size:12.5px;">Tải lên file đính kèm (Zip, PDF, Rar...)</label>
                        <div class="dropzone" id="dropzone-<?= $as['id'] ?>" onclick="document.getElementById('file-input-<?= $as['id'] ?>').click()">
                            <i class="fa-solid fa-cloud-arrow-up dropzone-icon"></i>
                            <p class="dropzone-text">Kéo thả tệp tin vào đây hoặc <span>chọn tệp</span> để tải lên</p>
                            <p class="dropzone-sub">Hỗ trợ các tệp dạng ZIP, RAR, PDF, DOCX... (Tối đa 10MB)</p>
                            <div class="dropzone-file-name" id="file-name-<?= $as['id'] ?>"></div>
                            <input type="file" name="attachment_file" id="file-input-<?= $as['id'] ?>" class="dropzone-input" style="display:none;" onchange="handleFileSelect(this, <?= $as['id'] ?>)">
                        </div>
                        <?php if ($has_submitted && !empty($as['sub_file_path'])): ?>
                            <div style="font-size:12px; color:var(--text2); margin-top:5px;">
                                File đã nộp: <a href="<?= htmlspecialchars($as['sub_file_path']) ?>" target="_blank" style="color:var(--accent); font-weight:700;"><i class="fa-solid fa-paperclip"></i> Xem file đính kèm</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-circle-check"></i> <?= $has_submitted ? 'Cập nhật bài làm' : 'Gửi bài làm' ?>
                    </button>
                    <?php if ($is_expired): ?>
                        <span style="font-size:12px; color:#ef4444; margin-left:10px; font-weight:700;"><i class="fa-solid fa-circle-exclamation"></i> Đã quá hạn nộp (Vẫn có thể nộp muộn)</span>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php endforeach; endif; ?>
    <script>
        async function getFileFromEntry(fileEntry) {
            return new Promise((resolve, reject) => {
                fileEntry.file(resolve, reject);
            });
        }

        async function readEntriesPromise(dirReader) {
            return new Promise((resolve, reject) => {
                dirReader.readEntries(resolve, reject);
            });
        }

        async function traverseDirectory(entry, zip, path = "") {
            if (entry.isFile) {
                const file = await getFileFromEntry(entry);
                zip.file(path + file.name, file);
            } else if (entry.isDirectory) {
                const dirReader = entry.createReader();
                let allEntries = [];
                let entries = await readEntriesPromise(dirReader);
                while (entries.length > 0) {
                    allEntries = allEntries.concat(entries);
                    entries = await readEntriesPromise(dirReader);
                }
                for (const childEntry of allEntries) {
                    await traverseDirectory(childEntry, zip, path + entry.name + "/");
                }
            }
        }

        async function handleDroppedItems(items, files, assignmentId, fileInput) {
            const fileNameEl = document.getElementById('file-name-' + assignmentId);
            const dropzone = document.getElementById('dropzone-' + assignmentId);
            
            let hasDirectory = false;
            const entries = [];
            for (let i = 0; i < items.length; i++) {
                const entry = items[i].webkitGetAsEntry();
                if (entry) {
                    entries.push(entry);
                    if (entry.isDirectory) {
                        hasDirectory = true;
                    }
                }
            }
            
            if (hasDirectory) {
                fileNameEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang tự động nén thư mục...';
                fileNameEl.style.display = 'inline-flex';
                
                try {
                    const zip = new JSZip();
                    for (const entry of entries) {
                        await traverseDirectory(entry, zip);
                    }
                    const content = await zip.generateAsync({ type: "blob" });
                    
                    let zipName = "bai_nop.zip";
                    if (entries.length === 1 && entries[0].isDirectory) {
                        zipName = entries[0].name + ".zip";
                    }
                    
                    const zippedFile = new File([content], zipName, { type: "application/zip" });
                    
                    const container = new DataTransfer();
                    container.items.add(zippedFile);
                    fileInput.files = container.files;
                    
                    handleFileSelect(fileInput, assignmentId);
                } catch (err) {
                    console.error(err);
                    alert("Lỗi khi tự động nén thư mục: " + err.message);
                    fileNameEl.style.display = 'none';
                    fileInput.value = '';
                }
            } else {
                fileInput.files = files;
                handleFileSelect(fileInput, assignmentId);
            }
        }

        function handleFileSelect(input, assignmentId) {
            const file = input.files[0];
            const fileNameEl = document.getElementById('file-name-' + assignmentId);
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    alert('Kích thước tệp tin quá lớn! Vui lòng chọn tệp dưới 10MB.');
                    input.value = '';
                    fileNameEl.style.display = 'none';
                    return;
                }
                fileNameEl.innerHTML = '<i class="fa-solid fa-paperclip"></i> ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                fileNameEl.style.display = 'inline-flex';
            } else {
                fileNameEl.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.dropzone').forEach(dropzone => {
                const assignmentId = dropzone.id.replace('dropzone-', '');
                const fileInput = document.getElementById('file-input-' + assignmentId);

                // Prevent defaults on drag events
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                    }, false);
                });

                // Add visual classes on hover drag over
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropzone.addEventListener(eventName, () => {
                        dropzone.classList.add('dragover');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, () => {
                        dropzone.classList.remove('dragover');
                    }, false);
                });

                // Handle dropped file
                dropzone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    if (dt.items && dt.items.length) {
                        handleDroppedItems(dt.items, dt.files, assignmentId, fileInput);
                    } else if (dt.files && dt.files.length) {
                        fileInput.files = dt.files;
                        handleFileSelect(fileInput, assignmentId);
                    }
                }, false);
            });
        });
    </script>
</body>
</html>
