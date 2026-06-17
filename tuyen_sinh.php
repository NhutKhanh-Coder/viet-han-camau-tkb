<?php require_once 'includes/public_header.php'; ?>

<!-- Glowing Background Orbs -->
<div class="glowing-orb orb-1"></div>
<div class="glowing-orb orb-2"></div>
<div class="glowing-orb orb-3"></div>

<!-- ===== TUYỂN SINH ===== -->
<section id="tuyen-sinh" class="section bg-alt" style="position: relative; overflow: hidden; background: transparent;">
    <div class="container" style="position: relative; z-index: 2;">
        <div class="admission-hero" style="margin-bottom: 80px;">
            <div>
                <div class="adm-tag" style="background: rgba(0,210,196,0.12); border-color: rgba(0,210,196,0.25); color: var(--teal);"><i class="fas fa-calendar-alt"></i> Tuyển sinh 2026</div>
                <h2 class="adm-title">Hành Trình Kiến Tạo Tri Thức</h2>
                <p class="adm-desc">Gia nhập cộng đồng học thuật Trường Cao Đẳng Nghề Việt Nam - Hàn Quốc Cà Mau. Chúng tôi tìm kiếm những cá nhân có khao khát làm chủ kỹ thuật và tư duy đổi mới để cùng kiến tạo tương lai.</p>
            </div>
        </div>

        <div class="adm-cards" style="margin-bottom: 80px;">
            <div class="adm-card accent-red" style="background: rgba(255, 255, 255, 0.72); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.45); border-top: 5px solid var(--crimson);">
                <div class="adm-card-icon"><i class="fas fa-clipboard-check"></i></div>
                <h3>Hình Thức Xét Tuyển</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Xét tuyển học bạ THPT/THCS (kết quả điểm trung bình lớp 12)</li>
                    <li><i class="fas fa-check-circle"></i> Xét tuyển dựa trên kết quả kỳ thi THPT Quốc gia năm 2026</li>
                    <li><i class="fas fa-check-circle"></i> Xét tuyển kết quả kỳ thi Đánh giá năng lực của ĐHQG</li>
                    <li><i class="fas fa-check-circle"></i> Tuyển thẳng học sinh giỏi cấp Tỉnh/Quốc gia và diện chính sách</li>
                </ul>
            </div>
            
            <div class="adm-card accent-gold" style="background: rgba(255, 255, 255, 0.72); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.45); border-top: 5px solid var(--gold);">
                <div class="adm-card-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3>Cơ Hội Học Tập & Học Bổng</h3>
                <ul>
                    <li><i class="fas fa-star"></i> <strong>Học bổng Thủ khoa:</strong> Tặng 100% học phí trọn gói năm học đầu tiên.</li>
                    <li><i class="fas fa-medal"></i> <strong>Quỹ khuyến học:</strong> Hỗ trợ tài chính toàn diện cho học viên nghèo vượt khó.</li>
                    <li><i class="fas fa-plane"></i> <strong>Cơ hội quốc tế:</strong> Chương trình liên kết trao đổi học tập tại Hàn Quốc.</li>
                    <li><i class="fas fa-briefcase"></i> <strong>Cam kết đầu ra:</strong> Đảm bảo 100% giới thiệu việc làm sau tốt nghiệp.</li>
                </ul>
            </div>
        </div>

        <!-- NEW: Dynamic Tuition Fees Calculator -->
        <div class="tuition-calculator-section" style="margin-bottom: 80px;">
            <div class="section-header centered">
                <div class="section-tag"><i class="fas fa-calculator"></i> Công cụ</div>
                <h2 class="section-title">Ước Tính <em>Học Phí</em> Tạm Tính</h2>
                <div class="divider-line center"></div>
                <p class="section-desc">Công cụ hỗ trợ học sinh và phụ huynh ước tính nhanh mức học phí theo từng học kỳ.</p>
            </div>
            
            <div class="calc-container">
                <h3 style="font-family:'Playfair Display',serif; font-size: 20px; font-weight: 850; color: var(--ink); text-align: center; margin-bottom: 20px;">Tính Học Phí Theo Chuyên Ngành</h3>
                <div class="calc-grid">
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="font-size:11.5px; font-weight:700; color:var(--ink-muted); text-transform:uppercase;">Chọn Ngành Học</label>
                        <select id="calcMajor" class="tkb-select" onchange="calculateTuition()">
                            <option value="cntt" data-fee="3600000">Công Nghệ Thông Tin</option>
                            <option value="oto" data-fee="3800000">Cơ Khí Ô Tô</option>
                            <option value="dien" data-fee="3400000">Điện - Điện Tử</option>
                            <option value="qtn" data-fee="3200000">Quản Trị Doanh Nghiệp</option>
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="font-size:11.5px; font-weight:700; color:var(--ink-muted); text-transform:uppercase;">Chính Sách Miễn Giảm</label>
                        <select id="calcDiscount" class="tkb-select" onchange="calculateTuition()">
                            <option value="none" data-rate="0">Không có (Đóng 100%)</option>
                            <option value="policy" data-rate="0.7">Gia đình chính sách / Hộ nghèo (Giảm 70%)</option>
                            <option value="vocational" data-rate="0.5">Tốt nghiệp THCS học nghề (Giảm 50%)</option>
                        </select>
                    </div>
                    <div class="calc-result-box">
                        <span style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">Học Phí Học Kỳ Tạm Tính</span>
                        <div class="calc-amount" id="tuitionResult">3.600.000 VNĐ</div>
                        <span style="font-size:11px; color:#64748b; margin-top:6px; display:block;">(Chưa bao gồm các khoản hỗ trợ bảo hiểm, ký túc xá)</span>
                    </div>
                </div>

                <!-- New: Dynamic Career Path & Salary Estimator -->
                <div class="career-estimator">
                    <h3 style="font-family:'Playfair Display',serif; font-size: 20px; font-weight: 850; color: var(--ink); text-align: center; margin-bottom: 15px;">Ước Tính Lộ Trình Lương & Sự Nghiệp</h3>
                    <div class="estimator-slider-wrap">
                        <label style="font-size:11.5px; font-weight:700; color:var(--ink-muted); text-transform:uppercase;">Số Năm Kinh Kinh Nghiệm Sau Tốt Nghiệp</label>
                        <input type="range" id="experienceSlider" class="slider-input" min="0" max="5" value="1" oninput="updateEstimate()">
                        <div class="estimator-labels">
                            <span>0 năm (Mới TN)</span>
                            <span>1 Năm</span>
                            <span>2 Năm</span>
                            <span>3 Năm</span>
                            <span>4 Năm</span>
                            <span>5+ Năm</span>
                        </div>
                    </div>
                    <div class="estimate-results">
                        <div class="estimate-title">Vị trí & Mức lương mô phỏng</div>
                        <div class="estimate-role" id="estRole">Lập trình viên Junior</div>
                        <div class="estimate-salary" id="estSalary">8.000.000 VNĐ - 12.000.000 VNĐ</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="admission-profile">
            <div class="section-header centered">
                <div class="section-tag"><i class="fas fa-folder-open"></i> Thủ tục</div>
                <h2 class="section-title">Hồ Sơ Nhập Học <em>Khóa 2026</em></h2>
                <div class="divider-line center"></div>
                <p class="section-desc">Chuẩn bị đầy đủ các giấy tờ cần thiết để chính thức trở thành tân sinh viên của nhà trường.</p>
            </div>
            
            <div class="steps-grid">
                <div class="step-card" style="background: rgba(255, 255, 255, 0.72); border: 1px solid rgba(255,255,255,0.45);">
                    <div class="step-num">1</div>
                    <h4>Phiếu Đăng Ký</h4>
                    <p>Phiếu đăng ký xét tuyển theo mẫu chuẩn của trường (tải từ phần Tài liệu hoặc đăng ký trực tuyến).</p>
                </div>
                <div class="step-card" style="background: rgba(255, 255, 255, 0.72); border: 1px solid rgba(255,255,255,0.45);">
                    <div class="step-num">2</div>
                    <h4>Bản Sao Học Bạ</h4>
                    <p>Bản sao công chứng học bạ cấp THPT (hoặc THCS đối với học viên đăng ký hệ đào tạo 9+).</p>
                </div>
                <div class="step-card" style="background: rgba(255, 255, 255, 0.72); border: 1px solid rgba(255,255,255,0.45);">
                    <div class="step-num">3</div>
                    <h4>Bằng Tốt Nghiệp</h4>
                    <p>Bản sao công chứng bằng tốt nghiệp hoặc giấy chứng nhận tốt nghiệp tạm thời năm 2026.</p>
                </div>
                <div class="step-card" style="background: rgba(255, 255, 255, 0.72); border: 1px solid rgba(255,255,255,0.45);">
                    <div class="step-num">4</div>
                    <h4>Giấy Tờ Cá Nhân</h4>
                    <p>Bản sao CMND/CCCD, giấy khai sinh hợp lệ và 4 ảnh chân dung kích thước 3x4 (chụp không quá 6 tháng).</p>
                </div>
            </div>
            
            <div class="adm-cta">
                <a href="/tkb/login.php" class="btn-primary" style="padding:18px 50px; font-size:16px;"><i class="fas fa-paper-plane"></i> Nộp hồ sơ trực tuyến ngay</a>
            </div>
        </div>
    </div>
</section>

<!-- Tuition Fee Estimator Script -->
<script>
function calculateTuition() {
    const majorSelect = document.getElementById('calcMajor');
    const discountSelect = document.getElementById('calcDiscount');
    
    const baseFee = parseFloat(majorSelect.options[majorSelect.selectedIndex].getAttribute('data-fee'));
    const discountRate = parseFloat(discountSelect.options[discountSelect.selectedIndex].getAttribute('data-rate'));
    
    const finalFee = baseFee * (1 - discountRate);
    
    // Format currency
    const formatter = new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    });
    
    document.getElementById('tuitionResult').textContent = formatter.format(finalFee);
    
    // Auto update estimate when major changes
    updateEstimate();
}

const careerData = {
    cntt: [
        { role: "Thực tập sinh Lập trình", range: "4.000.000 VNĐ - 6.000.000 VNĐ" },
        { role: "Lập trình viên Junior Web/Mobile", range: "8.000.000 VNĐ - 12.000.000 VNĐ" },
        { role: "Lập trình viên Middle Developer", range: "12.000.000 VNĐ - 18.000.000 VNĐ" },
        { role: "Lập trình viên Senior / Team Leader", range: "18.000.000 VNĐ - 28.000.000 VNĐ" },
        { role: "Kỹ sư giải pháp / Solution Architect", range: "28.000.000 VNĐ - 38.000.000 VNĐ" },
        { role: "Quản lý dự án phần mềm (PM)", range: "38.000.000 VNĐ - 55.000.000 VNĐ" }
    ],
    oto: [
        { role: "Học việc bảo dưỡng xưởng xe", range: "3.500.000 VNĐ - 5.000.000 VNĐ" },
        { role: "Kỹ thuật viên Sơ cấp sửa chữa", range: "7.000.000 VNĐ - 10.000.000 VNĐ" },
        { role: "Kỹ thuật viên Trung cấp sửa chữa gầm máy", range: "10.000.000 VNĐ - 15.000.000 VNĐ" },
        { role: "Kỹ thuật viên Chẩn đoán điện ô tô nâng cao", range: "15.000.000 VNĐ - 22.000.000 VNĐ" },
        { role: "Cố vấn dịch vụ kỹ thuật", range: "20.000.000 VNĐ - 30.000.000 VNĐ" },
        { role: "Quản lý xưởng ô tô / Service Manager", range: "30.000.000 VNĐ - 45.000.000 VNĐ" }
    ],
    dien: [
        { role: "Học việc lắp ráp tủ điện", range: "3.500.000 VNĐ - 5.000.000 VNĐ" },
        { role: "Kỹ thuật viên vận hành bảo trì cơ bản", range: "6.500.000 VNĐ - 9.000.000 VNĐ" },
        { role: "Kỹ thuật viên lập trình hệ thống PLC", range: "9.000.000 VNĐ - 14.000.000 VNĐ" },
        { role: "Kỹ sư giám sát tự động hóa công nghiệp", range: "14.000.000 VNĐ - 20.000.000 VNĐ" },
        { role: "Chuyên gia tích hợp hệ thống SCADA/HMI", range: "20.000.000 VNĐ - 30.000.000 VNĐ" },
        { role: "Trưởng bộ phận cơ điện nhà máy (Maintenance Manager)", range: "30.000.000 VNĐ - 42.000.000 VNĐ" }
    ],
    qtn: [
        { role: "Thực tập sinh Marketing / Sales", range: "3.000.000 VNĐ - 5.000.000 VNĐ" },
        { role: "Nhân viên phát triển kinh doanh", range: "6.000.000 VNĐ - 10.000.000 VNĐ" },
        { role: "Chuyên viên phân tích thị trường", range: "10.000.000 VNĐ - 15.000.000 VNĐ" },
        { role: "Trưởng nhóm kinh doanh (Sales Leader)", range: "15.000.000 VNĐ - 22.000.000 VNĐ" },
        { role: "Trưởng phòng Nhân sự / Marketing", range: "22.000.000 VNĐ - 32.000.000 VNĐ" },
        { role: "Giám đốc vận hành (COO) / Startup Founder", range: "32.000.000 VNĐ - 50.000.000 VNĐ" }
    ]
};

function updateEstimate() {
    const major = document.getElementById('calcMajor').value;
    const year = parseInt(document.getElementById('experienceSlider').value);
    
    const data = careerData[major] ? careerData[major][year] : null;
    if (data) {
        document.getElementById('estRole').textContent = data.role;
        document.getElementById('estSalary').textContent = data.range;
    }
}

// Initial estimation update
document.addEventListener('DOMContentLoaded', () => {
    updateEstimate();
});
</script>

<?php require_once 'includes/public_footer.php'; ?>
