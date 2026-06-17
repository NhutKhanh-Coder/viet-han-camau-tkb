<?php require_once 'includes/public_header.php'; ?>

<style>
/* =========================================================
   LIÊN HỆ PAGE – Indigo / Violet / Teal v2.0
   ========================================================= */

.lh-page {
    background: #f8fafc;
    min-height: 100vh;
    position: relative;
}

/* Page Hero Banner */
.lh-hero {
    background: #d91b43;
    padding: 90px 0 120px;
    position: relative;
    overflow: hidden;
}

.lh-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: none;
}

.lh-hero-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(217,27,67,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(217,27,67,0.04) 1px, transparent 1px);
    background-size: 50px 50px;
}

.lh-hero-inner {
    position: relative;
    z-index: 2;
    text-align: center;
}

.lh-hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 18px;
    background: rgba(217,27,67,0.15);
    border: 1px solid rgba(217,27,67,0.3);
    border-radius: 50px;
    color: #ff8fab;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-bottom: 24px;
}

.lh-hero-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(40px, 5vw, 60px);
    font-weight: 900;
    color: #f1f5f9;
    margin-bottom: 18px;
    letter-spacing: -1px;
    line-height: 1.2;
}

.lh-hero-title span {
    color: #ffffff;
}

.lh-hero-desc {
    font-size: 16px;
    color: #64748b;
    max-width: 560px;
    margin: 0 auto;
    line-height: 1.7;
}

/* Contact Info + Form Section */
.lh-main-section {
    padding: 0 0 100px;
    background: #f0f4ff;
    margin-top: -60px;
    position: relative;
    z-index: 2;
}

.lh-card-row {
    display: grid;
    grid-template-columns: 1fr 1.4fr;
    gap: 28px;
    align-items: start;
}

/* Left: Info Cards */
.lh-info-col { }

.lh-info-header {
    margin-bottom: 28px;
}
.lh-info-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 30px;
    font-weight: 900;
    color: #1e293b;
    margin-bottom: 10px;
}
.lh-info-header p {
    font-size: 14.5px;
    color: #64748b;
    line-height: 1.7;
}

.lh-contact-cards {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-bottom: 28px;
}

.lh-contact-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 20px;
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid rgba(217,27,67,0.1);
    box-shadow: 0 4px 16px rgba(217,27,67,0.06);
    transition: all 0.3s ease;
}

.lh-contact-card:hover {
    transform: translateX(6px);
    border-color: rgba(217,27,67,0.25);
    box-shadow: 0 8px 24px rgba(217,27,67,0.12);
}

.lh-cc-icon {
    width: 50px; height: 50px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.lh-ic-indigo { background: rgba(217,27,67,0.12); color: #d91b43; }
.lh-ic-violet { background: rgba(217,27,67,0.12); color: #d91b43; }
.lh-ic-teal   { background: rgba(217,27,67,0.12); color: #a8122e; }
.lh-ic-amber  { background: rgba(245,158,11,0.12); color: #d97706; }

.lh-cc-content strong {
    display: block;
    font-size: 13px;
    font-weight: 800;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.lh-cc-content span {
    font-size: 14px;
    color: #475569;
    font-weight: 500;
}

/* Social Links */
.lh-social-row {
    display: flex;
    gap: 10px;
}

.lh-social-btn {
    width: 46px; height: 46px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    text-decoration: none;
    transition: all 0.25s;
    border: 1px solid rgba(217,27,67,0.15);
}

.lh-sb-fb  { background: rgba(217,27,67,0.08); color: #d91b43; }
.lh-sb-yt  { background: rgba(239,68,68,0.1); color: #ef4444; }
.lh-sb-zl  { background: rgba(217,27,67,0.1); color: #a8122e; }
.lh-sb-em  { background: rgba(217,27,67,0.1); color: #d91b43; }

.lh-social-btn:hover { transform: translateY(-4px) scale(1.1); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }

/* Right: Registration Form */
.lh-form-card {
    background: #ffffff;
    border-radius: 28px;
    border: 1px solid rgba(217,27,67,0.1);
    box-shadow: 0 10px 40px rgba(217,27,67,0.08);
    overflow: hidden;
}

.lh-form-header {
    background: #d91b43;
    padding: 32px 36px;
    position: relative;
    overflow: hidden;
}

.lh-form-header::before {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 120px; height: 120px;
    background: none;
}

.lh-form-header h3 {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 900;
    color: #f1f5f9;
    margin-bottom: 6px;
    position: relative;
    z-index: 1;
}
.lh-form-header p {
    font-size: 13px;
    color: #94a3b8;
    position: relative;
    z-index: 1;
}

.lh-form-body { padding: 36px; }

.lh-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
.lh-form-grid .span-2 { grid-column: span 2; }

.lh-fgroup { display: flex; flex-direction: column; gap: 6px; }

.lh-flabel {
    font-size: 11px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.lh-finput,
.lh-fselect,
.lh-ftextarea {
    padding: 12px 16px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    font-family: 'Outfit', sans-serif;
    color: #1e293b;
    outline: none;
    transition: all 0.25s;
    width: 100%;
    box-sizing: border-box;
}

.lh-finput:focus,
.lh-fselect:focus,
.lh-ftextarea:focus {
    border-color: #d91b43;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(217,27,67,0.1);
}

.lh-fselect { cursor: pointer; }
.lh-ftextarea { resize: vertical; min-height: 100px; }

.lh-finput::placeholder,
.lh-ftextarea::placeholder { color: #94a3b8; }

.btn-lh-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 15px;
    background: #d91b43;
    color: #fff;
    border: none;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(217,27,67,0.35);
    font-family: 'Outfit', sans-serif;
    margin-top: 8px;
    position: relative;
    overflow: hidden;
}
.btn-lh-submit::before {
    content: '';
    position: absolute;
    inset: 0;
    background: #f43f6d;
    opacity: 0;
    transition: opacity 0.3s;
}
.btn-lh-submit:hover::before { opacity: 1; }
.btn-lh-submit:hover { transform: translateY(-2px); box-shadow: 0 14px 35px rgba(217,27,67,0.5); }
.btn-lh-submit > * { position: relative; z-index: 1; }

/* Success/Error alerts */
.lh-alert {
    padding: 14px 18px;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 600;
    margin-bottom: 18px;
    display: none;
    align-items: center;
    gap: 10px;
}
.lh-alert-ok { background: rgba(52,211,153,0.1); border: 1px solid rgba(52,211,153,0.3); color: #059669; display: none; }
.lh-alert-err { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #dc2626; display: none; }
.lh-alert.show { display: flex; }

/* Map Section */
.lh-map-section {
    padding: 0 0 100px;
    background: #f0f4ff;
}

.lh-map-wrapper {
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(217,27,67,0.1);
    border: 1px solid rgba(217,27,67,0.12);
    position: relative;
}

.lh-map-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 28px;
    box-shadow: inset 0 0 0 1px rgba(217,27,67,0.12);
    z-index: 1;
    pointer-events: none;
}

.lh-map-wrapper iframe {
    display: block;
    width: 100%;
    height: 450px;
    border: 0;
}

/* Quick Stats strip */
.lh-stat-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 40px;
}

.lh-stat-box {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid rgba(217,27,67,0.1);
    padding: 20px 18px;
    text-align: center;
    box-shadow: 0 4px 16px rgba(217,27,67,0.05);
    transition: all 0.3s ease;
}
.lh-stat-box:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(217,27,67,0.12); }
.lh-stat-num {
    font-family: 'Playfair Display', serif;
    font-size: 26px;
    font-weight: 900;
    color: #d91b43;
    line-height: 1;
    margin-bottom: 6px;
}
.lh-stat-lbl { font-size: 12px; color: #64748b; font-weight: 700; }

/* Responsive */
@media (max-width: 1024px) {
    .lh-card-row { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .lh-form-grid { grid-template-columns: 1fr; }
    .lh-form-grid .span-2 { grid-column: span 1; }
    .lh-form-body { padding: 24px; }
    .lh-form-header { padding: 24px; }
    .lh-stat-strip { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- Page Hero -->
<div class="lh-page">
    <div class="lh-hero">
        <div class="lh-hero-grid"></div>
        <div class="container lh-hero-inner">
            <div class="lh-hero-tag"><i class="fas fa-paper-plane"></i> Kết nối với chúng tôi</div>
            <h1 class="lh-hero-title">Liên Hệ & <span>Đăng Ký</span></h1>
            <p class="lh-hero-desc">Nhà trường luôn sẵn sàng lắng nghe và hỗ trợ bạn về chương trình học, học bổng và mọi dịch vụ sinh viên.</p>
        </div>
    </div>

    <!-- Main Contact Section -->
    <section class="lh-main-section">
        <div class="container" style="padding-top: 60px;">

            <!-- Quick Stats -->
            <div class="lh-stat-strip">
                <div class="lh-stat-box">
                    <div class="lh-stat-num">24/7</div>
                    <div class="lh-stat-lbl">Hỗ trợ trực tuyến</div>
                </div>
                <div class="lh-stat-box">
                    <div class="lh-stat-num">&lt;2h</div>
                    <div class="lh-stat-lbl">Thời gian phản hồi</div>
                </div>
                <div class="lh-stat-box">
                    <div class="lh-stat-num">98%</div>
                    <div class="lh-stat-lbl">Hài lòng dịch vụ</div>
                </div>
                <div class="lh-stat-box">
                    <div class="lh-stat-num">5★</div>
                    <div class="lh-stat-lbl">Đánh giá trung bình</div>
                </div>
            </div>

            <div class="lh-card-row">
                <!-- Left: Info Column -->
                <div class="lh-info-col">
                    <div class="lh-info-header">
                        <h2>Thông Tin Liên Hệ</h2>
                        <p>Mọi thắc mắc về tuyển sinh, học phí, học bổng và các hoạt động sinh viên – chúng tôi luôn ở đây.</p>
                    </div>

                    <div class="lh-contact-cards">
                        <div class="lh-contact-card">
                            <div class="lh-cc-icon lh-ic-indigo"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="lh-cc-content">
                                <strong>Địa Chỉ</strong>
                                <span>Số 08, đường Mậu Thân, Khóm 6, Phường 9, TP. Cà Mau</span>
                            </div>
                        </div>
                        <div class="lh-contact-card">
                            <div class="lh-cc-icon lh-ic-violet"><i class="fas fa-phone-alt"></i></div>
                            <div class="lh-cc-content">
                                <strong>Hotline Tuyển Sinh</strong>
                                <span>0290 3838 234 · 0290 3598 836</span>
                            </div>
                        </div>
                        <div class="lh-contact-card">
                            <div class="lh-cc-icon lh-ic-teal"><i class="fas fa-envelope"></i></div>
                            <div class="lh-cc-content">
                                <strong>Email</strong>
                                <span>tuyensinh@vkc.edu.vn</span>
                            </div>
                        </div>
                        <div class="lh-contact-card">
                            <div class="lh-cc-icon lh-ic-amber"><i class="fas fa-clock"></i></div>
                            <div class="lh-cc-content">
                                <strong>Giờ Làm Việc</strong>
                                <span>Thứ 2 – Thứ 6 · 07:30 – 17:00</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px; font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Theo dõi chúng tôi</div>
                    <div class="lh-social-row">
                        <a href="#" class="lh-social-btn lh-sb-fb" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="lh-social-btn lh-sb-yt" title="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="lh-social-btn lh-sb-zl" title="Zalo"><i class="fas fa-comment"></i></a>
                        <a href="mailto:tuyensinh@vkc.edu.vn" class="lh-social-btn lh-sb-em" title="Email"><i class="fas fa-envelope"></i></a>
                    </div>
                </div>

                <!-- Right: Registration Form -->
                <div class="lh-form-col">
                    <div class="lh-form-card">
                        <div class="lh-form-header">
                            <h3>📝 Đăng Ký Tuyển Sinh Online</h3>
                            <p>Điền thông tin để đội tư vấn liên hệ hỗ trợ bạn trong vòng 2 giờ</p>
                        </div>
                        <div class="lh-form-body">
                            <div class="lh-alert lh-alert-ok" id="lhAlertOk">
                                <i class="fas fa-check-circle"></i> Đăng ký thành công! Chúng tôi sẽ liên hệ với bạn sớm.
                            </div>
                            <div class="lh-alert lh-alert-err" id="lhAlertErr">
                                <i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra. Vui lòng thử lại.
                            </div>

                            <form action="/tkb/register.php" method="POST" id="lhForm" onsubmit="handleLhSubmit(event)">
                                <div class="lh-form-grid">
                                    <div class="lh-fgroup">
                                        <label class="lh-flabel">Tên đăng nhập</label>
                                        <input type="text" name="username" placeholder="vd: nguyenvana" required class="lh-finput">
                                    </div>
                                    <div class="lh-fgroup">
                                        <label class="lh-flabel">Mật khẩu</label>
                                        <input type="password" name="password" placeholder="Ít nhất 6 ký tự" required class="lh-finput">
                                    </div>
                                    <div class="lh-fgroup span-2">
                                        <label class="lh-flabel">Họ và tên đầy đủ</label>
                                        <input type="text" name="ho_ten" placeholder="Nhập họ và tên của bạn" required class="lh-finput">
                                    </div>
                                    <div class="lh-fgroup">
                                        <label class="lh-flabel">Email liên hệ</label>
                                        <input type="email" name="email" placeholder="example@email.com" required class="lh-finput">
                                    </div>
                                    <div class="lh-fgroup">
                                        <label class="lh-flabel">Số điện thoại</label>
                                        <input type="tel" name="sdt" placeholder="0xxx xxx xxx" required class="lh-finput">
                                    </div>
                                    <div class="lh-fgroup span-2">
                                        <label class="lh-flabel">Ngành học quan tâm</label>
                                        <select name="khoa" class="lh-fselect" required>
                                            <option value="">-- Chọn ngành học --</option>
                                            <option value="Công nghệ thông tin">🖥️ Công Nghệ Thông Tin</option>
                                            <option value="Cơ khí ô tô">🚗 Cơ Khí Ô Tô</option>
                                            <option value="Điện - Điện tử">⚡ Điện - Điện Tử</option>
                                            <option value="Quản trị doanh nghiệp">📊 Quản Trị Doanh Nghiệp</option>
                                        </select>
                                    </div>
                                    <div class="lh-fgroup span-2">
                                        <label class="lh-flabel">Tin nhắn / Câu hỏi (tùy chọn)</label>
                                        <textarea name="message" placeholder="Bạn có muốn hỏi gì không? Chúng tôi sẽ trả lời sớm nhất..." class="lh-ftextarea"></textarea>
                                    </div>
                                </div>

                                <button type="submit" class="btn-lh-submit" id="lhSubmitBtn">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>GỬI YÊU CẦU ĐĂNG KÝ</span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="lh-map-section">
        <div class="container">
            <div class="section-header centered sh-light" style="margin-bottom: 40px;">
                <div class="section-tag" style="background: rgba(217,27,67,0.1); border-color: rgba(217,27,67,0.2); color: #d91b43;"><i class="fas fa-map-marked-alt"></i> Bản đồ</div>
                <h2 class="section-title" style="color: #1e293b;">Vị Trí <em style="color: #d91b43;">Nhà Trường</em></h2>
                <div class="divider-line center" style="background: #d91b43;"></div>
            </div>
            <div class="lh-map-wrapper">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3935.1326466952756!2d105.1487843759325!3d9.18349289088279!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31a14eb3a33d5df7%3A0xe510f8a9e7019808!2zVHLGsOG7nW5nIENhbyDEkeG6s25nIG5naOG7gSBWaeG7h3QgTmFtIC0gSMOgbiBRdeG7kWMgQ8OgIE1hdQ!5e0!3m2!1svi!2s!4v1718150000000!5m2!1svi!2s"
                    allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>
</div>

<script>
function handleLhSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('lhSubmitBtn');
    const form = document.getElementById('lhForm');
    const okAlert = document.getElementById('lhAlertOk');
    const errAlert = document.getElementById('lhAlertErr');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Đang gửi...</span>';

    const fd = new FormData(form);

    fetch('/tkb/register.php', { method: 'POST', body: fd })
        .then(res => {
            okAlert.classList.add('show');
            okAlert.style.display = 'flex';
            form.reset();
        })
        .catch(() => {
            errAlert.classList.add('show');
            errAlert.style.display = 'flex';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i><span>GỬI YÊU CẦU ĐĂNG KÝ</span><i class="fas fa-arrow-right"></i>';
        });
}
</script>

<?php require_once 'includes/public_footer.php'; ?>
