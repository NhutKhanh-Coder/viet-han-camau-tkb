<?php
require_once '../config.php';
requirePrincipal();

$db = getDB();

// 1. Stats Queries
$total_students = $db->query("SELECT COUNT(*) as cnt FROM students")->fetch_assoc()['cnt'] ?? 0;
$total_teachers = $db->query("SELECT COUNT(*) as cnt FROM giang_vien")->fetch_assoc()['cnt'] ?? 0;
$total_subjects = $db->query("SELECT COUNT(*) as cnt FROM mon_hoc")->fetch_assoc()['cnt'] ?? 0;

// 2. Finance Stats
$finance_paid = (float)($db->query("SELECT SUM(so_tien) as total FROM tai_chinh WHERE trang_thai = 'Đã nộp'")->fetch_assoc()['total'] ?? 0);
$finance_unpaid = (float)($db->query("SELECT SUM(so_tien) as total FROM tai_chinh WHERE trang_thai != 'Đã nộp'")->fetch_assoc()['total'] ?? 0);

// 3. Grade Distribution Stats
$grade_excellent = (int)($db->query("SELECT COUNT(*) as cnt FROM diem WHERE diem_tong_ket >= 8.5")->fetch_assoc()['cnt'] ?? 0);
$grade_good = (int)($db->query("SELECT COUNT(*) as cnt FROM diem WHERE diem_tong_ket >= 7.0 AND diem_tong_ket < 8.5")->fetch_assoc()['cnt'] ?? 0);
$grade_average = (int)($db->query("SELECT COUNT(*) as cnt FROM diem WHERE diem_tong_ket >= 5.0 AND diem_tong_ket < 7.0")->fetch_assoc()['cnt'] ?? 0);
$grade_weak = (int)($db->query("SELECT COUNT(*) as cnt FROM diem WHERE diem_tong_ket < 5.0 AND diem_tong_ket IS NOT NULL")->fetch_assoc()['cnt'] ?? 0);

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tổng Quan Hiệu Trưởng</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
    <?php include '../includes/principal_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-gauge-high" style="color:#fbbf24"></i> Tổng Quan Phân Tích Hệ Thống</h1>
            <p style="color: var(--text2); margin-top: 5px;">Xin chào, Hiệu trưởng <strong><?= htmlspecialchars($_SESSION['ho_ten'] ?? 'Hiệu trưởng') ?></strong>. Dưới đây là phân tích hoạt động của nhà trường.</p>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-card" style="padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(251, 191, 36, 0.1); color: #fbbf24; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text2); font-weight: 500;">TỔNG SỐ SINH VIÊN</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--text);"><?= $total_students ?> Sinh Viên</div>
            </div>
        </div>

        <div class="stat-card" style="padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(56, 189, 248, 0.1); color: #38bdf8; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text2); font-weight: 500;">TỔNG SỐ GIẢNG VIÊN</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--text);"><?= $total_teachers ?> Giảng Viên</div>
            </div>
        </div>

        <div class="stat-card" style="padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text2); font-weight: 500;">TỔNG SỐ HỌC PHẦN</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--text);"><?= $total_subjects ?> Môn học</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; align-items: stretch;">
        
        <!-- Grades Distribution Chart -->
        <div class="card" style="margin:0;">
            <div class="card-head"><span class="card-title"><i class="fa-solid fa-chart-pie" style="color:#fbbf24; margin-right:8px;"></i> Biểu đồ kết quả học tập</span></div>
            <div class="card-body" style="display: flex; align-items: center; justify-content: center; min-height: 300px; padding: 20px;">
                <div style="width: 100%; max-width: 280px; position: relative;">
                    <canvas id="gradeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Finance Status Chart -->
        <div class="card" style="margin:0;">
            <div class="card-head"><span class="card-title"><i class="fa-solid fa-wallet" style="color:#fbbf24; margin-right:8px;"></i> Biểu đồ thống kê học phí</span></div>
            <div class="card-body" style="display: flex; align-items: center; justify-content: center; min-height: 300px; padding: 20px;">
                <div style="width: 100%; max-width: 280px; position: relative;">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- Chart JS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Grade Chart Setup
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        const gradeChart = new Chart(gradeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Xuất sắc (>= 8.5)', 'Khá (7.0 - 8.4)', 'Trung bình (5.0 - 6.9)', 'Yếu/Kém (< 5.0)'],
                datasets: [{
                    data: [<?= $grade_excellent ?>, <?= $grade_good ?>, <?= $grade_average ?>, <?= $grade_weak ?>],
                    backgroundColor: ['#c084fc', '#f43f6d', '#4ade80', '#f87171'],
                    borderWidth: 1,
                    borderColor: 'rgba(255, 255, 255, 0.08)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#9ca3af',
                            font: { family: 'Outfit', size: 11 }
                        }
                    }
                }
            }
        });

        // Finance Chart Setup
        const financeCtx = document.getElementById('financeChart').getContext('2d');
        const financeChart = new Chart(financeCtx, {
            type: 'pie',
            data: {
                labels: ['Đã Nộp Học Phí', 'Chưa Nộp Học Phí'],
                datasets: [{
                    data: [<?= $finance_paid ?>, <?= $finance_unpaid ?>],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 1,
                    borderColor: 'rgba(255, 255, 255, 0.08)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#9ca3af',
                            font: { family: 'Outfit', size: 11 }
                        }
                    }
                }
            }
        });
    </script>

    </div> <!-- content-pad -->
    </div> <!-- main-content -->
</body>
</html>
