import os

html_content = """<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BÁO CÁO HƯỚNG DẪN SỬ DỤNG VÀ GIỚI THIỆU HỆ THỐNG TOÀN DIỆN - TRƯỜNG CAO ĐẲNG CÀ MAU</title>
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,400&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        /* =========================================================
           DESIGN SYSTEM & PRINT STYLING (A4 PDF OPTIMIZED)
           ========================================================= */
        :root {
            --primary: #d91b43;
            --primary-dark: #a8122e;
            --primary-light: #f43f6d;
            --secondary: #1e293b;
            --secondary-light: #334155;
            --accent: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-light: #f8fafc;
            --bg-card: #ffffff;
            --border: #cbd5e1;
            --border-light: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #475569;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-dark);
            background-color: #f1f5f9;
            line-height: 1.6;
            font-size: 14.5px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Action Bar for web view */
        .top-action-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 65px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            z-index: 9999;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }

        .top-action-bar .logo-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            color: var(--primary);
            font-size: 16px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(217, 27, 67, 0.35);
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(217, 27, 67, 0.45);
        }

        .btn-back {
            background: #f1f5f9;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        .btn-back:hover {
            background: #e2e8f0;
            color: var(--text-dark);
        }

        /* Paper Sheet Container */
        .document-container {
            max-width: 920px;
            margin: 85px auto 50px auto;
            background: #ffffff;
            padding: 60px 70px;
            box-shadow: 0 10px 35px rgba(0,0,0,0.08);
            border-radius: 6px;
        }

        /* ==========================================
           COVER PAGE (TRANG BÌA)
           ========================================== */
        .cover-page {
            min-height: 940px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            border: 4px double var(--primary);
            padding: 55px 45px;
            position: relative;
            background: radial-gradient(circle at top right, rgba(217,27,67,0.04), transparent 70%);
            page-break-after: always;
            break-after: page;
        }

        .cover-header h3 {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .cover-header h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .cover-divider {
            width: 180px;
            height: 3px;
            background: var(--primary);
            margin: 18px auto;
            border-radius: 2px;
        }

        .cover-body {
            margin: 30px 0;
        }

        .cover-badge {
            display: inline-block;
            padding: 7px 22px;
            background: rgba(217, 27, 67, 0.1);
            color: var(--primary);
            border-radius: 30px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 30px;
        }

        .cover-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 32px;
            font-weight: 900;
            color: var(--secondary);
            line-height: 1.35;
            margin-bottom: 22px;
            text-transform: uppercase;
        }

        .cover-subtitle {
            font-size: 16px;
            color: var(--text-muted);
            font-weight: 500;
            max-width: 680px;
            margin: 0 auto 35px auto;
            line-height: 1.6;
        }

        .cover-info-box {
            background: var(--bg-light);
            border: 1px solid var(--border-light);
            border-left: 5px solid var(--primary);
            padding: 28px 40px;
            border-radius: 10px;
            text-align: left;
            max-width: 580px;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .cover-info-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .cover-info-box td {
            padding: 9px 0;
            font-size: 14.5px;
        }

        .cover-info-box td.label {
            font-weight: 600;
            color: var(--text-muted);
            width: 48%;
        }

        .cover-info-box td.val {
            font-weight: 700;
            color: var(--text-dark);
        }

        .cover-footer {
            font-size: 13.5px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-light);
            padding-top: 22px;
        }

        /* Typography & Headings */
        h1, h2, h3, h4, h5 {
            color: var(--secondary);
            font-weight: 700;
        }

        .section-title {
            font-size: 22px;
            color: var(--primary-dark);
            border-bottom: 2.5px solid var(--primary);
            padding-bottom: 8px;
            margin: 45px 0 25px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            page-break-after: avoid;
        }

        .section-title i {
            font-size: 20px;
            color: var(--primary);
            background: rgba(217, 27, 67, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .subsection-title {
            font-size: 17px;
            color: var(--secondary);
            margin: 32px 0 16px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 12px;
            border-left: 4px solid var(--primary);
            page-break-after: avoid;
        }

        .subsubsection-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--secondary-light);
            margin: 20px 0 10px 0;
        }

        p {
            margin-bottom: 14px;
            color: #334155;
            text-align: justify;
            line-height: 1.65;
        }

        /* Table of Contents Box */
        .toc-box {
            background: #f8fafc;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 30px 35px;
            margin: 30px 0 45px 0;
        }

        .toc-header {
            font-size: 19px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 12px;
        }

        .toc-list {
            list-style: none;
        }

        .toc-list > li {
            font-weight: 700;
            margin-bottom: 14px;
            font-size: 15px;
        }

        .toc-list > li > a {
            color: var(--secondary);
            text-decoration: none;
        }

        .toc-sublist {
            list-style: none;
            padding-left: 28px;
            margin-top: 8px;
            font-weight: 400;
        }

        .toc-sublist li {
            margin-bottom: 7px;
            font-size: 14px;
        }

        .toc-sublist a {
            color: var(--text-muted);
            text-decoration: none;
        }

        .toc-sublist a:hover {
            color: var(--primary);
        }

        /* Glossary Table */
        .glossary-box {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
        }

        /* Feature Cards Grid */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 22px 0;
        }

        .feature-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }

        .feature-card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(217, 27, 67, 0.1), rgba(217, 27, 67, 0.2));
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }

        .feature-card-title {
            font-weight: 700;
            font-size: 15px;
            color: var(--secondary);
        }

        .feature-card-body {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.55;
        }

        /* UI Mockup Display Boxes */
        .ui-mockup-box {
            background: #1e293b;
            color: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Fira Code', monospace;
            font-size: 13px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border: 1px solid #334155;
        }

        .ui-mockup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #334155;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #94a3b8;
        }

        .ui-mockup-dots {
            display: flex;
            gap: 6px;
        }

        .ui-mockup-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot-red { background: #ef4444; }
        .dot-yellow { background: #f59e0b; }
        .dot-green { background: #10b981; }

        /* Flowcharts & Diagram Boxes */
        .diagram-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px dashed var(--border);
            border-radius: 12px;
            padding: 25px 20px;
            margin: 25px 0;
            gap: 10px;
            page-break-inside: avoid;
        }

        .diagram-step {
            background: #ffffff;
            border: 2px solid var(--primary);
            border-radius: 10px;
            padding: 14px 16px;
            text-align: center;
            flex: 1;
            box-shadow: 0 3px 8px rgba(0,0,0,0.04);
        }

        .diagram-step-title {
            font-weight: 700;
            font-size: 13px;
            color: var(--secondary);
            margin-bottom: 4px;
        }

        .diagram-step-desc {
            font-size: 11.5px;
            color: var(--text-muted);
        }

        .diagram-arrow {
            color: var(--primary);
            font-size: 18px;
            font-weight: bold;
        }

        /* Step Lists */
        .step-list {
            list-style: none;
            margin: 22px 0;
            counter-reset: step-counter;
        }

        .step-item {
            position: relative;
            padding-left: 52px;
            margin-bottom: 22px;
        }

        .step-item::before {
            counter-increment: step-counter;
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 34px;
            height: 34px;
            background: var(--primary);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            box-shadow: 0 3px 8px rgba(217, 27, 67, 0.3);
        }

        .step-title {
            font-weight: 700;
            font-size: 15px;
            color: var(--secondary);
            margin-bottom: 5px;
        }

        .step-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Callout Boxes */
        .callout {
            padding: 18px 22px;
            border-radius: 10px;
            margin: 22px 0;
            display: flex;
            gap: 16px;
            align-items: flex-start;
            font-size: 14px;
            line-height: 1.6;
        }

        .callout-info {
            background: #eff6ff;
            border-left: 5px solid var(--accent);
            color: #1e40af;
        }

        .callout-warning {
            background: #fffbeb;
            border-left: 5px solid var(--warning);
            color: #92400e;
        }

        .callout-success {
            background: #ecfdf5;
            border-left: 5px solid var(--success);
            color: #065f46;
        }

        .callout i {
            font-size: 20px;
            margin-top: 2px;
        }

        /* Tables */
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            margin: 22px 0;
            font-size: 13.5px;
        }

        .custom-table th, .custom-table td {
            padding: 12px 16px;
            border: 1px solid var(--border-light);
            text-align: left;
        }

        .custom-table th {
            background: #f1f5f9;
            color: var(--secondary);
            font-weight: 700;
        }

        .custom-table tr:nth-child(even) {
            background: #f8fafc;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-primary { background: rgba(217,27,67,0.1); color: var(--primary); }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-dark { background: #e2e8f0; color: #334155; }

        /* Page break utilities */
        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Footer inside PDF */
        .doc-footer-print {
            display: none;
        }

        /* =========================================================
           PRINT MEDIA STYLING (A4 PDF EXPORT)
           ========================================================= */
        @media print {
            @page {
                size: A4 portrait;
                margin: 18mm 15mm 20mm 15mm;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 10.5pt;
                line-height: 1.5;
            }

            .top-action-bar, .no-print {
                display: none !important;
            }

            .document-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .cover-page {
                height: 98vh !important;
                min-height: auto !important;
                padding: 40px 20px !important;
            }

            .section-title {
                color: #800020 !important;
                border-bottom: 2px solid #800020 !important;
                margin-top: 28px !important;
            }

            .feature-card {
                border: 1px solid #ccc !important;
                box-shadow: none !important;
            }

            .custom-table th {
                background: #e2e8f0 !important;
                color: #000 !important;
            }

            a {
                text-decoration: none !important;
                color: #000 !important;
            }

            .doc-footer-print {
                display: block;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 9pt;
                color: #666;
                border-top: 1px solid #ccc;
                padding-top: 5px;
            }
        }
    </style>
</head>
<body>

    <!-- TOP ACTION BAR FOR BROWSER VIEW -->
    <div class="top-action-bar no-print">
        <div class="logo-title">
            <i class="fa-solid fa-graduation-cap fa-lg"></i>
            <span>HỆ THỐNG QUẢN LÝ THỜI KHÓA BIỂU & HỌC TẬP TRỰC TUYẾN</span>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="index.php" class="btn-action btn-back">
                <i class="fa-solid fa-arrow-left"></i> Trở về Trang chủ
            </a>
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="fa-solid fa-file-pdf"></i> Xuất File PDF / In Hướng Dẫn Nộp Cô
            </button>
        </div>
    </div>

    <!-- MAIN DOCUMENT WRAPPER -->
    <div class="document-container">

        <!-- ==========================================
             TRANG BÌA (COVER PAGE)
             ========================================== -->
        <div class="cover-page">
            <div class="cover-header">
                <h3>TRƯỜNG CAO ĐẲNG CÀ MAU</h3>
                <h4>KHOA CÔNG NGHỆ THÔNG TIN</h4>
                <div class="cover-divider"></div>
            </div>

            <div class="cover-body">
                <span class="cover-badge"><i class="fa-solid fa-file-contract"></i> BÁO CÁO TÀI LIỆU HƯỚNG DẪN HOÀN CHỈNH</span>
                <h1 class="cover-title">HƯỚNG DẪN SỬ DỤNG VÀ GIỚI THIỆU TOÀN DIỆN CHỨC NĂNG HỆ THỐNG</h1>
                <p class="cover-subtitle">
                    Hệ thống Quản lý Thời khóa biểu, Học tập Trực tuyến, Biên dịch Code IDE & Trợ lý Trí tuệ Nhân tạo AI
                    <br><strong>(Tài liệu báo cáo chi tiết dành cho Sinh viên, Giảng viên & Quản trị viên)</strong>
                </p>

                <div class="cover-info-box">
                    <table>
                        <tr>
                            <td class="label"><i class="fa-solid fa-chalkboard-user"></i> Giảng viên hướng dẫn:</td>
                            <td class="val">Giảng viên Bộ môn CNTT</td>
                        </tr>
                        <tr>
                            <td class="label"><i class="fa-solid fa-user-gear"></i> Tác giả / Sinh viên thực hiện:</td>
                            <td class="val">Lê Nhựt Khánh - Coder</td>
                        </tr>
                        <tr>
                            <td class="label"><i class="fa-solid fa-layer-group"></i> Phân hệ báo cáo:</td>
                            <td class="val">Sinh viên & Giáo viên & Quản trị viên</td>
                        </tr>
                        <tr>
                            <td class="label"><i class="fa-solid fa-code-branch"></i> Phiên bản Hệ thống:</td>
                            <td class="val">v2.5 Enterprise Master Edition</td>
                        </tr>
                        <tr>
                            <td class="label"><i class="fa-solid fa-calendar-check"></i> Ngày xuất tài liệu:</td>
                            <td class="val">Năm 2026</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="cover-footer">
                <p><strong>CÀ MAU - NĂM 2026</strong></p>
                <p style="font-size: 12px; margin-top: 4px;">Tài liệu lưu hành nội bộ - Trường Cao đẳng Cà Mau</p>
            </div>
        </div>

        <!-- ==========================================
             MỤC LỤC & BẢNG VIẾT TẮT
             ========================================== -->
        <div class="toc-box avoid-break">
            <div class="toc-header">
                <i class="fa-solid fa-list-ol"></i> MỤC LỤC CHI TIẾT BÁO CÁO
            </div>
            <ul class="toc-list">
                <li>
                    PHẦN 1: TỔNG QUAN HỆ THỐNG & ĐĂNG NHẬP
                    <ul class="toc-sublist">
                        <li><a href="#p1-1">1.1 Giới thiệu mục đích hệ thống</a></li>
                        <li><a href="#p1-2">1.2 Phân quyền sử dụng (Sinh viên - Giảng viên - Quản trị)</a></li>
                        <li><a href="#p1-3">1.3 Hướng dẫn Đăng ký & Đăng nhập tài khoản</a></li>
                        <li><a href="#p1-4">1.4 Quy trình Khôi phục mật khẩu OTP</a></li>
                    </ul>
                </li>
                <li>
                    PHẦN 2: CHỨC NĂNG DÀNH CHO SINH VIÊN (STUDENT PORTAL)
                    <ul class="toc-sublist">
                        <li><a href="#p2-1">2.1 Trang Tổng quan (Dashboard) & Thời khóa biểu</a></li>
                        <li><a href="#p2-2">2.2 Trợ lý Trí tuệ Nhân tạo AI (AI Learning Assistant)</a></li>
                        <li><a href="#p2-3">2.3 Học bài trực tuyến & Tra cứu Bài giảng Slide</a></li>
                        <li><a href="#p2-4">2.4 Quản lý & Nộp Bài tập Tự luận / Thực hành</a></li>
                        <li><a href="#p2-5">2.5 Trình biên dịch Code IDE Online & Chấm bài tự động</a></li>
                        <li><a href="#p2-6">2.6 Thi & Làm bài Trắc nghiệm trực tuyến (Quiz Engine)</a></li>
                        <li><a href="#p2-7">2.7 Theo dõi Tiến độ Đồ án & Bài tập lớn</a></li>
                        <li><a href="#p2-8">2.8 Ôn tập với Thẻ ghi nhớ (Flashcards) & Mini Games</a></li>
                        <li><a href="#p2-9">2.9 Cửa hàng AI Shop & Hệ thống Đổi điểm thưởng tích lũy</a></li>
                        <li><a href="#p2-10">2.10 Trình phát Nhạc tập trung (Lo-Fi Music Player)</a></li>
                        <li><a href="#p2-11">2.11 Quản lý Hồ sơ cá nhân (Profile) & Tiến độ học tập</a></li>
                    </ul>
                </li>
                <li>
                    PHẦN 3: CHỨC NĂNG DÀNH CHO GIẢNG VIÊN (TEACHER PORTAL)
                    <ul class="toc-sublist">
                        <li><a href="#p3-1">3.1 Trang Quản lý Giảng dạy (Teacher Dashboard)</a></li>
                        <li><a href="#p3-2">3.2 Quản lý Lớp học & Quản lý Danh sách Sinh viên</a></li>
                        <li><a href="#p3-3">3.3 Điểm danh Sinh viên trực tuyến & Báo cáo chuyên cần</a></li>
                        <li><a href="#p3-4">3.4 Quản lý Bài tập & Chấm điểm Tự luận / Nhận xét</a></li>
                        <li><a href="#p3-5">3.5 Quản lý & Chấm Code Lập trình Tự động (Testcases)</a></li>
                        <li><a href="#p3-6">3.6 Ngân hàng Câu hỏi & Quản lý Đề thi Trắc nghiệm (Quiz)</a></li>
                        <li><a href="#p3-7">3.7 Phân công & Quản lý Đồ án Môn học</a></li>
                        <li><a href="#p3-8">3.8 Quản lý Bài giảng, Slide & Upload Tài liệu</a></li>
                        <li><a href="#p3-9">3.9 Quản lý Bài thực hành Lab & Nhật ký Giảng dạy</a></li>
                        <li><a href="#p3-10">3.10 Quản lý AI Key & Kho Nhạc Học Tập</a></li>
                    </ul>
                </li>
                <li>
                    PHẦN 4: PHÂN HỆ QUẢN TRỊ VIÊN (ADMIN PORTAL)
                </li>
                <li>
                    PHẦN 5: SƠ ĐỒ LUỒNG NGHỆ NGHIỆP & QUY TRÌNH PHỐI HỢP
                </li>
                <li>
                    PHẦN 6: HƯỚNG DẪN XỬ LÝ SỰ CỐ & CÂU HỎI THƯỜNG GẶP (FAQ)
                </li>
                <li>
                    PHẦN 7: KẾT LUẬN & CHỮ KÝ BÁO CÁO
                </li>
            </ul>
        </div>

        <div class="glossary-box avoid-break">
            <h4 style="color: var(--primary); margin-bottom: 10px;"><i class="fa-solid fa-spell-check"></i> Bảng Danh Mục Từ Viết Tắt & Thuật Ngữ</h4>
            <table class="custom-table" style="margin: 0; font-size: 13px;">
                <thead>
                    <tr>
                        <th style="width: 25%;">Ký hiệu / Thuật ngữ</th>
                        <th style="width: 75%;">Giải thích ý nghĩa</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><strong>TKB</strong></td><td>Thời khóa biểu học tập & giảng dạy hàng tuần.</td></tr>
                    <tr><td><strong>Code IDE</strong></td><td>Integrated Development Environment - Trình biên dịch & soạn thảo mã nguồn trực tuyến.</td></tr>
                    <tr><td><strong>Quiz Engine</strong></td><td>Hệ thống tổ chức và chấm điểm thi trắc nghiệm trực tuyến tự động.</td></tr>
                    <tr><td><strong>Testcase</strong></td><td>Bộ dữ liệu đầu vào (Input) và đầu ra chuẩn (Expected Output) dùng để chấm bài lập trình.</td></tr>
                    <tr><td><strong>AI Token</strong></td><td>Đơn vị điểm dùng để thực hiện các truy vấn trợ lý trí tuệ nhân tạo.</td></tr>
                    <tr><td><strong>OTP</strong></td><td>One-Time Password - Mã xác thực một lần gửi qua Email để khôi phục mật khẩu.</td></tr>
                </tbody>
            </table>
        </div>

        <div class="page-break"></div>

        <!-- ==========================================
             PHẦN 1: TỔNG QUAN HỆ THỐNG
             ========================================== -->
        <h2 class="section-title" id="p1-1">
            <i class="fa-solid fa-cubes"></i> PHẦN 1: TỔNG QUAN HỆ THỐNG & QUY TRÌNH KẾT NỐI
        </h2>

        <h3 class="subsection-title" id="p1-1-detail">1.1 Giới thiệu mục đích hệ thống</h3>
        <p>
            Hệ thống Quản lý Thời khóa biểu và Học tập Trực tuyến là giải pháp phần mềm được phát triển riêng cho <strong>Trường Cao đẳng Cà Mau</strong>. Hệ thống giải quyết triệt để nhu cầu chuyển đổi số trong quản lý đào tạo, giúp sinh viên nâng cao năng lực tự học và hỗ trợ giảng viên tối ưu hóa quy trình quản lý lớp học, chấm bài tập trình tự động.
        </p>

        <h3 class="subsection-title" id="p1-2">1.2 Bảng Phân Quyền & Đặc Quyền Sử Dụng</h3>
        <table class="custom-table avoid-break">
            <thead>
                <tr>
                    <th style="width: 20%;">Vai trò</th>
                    <th style="width: 45%;">Quyền hạn & Chức năng chính</th>
                    <th style="width: 35%;">Đối tượng áp dụng</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge badge-primary"><i class="fa-solid fa-user-graduate"></i> Sinh viên</span></td>
                    <td>Xem TKB, Học bài trực tuyến, Nộp bài tập, Biên dịch Code IDE, Thi Quiz, Hỏi AI 24/7, Đổi quà AI Shop, Nghe nhạc tập trung.</td>
                    <td>Học sinh, Sinh viên các khóa tại trường.</td>
                </tr>
                <tr>
                    <td><span class="badge badge-success"><i class="fa-solid fa-chalkboard-user"></i> Giảng viên</span></td>
                    <td>Quản lý lớp, Điểm danh sinh viên, Tạo bài tập, Cấu hình Testcase chấm code tự động, Ngân hàng câu hỏi Quiz, Chấm đồ án.</td>
                    <td>Giảng viên cơ hữu và thỉnh giảng.</td>
                </tr>
                <tr>
                    <td><span class="badge badge-dark"><i class="fa-solid fa-shield-halved"></i> Quản trị viên</span></td>
                    <td>Quản lý tài khoản toàn trường, Phân công giảng dạy, Cấu hình hệ thống, Sao lưu dữ liệu (Backup DB).</td>
                    <td>Ban Quản trị CNTT & Phòng Đào tạo.</td>
                </tr>
            </tbody>
        </table>

        <h3 class="subsection-title" id="p1-3">1.3 Hướng dẫn Đăng ký & Đăng nhập</h3>
        <div class="step-list avoid-break">
            <div class="step-item">
                <div class="step-title">Bước 1: Truy cập địa chỉ trang web</div>
                <div class="step-desc">Mở trình duyệt Web (Google Chrome, Microsoft Edge, Safari) và truy cập địa chỉ website hệ thống. Thao tác chọn nút <strong>"Đăng nhập"</strong> ở thanh menu chính.</div>
            </div>
            <div class="step-item">
                <div class="step-title">Bước 2: Nhập thông tin xác thực</div>
                <div class="step-desc">Điền Tên tài khoản / Mã sinh viên / Mã giảng viên và Mật khẩu. Lựa chọn phân quyền phù hợp (Sinh viên hoặc Giảng viên).</div>
            </div>
            <div class="step-item">
                <div class="step-title">Bước 3: Đăng nhập thành công</div>
                <div class="step-desc">Hệ thống sẽ điều hướng người dùng tới Trang Tổng quan (Dashboard) tương ứng với vai trò đã đăng nhập.</div>
            </div>
        </div>

        <h3 class="subsection-title" id="p1-4">1.4 Quy trình Khôi phục Mật khẩu khi quên</h3>
        <p>
            Trường hợp người dùng quên mật khẩu đăng nhập, hệ thống tích hợp quy trình gửi mã xác thực OTP qua Email an toàn:
        </p>
        <div class="diagram-container avoid-break">
            <div class="diagram-step">
                <div class="diagram-step-title">1. Nhấn "Quên Mật Khẩu"</div>
                <div class="diagram-step-desc">Nhập Email sinh viên/GV</div>
            </div>
            <div class="diagram-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            <div class="diagram-step">
                <div class="diagram-step-title">2. Nhận Mã OTP</div>
                <div class="diagram-step-desc">Hệ thống gửi OTP 6 số qua Mail</div>
            </div>
            <div class="diagram-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            <div class="diagram-step">
                <div class="diagram-step-title">3. Đặt Mật Khẩu Mới</div>
                <div class="diagram-step-desc">Nhập OTP & Khởi tạo Pass mới</div>
            </div>
        </div>

        <div class="page-break"></div>

        <!-- ==========================================
             PHẦN 2: PHÂN HỆ SINH VIÊN
             ========================================== -->
        <h2 class="section-title" id="p2-1">
            <i class="fa-solid fa-user-graduate"></i> PHẦN 2: CHỨC NĂNG DÀNH CHO SINH VIÊN (STUDENT PORTAL)
        </h2>

        <h3 class="subsection-title" id="p2-1-detail">2.1 Trang Dashboard Sinh viên & Thời khóa biểu</h3>
        <p>
            Trang Dashboard Sinh viên được thiết kế giao diện dạng thẻ (Widget) trực quan. Sinh viên dễ dàng theo dõi thời khóa biểu hàng tuần, đếm ngược ca học sắp diễn ra, nhận thông báo quan trọng từ nhà trường và xem tiến độ học tập tích lũy.
        </p>
        
        <div class="ui-mockup-box avoid-break">
            <div class="ui-mockup-header">
                <div class="ui-mockup-dots">
                    <div class="ui-mockup-dot dot-red"></div>
                    <div class="ui-mockup-dot dot-yellow"></div>
                    <div class="ui-mockup-dot dot-green"></div>
                </div>
                <span>GIAO DIỆN DASHBOARD SINH VIÊN - THỜI KHÓA BIỂU TUẦN</span>
            </div>
            <pre style="margin: 0;">
+-----------------------------------------------------------------------------------+
|  [LỊCH HỌC HÔM NAY]                                                               |
|  Môn: Lập trình Web PHP | Phòng: Lab 03 | Tiết 1-4 | GV: Nguyễn Văn A               |
|  Trạng thái: Sap diễn ra (Còn 15 phút)                                            |
+-----------------------------------------------------------------------------------+
|  [THÔNG BÁO MỚI]                       |  [TIẾN ĐỘ HỌC TẬP]                      |
|  - Nộp bài tập C++ trước 23:00 hôm nay |  Hoàn thành: 85% môn học                  |
|  - Thi Quiz giữa kỳ môn CSDL           |  Điểm thưởng tích lũy: 450 Points         |
+-----------------------------------------------------------------------------------+
            </pre>
        </div>

        <h3 class="subsection-title" id="p2-2">2.2 Trợ lý Học tập Trí tuệ Nhân tạo (AI Assistant)</h3>
        <p>
            Tích hợp công nghệ AI tiên tiến, hỗ trợ sinh viên giải đáp thắc mắc 24/7. Trợ lý AI có khả năng:
        </p>
        <ul>
            <li><strong>Giải thích lý thuyết:</strong> Giải thích các thuật toán, cú pháp lệnh khó hiểu.</li>
            <li><strong>Fix lỗi Code (Debug):</strong> Phân tích thông báo lỗi biên dịch và đưa ra gợi ý sửa code từng dòng.</li>
            <li><strong>Tóm tắt bài giảng:</strong> Tóm tắt tài liệu học tập dài thành các ý chính ngắn gọn.</li>
        </ul>

        <h3 class="subsection-title" id="p2-3">2.3 Học bài Trực tuyến & Xem Slide Bài giảng</h3>
        <p>
            Sinh viên truy cập kho tài liệu bài giảng HTML tương tác hoặc xem các Slide (.pdf, .pptx) và Video giảng dạy do giáo viên bộ môn đăng tải. Hệ thống tự động ghi nhận phần trăm bài giảng đã học.
        </p>

        <h3 class="subsection-title" id="p2-4">2.4 Quản lý & Nộp Bài tập Tự luận / Thực hành</h3>
        <div class="step-list avoid-break">
            <div class="step-item">
                <div class="step-title">Bước 1: Chọn Bài tập cần nộp</div>
                <div class="step-desc">Vào danh sách bài tập, lọc bài tập theo môn học hoặc trạng thái (Chưa nộp, Quá hạn, Đã nộp).</div>
            </div>
            <div class="step-item">
                <div class="step-title">Bước 2: Soạn bài nộp hoặc tải tệp lên</div>
                <div class="step-desc">Nhập văn bản trả lời trực tiếp hoặc kéo thả tệp tin đính kèm (.zip, .pdf, .docx, .cpp).</div>
            </div>
            <div class="step-item">
                <div class="step-title">Bước 3: Xác nhận nộp bài</div>
                <div class="step-desc">Nhấn <strong>Nộp bài</strong>. Hệ thống ghi nhận thời gian nộp bài chính xác đến từng giây.</div>
            </div>
        </div>

        <h3 class="subsection-title" id="p2-5">2.5 Trình biên dịch Code IDE Online & Chấm tự động</h3>
        <p>
            Đây là công cụ rèn luyện kỹ năng lập trình cốt lõi. Sinh viên có thể viết code, chạy thử và nộp bài chấm tự động trực tiếp trên trình duyệt mà không cần cài đặt phần mềm biên dịch phức tạp.
        </p>

        <div class="diagram-container avoid-break">
            <div class="diagram-step">
                <div class="diagram-step-title">1. Soạn thảo Code</div>
                <div class="diagram-step-desc">Monaco Editor gợi ý cú pháp</div>
            </div>
            <div class="diagram-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            <div class="diagram-step">
                <div class="diagram-step-title">2. Chạy thử (Run)</div>
                <div class="diagram-step-desc">Kiểm tra kết quả Input/Output</div>
            </div>
            <div class="diagram-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            <div class="diagram-step">
                <div class="diagram-step-title">3. Nộp bài (Submit)</div>
                <div class="diagram-step-desc">Hệ thống chấm qua Testcases</div>
            </div>
        </div>

        <h3 class="subsection-title" id="p2-6">2.6 Thi & Làm bài Trắc nghiệm Trực tuyến (Quiz)</h3>
        <p>
            Giao diện làm bài thi trắc nghiệm được tối ưu hóa nhằm đảm bảo tính công bằng và chính xác:
        </p>
        <table class="custom-table avoid-break">
            <thead>
                <tr>
                    <th style="width: 30%;">Tính năng Quiz</th>
                    <th style="width: 70%;">Mô tả chi tiết tác dụng</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Đồng hồ đếm ngược</strong></td>
                    <td>Hiển thị thời gian làm bài còn lại, tự động nộp bài khi hết giờ.</td>
                </tr>
                <tr>
                    <td><strong>Tự động lưu bài làm</strong></td>
                    <td>Lưu câu trả lời ngay khi sinh viên tích chọn, bảo vệ dữ liệu nếu mất mạng.</td>
                </tr>
                <tr>
                    <td><strong>Xáo trộn câu hỏi & đáp án</strong></td>
                    <td>Mỗi sinh viên nhận được thứ tự câu hỏi khác nhau chống chép bài.</td>
                </tr>
                <tr>
                    <td><strong>Cảnh báo chuyển Tab</strong></td>
                    <td>Ghi nhận số lần sinh viên rời khỏi màn hình thi để chống gian lận.</td>
                </tr>
            </tbody>
        </table>

        <div class="feature-grid avoid-break">
            <div class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-icon"><i class="fa-solid fa-diagram-project"></i></div>
                    <div class="feature-card-title">2.7 Tiến độ Đồ án Môn học</div>
                </div>
                <div class="feature-card-body">
                    Nộp báo cáo định kỳ theo tuần cho giảng viên hướng dẫn, theo dõi trạng thái duyệt đề tài và nhận góp ý chỉnh sửa.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-icon"><i class="fa-solid fa-note-sticky"></i></div>
                    <div class="feature-card-title">2.8 Flashcards & Mini Games</div>
                </div>
                <div class="feature-card-body">
                    Ôn tập kiến thức qua thẻ học ghi nhớ từ vựng/thuật ngữ và tham gia các game trắc nghiệm tính điểm thưởng hấp dẫn.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-icon"><i class="fa-solid fa-store"></i></div>
                    <div class="feature-card-title">2.9 Đổi quà AI Shop</div>
                </div>
                <div class="feature-card-body">
                    Dùng điểm thưởng học tập tích lũy được khi làm bài tập đúng hạn để đổi Token AI mở rộng hoặc avatar độc quyền.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-icon"><i class="fa-solid fa-headphones"></i></div>
                    <div class="feature-card-title">2.10 Trình phát Nhạc Lo-Fi</div>
                </div>
                <div class="feature-card-body">
                    Nghe danh sách nhạc Lo-Fi chất lượng cao không lời tích hợp sẵn giúp loại bỏ xao nhãng và tăng cường tập trung.
                </div>
            </div>
        </div>

        <div class="page-break"></div>

        <!-- ==========================================
             PHẦN 3: PHÂN HỆ GIẢNG VIÊN
             ========================================== -->
        <h2 class="section-title" id="p3-1">
            <i class="fa-solid fa-chalkboard-user"></i> PHẦN 3: CHỨC NĂNG DÀNH CHO GIẢNG VIÊN (TEACHER PORTAL)
        </h2>

        <h3 class="subsection-title" id="p3-1-detail">3.1 Dashboard Giảng dạy & 3.2 Quản lý Lớp học</h3>
        <p>
            Cung cấp cái nhìn toàn cảnh về lịch giảng dạy trong tuần, danh sách lớp phụ trách, sĩ số sinh viên, bài tập đang chờ chấm và số lượng sinh viên vắng mặt cần chú ý.
        </p>

        <h3 class="subsection-title" id="p3-3">3.3 Điểm danh Sinh viên Trực tuyến</h3>
        <p>
            Giảng viên dễ dàng thực hiện điểm danh lớp học theo từng ca/buổi. Hệ thống hỗ trợ 3 trạng thái: <strong>Có mặt</strong>, <strong>Vắng có phép</strong>, <strong>Vắng không phép</strong>.
        </p>
        
        <div class="callout callout-success avoid-break">
            <i class="fa-solid fa-file-excel"></i>
            <div>
                <strong>Xuất báo cáo chuyên cần:</strong> Giảng viên có thể xuất bảng điểm danh toàn khóa ra tệp Excel chuẩn của Nhà trường chỉ với 1 cú click chuột.
            </div>
        </div>

        <h3 class="subsection-title" id="p3-4">3.4 Quản lý Bài tập & Chấm điểm Tự luận</h3>
        <div class="step-list avoid-break">
            <div class="step-item">
                <div class="step-title">Tạo bài tập mới cho lớp</div>
                <div class="step-desc">Nhập tiêu đề, yêu cầu đề bài, hạn nộp (Deadline), chọn môn học và các lớp áp dụng.</div>
            </div>
            <div class="step-item">
                <div class="step-title">Theo dõi bài nộp của sinh viên</div>
                <div class="step-desc">Xem bảng tổng hợp danh sách sinh viên đã nộp bài, thời gian nộp và trạng thái nộp muộn.</div>
            </div>
            <div class="step-item">
                <div class="step-title">Chấm điểm và trả kết quả</div>
                <div class="step-desc">Nhập điểm số (Thang điểm 10) và ghi nhận xét chi tiết. Hệ thống sẽ tự động thông báo tới sinh viên.</div>
            </div>
        </div>

        <h3 class="subsection-title" id="p3-5">3.5 Cấu hình Chấm Code Lập trình Tự động</h3>
        <p>
            Giảng viên tạo bài tập lập trình và thiết lập các bộ Testcase chuẩn. Hệ thống tự động biên dịch code của sinh viên và so sánh dữ liệu đầu ra với Output chuẩn.
        </p>

        <table class="custom-table avoid-break">
            <thead>
                <tr>
                    <th style="width: 25%;">Thông số Cấu hình</th>
                    <th style="width: 45%;">Giải thích chức năng</th>
                    <th style="width: 30%;">Ví dụ thiết lập</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Time Limit (Thời gian)</strong></td>
                    <td>Giới hạn thời gian chạy tối đa của chương trình.</td>
                    <td>1.0 Second (1 giây)</td>
                </tr>
                <tr>
                    <td><strong>Memory Limit (Bộ nhớ)</strong></td>
                    <td>Giới hạn dung lượng RAM tối đa cho phép sử dụng.</td>
                    <td>256 MB</td>
                </tr>
                <tr>
                    <td><strong>Input / Output Mẫu</strong></td>
                    <td>Testcase công khai cho sinh viên nhìn thấy để tham khảo.</td>
                    <td>Input: 3 5 -> Output: 8</td>
                </tr>
                <tr>
                    <td><strong>Testcases Ẩn</strong></td>
                    <td>Bộ testcase ẩn dùng để chấm điểm chính thức chống gian lận.</td>
                    <td>5-10 bộ test ngẫu nhiên</td>
                </tr>
            </tbody>
        </table>

        <h3 class="subsection-title" id="p3-6">3.6 Ngân hàng Câu hỏi & Tạo Đề thi Quiz</h3>
        <p>
            Hỗ trợ giảng viên tạo ngân hàng câu hỏi trắc nghiệm chia theo từng chương/môn học. Cho phép nhập câu hỏi hàng loạt từ file Excel. Giảng viên cấu hình thời gian làm bài, chế độ xem đáp án sau khi thi và số lượt làm bài cho phép.
        </p>

        <div class="feature-grid avoid-break">
            <div class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-icon"><i class="fa-solid fa-diagram-project"></i></div>
                    <div class="feature-card-title">3.7 Quản lý Đồ án Môn học</div>
                </div>
                <div class="feature-card-body">
                    Phân công danh sách đề tài đồ án cho từng nhóm sinh viên, duyệt tiến độ từng tuần, chấm điểm báo cáo và nhận xét cuối kỳ.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-icon"><i class="fa-solid fa-folder-open"></i></div>
                    <div class="feature-card-title">3.8 Quản lý Bài giảng & Slide</div>
                </div>
                <div class="feature-card-body">
                    Upload và phân loại giáo trình, slide bài giảng (.pdf, .pptx) theo từng môn để sinh viên đăng ký học tập dễ dàng tra cứu.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-icon"><i class="fa-solid fa-flask"></i></div>
                    <div class="feature-card-title">3.9 Thực hành Lab & Nhật ký</div>
                </div>
                <div class="feature-card-body">
                    Đăng tải hướng dẫn bài thực hành phòng máy và ghi chép nhật ký giảng dạy sau mỗi buổi lên lớp.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-icon"><i class="fa-solid fa-sliders"></i></div>
                    <div class="feature-card-title">3.10 Cấu hình AI & Nhạc</div>
                </div>
                <div class="feature-card-body">
                    Quản lý danh sách API Key AI dự phòng, thêm mới các bài nhạc Lo-Fi phục vụ không gian học tập sinh viên.
                </div>
            </div>
        </div>

        <div class="page-break"></div>

        <!-- ==========================================
             PHẦN 4: PHÂN HỆ QUẢN TRỊ VIÊN
             ========================================== -->
        <h2 class="section-title" id="p4-1">
            <i class="fa-solid fa-shield-halved"></i> PHẦN 4: CHỨC NĂNG DÀNH CHO QUẢN TRỊ VIÊN (ADMIN PORTAL)
        </h2>
        <p>
            Phân hệ Admin đảm bảo cho toàn bộ hệ thống vận hành ổn định, bảo mật và chính xác. Các tác vụ chính bao gồm:
        </p>

        <div class="step-list avoid-break">
            <div class="step-item">
                <div class="step-title">Quản lý Tài khoản & Phân quyền</div>
                <div class="step-desc">Tạo mới, sửa đổi, khóa hoặc mở khóa tài khoản Sinh viên, Giảng viên. Cấp quyền truy cập hệ thống.</div>
            </div>
            <div class="step-item">
                <div class="step-title">Quản lý Môn học & Thời khóa biểu toàn trường</div>
                <div class="step-desc">Khởi tạo chương trình môn học, danh mục lớp học và xếp lịch thời khóa biểu phòng máy / lý thuyết.</div>
            </div>
            <div class="step-item">
                <div class="step-title">Sao lưu & Phục hồi Dữ liệu (Database Backup)</div>
                <div class="step-desc">Thực hiện sao lưu định kỳ cơ sở dữ liệu hệ thống (.sql, .db), đảm bảo an toàn dữ liệu phòng sự cố.</div>
            </div>
        </div>

        <!-- ==========================================
             PHẦN 5: SƠ ĐỒ QUY TRÌNH NGHỆ NGHIỆP
             ========================================== -->
        <h2 class="section-title" id="p5-1">
            <i class="fa-solid fa-network-wired"></i> PHẦN 5: SƠ ĐỒ WORKFLOW & QUY TRÌNH PHỐI HỢP
        </h2>

        <h3 class="subsection-title">Quy trình Giao bài - Làm bài - Chấm Code Tự động</h3>
        <div class="diagram-container avoid-break">
            <div class="diagram-step">
                <div class="diagram-step-title">1. GIẢNG VIÊN</div>
                <div class="diagram-step-desc">Tạo đề bài Code & Thêm bộ Testcase chuẩn</div>
            </div>
            <div class="diagram-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            <div class="diagram-step">
                <div class="diagram-step-title">2. SINH VIÊN</div>
                <div class="diagram-step-desc">Viết code trên IDE & Nộp bài trực tuyến</div>
            </div>
            <div class="diagram-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            <div class="diagram-step">
                <div class="diagram-step-title">3. HỆ THỐNG IDE</div>
                <div class="diagram-step-desc">Biên dịch & Chạy tự động qua Testcases</div>
            </div>
            <div class="diagram-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            <div class="diagram-step">
                <div class="diagram-step-title">4. KẾT QUẢ</div>
                <div class="diagram-step-desc">Lưu điểm & Gửi thông báo tới cả hai bên</div>
            </div>
        </div>

        <!-- ==========================================
             PHẦN 6: XỬ LÝ SỰ CỐ (FAQ)
             ========================================== -->
        <h2 class="section-title" id="p6-1">
            <i class="fa-solid fa-circle-question"></i> PHẦN 6: HƯỚNG DẪN XỬ LÝ SỰ CỐ & CÂU HỎI THƯỜNG GẶP (FAQ)
        </h2>

        <div class="callout callout-warning avoid-break">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Câu 1: Cách xuất file Hướng dẫn sử dụng này ra bản PDF đẹp nhất để nộp cho Giảng viên?</strong>
                <br>
                <em>Trả lời:</em> Click nút màu đỏ <strong>"Xuất File PDF / In Hướng Dẫn Nộp Cô"</strong> trên cùng bên phải. Khi hộp thoại in của trình duyệt hiện lên:
                <br>• Mục <strong>Destination (Đích đến):</strong> Chọn <em>Save as PDF (Lưu dưới dạng PDF)</em>.
                <br>• Mục <strong>Paper size (Khổ giấy):</strong> Chọn <em>A4</em>.
                <br>• Mục <strong>Margins (Lề):</strong> Chọn <em>Default (Mặc định)</em>.
                <br>• Tích chọn <strong>Background graphics (Đồ họa nền)</strong> để giữ nguyên màu sắc các thẻ badge và bảng biểu.
            </div>
        </div>

        <div class="callout callout-info avoid-break">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                <strong>Câu 2: Tại sao làm bài nộp Code báo lỗi "Compilation Error"?</strong>
                <br>
                <em>Trả lời:</em> Lỗi biên dịch xảy ra khi code của bạn bị sai cú pháp (thiếu dấu chấm phẩy, sai tên biến, quên khai báo thư viện). Bạn có thể bấm nút <strong>"Nhờ AI Giải thích Lỗi"</strong> để xem phân tích chi tiết.
            </div>
        </div>

        <div class="callout callout-info avoid-break">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                <strong>Câu 3: Tôi không nhận được Email khôi phục mật khẩu OTP thì làm thế nào?</strong>
                <br>
                <em>Trả lời:</em> Kiểm tra hòm thư Rác/Spam trong Email của bạn. Nếu vẫn không thấy, vui lòng liên hệ Giảng viên chủ nhiệm hoặc Quản trị viên để đặt lại mật khẩu thủ công.
            </div>
        </div>

        <!-- ==========================================
             PHẦN 7: KẾT LUẬN & KÝ TÊN BÁO CÁO
             ========================================== -->
        <h2 class="section-title" id="p7-1">
            <i class="fa-solid fa-flag-checkered"></i> PHẦN 7: KẾT LUẬN & CHỮ KÝ BÁO CÁO
        </h2>
        <p>
            Hệ thống Quản lý Thời khóa biểu & Học tập Trực tuyến là sản phẩm được xây dựng bài bản, ứng dụng các công nghệ lập trình web tiên tiến nhất hiện nay. Tài liệu hướng dẫn này cung cấp đầy đủ thông tin vận hành cho mọi phân hệ người dùng. Kính trình Giảng viên xem xét và đánh giá!
        </p>

        <div style="margin-top: 60px; display: flex; justify-content: space-between; page-break-inside: avoid;">
            <div style="text-align: center; width: 45%;">
                <p><strong>XÁC NHẬN CỦA GIẢNG VIÊN HƯỚNG DẪN</strong></p>
                <p style="font-size: 13px; color: #666; margin-top: 5px;">(Ký và ghi rõ họ tên)</p>
                <div style="height: 90px;"></div>
                <p>__________________________</p>
            </div>
            <div style="text-align: center; width: 45%;">
                <p><strong>NGƯỜI LẬP BÁO CÁO / SINH VIÊN</strong></p>
                <p style="font-size: 13px; color: #666; margin-top: 5px;">(Ký và ghi rõ họ tên)</p>
                <div style="height: 90px;"></div>
                <p style="font-size: 16px; font-weight: 800; color: var(--primary);">Lê Nhựt Khánh</p>
            </div>
        </div>

        <div class="doc-footer-print">
            Báo cáo Hướng dẫn sử dụng Hệ thống - Trường Cao đẳng Cà Mau - Năm 2026
        </div>

    </div>

</body>
</html>
"""

target_path = r"c:\xampp\htdocs\tkb\huong_dan_su_dung.html"
with open(target_path, "w", encoding="utf-8") as f:
    f.write(html_content)

print(f"Successfully generated {target_path} with size {len(html_content)} bytes")
