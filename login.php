<?php
session_start();
require_once __DIR__ . '/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $dashboardUrl = getDashboardUrlByRole($_SESSION['role'] ?? '');
    header("Location: $dashboardUrl");
    exit;
}

require_once __DIR__ . '/includes/public_header.php';
?>

<style>
/* =========================================================
   LOGIN PAGE – Trắng & Đỏ Premium v3.0
   ========================================================= */

/* --- BIẾN MÀU ĐỎ-TRẮNG --- */
:root {
    --red-main:   #d91b43;
    --red-dark:   #a8122e;
    --red-light:  #f43f6d;
    --red-soft:   rgba(217, 27, 67, 0.08);
    --red-border: rgba(217, 27, 67, 0.2);
    --ink:        #1a1a2e;
    --ink-mid:    #475569;
    --ink-soft:   #94a3b8;
    --bg-white:   #ffffff;
    --bg-light:   #fdf4f6;
    --bg-gray:    #f8fafc;
}

.login-page-bg {
    min-height: 100vh;
    display: flex;
    align-items: stretch;
    background: var(--bg-gray);
    position: relative;
    overflow: hidden;
}

/* Hoa văn nền nhẹ */
.login-mesh {
    position: fixed;
    inset: 0;
    background: none;
    z-index: 0;
    pointer-events: none;
}

.login-grid-overlay {
    position: fixed;
    inset: 0;
    background: none;
    z-index: 0;
    pointer-events: none;
}

.login-inner {
    position: relative;
    z-index: 2;
    display: flex;
    width: 100%;
    min-height: 100vh;
}

/* ---- PANEL TRÁI ---- */
.lp-left {
    flex: 1.1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 60px 56px;
    border-right: 1px solid rgba(217,27,67,0.1);
    background: #fdf4f6;
    position: relative;
    overflow: hidden;
}

/* Dải đỏ bên trái */
.lp-left::before {
    content: '';
    position: absolute;
    top: 0; left: 0; bottom: 0;
    width: 5px;
    background: var(--red-main);
    border-radius: 0 4px 4px 0;
}

/* Vòng trang trí */
.lp-left::after {
    content: '';
    position: absolute;
    width: 380px; height: 380px;
    background: none;
    bottom: -80px; right: -80px;
    pointer-events: none;
}

.lp-brand {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 50px;
}

.lp-logo {
    width: 54px; height: 54px;
    background: #fff;
    border: 2px solid rgba(217,27,67,0.15);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(217,27,67,0.12);
}
.lp-logo img { width: 38px; height: 38px; object-fit: contain; }

.lp-brand-name {
    font-size: 13px;
    font-weight: 800;
    color: var(--ink);
    line-height: 1.3;
}
.lp-brand-name span { display: block; font-size: 11px; color: var(--ink-soft); font-weight: 600; }

.lp-hero-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.lp-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: var(--red-soft);
    border: 1px solid var(--red-border);
    border-radius: 50px;
    color: var(--red-main);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-bottom: 24px;
    width: fit-content;
}

.lp-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(32px, 3.2vw, 50px);
    font-weight: 900;
    line-height: 1.15;
    color: var(--ink);
    margin-bottom: 18px;
    letter-spacing: -0.5px;
}

.lp-title .lp-highlight {
    color: var(--red-main);
}

.lp-desc {
    font-size: 15px;
    color: var(--ink-mid);
    line-height: 1.75;
    margin-bottom: 36px;
    max-width: 420px;
}

.lp-features {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 36px;
}

.lp-feature {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    background: #ffffff;
    border: 1px solid rgba(217,27,67,0.1);
    border-radius: 16px;
    box-shadow: 0 2px 10px rgba(217,27,67,0.05);
    transition: all 0.3s ease;
}
.lp-feature:hover {
    background: var(--red-soft);
    border-color: var(--red-border);
    transform: translateX(5px);
    box-shadow: 0 6px 20px rgba(217,27,67,0.1);
}

.lp-feature-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}
.fi-indigo { background: rgba(217,27,67,0.1); color: var(--red-main); border: 1px solid rgba(217,27,67,0.2); }
.fi-violet { background: rgba(217,27,67,0.07); color: var(--red-dark); border: 1px solid rgba(217,27,67,0.15); }
.fi-teal   { background: rgba(217,27,67,0.05); color: var(--red-light); border: 1px solid rgba(217,27,67,0.12); }

.lp-feature-text strong { display: block; font-size: 14px; font-weight: 700; color: var(--ink); }
.lp-feature-text span { font-size: 12px; color: var(--ink-soft); }

/* Portal Tabs */
.lp-portal-section { margin-top: 36px; }

.lp-portal-tabs {
    display: flex;
    gap: 4px;
    padding: 4px;
    background: rgba(217,27,67,0.05);
    border-radius: 12px;
    margin-bottom: 14px;
    border: 1px solid rgba(217,27,67,0.1);
}

.lp-ptab-btn {
    flex: 1;
    padding: 8px 12px;
    border: none;
    background: transparent;
    color: var(--ink-soft);
    font-family: 'Outfit', sans-serif;
    font-size: 12px;
    font-weight: 700;
    border-radius: 9px;
    cursor: pointer;
    transition: all 0.25s;
}
.lp-ptab-btn.active {
    background: var(--red-main);
    color: #ffffff;
    box-shadow: 0 3px 10px rgba(217,27,67,0.35);
}

.lp-portal-content { display: none; }
.lp-portal-content.active { display: flex; flex-direction: column; gap: 10px; animation: lpTabFade 0.35s ease; }
@keyframes lpTabFade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

.lp-doc-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #ffffff;
    border: 1px solid rgba(217,27,67,0.08);
    border-radius: 12px;
    transition: 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.lp-doc-item:hover { background: var(--red-soft); border-color: var(--red-border); }
.lp-doc-info h4 { font-size: 12.5px; font-weight: 700; color: var(--ink); margin-bottom: 3px; }
.lp-doc-info p { font-size: 11px; color: var(--ink-soft); }
.lp-btn-dl {
    background: var(--red-soft);
    color: var(--red-main);
    border: 1px solid var(--red-border);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s;
    display: flex; align-items: center; gap: 5px;
    text-decoration: none;
    white-space: nowrap;
}
.lp-btn-dl:hover { background: var(--red-main); color: #fff; border-color: var(--red-main); }

.lp-lib-item {
    padding: 12px 16px;
    background: #ffffff;
    border: 1px solid rgba(217,27,67,0.1);
    border-left: 3px solid var(--red-main);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.lp-lib-item h4 { font-size: 12.5px; font-weight: 700; color: var(--red-dark); margin-bottom: 3px; }
.lp-lib-item p { font-size: 11px; color: var(--ink-soft); line-height: 1.5; }

.lp-event-item {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 12px 16px;
    background: #ffffff;
    border: 1px solid rgba(217,27,67,0.08);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.lp-event-date {
    background: var(--red-main);
    color: #fff;
    width: 46px; height: 46px;
    border-radius: 12px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    font-weight: 800; line-height: 1.2;
    flex-shrink: 0; font-size: 14px;
    box-shadow: 0 4px 12px rgba(217,27,67,0.3);
}
.lp-event-date span { font-size: 8px; text-transform: uppercase; font-weight: 700; }
.lp-event-info h4 { font-size: 12.5px; font-weight: 700; color: var(--ink); margin-bottom: 2px; }
.lp-event-info p { font-size: 11px; color: var(--ink-soft); }

/* ---- PANEL PHẢI (FORM) ---- */
.lp-right {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 60px 70px;
    background: #ffffff;
}

.lp-form-header {
    margin-bottom: 32px;
}

.lp-form-eyebrow {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--red-main);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lp-form-eyebrow::before {
    content: '';
    width: 24px; height: 2px;
    background: var(--red-main);
    border-radius: 2px;
}

.lp-form-title {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    font-weight: 900;
    color: var(--ink);
    margin-bottom: 6px;
    letter-spacing: -0.5px;
}

.lp-form-sub {
    font-size: 13.5px;
    color: var(--ink-soft);
    font-weight: 500;
}

/* Role switcher */
.lp-role-switcher {
    display: flex;
    gap: 4px;
    padding: 4px;
    background: var(--bg-gray);
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    margin-bottom: 28px;
}

.role-btn {
    flex: 1;
    padding: 11px 12px;
    border: none;
    background: transparent;
    color: var(--ink-soft);
    font-weight: 700;
    font-size: 13px;
    font-family: 'Outfit', sans-serif;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.role-btn.active {
    background: var(--red-main);
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(217,27,67,0.3);
}

/* Form elements */
.form-group { margin-bottom: 20px; }
.form-label-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.form-label {
    font-size: 11px;
    font-weight: 800;
    color: var(--ink-mid);
    text-transform: uppercase;
    letter-spacing: 1px;
}
.forgot-link {
    font-size: 12px;
    color: var(--red-main);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}
.forgot-link:hover { color: var(--red-dark); text-decoration: underline; }

.input-wrap { position: relative; }
.input-wrap > i {
    position: absolute;
    left: 16px; top: 50%;
    transform: translateY(-50%);
    color: var(--ink-soft);
    font-size: 14px;
    z-index: 1;
}
.form-input {
    width: 100%;
    padding: 13px 16px 13px 46px;
    background: var(--bg-gray);
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    font-family: 'Outfit', sans-serif;
    color: var(--ink);
    outline: none;
    transition: all 0.25s;
    box-sizing: border-box;
}
.form-input::placeholder { color: #cbd5e1; }
.form-input:focus {
    border-color: var(--red-main);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(217,27,67,0.1);
}

.toggle-pass {
    position: absolute;
    right: 14px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none;
    color: var(--ink-soft);
    cursor: pointer; padding: 4px;
    transition: color 0.2s;
}
.toggle-pass:hover { color: var(--ink); }

/* Info box */
.info-box {
    background: rgba(217,27,67,0.05);
    border: 1px solid rgba(217,27,67,0.18);
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 20px;
}
.info-box i { color: var(--red-main); font-size: 15px; }
.info-box p { font-size: 12px; color: var(--red-dark); margin: 0; line-height: 1.4; }

/* Remember me */
.remember-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
}
.remember-wrap input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: var(--red-main);
    cursor: pointer;
}
.remember-wrap label {
    font-size: 13px;
    color: var(--ink-mid);
    cursor: pointer;
    font-weight: 500;
}

/* Nút đăng nhập */
.btn-login {
    width: 100%;
    padding: 14px 24px;
    background: var(--red-main);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 8px 25px rgba(217,27,67,0.35);
    font-family: 'Outfit', sans-serif;
    position: relative;
    overflow: hidden;
    letter-spacing: 0.5px;
}
.btn-login::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--red-dark);
    opacity: 0;
    transition: opacity 0.3s;
}
.btn-login:hover:not(:disabled)::before { opacity: 1; }
.btn-login:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 14px 35px rgba(217,27,67,0.45); }
.btn-login:active { transform: translateY(0); }
.btn-login:disabled { background: #e2e8f0; box-shadow: none; cursor: not-allowed; color: var(--ink-soft); }
.btn-login > * { position: relative; z-index: 1; }

/* Face login btn */
.btn-face {
    width: 100%;
    padding: 12px;
    margin-top: 12px;
    background: #ffffff;
    border: 1.5px dashed rgba(217,27,67,0.25);
    border-radius: 12px;
    color: var(--ink-soft);
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-family: 'Outfit', sans-serif;
}
.btn-face:hover {
    background: var(--red-soft);
    border-color: var(--red-main);
    color: var(--red-main);
}

/* Bottom links */
.links-bottom {
    margin-top: 24px;
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.links-bottom a {
    color: var(--ink-soft);
    font-size: 13px;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}
.links-bottom a strong { color: var(--red-main); }
.links-bottom a:hover { color: var(--red-main); }

/* Alerts */
.alert {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 20px;
    display: none;
    font-weight: 500;
}
.alert-error   { background: rgba(220,38,38,0.07); border: 1px solid rgba(220,38,38,0.2); color: #b91c1c; }
.alert-success { background: rgba(22,163,74,0.07); border: 1px solid rgba(22,163,74,0.2); color: #15803d; }

/* Face modal */
.modal-overlay {
    display:none; position:fixed; inset:0;
    background: rgba(26,10,15,0.75);
    z-index:9999;
    align-items:center; justify-content:center;
    backdrop-filter:blur(10px);
}
.modal-overlay.show { display:flex; }
.modal-box {
    background: #fff;
    border: 1px solid rgba(217,27,67,0.15);
    border-radius: 28px;
    padding: 36px;
    width: 500px;
    max-width: 95vw;
    text-align: center;
    box-shadow: 0 30px 80px rgba(217,27,67,0.15);
}
.modal-title { font-family: 'Playfair Display', serif; font-size:22px; font-weight:800; color:var(--ink); margin-bottom:8px; }
.modal-sub { font-size:13.5px; color:var(--ink-soft); margin-bottom:24px; }
.video-container { position:relative; width:100%; aspect-ratio:4/3; background:#1a1a1a; border-radius:20px; overflow:hidden; margin-bottom:24px; border:2px solid rgba(217,27,67,0.15); }
#faceVideo { width:100%; height:100%; object-fit:cover; }
#faceCanvas { position:absolute; inset:0; width:100%; height:100%; }
.scan-line { position:absolute; top:0; left:0; right:0; height:3px; background:var(--red-main); animation:scan 2.5s linear infinite; display:none; }
.scan-line.active { display:block; }
@keyframes scan { 0%{top:0}100%{top:100%} }
.face-status { font-size:14px; color:var(--ink-soft); margin-bottom:20px; min-height:20px; font-weight:600; }
.face-status.detecting { color: #d97706; }
.face-status.found     { color: #16a34a; }
.face-status.error     { color: var(--red-main); }
.btn-cancel {
    width: 100%; padding:12px;
    background: var(--bg-gray);
    border: 1.5px solid #e2e8f0;
    border-radius:10px; color:var(--ink-mid);
    font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s;
    font-family: 'Outfit', sans-serif;
}
.btn-cancel:hover { background:rgba(217,27,67,0.06); border-color:var(--red-border); color:var(--red-main); }

/* Responsive */
@media (max-width: 1100px) {
    .lp-left { padding: 40px 40px; }
    .lp-right { padding: 40px 50px; }
}

@media (max-width: 900px) {
    .login-inner { flex-direction: column; }
    .lp-left { display: none; }
    .lp-right { min-height: 100vh; justify-content: center; padding: 40px 28px; }
}
</style>

<div class="login-page-bg">
    <div class="login-mesh"></div>
    <div class="login-grid-overlay"></div>

    <div class="login-inner">
        <!-- LEFT PANEL -->
        <div class="lp-left">
            <div class="lp-brand">
                <div class="lp-logo">
                    <img src="/tkb/assets/img/logo_vkc.jpg" alt="Logo" onerror="this.src='';this.parentElement.innerHTML='🛡️';this.parentElement.style.cssText='font-size:24px;display:flex;align-items:center;justify-content:center;'">
                </div>
                <div class="lp-brand-name">
                    Cao đẳng Nghề VN – HQ Cà Mau
                    <span>Cổng Thông Tin Học Tập</span>
                </div>
            </div>

            <div class="lp-hero-text">
                <div class="lp-tag"><i class="fas fa-shield-alt"></i> Hệ thống bảo mật cao</div>
                <h2 class="lp-title">
                    Cổng Thông Tin
                    <span class="lp-highlight">Học Tập</span>
                    Thông Minh
                </h2>
                <p class="lp-desc">Xem kết quả học tập, thời khóa biểu, tài chính, điểm danh và trao đổi tài liệu cùng giáo viên.</p>

                <div class="lp-features">
                    <div class="lp-feature">
                        <div class="lp-feature-icon fi-indigo"><i class="fas fa-calendar-alt"></i></div>
                        <div class="lp-feature-text">
                            <strong>Thời Khóa Biểu Trực Quan</strong>
                            <span>Xem lịch học theo tuần, tháng và xuất file</span>
                        </div>
                    </div>
                    <div class="lp-feature">
                        <div class="lp-feature-icon fi-violet"><i class="fas fa-chart-line"></i></div>
                        <div class="lp-feature-text">
                            <strong>Theo Dõi Kết Quả Học Tập</strong>
                            <span>Điểm số, điểm danh và tiến trình từng học kỳ</span>
                        </div>
                    </div>
                    <div class="lp-feature">
                        <div class="lp-feature-icon fi-teal"><i class="fas fa-robot"></i></div>
                        <div class="lp-feature-text">
                            <strong>Trợ Lý AI Hỗ Trợ 24/7</strong>
                            <span>Chat với AI để giải đáp thắc mắc tức thì</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Portal Quick Info -->
            <div class="lp-portal-section">
                <div class="lp-portal-tabs">
                    <button class="lp-ptab-btn active" onclick="switchPortalTab(event,'docs')">Tài Liệu</button>
                    <button class="lp-ptab-btn" onclick="switchPortalTab(event,'lib')">Thư Viện</button>
                    <button class="lp-ptab-btn" onclick="switchPortalTab(event,'events')">Sự Kiện</button>
                </div>

                <div class="lp-portal-content active" id="ptab-docs">
                    <div class="lp-doc-item">
                        <div class="lp-doc-info">
                            <h4><i class="fas fa-file-pdf"></i> Sổ Tay Sinh Viên Khóa 2026</h4>
                            <p>Cập nhật: 10/08/2026 · 2.4 MB</p>
                        </div>
                        <a href="#" class="lp-btn-dl"><i class="fas fa-download"></i> Tải</a>
                    </div>
                    <div class="lp-doc-item">
                        <div class="lp-doc-info">
                            <h4><i class="fas fa-file-word"></i> Mẫu Đơn Đăng Ký KTX</h4>
                            <p>Cập nhật: 05/08/2026 · 1.1 MB</p>
                        </div>
                        <a href="#" class="lp-btn-dl"><i class="fas fa-download"></i> Tải</a>
                    </div>
                </div>

                <div class="lp-portal-content" id="ptab-lib">
                    <div class="lp-lib-item">
                        <h4><i class="fas fa-book-reader"></i> Phòng Đọc Tự Chọn</h4>
                        <p>Mở cửa 24/7. Không gian yên tĩnh với hàng ngàn đầu sách.</p>
                    </div>
                    <div class="lp-lib-item">
                        <h4><i class="fas fa-laptop-code"></i> Thư Viện Số IEEE, ACM</h4>
                        <p>Truy cập miễn phí kho tài liệu điện tử quốc tế.</p>
                    </div>
                </div>

                <div class="lp-portal-content" id="ptab-events">
                    <div class="lp-event-item">
                        <div class="lp-event-date">25<span>Thg 9</span></div>
                        <div class="lp-event-info">
                            <h4>Ngày Hội Tân Sinh Viên</h4>
                            <p>Giao lưu văn nghệ, bốc thăm trúng thưởng.</p>
                        </div>
                    </div>
                    <div class="lp-event-item">
                        <div class="lp-event-date">02<span>Thg 10</span></div>
                        <div class="lp-event-info">
                            <h4>Hội Thảo AI & Tương Lai</h4>
                            <p>Chuyên gia công nghệ chia sẻ xu hướng AI.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="lp-right">
            <div class="lp-form-header">
                <div class="lp-form-eyebrow">Đăng nhập</div>
                <h2 class="lp-form-title">Chào Mừng
                    Trở Lại!</h2>
                <p class="lp-form-sub">Cổng hệ thống quản trị & đào tạo VKC</p>
            </div>

            <div class="lp-role-switcher" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 20px;">
                <button type="button" class="role-btn active" onclick="switchRole('student')" id="tabStudent" style="padding: 10px 6px; font-size: 11px;">
                    <i class="fa-solid fa-graduation-cap"></i> SINH VIÊN
                </button>
                <button type="button" class="role-btn" onclick="switchRole('teacher')" id="tabTeacher" style="padding: 10px 6px; font-size: 11px;">
                    <i class="fa-solid fa-chalkboard-user"></i> GIÁO VIÊN
                </button>
                <button type="button" class="role-btn" onclick="switchRole('principal')" id="tabPrincipal" style="padding: 10px 6px; font-size: 11px;">
                    <i class="fa-solid fa-user-tie"></i> HIỆU TRƯỞNG
                </button>
                <button type="button" class="role-btn" onclick="switchRole('admin')" id="tabAdmin" style="padding: 10px 6px; font-size: 11px;">
                    <i class="fa-solid fa-user-shield"></i> QUẢN TRỊ
                </button>
            </div>

            <div class="alert alert-error" id="alertError"></div>
            <div class="alert alert-success" id="alertSuccess"></div>

            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <div class="form-label-wrap">
                        <label class="form-label" id="labelUser">MÃ SINH VIÊN</label>
                    </div>
                    <div class="input-wrap">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" id="username" class="form-input" placeholder="Nhập mã sinh viên của bạn" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-label-wrap">
                        <label class="form-label">MẬT KHẨU</label>
                        <a href="/tkb/forgot_password.php" class="forgot-link" id="forgotLink">Quên mật khẩu?</a>
                    </div>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" class="form-input" placeholder="Nhập mật khẩu" required>
                        <button type="button" class="toggle-pass" onclick="togglePass()">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="info-box" id="infoBox">
                    <i class="fa-solid fa-circle-info"></i>
                    <p>Sinh viên: Mật khẩu mặc định là <strong>Ngày sinh (ddmmyyyy)</strong> của bạn.</p>
                </div>

                <div class="remember-wrap">
                    <input type="checkbox" id="remember">
                    <label for="remember">Ghi nhớ đăng nhập</label>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <i class="fa-solid fa-unlock" id="btnIcon"></i>
                    <span id="btnText">ĐĂNG NHẬP HỆ THỐNG</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <button class="btn-face" onclick="openFaceLogin()">
                <i class="fa-solid fa-camera"></i> Đăng nhập bằng khuôn mặt
            </button>

            <div class="links-bottom">
                <a href="/tkb/register.php">Chưa có tài khoản? <strong>Đăng ký ngay</strong></a>
                <a href="/tkb/index.php"><i class="fa-solid fa-arrow-left"></i> Quay lại trang chủ</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nhận diện khuôn mặt -->
<div class="modal-overlay" id="faceModal">
    <div class="modal-box">
        <h3 class="modal-title"><i class="fa-solid fa-face-viewfinder"></i> Nhận Diện Khuôn Mặt</h3>
        <p class="modal-sub">Hướng thẳng mặt vào camera và giữ yên</p>
        <div class="video-container">
            <video id="faceVideo" autoplay muted playsinline></video>
            <canvas id="faceCanvas"></canvas>
            <div class="scan-line" id="scanLine"></div>
        </div>
        <div class="face-status" id="faceStatus">Đang khởi động camera...</div>
        <button class="btn-cancel" onclick="closeFaceModal()"><i class="fa-solid fa-xmark"></i> Hủy & Đóng</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
// --- PORTAL TABS ---
function switchPortalTab(e, tabId) {
    document.querySelectorAll('.lp-ptab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.lp-portal-content').forEach(c => c.classList.remove('active'));
    e.currentTarget.classList.add('active');
    document.getElementById('ptab-' + tabId).classList.add('active');
}

// --- ROLE SWITCHER ---
var currentRole = 'student';
function switchRole(role) {
    currentRole = role;
    document.getElementById('tabStudent').classList.toggle('active', role === 'student');
    document.getElementById('tabTeacher').classList.toggle('active', role === 'teacher');
    document.getElementById('tabPrincipal').classList.toggle('active', role === 'principal');
    document.getElementById('tabAdmin').classList.toggle('active', role === 'admin');
    
    var infoBox = document.getElementById('infoBox');
    var labelUser = document.getElementById('labelUser');
    var inputUser = document.getElementById('username');

    if (role === 'student') {
        labelUser.textContent = 'MÃ SINH VIÊN';
        inputUser.placeholder = 'Nhập mã sinh viên của bạn';
        infoBox.style.display = 'flex';
    } else if (role === 'teacher') {
        labelUser.textContent = 'TÀI KHOẢN GIÁO VIÊN';
        inputUser.placeholder = 'Nhập mã giáo viên (ví dụ: gv001)';
        infoBox.style.display = 'none';
    } else if (role === 'principal') {
        labelUser.textContent = 'TÀI KHOẢN HIỆU TRƯỞNG';
        inputUser.placeholder = 'Nhập tài khoản hiệu trưởng';
        infoBox.style.display = 'none';
    } else {
        labelUser.textContent = 'TÀI KHOẢN QUẢN TRỊ';
        inputUser.placeholder = 'Nhập tên đăng nhập admin';
        infoBox.style.display = 'none';
    }
    clearAlert();
}

// --- TOGGLE PASSWORD ---
function togglePass() {
    var p = document.getElementById('password');
    var ic = document.getElementById('eyeIcon');
    if (p.type === 'password') {
        p.type = 'text';
        ic.className = 'fa-regular fa-eye-slash';
    } else {
        p.type = 'password';
        ic.className = 'fa-regular fa-eye';
    }
}

// --- ALERTS ---
function showAlert(type, msg) {
    clearAlert();
    var el = document.getElementById(type === 'error' ? 'alertError' : 'alertSuccess');
    el.textContent = msg;
    el.style.display = 'block';
}
function clearAlert() {
    document.getElementById('alertError').style.display = 'none';
    document.getElementById('alertSuccess').style.display = 'none';
}

// --- LOGIN HANDLER ---
async function handleLogin(e) {
    e.preventDefault();
    clearAlert();

    var btn = document.getElementById('btnLogin');
    var btnText = document.getElementById('btnText');
    var btnIcon = document.getElementById('btnIcon');

    btn.disabled = true;
    btnText.textContent = 'Đang xử lý...';
    btnIcon.className = 'fa-solid fa-spinner fa-spin';

    var username = document.getElementById('username').value;
    var password = document.getElementById('password').value;

    var fd = new FormData();
    fd.append('username', username);
    fd.append('password', password);
    fd.append('role', currentRole);
    if (document.getElementById('remember').checked) {
        fd.append('remember', '1');
    }

    try {
        var res = await fetch('/tkb/api/login.php', { method: 'POST', body: fd });
        var data = await res.json();
        if (data.success) {
            showAlert('success', 'Đăng nhập thành công! Đang chuyển hướng...');
            setTimeout(function() { window.location.href = data.redirect; }, 800);
        } else {
            showAlert('error', data.message || 'Đăng nhập thất bại!');
            btn.disabled = false;
            btnText.textContent = 'ĐĂNG NHẬP HỆ THỐNG';
            btnIcon.className = 'fa-solid fa-unlock';
        }
    } catch(err) {
        showAlert('error', 'Lỗi kết nối máy chủ!');
        btn.disabled = false;
        btnText.textContent = 'ĐĂNG NHẬP HỆ THỐNG';
        btnIcon.className = 'fa-solid fa-unlock';
    }
}

// --- FACE RECOGNITION LOGIN ---
var stream = null, faceInterval = null, modelsLoaded = false, detecting = false;

async function loadFaceModels() {
    if (modelsLoaded) return true;
    var status = document.getElementById('faceStatus');
    status.textContent = 'Đang tải mô hình AI...';
    status.className = 'face-status detecting';
    try {
        var MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        modelsLoaded = true;
        return true;
    } catch(e) {
        status.textContent = 'Lỗi tải mô hình AI: ' + e.message;
        status.className = 'face-status error';
        return false;
    }
}

async function openFaceLogin() {
    document.getElementById('faceModal').classList.add('show');
    var ok = await loadFaceModels();
    if (!ok) return;
    var status = document.getElementById('faceStatus');
    status.textContent = 'Đang mở camera...';
    status.className = 'face-status detecting';
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        var video = document.getElementById('faceVideo');
        video.srcObject = stream;
        await new Promise(r => video.onloadedmetadata = r);
        document.getElementById('scanLine').classList.add('active');
        startFaceDetection();
    } catch(e) {
        status.textContent = 'Không thể truy cập camera: ' + e.message;
        status.className = 'face-status error';
    }
}

function closeFaceModal() {
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    if (faceInterval) { clearInterval(faceInterval); faceInterval = null; }
    document.getElementById('scanLine').classList.remove('active');
    document.getElementById('faceModal').classList.remove('show');
    detecting = false;
}

async function startFaceDetection() {
    var video = document.getElementById('faceVideo');
    var canvas = document.getElementById('faceCanvas');
    var status = document.getElementById('faceStatus');
    var holdCount = 0;
    status.textContent = 'Nhìn thẳng vào camera...';
    status.className = 'face-status detecting';

    faceInterval = setInterval(async function() {
        if (detecting) return;
        detecting = true;

        var opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
        var detection = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        var ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (detection) {
            var dims = faceapi.matchDimensions(canvas, video, true);
            var resized = faceapi.resizeResults(detection, dims);
            faceapi.draw.drawDetections(canvas, resized);
            faceapi.draw.drawFaceLandmarks(canvas, resized);

            holdCount++;
            status.textContent = 'Đang nhận diện... (' + holdCount + '/3)';
            status.className = 'face-status found';

            if (holdCount >= 3) {
                clearInterval(faceInterval);
                status.textContent = 'Đang xác thực tài khoản...';
                await verifyFace(Array.from(detection.descriptor));
            }
        } else {
            holdCount = 0;
            status.textContent = 'Không thấy khuôn mặt. Hãy căn chỉnh góc độ...';
            status.className = 'face-status detecting';
        }
        detecting = false;
    }, 500);
}

async function verifyFace(descriptor) {
    var status = document.getElementById('faceStatus');
    try {
        var res = await fetch('/tkb/api/face_login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ descriptor: descriptor, role: currentRole })
        });
        var data = await res.json();
        if (data.success) {
            status.textContent = 'Xác thực thành công! Xin chào ' + data.name;
            status.className = 'face-status found';
            setTimeout(function() {
                closeFaceModal();
                showAlert('success', 'Đăng nhập thành công!');
                window.location.href = data.redirect;
            }, 800);
        } else {
            status.textContent = data.message || 'Không khớp khuôn mặt!';
            status.className = 'face-status error';
            setTimeout(startFaceDetection, 2000);
        }
    } catch(e) {
        status.textContent = 'Lỗi kết nối máy chủ!';
        status.className = 'face-status error';
        setTimeout(startFaceDetection, 2000);
    }
}
</script>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
