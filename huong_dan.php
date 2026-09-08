<?php require_once 'includes/public_header.php'; ?>

<style>
/* =========================================================
   HƯỚNG DẪN SỬ DỤNG – Premium Documentation Page
   ========================================================= */

/* --- Variables --- */
:root {
    --doc-primary: #d91b43;
    --doc-primary-light: #f43f6d;
    --doc-primary-soft: rgba(217, 27, 67, 0.08);
    --doc-primary-border: rgba(217, 27, 67, 0.15);
    --doc-ink: #1a1a2e;
    --doc-ink-mid: #475569;
    --doc-ink-soft: #94a3b8;
    --doc-bg: #ffffff;
    --doc-bg-alt: #f8fafc;
    --doc-bg-code: #f1f5f9;
    --doc-border: #e2e8f0;
    --doc-success: #10b981;
    --doc-warning: #f59e0b;
    --doc-info: #3b82f6;
    --doc-danger: #ef4444;
}

/* --- Page Layout --- */
.doc-page {
    display: flex;
    min-height: 100vh;
    background: var(--doc-bg);
    padding-top: 0;
}

/* --- Sidebar TOC --- */
.doc-sidebar {
    position: sticky;
    top: 80px;
    width: 280px;
    height: calc(100vh - 80px);
    overflow-y: auto;
    padding: 32px 0 32px 24px;
    flex-shrink: 0;
    border-right: 1px solid var(--doc-border);
    background: var(--doc-bg-alt);
}

.doc-sidebar::-webkit-scrollbar { width: 3px; }
.doc-sidebar::-webkit-scrollbar-thumb { background: var(--doc-primary); border-radius: 4px; }

.doc-sidebar-title {
    font-family: 'Playfair Display', serif;
    font-size: 14px;
    font-weight: 800;
    color: var(--doc-primary);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 20px;
    padding-right: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.doc-sidebar-title i {
    font-size: 16px;
}

.doc-toc {
    list-style: none;
    padding: 0;
    margin: 0;
}

.doc-toc li {
    margin-bottom: 2px;
}

.doc-toc li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--doc-ink-mid);
    text-decoration: none;
    border-radius: 10px 0 0 10px;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
    line-height: 1.4;
}

.doc-toc li a:hover {
    background: var(--doc-primary-soft);
    color: var(--doc-primary);
    border-left-color: var(--doc-primary-light);
}

.doc-toc li a.active {
    background: var(--doc-primary-soft);
    color: var(--doc-primary);
    border-left-color: var(--doc-primary);
    font-weight: 700;
}

.doc-toc li a .toc-icon {
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border-radius: 6px;
    background: var(--doc-primary-soft);
    color: var(--doc-primary);
    flex-shrink: 0;
}

.doc-toc .toc-sub {
    padding-left: 20px;
}

.doc-toc .toc-sub a {
    font-size: 12.5px;
    padding: 6px 12px;
    color: var(--doc-ink-soft);
}

/* --- Main Content --- */
.doc-main {
    flex: 1;
    max-width: 900px;
    padding: 40px 50px 80px;
    margin: 0 auto;
}

/* --- Hero Banner --- */
.doc-hero {
    background: linear-gradient(135deg, #d91b43 0%, #a8122e 50%, #7f0e22 100%);
    border-radius: 20px;
    padding: 48px 44px;
    margin-bottom: 48px;
    position: relative;
    overflow: hidden;
    color: #fff;
}

.doc-hero::before {
    content: '';
    position: absolute;
    top: -60%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.doc-hero::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
    border-radius: 50%;
}

.doc-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(255,255,255,0.15);
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 18px;
    backdrop-filter: blur(4px);
}

.doc-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 900;
    margin: 0 0 12px;
    position: relative;
    z-index: 1;
    line-height: 1.3;
}

.doc-hero p {
    font-size: 15px;
    opacity: 0.85;
    line-height: 1.7;
    margin: 0;
    position: relative;
    z-index: 1;
    max-width: 600px;
}

.doc-hero-meta {
    display: flex;
    gap: 24px;
    margin-top: 20px;
    position: relative;
    z-index: 1;
}

.doc-hero-meta span {
    font-size: 12px;
    opacity: 0.7;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* --- Section --- */
.doc-section {
    margin-bottom: 56px;
    scroll-margin-top: 100px;
    animation: fadeInUp 0.5s ease;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

.doc-section-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--doc-border);
}

.doc-section-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--doc-primary), var(--doc-primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(217, 27, 67, 0.25);
}

.doc-section-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 800;
    color: var(--doc-ink);
    margin: 0;
    line-height: 1.3;
}

.doc-section-header h2 em {
    color: var(--doc-primary);
    font-style: normal;
}

/* --- Sub Section --- */
.doc-subsection {
    margin-bottom: 32px;
    padding: 24px 28px;
    background: var(--doc-bg-alt);
    border-radius: 16px;
    border: 1px solid var(--doc-border);
    transition: all 0.3s ease;
}

.doc-subsection:hover {
    border-color: var(--doc-primary-border);
    box-shadow: 0 4px 20px rgba(217, 27, 67, 0.06);
}

.doc-subsection h3 {
    font-size: 17px;
    font-weight: 700;
    color: var(--doc-ink);
    margin: 0 0 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.doc-subsection h3 .sub-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: var(--doc-primary-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--doc-primary);
    font-size: 14px;
    flex-shrink: 0;
}

.doc-subsection .doc-path {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: var(--doc-bg-code);
    border: 1px solid var(--doc-border);
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 12.5px;
    color: var(--doc-primary);
    margin-bottom: 12px;
    font-weight: 600;
}

.doc-subsection p, .doc-subsection ul {
    font-size: 14px;
    color: var(--doc-ink-mid);
    line-height: 1.8;
    margin: 0;
}

.doc-subsection ul {
    padding-left: 0;
    list-style: none;
}

.doc-subsection ul li {
    padding: 5px 0;
    padding-left: 22px;
    position: relative;
}

.doc-subsection ul li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 12px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--doc-primary);
    opacity: 0.5;
}

/* --- Tables --- */
.doc-table-wrap {
    overflow-x: auto;
    margin: 16px 0;
    border-radius: 14px;
    border: 1px solid var(--doc-border);
}

.doc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}

.doc-table thead th {
    background: linear-gradient(135deg, var(--doc-primary), var(--doc-primary-light));
    color: #fff;
    padding: 14px 18px;
    text-align: left;
    font-weight: 700;
    font-size: 12.5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.doc-table thead th:first-child { border-radius: 13px 0 0 0; }
.doc-table thead th:last-child { border-radius: 0 13px 0 0; }

.doc-table tbody td {
    padding: 13px 18px;
    border-bottom: 1px solid var(--doc-border);
    color: var(--doc-ink-mid);
    vertical-align: top;
}

.doc-table tbody tr:last-child td { border-bottom: none; }

.doc-table tbody tr:hover td {
    background: var(--doc-primary-soft);
}

.doc-table .role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
}

.role-student { background: #dbeafe; color: #1d4ed8; }
.role-teacher { background: #d1fae5; color: #059669; }
.role-admin { background: #fce7f3; color: #be185d; }

/* --- Alert Boxes --- */
.doc-alert {
    display: flex;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 12px;
    margin: 16px 0;
    font-size: 13.5px;
    line-height: 1.7;
    align-items: flex-start;
}

.doc-alert-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
    margin-top: 2px;
}

.doc-alert-info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
}
.doc-alert-info .doc-alert-icon { background: #bfdbfe; color: #1d4ed8; }

.doc-alert-warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
}
.doc-alert-warning .doc-alert-icon { background: #fde68a; color: #b45309; }

.doc-alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}
.doc-alert-success .doc-alert-icon { background: #a7f3d0; color: #059669; }

.doc-alert-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}
.doc-alert-danger .doc-alert-icon { background: #fecaca; color: #dc2626; }

/* --- Code Blocks --- */
.doc-code {
    background: #1e293b;
    color: #e2e8f0;
    padding: 20px 24px;
    border-radius: 14px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.8;
    overflow-x: auto;
    margin: 16px 0;
    position: relative;
}

.doc-code::before {
    content: '';
    position: absolute;
    top: 12px;
    left: 16px;
    width: 10px; height: 10px;
    border-radius: 50%;
    background: #ef4444;
    box-shadow: 16px 0 0 #f59e0b, 32px 0 0 #10b981;
}

.doc-code pre {
    margin: 0;
    padding-top: 14px;
    white-space: pre-wrap;
    word-break: break-all;
}

.doc-code .code-comment { color: #64748b; }
.doc-code .code-folder { color: #38bdf8; }
.doc-code .code-file { color: #94a3b8; }

/* --- Step Cards --- */
.doc-steps {
    counter-reset: step;
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin: 16px 0;
}

.doc-step {
    counter-increment: step;
    display: flex;
    gap: 16px;
    padding: 18px 22px;
    background: var(--doc-bg);
    border: 1px solid var(--doc-border);
    border-radius: 14px;
    transition: all 0.3s ease;
}

.doc-step:hover {
    border-color: var(--doc-primary-border);
    transform: translateX(4px);
    box-shadow: 0 4px 16px rgba(217, 27, 67, 0.06);
}

.doc-step::before {
    content: counter(step);
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--doc-primary), var(--doc-primary-light));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 800;
    flex-shrink: 0;
}

.doc-step-content h4 {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--doc-ink);
    margin: 0 0 4px;
}

.doc-step-content p {
    font-size: 13px;
    color: var(--doc-ink-mid);
    margin: 0;
    line-height: 1.6;
}

/* --- Feature Grid --- */
.doc-feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
    margin: 20px 0;
}

.doc-feature-card {
    padding: 22px;
    background: var(--doc-bg);
    border: 1px solid var(--doc-border);
    border-radius: 16px;
    transition: all 0.3s ease;
    cursor: default;
}

.doc-feature-card:hover {
    border-color: var(--doc-primary-border);
    box-shadow: 0 8px 24px rgba(217, 27, 67, 0.08);
    transform: translateY(-2px);
}

.doc-feature-card .fc-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 14px;
}

.doc-feature-card h4 {
    font-size: 15px;
    font-weight: 700;
    color: var(--doc-ink);
    margin: 0 0 6px;
}

.doc-feature-card p {
    font-size: 12.5px;
    color: var(--doc-ink-mid);
    margin: 0;
    line-height: 1.6;
}

.fc-blue { background: #dbeafe; color: #2563eb; }
.fc-green { background: #d1fae5; color: #059669; }
.fc-amber { background: #fef3c7; color: #d97706; }
.fc-rose { background: #fce7f3; color: #be185d; }
.fc-purple { background: #ede9fe; color: #7c3aed; }
.fc-cyan { background: #cffafe; color: #0891b2; }
.fc-orange { background: #ffedd5; color: #ea580c; }
.fc-emerald { background: #d1fae5; color: #059669; }
.fc-indigo { background: #e0e7ff; color: #4338ca; }
.fc-red { background: #fee2e2; color: #dc2626; }
.fc-teal { background: #ccfbf1; color: #0d9488; }

/* --- Security Table --- */
.security-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    background: var(--doc-bg);
    border: 1px solid var(--doc-border);
    border-radius: 12px;
    margin-bottom: 10px;
    transition: all 0.2s ease;
}

.security-item:hover {
    border-color: var(--doc-primary-border);
}

.security-item .sec-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: var(--doc-primary-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--doc-primary);
    font-size: 15px;
    flex-shrink: 0;
}

.security-item .sec-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--doc-ink);
}

.security-item .sec-desc {
    font-size: 12.5px;
    color: var(--doc-ink-mid);
    margin-top: 2px;
}

/* --- Troubleshoot Accordion --- */
.doc-trouble {
    border: 1px solid var(--doc-border);
    border-radius: 14px;
    margin-bottom: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.doc-trouble:hover { border-color: var(--doc-primary-border); }

.doc-trouble-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    cursor: pointer;
    background: var(--doc-bg-alt);
    transition: all 0.2s;
    user-select: none;
}

.doc-trouble-header:hover { background: var(--doc-primary-soft); }

.doc-trouble-header .trouble-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #fee2e2;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}

.doc-trouble-header span {
    flex: 1;
    font-size: 14px;
    font-weight: 600;
    color: var(--doc-ink);
}

.doc-trouble-header .chevron {
    font-size: 12px;
    color: var(--doc-ink-soft);
    transition: transform 0.3s;
}

.doc-trouble.open .doc-trouble-header .chevron { transform: rotate(180deg); }

.doc-trouble-body {
    padding: 0 20px;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.doc-trouble.open .doc-trouble-body {
    padding: 16px 20px;
    max-height: 300px;
}

.doc-trouble-body ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.doc-trouble-body ul li {
    padding: 6px 0;
    font-size: 13.5px;
    color: var(--doc-ink-mid);
    padding-left: 20px;
    position: relative;
}

.doc-trouble-body ul li::before {
    content: '→';
    position: absolute;
    left: 0;
    color: var(--doc-primary);
    font-weight: 700;
}

/* --- Back to Top --- */
.doc-back-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--doc-primary), var(--doc-primary-light));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(217, 27, 67, 0.3);
    z-index: 999;
    border: none;
}

.doc-back-top.visible { opacity: 1; visibility: visible; }
.doc-back-top:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(217, 27, 67, 0.4); }

/* --- Mobile Responsive --- */
@media (max-width: 1024px) {
    .doc-sidebar { display: none; }
    .doc-main { padding: 24px 20px 60px; }
    .doc-hero { padding: 32px 24px; }
    .doc-hero h1 { font-size: 24px; }
    .doc-feature-grid { grid-template-columns: 1fr; }
}

/* --- Mobile TOC Toggle --- */
.doc-mobile-toc-btn {
    display: none;
    position: fixed;
    bottom: 30px;
    left: 30px;
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, #1e293b, #334155);
    color: #fff;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    z-index: 998;
    border: none;
    transition: all 0.3s;
}

.doc-mobile-toc-btn:hover { transform: translateY(-3px); }

@media (max-width: 1024px) {
    .doc-mobile-toc-btn { display: flex; }
}

/* TOC Drawer Mobile */
.doc-toc-drawer {
    position: fixed;
    top: 0; left: 0;
    width: 300px;
    height: 100vh;
    background: var(--doc-bg);
    z-index: 1000;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    overflow-y: auto;
    padding: 24px;
    box-shadow: 4px 0 30px rgba(0,0,0,0.15);
}

.doc-toc-drawer.open { transform: translateX(0); }

.doc-toc-drawer-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}

.doc-toc-drawer-overlay.open { opacity: 1; visibility: visible; }

.doc-toc-drawer .doc-toc li a {
    border-radius: 10px;
}

/* Print styles */
@media print {
    .doc-sidebar, .doc-back-top, .doc-mobile-toc-btn { display: none !important; }
    .doc-main { padding: 0; max-width: 100%; }
    .doc-hero { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
}
</style>

<!-- Mobile TOC Drawer -->
<div class="doc-toc-drawer-overlay" id="tocOverlay" onclick="closeTocDrawer()"></div>
<div class="doc-toc-drawer" id="tocDrawer">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <span class="doc-sidebar-title" style="margin:0;"><i class="fas fa-list"></i> Mục lục</span>
        <button onclick="closeTocDrawer()" style="background:none;border:none;font-size:20px;color:var(--doc-ink-soft);cursor:pointer;">&times;</button>
    </div>
    <ul class="doc-toc" id="mobileToc"></ul>
</div>

<button class="doc-mobile-toc-btn" onclick="openTocDrawer()" title="Mục lục">
    <i class="fas fa-list"></i>
</button>

<div class="doc-page">

    <!-- ======= SIDEBAR TOC ======= -->
    <aside class="doc-sidebar" id="docSidebar">
        <div class="doc-sidebar-title"><i class="fas fa-book-open"></i> Mục Lục</div>
        <ul class="doc-toc" id="desktopToc">
            <li><a href="#sec-intro" class="active"><span class="toc-icon"><i class="fas fa-home"></i></span> Giới thiệu tổng quan</a></li>
            <li><a href="#sec-requirements"><span class="toc-icon"><i class="fas fa-server"></i></span> Yêu cầu hệ thống</a></li>
            <li><a href="#sec-install"><span class="toc-icon"><i class="fas fa-download"></i></span> Cài đặt & Khởi chạy</a></li>
            <li><a href="#sec-public"><span class="toc-icon"><i class="fas fa-globe"></i></span> Trang công khai</a></li>
            <li><a href="#sec-auth"><span class="toc-icon"><i class="fas fa-lock"></i></span> Đăng ký & Đăng nhập</a></li>
            <li><a href="#sec-student"><span class="toc-icon"><i class="fas fa-user-graduate"></i></span> Dành cho Sinh viên</a></li>
            <li><a href="#sec-teacher"><span class="toc-icon"><i class="fas fa-chalkboard-user"></i></span> Dành cho Giảng viên</a></li>
            <li><a href="#sec-admin"><span class="toc-icon"><i class="fas fa-shield-halved"></i></span> Dành cho Quản trị viên</a></li>
            <li><a href="#sec-api"><span class="toc-icon"><i class="fas fa-plug"></i></span> API & Tích hợp</a></li>
            <li><a href="#sec-security"><span class="toc-icon"><i class="fas fa-lock"></i></span> Bảo mật</a></li>
            <li><a href="#sec-troubleshoot"><span class="toc-icon"><i class="fas fa-wrench"></i></span> Xử lý sự cố</a></li>
            <li><a href="#sec-contact"><span class="toc-icon"><i class="fas fa-phone"></i></span> Liên hệ hỗ trợ</a></li>
        </ul>
    </aside>

    <!-- ======= MAIN CONTENT ======= -->
    <main class="doc-main">

        <!-- HERO -->
        <div class="doc-hero">
            <div class="doc-hero-badge"><i class="fas fa-book"></i> Tài liệu chính thức v2.5</div>
            <h1>Hướng Dẫn Sử Dụng<br>Hệ Thống Quản Lý Đào Tạo</h1>
            <p>Tài liệu hướng dẫn chi tiết dành cho Sinh viên, Giảng viên và Quản trị viên — Trường Cao Đẳng Cà Mau</p>
            <div class="doc-hero-meta" style="margin-bottom: 20px;">
                <span><i class="fas fa-calendar"></i> Cập nhật: Năm 2026</span>
                <span><i class="fas fa-user"></i> Tác giả: Lê Nhựt Khánh - Coder</span>
                <span><i class="fas fa-code-branch"></i> Phiên bản: 2.5</span>
            </div>
            <a href="huong_dan_su_dung.html" target="_blank" style="display:inline-flex;align-items:center;gap:10px;padding:12px 24px;background:#ffffff;color:#d91b43;font-weight:700;border-radius:12px;text-decoration:none;box-shadow:0 4px 15px rgba(0,0,0,0.2);transition:all 0.2s;font-size:14px;">
                <i class="fas fa-file-pdf fa-lg"></i> MỞ BẢN HƯỚNG DẪN BÁO CÁO (XUẤT FILE PDF NỘP CÔ)
            </a>
        </div>

        <!-- =========== 1. GIỚI THIỆU =========== -->
        <section class="doc-section" id="sec-intro">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-info-circle"></i></div>
                <h2>Giới Thiệu <em>Tổng Quan</em></h2>
            </div>

            <p style="font-size:14.5px;color:var(--doc-ink-mid);line-height:1.8;margin-bottom:20px;">
                Hệ thống <strong>Quản lý Đào tạo</strong> là nền tảng web toàn diện phục vụ hoạt động giảng dạy, học tập và quản trị của Trường Cao Đẳng Cà Mau. Hệ thống hỗ trợ <strong>3 vai trò</strong> người dùng:
            </p>

            <div class="doc-table-wrap">
                <table class="doc-table">
                    <thead>
                        <tr>
                            <th>Vai trò</th>
                            <th>Mô tả chính</th>
                            <th>Số chức năng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="role-badge role-student"><i class="fas fa-user-graduate"></i> Sinh viên</span></td>
                            <td>Xem TKB, học bài, làm quiz, nộp bài tập, thực hành code, quản lý đồ án</td>
                            <td><strong>11+</strong> tính năng</td>
                        </tr>
                        <tr>
                            <td><span class="role-badge role-teacher"><i class="fas fa-chalkboard-user"></i> Giảng viên</span></td>
                            <td>Quản lý lớp, giao bài tập, tạo quiz, điểm danh, tài liệu, đồ án</td>
                            <td><strong>13+</strong> tính năng</td>
                        </tr>
                        <tr>
                            <td><span class="role-badge role-admin"><i class="fas fa-shield-halved"></i> Quản trị viên</span></td>
                            <td>Quản lý toàn bộ hệ thống: người dùng, lớp, môn, phân quyền, backup</td>
                            <td><strong>10+</strong> tính năng</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="doc-alert doc-alert-info">
                <div class="doc-alert-icon"><i class="fas fa-graduation-cap"></i></div>
                <div><strong>4 ngành đào tạo:</strong> Công Nghệ Thông Tin • Cơ Khí Ô Tô • Điện - Điện Tử • Quản Trị Doanh Nghiệp</div>
            </div>
        </section>

        <!-- =========== 2. YÊU CẦU HỆ THỐNG =========== -->
        <section class="doc-section" id="sec-requirements">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-server"></i></div>
                <h2>Yêu Cầu <em>Hệ Thống</em></h2>
            </div>

            <div class="doc-subsection">
                <h3><span class="sub-icon"><i class="fas fa-desktop"></i></span> Máy chủ (Server)</h3>
                <div class="doc-table-wrap">
                    <table class="doc-table">
                        <thead><tr><th>Thành phần</th><th>Yêu cầu tối thiểu</th></tr></thead>
                        <tbody>
                            <tr><td><strong>Web Server</strong></td><td>Apache (XAMPP khuyến nghị)</td></tr>
                            <tr><td><strong>PHP</strong></td><td>Phiên bản 7.3 trở lên</td></tr>
                            <tr><td><strong>Database</strong></td><td>MySQL / MariaDB</td></tr>
                            <tr><td><strong>Python</strong></td><td>3.x (cho module AI — <code>app.py</code>)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="doc-subsection">
                <h3><span class="sub-icon"><i class="fas fa-globe"></i></span> Trình duyệt (Client)</h3>
                <ul>
                    <li>Google Chrome 90+ <strong>(khuyến nghị)</strong></li>
                    <li>Mozilla Firefox 88+</li>
                    <li>Microsoft Edge 90+</li>
                    <li>Safari 14+</li>
                </ul>
                <div class="doc-alert doc-alert-success" style="margin-top:12px;">
                    <div class="doc-alert-icon"><i class="fas fa-mobile-alt"></i></div>
                    <div>Hệ thống hỗ trợ giao diện <strong>responsive</strong> — hiển thị tốt trên cả máy tính, tablet và điện thoại.</div>
                </div>
            </div>
        </section>

        <!-- =========== 3. CÀI ĐẶT =========== -->
        <section class="doc-section" id="sec-install">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-download"></i></div>
                <h2>Cài Đặt & <em>Khởi Chạy</em></h2>
            </div>

            <div class="doc-steps">
                <div class="doc-step">
                    <div class="doc-step-content">
                        <h4>Cài đặt XAMPP</h4>
                        <p>Tải XAMPP từ <a href="https://www.apachefriends.org" target="_blank" style="color:var(--doc-primary);font-weight:600;">apachefriends.org</a> → Cài đặt → Khởi động <strong>Apache</strong> và <strong>MySQL</strong> từ Control Panel</p>
                    </div>
                </div>
                <div class="doc-step">
                    <div class="doc-step-content">
                        <h4>Triển khai mã nguồn</h4>
                        <p>Sao chép toàn bộ thư mục dự án vào <code>C:\xampp\htdocs\tkb\</code></p>
                    </div>
                </div>
                <div class="doc-step">
                    <div class="doc-step-content">
                        <h4>Tạo cơ sở dữ liệu</h4>
                        <p>Mở <strong>phpMyAdmin</strong> (<code>http://localhost/phpmyadmin</code>) → Tạo database <code>truong_caodang</code> → Import file <code>if0_41796593_truong_caodang.sql</code></p>
                    </div>
                </div>
                <div class="doc-step">
                    <div class="doc-step-content">
                        <h4>Cấu hình tự động</h4>
                        <p>File <code>config.php</code> tự động nhận diện môi trường: <strong>Localhost</strong> (root / không mật khẩu) hoặc <strong>Production</strong> (InfinityFree)</p>
                    </div>
                </div>
                <div class="doc-step">
                    <div class="doc-step-content">
                        <h4>Truy cập hệ thống</h4>
                        <p>Mở trình duyệt → Truy cập <a href="http://localhost/tkb/" style="color:var(--doc-primary);font-weight:600;">http://localhost/tkb/</a></p>
                    </div>
                </div>
            </div>

            <div class="doc-code">
                <pre><span class="code-comment"># Cấu trúc thư mục dự án</span>
<span class="code-folder">tkb/</span>
├── <span class="code-folder">admin/</span>          <span class="code-comment"># Trang quản trị viên</span>
├── <span class="code-folder">api/</span>            <span class="code-comment"># API endpoints</span>
├── <span class="code-folder">assets/</span>         <span class="code-comment"># CSS, hình ảnh, tài nguyên</span>
├── <span class="code-folder">includes/</span>       <span class="code-comment"># Header, footer, navigation</span>
├── <span class="code-folder">student/</span>        <span class="code-comment"># Trang sinh viên</span>
├── <span class="code-folder">teacher/</span>        <span class="code-comment"># Trang giảng viên</span>
├── <span class="code-file">config.php</span>      <span class="code-comment"># Cấu hình hệ thống</span>
├── <span class="code-file">index.php</span>       <span class="code-comment"># Trang chủ</span>
├── <span class="code-file">login.php</span>       <span class="code-comment"># Trang đăng nhập</span>
└── <span class="code-file">app.py</span>          <span class="code-comment"># Flask AI server</span></pre>
            </div>
        </section>

        <!-- =========== 4. TRANG CÔNG KHAI =========== -->
        <section class="doc-section" id="sec-public">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-globe"></i></div>
                <h2>Trang <em>Công Khai</em></h2>
            </div>

            <p style="font-size:14px;color:var(--doc-ink-mid);margin-bottom:20px;line-height:1.7;">
                Các trang sau có thể truy cập <strong>không cần đăng nhập</strong>:
            </p>

            <div class="doc-table-wrap">
                <table class="doc-table">
                    <thead><tr><th>Trang</th><th>Đường dẫn</th><th>Mô tả</th></tr></thead>
                    <tbody>
                        <tr><td>🏠 <strong>Trang chủ</strong></td><td><code>/tkb/index.php</code></td><td>Giới thiệu trường, TKB công khai, đăng ký</td></tr>
                        <tr><td>ℹ️ <strong>Giới thiệu</strong></td><td><code>/tkb/gioi_thieu.php</code></td><td>Lịch sử, tầm nhìn, sứ mệnh nhà trường</td></tr>
                        <tr><td>📚 <strong>Đào tạo</strong></td><td><code>/tkb/dao_tao.php</code></td><td>Chi tiết chương trình đào tạo theo ngành</td></tr>
                        <tr><td>📰 <strong>Tuyển sinh</strong></td><td><code>/tkb/tuyen_sinh.php</code></td><td>Thông tin, điều kiện, quy trình tuyển sinh</td></tr>
                        <tr><td>📖 <strong>Tài liệu</strong></td><td><code>/tkb/tai_lieu.php</code></td><td>Tài liệu học tập công khai</td></tr>
                        <tr><td>📷 <strong>Thư viện</strong></td><td><code>/tkb/thu_vien.php</code></td><td>Thư viện ảnh, tài nguyên</td></tr>
                        <tr><td>🎉 <strong>Sự kiện</strong></td><td><code>/tkb/su_kien.php</code></td><td>Các sự kiện sắp tới của trường</td></tr>
                        <tr><td>📞 <strong>Liên hệ</strong></td><td><code>/tkb/lien_he.php</code></td><td>Form liên hệ, bản đồ, thông tin trường</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- =========== 5. ĐĂNG KÝ & ĐĂNG NHẬP =========== -->
        <section class="doc-section" id="sec-auth">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-lock"></i></div>
                <h2>Đăng Ký & <em>Đăng Nhập</em></h2>
            </div>

            <div class="doc-subsection">
                <h3><span class="sub-icon"><i class="fas fa-user-plus"></i></span> Đăng ký tài khoản Sinh viên</h3>
                <div class="doc-steps">
                    <div class="doc-step"><div class="doc-step-content"><h4>Truy cập trang chủ</h4><p>Vào <code>/tkb/index.php</code> và tìm form đăng ký</p></div></div>
                    <div class="doc-step"><div class="doc-step-content"><h4>Điền thông tin</h4><p><strong>Bắt buộc:</strong> Tên đăng nhập, Mật khẩu, Họ tên, Khoa — <strong>Tùy chọn:</strong> Email, SĐT</p></div></div>
                    <div class="doc-step"><div class="doc-step-content"><h4>Hoàn tất</h4><p>Hệ thống tự động đăng nhập và chuyển đến Dashboard sinh viên</p></div></div>
                </div>
                <div class="doc-alert doc-alert-info">
                    <div class="doc-alert-icon"><i class="fas fa-info"></i></div>
                    <div>Tài khoản <strong>Giảng viên</strong> và <strong>Quản trị viên</strong> được tạo bởi Admin, không thể tự đăng ký.</div>
                </div>
            </div>

            <div class="doc-subsection">
                <h3><span class="sub-icon"><i class="fas fa-sign-in-alt"></i></span> Đăng nhập</h3>
                <div class="doc-path"><i class="fas fa-link"></i> /tkb/login.php</div>
                <ul>
                    <li>Nhập <strong>Tên đăng nhập</strong> và <strong>Mật khẩu</strong></li>
                    <li>Hỗ trợ đăng nhập bằng <strong>nhận diện khuôn mặt</strong> (Face Login)</li>
                    <li>Tự động chuyển đến Dashboard tương ứng theo vai trò</li>
                </ul>
            </div>

            <div class="doc-subsection">
                <h3><span class="sub-icon"><i class="fas fa-key"></i></span> Quên mật khẩu</h3>
                <p>Tại trang đăng nhập → Nhấn <strong>"Quên mật khẩu?"</strong> → Xác thực danh tính → Đặt lại mật khẩu mới tại <code>/tkb/reset_password.php</code></p>
            </div>
        </section>

        <!-- =========== 6. SINH VIÊN =========== -->
        <section class="doc-section" id="sec-student">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-user-graduate"></i></div>
                <h2>Dành Cho <em>Sinh Viên</em></h2>
            </div>

            <div class="doc-alert doc-alert-info">
                <div class="doc-alert-icon"><i class="fas fa-palette"></i></div>
                <div><strong>Giao diện:</strong> Theme Minecraft độc đáo với hiệu ứng LED 7 màu RGB — Hỗ trợ tuỳ chỉnh tone màu và banner cá nhân.</div>
            </div>

            <div class="doc-feature-grid">
                <div class="doc-feature-card">
                    <div class="fc-icon fc-green"><i class="fas fa-home"></i></div>
                    <h4>Cổng Sinh viên</h4>
                    <p>Dashboard tổng quan: TKB cá nhân, thông báo, thống kê điểm, tiến độ học tập.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /student/dashboard.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-blue"><i class="fas fa-book-open"></i></div>
                    <h4>Học bài</h4>
                    <p>Truy cập tài liệu, bài giảng, slide theo môn. Đánh dấu tiến độ.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /student/hoc_bai.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-emerald"><i class="fas fa-check-circle"></i></div>
                    <h4>Làm Quiz</h4>
                    <p>Tham gia kiểm tra trắc nghiệm, xem kết quả tức thì, lịch sử quiz.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /student/quiz.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-amber"><i class="fas fa-clone"></i></div>
                    <h4>Flashcard</h4>
                    <p>Ôn tập bằng thẻ ghi nhớ: lật thẻ xem đáp án, phân loại đã nhớ/chưa nhớ.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /student/flashcard.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-purple"><i class="fas fa-robot"></i></div>
                    <h4>AI Hỗ trợ</h4>
                    <p>Chat với trợ lý AI: hỏi bài, giải thích khái niệm, hỗ trợ code & debug.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /student/ai.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-orange"><i class="fas fa-pen-to-square"></i></div>
                    <h4>Nộp bài tập</h4>
                    <p>Xem bài tập được giao, upload bài làm, theo dõi trạng thái chấm điểm.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /student/baitap.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-cyan"><i class="fas fa-code"></i></div>
                    <h4>Thực hành Code (IDE)</h4>
                    <p>Môi trường lập trình trực tuyến: viết code, chạy thử, xem kết quả real-time.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /student/code_ide.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-rose"><i class="fas fa-folder-open"></i></div>
                    <h4>Quản lý Đồ án</h4>
                    <p>Xem đồ án được phân, cập nhật tiến độ, tải tài liệu, liên lạc GV hướng dẫn.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /student/doan.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-indigo"><i class="fas fa-chart-line"></i></div>
                    <h4>Theo dõi Tiến độ</h4>
                    <p>Biểu đồ trực quan tiến độ học tập, thống kê điểm theo môn và tuần/tháng.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /student/tien_do.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-teal"><i class="fas fa-user"></i></div>
                    <h4>Hồ sơ Cá nhân</h4>
                    <p>Xem/sửa thông tin, đổi avatar, đổi mật khẩu, xem mã SV, lớp, khoa.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /student/profile.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-red"><i class="fas fa-palette"></i></div>
                    <h4>Tùy chỉnh Giao diện</h4>
                    <p>Đổi banner trang chủ, chọn tone màu theme — Lưu vào trình duyệt.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> Sidebar menu</div>
                </div>
            </div>
        </section>

        <!-- =========== 7. GIẢNG VIÊN =========== -->
        <section class="doc-section" id="sec-teacher">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-chalkboard-user"></i></div>
                <h2>Dành Cho <em>Giảng Viên</em></h2>
            </div>

            <div class="doc-alert doc-alert-info">
                <div class="doc-alert-icon"><i class="fas fa-info"></i></div>
                <div><strong>Giao diện:</strong> Theme sáng (Light) chuyên nghiệp, sidebar cố định bên trái. Admin đã được gộp vào cùng giao diện giảng viên.</div>
            </div>

            <div class="doc-feature-grid">
                <div class="doc-feature-card">
                    <div class="fc-icon fc-blue"><i class="fas fa-gauge"></i></div>
                    <h4>Tổng quan</h4>
                    <p>Thống kê: số môn, số SV, số lớp. Lịch giảng dạy. Thông báo mới nhất.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/dashboard.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-green"><i class="fas fa-graduation-cap"></i></div>
                    <h4>Quản lý Lớp</h4>
                    <p>Xem danh sách lớp phụ trách, danh sách sinh viên trong từng lớp.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/quanlylop.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-orange"><i class="fas fa-pen-to-square"></i></div>
                    <h4>Giao bài tập</h4>
                    <p>Tạo bài tập mới, phân cho lớp/SV, xem bài nộp, chấm điểm & nhận xét.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/baitap.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-cyan"><i class="fas fa-code"></i></div>
                    <h4>Quản lý Thực hành</h4>
                    <p>Tạo bài thực hành lập trình, thiết lập test case, chấm code tự động.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/quanly_thuchanh.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-purple"><i class="fas fa-brain"></i></div>
                    <h4>Quiz</h4>
                    <p>Tạo quiz trắc nghiệm, thiết lập thời gian, phân quiz cho lớp, xem thống kê.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/quiz.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-emerald"><i class="fas fa-user-check"></i></div>
                    <h4>Điểm danh</h4>
                    <p>Điểm danh theo buổi, xem lịch sử điểm danh, thống kê tỷ lệ chuyên cần.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/diemdanh.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-amber"><i class="fas fa-folder"></i></div>
                    <h4>Tài liệu</h4>
                    <p>Upload tài liệu giảng dạy (PDF, Word, PPT…), phân loại và chia sẻ cho SV.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/tailieu.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-rose"><i class="fas fa-folder-open"></i></div>
                    <h4>Đồ án</h4>
                    <p>Tạo đề tài, phân công SV/nhóm, theo dõi tiến độ, chấm điểm đồ án.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/doan.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-teal"><i class="fas fa-user"></i></div>
                    <h4>Hồ sơ Cá nhân</h4>
                    <p>Chỉnh sửa thông tin GV, đổi avatar, mật khẩu, xem mã GV và khoa.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/profile.php</div>
                </div>
            </div>

            <!-- Admin functions within teacher -->
            <div style="margin-top:32px;">
                <h3 style="font-size:18px;font-weight:800;color:var(--doc-ink);margin-bottom:16px;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-shield-halved" style="color:var(--doc-primary);"></i> Quản trị Hệ thống (dành cho GV có quyền)
                </h3>
                <div class="doc-feature-grid">
                    <div class="doc-feature-card">
                        <div class="fc-icon fc-blue"><i class="fas fa-users"></i></div>
                        <h4>QL Sinh viên</h4>
                        <p>Xem, thêm, sửa, xóa thông tin SV. Lọc theo lớp/khoa.</p>
                        <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/quanly_sinhvien.php</div>
                    </div>
                    <div class="doc-feature-card">
                        <div class="fc-icon fc-green"><i class="fas fa-chalkboard-user"></i></div>
                        <h4>QL Giảng viên</h4>
                        <p>Xem danh sách, chỉnh sửa thông tin giảng viên.</p>
                        <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/quanly_giangvien.php</div>
                    </div>
                    <div class="doc-feature-card">
                        <div class="fc-icon fc-amber"><i class="fas fa-book"></i></div>
                        <h4>QL Môn học</h4>
                        <p>Thêm/sửa/xóa môn học, mã môn, tên môn, số tín chỉ.</p>
                        <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/quanly_monhoc.php</div>
                    </div>
                    <div class="doc-feature-card">
                        <div class="fc-icon fc-indigo"><i class="fas fa-clock-rotate-left"></i></div>
                        <h4>Nhật ký</h4>
                        <p>Xem log hoạt động hệ thống: ai, làm gì, lúc nào.</p>
                        <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /teacher/nhatky.php</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========== 8. QUẢN TRỊ VIÊN =========== -->
        <section class="doc-section" id="sec-admin">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-shield-halved"></i></div>
                <h2>Dành Cho <em>Quản Trị Viên</em></h2>
            </div>

            <div class="doc-alert doc-alert-warning">
                <div class="doc-alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div><strong>Lưu ý:</strong> Dashboard Admin tự động chuyển hướng đến giao diện Giảng viên (<code>/tkb/teacher/dashboard.php</code>). Các trang quản trị riêng nằm tại <code>/tkb/admin/</code>.</div>
            </div>

            <div class="doc-feature-grid">
                <div class="doc-feature-card">
                    <div class="fc-icon fc-blue"><i class="fas fa-user-graduate"></i></div>
                    <h4>Quản lý Sinh viên</h4>
                    <p>CRUD toàn bộ sinh viên, lọc theo khoa/lớp, xuất dữ liệu.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /admin/students.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-green"><i class="fas fa-chalkboard-user"></i></div>
                    <h4>Quản lý Giảng viên</h4>
                    <p>Tạo tài khoản GV mới, phân công khoa/môn, chỉnh sửa thông tin.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /admin/giangvien.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-amber"><i class="fas fa-graduation-cap"></i></div>
                    <h4>Quản lý Lớp</h4>
                    <p>Tạo lớp mới, phân GVCN, quản lý thời khóa biểu.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /admin/lop.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-purple"><i class="fas fa-book"></i></div>
                    <h4>Quản lý Môn học</h4>
                    <p>Thêm/sửa/xóa môn học, quản lý mã môn và số tín chỉ.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /admin/monhoc.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-red"><i class="fas fa-user-lock"></i></div>
                    <h4>Phân quyền</h4>
                    <p>Nâng/hạ quyền: student ↔ teacher ↔ admin. Khóa/mở khóa tài khoản.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /admin/phanquyen.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-indigo"><i class="fas fa-clock-rotate-left"></i></div>
                    <h4>Nhật ký Hệ thống</h4>
                    <p>Toàn bộ log hoạt động, lọc theo user/hành động/IP/thời gian.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /admin/nhatky.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-cyan"><i class="fas fa-database"></i></div>
                    <h4>Quản lý Backup</h4>
                    <p>Sao lưu database, tải file backup (.sql), khôi phục dữ liệu.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /admin/backup.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-orange"><i class="fas fa-gears"></i></div>
                    <h4>Cài đặt Hệ thống</h4>
                    <p>Cấu hình tên trường, logo, thông báo, TKB mẫu, tùy chọn hệ thống.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /admin/caidat.php</div>
                </div>
                <div class="doc-feature-card">
                    <div class="fc-icon fc-teal"><i class="fas fa-id-card"></i></div>
                    <h4>Hồ sơ Admin</h4>
                    <p>Chỉnh sửa thông tin cá nhân, đổi mật khẩu admin.</p>
                    <div class="doc-path" style="margin-top:10px;"><i class="fas fa-link"></i> /admin/profile.php</div>
                </div>
            </div>
        </section>

        <!-- =========== 9. API =========== -->
        <section class="doc-section" id="sec-api">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-plug"></i></div>
                <h2>API & <em>Tích Hợp</em></h2>
            </div>

            <p style="font-size:14px;color:var(--doc-ink-mid);margin-bottom:20px;line-height:1.7;">
                Hệ thống cung cấp các API endpoint tại thư mục <code>/tkb/api/</code>:
            </p>

            <div class="doc-table-wrap">
                <table class="doc-table">
                    <thead><tr><th>API</th><th>Endpoint</th><th>Mô tả</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Đăng nhập</strong></td><td><code>/api/login.php</code></td><td>Xác thực người dùng</td></tr>
                        <tr><td><strong>Đăng xuất</strong></td><td><code>/api/logout.php</code></td><td>Hủy phiên đăng nhập</td></tr>
                        <tr><td><strong>Face Login</strong></td><td><code>/api/face_login.php</code></td><td>Đăng nhập nhận diện khuôn mặt</td></tr>
                        <tr><td><strong>TKB công khai</strong></td><td><code>/api/get_public_tkb.php</code></td><td>Lấy thời khóa biểu công khai</td></tr>
                        <tr><td><strong>Thông tin SV</strong></td><td><code>/api/get_student_info.php</code></td><td>Lấy thông tin sinh viên</td></tr>
                        <tr><td><strong>Chạy code</strong></td><td><code>/api/run_code.php</code></td><td>Thực thi code (Code IDE)</td></tr>
                        <tr><td><strong>Lưu điểm</strong></td><td><code>/api/save_grade.php</code></td><td>Lưu điểm sinh viên</td></tr>
                        <tr><td><strong>Nộp bài TH</strong></td><td><code>/api/submit_practice.php</code></td><td>Nộp bài thực hành</td></tr>
                        <tr><td><strong>Upload banner</strong></td><td><code>/api/upload_banner.php</code></td><td>Tải lên banner tùy chỉnh</td></tr>
                        <tr><td><strong>Upload file</strong></td><td><code>/api/upload_binary.php</code></td><td>Tải lên file nhị phân</td></tr>
                        <tr><td><strong>Bài thi TH</strong></td><td><code>/api/get_practice_exam.php</code></td><td>Lấy đề thi thực hành</td></tr>
                        <tr><td><strong>Deploy web</strong></td><td><code>/api/deploy_web.php</code></td><td>Triển khai website</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="doc-subsection" style="margin-top:20px;">
                <h3><span class="sub-icon"><i class="fas fa-robot"></i></span> Module AI (Python)</h3>
                <ul>
                    <li><strong>File:</strong> <code>app.py</code> — Flask server chạy AI hỗ trợ</li>
                    <li><strong>Database:</strong> <code>quiz_vhcm.db</code> (SQLite) — Dữ liệu quiz cho AI</li>
                    <li><strong>Khởi chạy:</strong> <code>python app.py</code></li>
                </ul>
            </div>
        </section>

        <!-- =========== 10. BẢO MẬT =========== -->
        <section class="doc-section" id="sec-security">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-lock"></i></div>
                <h2>Bảo Mật <em>Hệ Thống</em></h2>
            </div>

            <div class="security-item">
                <div class="sec-icon"><i class="fas fa-key"></i></div>
                <div>
                    <div class="sec-title">Mã hóa mật khẩu</div>
                    <div class="sec-desc">Sử dụng <code>password_hash()</code> với thuật toán <code>PASSWORD_DEFAULT</code> (bcrypt)</div>
                </div>
            </div>
            <div class="security-item">
                <div class="sec-icon"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <div class="sec-title">Chống tấn công DDoS</div>
                    <div class="sec-desc">Module <code>anti_ddos.php</code> giới hạn request và bảo vệ server</div>
                </div>
            </div>
            <div class="security-item">
                <div class="sec-icon"><i class="fas fa-user-lock"></i></div>
                <div>
                    <div class="sec-title">Phân quyền chặt chẽ</div>
                    <div class="sec-desc">Kiểm tra quyền truy cập trên mọi trang: <code>requireLogin()</code>, <code>requireAdmin()</code>, <code>requireTeacher()</code>, <code>requireStudent()</code></div>
                </div>
            </div>
            <div class="security-item">
                <div class="sec-icon"><i class="fas fa-cookie"></i></div>
                <div>
                    <div class="sec-title">Session an toàn</div>
                    <div class="sec-desc">Cookie httponly, SameSite=Lax, đường dẫn giới hạn <code>/tkb</code></div>
                </div>
            </div>
            <div class="security-item">
                <div class="sec-icon"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <div class="sec-title">Ghi nhật ký hệ thống</div>
                    <div class="sec-desc">Log mọi hành động quan trọng với thông tin IP, user, thời gian</div>
                </div>
            </div>
            <div class="security-item">
                <div class="sec-icon"><i class="fas fa-face-smile"></i></div>
                <div>
                    <div class="sec-title">Đăng nhập khuôn mặt</div>
                    <div class="sec-desc">Hỗ trợ xác thực bằng Face Recognition qua API <code>/api/face_login.php</code></div>
                </div>
            </div>
        </section>

        <!-- =========== 11. XỬ LÝ SỰ CỐ =========== -->
        <section class="doc-section" id="sec-troubleshoot">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-wrench"></i></div>
                <h2>Xử Lý <em>Sự Cố</em></h2>
            </div>

            <div class="doc-trouble" onclick="this.classList.toggle('open')">
                <div class="doc-trouble-header">
                    <div class="trouble-icon"><i class="fas fa-times"></i></div>
                    <span>Không thể đăng nhập</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="doc-trouble-body">
                    <ul>
                        <li>Kiểm tra tên đăng nhập và mật khẩu (phân biệt hoa/thường)</li>
                        <li>Xóa cache trình duyệt và thử lại</li>
                        <li>Sử dụng chức năng "Quên mật khẩu"</li>
                        <li>Liên hệ Admin nếu tài khoản bị khóa</li>
                    </ul>
                </div>
            </div>

            <div class="doc-trouble" onclick="this.classList.toggle('open')">
                <div class="doc-trouble-header">
                    <div class="trouble-icon"><i class="fas fa-times"></i></div>
                    <span>Trang trắng hoặc lỗi 500</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="doc-trouble-body">
                    <ul>
                        <li>Kiểm tra Apache và MySQL đang chạy trong XAMPP Control Panel</li>
                        <li>Kiểm tra file <code>config.php</code> có đúng thông tin database</li>
                        <li>Xem error log tại: <code>C:\xampp\apache\logs\error.log</code></li>
                    </ul>
                </div>
            </div>

            <div class="doc-trouble" onclick="this.classList.toggle('open')">
                <div class="doc-trouble-header">
                    <div class="trouble-icon"><i class="fas fa-times"></i></div>
                    <span>Không kết nối được Database</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="doc-trouble-body">
                    <ul>
                        <li>Đảm bảo MySQL đang chạy trong XAMPP</li>
                        <li>Kiểm tra database <code>truong_caodang</code> đã được tạo</li>
                        <li>Kiểm tra lại thông tin kết nối trong <code>config.php</code></li>
                    </ul>
                </div>
            </div>

            <div class="doc-trouble" onclick="this.classList.toggle('open')">
                <div class="doc-trouble-header">
                    <div class="trouble-icon"><i class="fas fa-times"></i></div>
                    <span>Hình ảnh / Avatar không hiển thị</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="doc-trouble-body">
                    <ul>
                        <li>Kiểm tra thư mục <code>assets/img/avatars/</code> có quyền ghi</li>
                        <li>Đảm bảo file ảnh tồn tại và đúng định dạng (jpg, png, gif, webp)</li>
                        <li>Kiểm tra đường dẫn ảnh trong database</li>
                    </ul>
                </div>
            </div>

            <div class="doc-trouble" onclick="this.classList.toggle('open')">
                <div class="doc-trouble-header">
                    <div class="trouble-icon"><i class="fas fa-times"></i></div>
                    <span>Code IDE không chạy được</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="doc-trouble-body">
                    <ul>
                        <li>Kiểm tra API <code>/api/run_code.php</code> hoạt động</li>
                        <li>Đảm bảo server có cài compiler/interpreter tương ứng</li>
                        <li>Kiểm tra quyền thực thi trên server</li>
                    </ul>
                </div>
            </div>

            <div class="doc-trouble" onclick="this.classList.toggle('open')">
                <div class="doc-trouble-header">
                    <div class="trouble-icon"><i class="fas fa-times"></i></div>
                    <span>Module AI không hoạt động</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="doc-trouble-body">
                    <ul>
                        <li>Kiểm tra Python 3.x đã cài đặt</li>
                        <li>Cài đặt dependencies: <code>pip install flask</code></li>
                        <li>Chạy <code>python app.py</code> và kiểm tra port</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- =========== 12. LIÊN HỆ =========== -->
        <section class="doc-section" id="sec-contact">
            <div class="doc-section-header">
                <div class="doc-section-icon"><i class="fas fa-phone"></i></div>
                <h2>Liên Hệ <em>Hỗ Trợ</em></h2>
            </div>

            <div class="doc-subsection">
                <div class="doc-table-wrap">
                    <table class="doc-table">
                        <tbody>
                            <tr><td style="width:40%;"><strong>🏫 Trường</strong></td><td>Trường Cao Đẳng Cà Mau</td></tr>
                            <tr><td><strong>👨‍💻 Phát triển bởi</strong></td><td>Lê Nhựt Khánh</td></tr>
                            <tr><td><strong>🌐 Website</strong></td><td><a href="/tkb/lien_he.php" style="color:var(--doc-primary);font-weight:600;">Trang Liên hệ</a></td></tr>
                            <tr><td><strong>📌 Phiên bản</strong></td><td>1.0 — Cập nhật 29/07/2026</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="doc-alert doc-alert-success">
                <div class="doc-alert-icon"><i class="fas fa-heart"></i></div>
                <div>Cảm ơn bạn đã sử dụng hệ thống! Nếu gặp vấn đề, vui lòng liên hệ qua <a href="/tkb/lien_he.php" style="color:inherit;font-weight:700;">trang Liên hệ</a> hoặc liên hệ trực tiếp với đội ngũ phát triển.</div>
            </div>
        </section>

    </main>
</div>

<!-- Back to Top Button -->
<button class="doc-back-top" id="backTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// ======= Back to Top =======
window.addEventListener('scroll', function() {
    const btn = document.getElementById('backTopBtn');
    if (window.scrollY > 400) {
        btn.classList.add('visible');
    } else {
        btn.classList.remove('visible');
    }
});

// ======= Active TOC on Scroll =======
const tocLinks = document.querySelectorAll('#desktopToc a');
const sections = document.querySelectorAll('.doc-section');

function updateActiveToc() {
    let currentSection = '';
    sections.forEach(section => {
        const rect = section.getBoundingClientRect();
        if (rect.top <= 150) {
            currentSection = section.id;
        }
    });
    tocLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + currentSection) {
            link.classList.add('active');
        }
    });
}

window.addEventListener('scroll', updateActiveToc);

// ======= Smooth Scroll for TOC links =======
document.querySelectorAll('.doc-toc a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            closeTocDrawer();
        }
    });
});

// ======= Mobile TOC Drawer =======
function openTocDrawer() {
    document.getElementById('tocDrawer').classList.add('open');
    document.getElementById('tocOverlay').classList.add('open');
}

function closeTocDrawer() {
    document.getElementById('tocDrawer').classList.remove('open');
    document.getElementById('tocOverlay').classList.remove('open');
}

// ======= Clone TOC for Mobile =======
document.addEventListener('DOMContentLoaded', function() {
    const desktopToc = document.getElementById('desktopToc');
    const mobileToc = document.getElementById('mobileToc');
    mobileToc.innerHTML = desktopToc.innerHTML;

    // Re-attach click events for mobile toc links
    mobileToc.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                closeTocDrawer();
            }
        });
    });
});
</script>

<?php require_once 'includes/public_footer.php'; ?>
