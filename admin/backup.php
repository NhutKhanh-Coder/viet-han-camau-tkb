<?php
require_once '../config.php';
requireAdmin();
$msg = '';

$backupDir = 'c:/xampp/htdocs/tkb/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. Database backup generator
if ($action === 'db_backup') {
    $db = getDB();
    $tables = [];
    $res = $db->query("SHOW TABLES");
    while ($row = $res->fetch_row()) {
        $tables[] = $row[0];
    }
    
    $sqlContent = "-- VKC Database Backup\n";
    $sqlContent .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n\n";
    $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // Table Structure
        $resCreate = $db->query("SHOW CREATE TABLE `$table`")->fetch_row();
        $sqlContent .= "\n\nDROP TABLE IF EXISTS `$table`;\n";
        $sqlContent .= $resCreate[1] . ";\n\n";
        
        // Table Data
        $resData = $db->query("SELECT * FROM `$table`");
        while ($row = $resData->fetch_assoc()) {
            $keys = array_map(function($k) { return "`$k`"; }, array_keys($row));
            $vals = array_map(function($v) use ($db) {
                if ($v === null) return "NULL";
                return "'" . $db->real_escape_string($v) . "'";
            }, array_values($row));
            
            $sqlContent .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $vals) . ");\n";
        }
    }
    $sqlContent .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
    
    $fileName = 'backup_db_' . date('Ymd_His') . '.sql';
    file_put_contents($backupDir . $fileName, $sqlContent);
    writeSystemLog("Sao lưu cơ sở dữ liệu: $fileName");
    $msg = "success:Đã sao lưu cơ sở dữ liệu thành công!";
    $db->close();
}

// 2. File backup generator (Zips assets/ and includes/ folders)
elseif ($action === 'file_backup') {
    if (class_exists('ZipArchive')) {
        $zipName = 'backup_files_' . date('Ymd_His') . '.zip';
        $zipPath = $backupDir . $zipName;
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add specific source directories to avoid recursion
            $folders = ['c:/xampp/htdocs/tkb/assets/', 'c:/xampp/htdocs/tkb/includes/'];
            foreach ($folders as $fol) {
                if (is_dir($fol)) {
                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fol), RecursiveIteratorIterator::LEAVES_ONLY);
                    foreach ($files as $name => $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen('c:/xampp/htdocs/tkb/'));
                            $zip->addFile($filePath, $relativePath);
                        }
                    }
                }
            }
            $zip->close();
            writeSystemLog("Sao lưu tệp tin hệ thống: $zipName");
            $msg = "success:Sao lưu tệp tin hệ thống thành công!";
        } else {
            $msg = "error:Không thể tạo tệp zip.";
        }
    } else {
        $msg = "error:Ứng dụng chưa kích hoạt extension ZipArchive của PHP.";
    }
}

// 3. Database Restore
elseif ($action === 'restore' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $restoreFile = $_POST['restore_file'] ?? '';
    $filePath = $backupDir . basename($restoreFile);
    if ($restoreFile && file_exists($filePath)) {
        $db = getDB();
        $sql = file_get_contents($filePath);
        
        // Disable foreign keys check
        $db->query("SET FOREIGN_KEY_CHECKS=0");
        
        // Execute multi queries
        if ($db->multi_query($sql)) {
            do {
                if ($res = $db->store_result()) {
                    $res->free();
                }
            } while ($db->next_result());
            writeSystemLog("Khôi phục cơ sở dữ liệu từ tệp: $restoreFile");
            $msg = "success:Khôi phục cơ sở dữ liệu thành công!";
        } else {
            $msg = "error:Lỗi khôi phục: " . $db->error;
        }
        $db->query("SET FOREIGN_KEY_CHECKS=1");
        $db->close();
    } else {
        $msg = "error:Tệp khôi phục không hợp lệ hoặc không tồn tại.";
    }
}

// 4. Download file handler
elseif ($action === 'download' && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filePath = $backupDir . $file;
    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
    }
}

// 5. Delete backup file
elseif ($action === 'delete' && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filePath = $backupDir . $file;
    if (file_exists($filePath)) {
        unlink($filePath);
        writeSystemLog("Xóa tệp sao lưu: $file");
        $msg = "success:Đã xóa tệp sao lưu thành công!";
    }
}

// List all files in backup folder
$filesList = [];
if (is_dir($backupDir)) {
    $dir = opendir($backupDir);
    while (($file = readdir($dir)) !== false) {
        if ($file !== '.' && $file !== '..') {
            $filesList[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'date' => filemtime($backupDir . $file)
            ];
        }
    }
    closedir($dir);
    // Sort desc by date
    usort($filesList, function($a, $b) { return $b['date'] - $a['date']; });
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sao lưu & Phục hồi - Hệ Thống Quản Trị</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body class="admin-portal">
    <?php include '../includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-database"></i> Hệ Thống Sao Lưu &amp; Phục Hồi</h1>
                <p class="page-sub">Hệ thống tạo điểm sao lưu an toàn cho cơ sở dữ liệu SQL và mã nguồn tệp tin assets</p>
            </div>
            <div style="display: flex; gap:10px; flex-wrap:wrap;">
                <a href="?action=db_backup" class="btn btn-primary"><i class="fa-solid fa-database"></i> Sao lưu Database</a>
                <a href="?action=file_backup" class="btn btn-ghost"><i class="fa-solid fa-file-zipper" style="color:#0284c7;"></i> Sao lưu Files</a>
            </div>
        </div>

        <!-- Feedback messages -->
        <?php if ($msg): 
            $parts = explode(':', $msg);
            $type = $parts[0];
            $text = $parts[1];
        ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>">
                <i class="fa-solid <?= $type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <?= htmlspecialchars($text) ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
            <!-- List files -->
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-folder-open"></i> Lịch sử sao lưu hệ thống (<?= count($filesList) ?>)</span>
                </div>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Tên tệp tin</th>
                                <th style="text-align: center; width: 120px;">Dung lượng</th>
                                <th style="text-align: center; width: 160px;">Ngày tạo</th>
                                <th style="text-align: center; width: 140px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filesList)): ?>
                                <tr><td colspan="4" style="text-align:center; color:#94a3b8; padding:40px;">Chưa có bản sao lưu nào được lưu trữ.</td></tr>
                            <?php else: foreach ($filesList as $f): 
                                $is_sql = strpos($f['name'], '.sql') !== false;
                            ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <i class="fa-solid <?= $is_sql ? 'fa-file-code' : 'fa-file-zipper' ?>" style="color:<?= $is_sql ? '#e11d48' : '#0284c7' ?>; font-size:16px;"></i>
                                            <strong style="color: #0f172a; font-size:13.5px;"><?= htmlspecialchars($f['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td style="text-align: center; font-size:13px; color:#64748b;">
                                        <span style="background:#f1f5f9; padding:3px 8px; border-radius:6px; font-weight:600; font-size:12px;">
                                            <?= round($f['size'] / 1024, 1) ?> KB
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-size: 13px; color: #64748b; white-space:nowrap;">
                                        <?= date('d/m/Y H:i', $f['date']) ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display:inline-flex; align-items:center; gap:6px; justify-content:center;">
                                            <a href="?action=download&file=<?= urlencode($f['name']) ?>" class="btn btn-ghost btn-sm" title="Tải về"><i class="fa-solid fa-download" style="color:#0284c7;"></i> Tải</a>
                                            <a href="?action=delete&file=<?= urlencode($f['name']) ?>" class="btn btn-danger btn-sm" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa tệp sao lưu này?')"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Restore panel -->
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Phục hồi điểm cơ sở</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="backup.php">
                        <input type="hidden" name="action" value="restore">
                        <div class="form-group">
                            <label class="form-label">Chọn tệp khôi phục (.sql) *</label>
                            <select name="restore_file" class="form-select" required style="cursor:pointer;">
                                <option value="">-- Chọn điểm khôi phục DB --</option>
                                <?php foreach ($filesList as $f): if (strpos($f['name'], '.sql') !== false): ?>
                                    <option value="<?= htmlspecialchars($f['name']) ?>"><?= htmlspecialchars($f['name']) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-danger" style="width:100%; justify-content:center;" onclick="return confirm('CẢNH BÁO: Phục hồi dữ liệu sẽ ghi đè toàn bộ DB hiện tại. Xác nhận khôi phục?')">
                            <i class="fa-solid fa-rotate-left"></i> Khôi phục ngay
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
