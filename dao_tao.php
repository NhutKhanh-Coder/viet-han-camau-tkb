<?php require_once 'includes/public_header.php'; ?>

<!-- Glowing Background Orbs -->
<div class="glowing-orb orb-1"></div>
<div class="glowing-orb orb-2"></div>
<div class="glowing-orb orb-3"></div>

<!-- ===== ĐÀO TẠO ===== -->
<section id="dao-tao" class="section" style="position: relative; overflow: hidden; background: transparent;">
    <div class="container" style="position: relative; z-index: 2;">
        <div class="section-header centered">
            <div class="section-tag"><i class="fas fa-graduation-cap"></i> Đào tạo</div>
            <h2 class="section-title">Chương Trình <em>Đào Tạo</em></h2>
            <div class="divider-line center"></div>
            <p class="section-desc">Hệ thống đào tạo bám sát thực tiễn công nghệ, bứt phá năng lực cạnh tranh toàn cầu.</p>
        </div>
        
        <!-- Interactive Majors Container -->
        <div class="majors-container">
            <!-- Sidebar Navigation -->
            <div class="majors-sidebar">
                <button class="major-nav-btn active" onclick="switchMajorTab(event, 'cntt')">
                    <span><i class="fas fa-laptop-code"></i> Công Nghệ Thông Tin</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="major-nav-btn" onclick="switchMajorTab(event, 'oto')">
                    <span><i class="fas fa-car-side"></i> Cơ Khí Ô Tô</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="major-nav-btn" onclick="switchMajorTab(event, 'dien')">
                    <span><i class="fas fa-bolt"></i> Điện - Điện Tử</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="major-nav-btn" onclick="switchMajorTab(event, 'qtn')">
                    <span><i class="fas fa-chart-line"></i> Quản Trị Doanh Nghiệp</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Detail Panels -->
            <div class="majors-content-panels">
                <!-- Panel 1: CNTT -->
                <div class="major-detail-panel active" id="panel-cntt">
                    <div class="major-detail-header">
                        <div class="major-detail-icon"><i class="fas fa-laptop-code"></i></div>
                        <div class="major-detail-title">
                            <h3>Ngành Công Nghệ Thông Tin</h3>
                            <span>Hệ Cao Đẳng Chính Quy · Mã ngành: 6480201</span>
                        </div>
                    </div>
                    <div class="major-detail-body" style="font-size: 15px; line-height: 1.7; color: #475569;">
                        <p style="margin-bottom: 20px;">Khoa Công Nghệ Thông Tin đào tạo chuyên sâu về phát triển phần mềm, xây dựng và quản trị hệ thống website, mạng máy tính và an toàn bảo mật. Sinh viên được tiếp cận các công nghệ lập trình hiện đại nhất như Javascript, PHP, Python và phát triển ứng dụng di động.</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div style="background: rgba(255,255,255,0.5); padding: 16px; border-radius: 12px; border:1px solid rgba(0,0,0,0.03);">
                                <strong style="color:var(--ink); display:block; margin-bottom:6px;"><i class="far fa-clock" style="color:var(--crimson);"></i> Thời gian đào tạo</strong>
                                2.5 - 3 năm (Học thực hành 70%)
                            </div>
                            <div style="background: rgba(255,255,255,0.5); padding: 16px; border-radius: 12px; border:1px solid rgba(0,0,0,0.03);">
                                <strong style="color:var(--ink); display:block; margin-bottom:6px;"><i class="fas fa-award" style="color:var(--gold);"></i> Bằng cấp tốt nghiệp</strong>
                                Kỹ sư thực hành công nghệ thông tin
                            </div>
                        </div>
                        <h4 style="font-family:'Playfair Display',serif; color:var(--ink); font-size:18px; margin-bottom:12px; font-weight:800;">Vị trí việc làm sau tốt nghiệp</h4>
                        <ul style="list-style:none; padding-left:0; display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:24px;">
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Lập trình viên Web/Mobile</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Chuyên viên quản trị mạng</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Kỹ thuật viên bảo trì máy tính</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Tester & Đảm bảo chất lượng</li>
                        </ul>

                        <h5 class="skills-matrix-title">Ma Trận Kỹ Năng Theo Học Kỳ</h5>
                        <div class="skills-matrix-grid">
                            <div class="skills-semester">
                                <h5>Học Kỳ 1</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">HTML5</span>
                                    <span class="skills-badge">CSS3 Layouts</span>
                                    <span class="skills-badge">Tin học cơ bản</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 2</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Javascript ES6</span>
                                    <span class="skills-badge">Cấu trúc dữ liệu</span>
                                    <span class="skills-badge">Cơ sở dữ liệu SQL</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 3</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Lập trình PHP thuần</span>
                                    <span class="skills-badge">AI Face API</span>
                                    <span class="skills-badge">Bootstrap & jQuery</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 4</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Framework Laravel</span>
                                    <span class="skills-badge">Đồ án Tốt Nghiệp</span>
                                    <span class="skills-badge">An toàn bảo mật</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel 2: Ô tô -->
                <div class="major-detail-panel" id="panel-oto">
                    <div class="major-detail-header">
                        <div class="major-detail-icon"><i class="fas fa-car-side"></i></div>
                        <div class="major-detail-title">
                            <h3>Ngành Công Nghệ Ô Tô</h3>
                            <span>Hệ Cao Đẳng Chính Quy · Mã ngành: 6510205</span>
                        </div>
                    </div>
                    <div class="major-detail-body" style="font-size: 15px; line-height: 1.7; color: #475569;">
                        <p style="margin-bottom: 20px;">Trang bị kiến thức lý thuyết và kỹ năng thực hành bảo dưỡng, chẩn đoán, sửa chữa các hệ thống động cơ, truyền lực, hệ thống điện - điện tử trên xe ô tô đời mới. Xưởng thực hành được đầu tư thiết bị kiểm định hiện đại đạt chuẩn Toyota Việt Nam.</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div style="background: rgba(255,255,255,0.5); padding: 16px; border-radius: 12px; border:1px solid rgba(0,0,0,0.03);">
                                <strong style="color:var(--ink); display:block; margin-bottom:6px;"><i class="far fa-clock" style="color:var(--crimson);"></i> Thời gian đào tạo</strong>
                                3 năm (Học tại xưởng Toyota 70%)
                            </div>
                            <div style="background: rgba(255,255,255,0.5); padding: 16px; border-radius: 12px; border:1px solid rgba(0,0,0,0.03);">
                                <strong style="color:var(--ink); display:block; margin-bottom:6px;"><i class="fas fa-award" style="color:var(--gold);"></i> Bằng cấp tốt nghiệp</strong>
                                Kỹ sư thực hành Công nghệ cơ khí Ô tô
                            </div>
                        </div>
                        <h4 style="font-family:'Playfair Display',serif; color:var(--ink); font-size:18px; margin-bottom:12px; font-weight:800;">Vị trí việc làm sau tốt nghiệp</h4>
                        <ul style="list-style:none; padding-left:0; display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:24px;">
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Kỹ thuật viên chẩn đoán điện ô tô</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Tư vấn dịch vụ tại Showroom</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Quản lý xưởng sửa chữa ô tô</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Kiểm định viên xe cơ giới</li>
                        </ul>

                        <h5 class="skills-matrix-title">Ma Trận Kỹ Năng Theo Học Kỳ</h5>
                        <div class="skills-matrix-grid">
                            <div class="skills-semester">
                                <h5>Học Kỳ 1</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Vẽ kỹ thuật cơ bản</span>
                                    <span class="skills-badge">Cơ lý thuyết</span>
                                    <span class="skills-badge">An toàn xưởng</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 2</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Cơ cấu động cơ ô tô</span>
                                    <span class="skills-badge">Hệ thống gầm</span>
                                    <span class="skills-badge">Điện ô tô cơ bản</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 3</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Hệ thống EFI</span>
                                    <span class="skills-badge">Hộp số tự động</span>
                                    <span class="skills-badge">Chẩn đoán OBD-II</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 4</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Hệ thống xe Hybrid/EV</span>
                                    <span class="skills-badge">Thực tập Toyota</span>
                                    <span class="skills-badge">Quản lý dịch vụ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel 3: Điện -->
                <div class="major-detail-panel" id="panel-dien">
                    <div class="major-detail-header">
                        <div class="major-detail-icon"><i class="fas fa-bolt"></i></div>
                        <div class="major-detail-title">
                            <h3>Ngành Điện - Điện Tử</h3>
                            <span>Hệ Cao Đẳng Chính Quy · Mã ngành: 6520224</span>
                        </div>
                    </div>
                    <div class="major-detail-body" style="font-size: 15px; line-height: 1.7; color: #475569;">
                        <p style="margin-bottom: 20px;">Đào tạo kỹ năng thiết kế mạch điện tử, lập trình PLC và tự động hóa trong sản xuất công nghiệp. Học viên được trang bị tư duy và năng lực vận hành, tối ưu hóa các hệ thống máy móc tự động sử dụng Robot và năng lượng tái tạo.</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div style="background: rgba(255,255,255,0.5); padding: 16px; border-radius: 12px; border:1px solid rgba(0,0,0,0.03);">
                                <strong style="color:var(--ink); display:block; margin-bottom:6px;"><i class="far fa-clock" style="color:var(--crimson);"></i> Thời gian đào tạo</strong>
                                2.5 năm (Thực hành phòng Lab Siemens)
                            </div>
                            <div style="background: rgba(255,255,255,0.5); padding: 16px; border-radius: 12px; border:1px solid rgba(0,0,0,0.03);">
                                <strong style="color:var(--ink); display:block; margin-bottom:6px;"><i class="fas fa-award" style="color:var(--gold);"></i> Bằng cấp tốt nghiệp</strong>
                                Kỹ sư thực hành Điện tử công nghiệp
                            </div>
                        </div>
                        <h4 style="font-family:'Playfair Display',serif; color:var(--ink); font-size:18px; margin-bottom:12px; font-weight:800;">Vị trí việc làm sau tốt nghiệp</h4>
                        <ul style="list-style:none; padding-left:0; display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:24px;">
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Kỹ thuật viên bảo trì hệ thống điện</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Lập trình viên hệ thống PLC</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Quản lý dây chuyền tự động</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Giám sát an toàn điện công nghiệp</li>
                        </ul>

                        <h5 class="skills-matrix-title">Ma Trận Kỹ Năng Theo Học Kỳ</h5>
                        <div class="skills-matrix-grid">
                            <div class="skills-semester">
                                <h5>Học Kỳ 1</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Mạch điện cơ bản</span>
                                    <span class="skills-badge">Linh kiện điện tử</span>
                                    <span class="skills-badge">Đo lường điện</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 2</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Mạch điện tử số</span>
                                    <span class="skills-badge">Vi điều khiển</span>
                                    <span class="skills-badge">Thiết kế PCB Orcad</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 3</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Lập trình PLC Siemens</span>
                                    <span class="skills-badge">Hệ thống khí nén</span>
                                    <span class="skills-badge">Động cơ & Biến tần</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 4</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Robot công nghiệp</span>
                                    <span class="skills-badge">SCADA & Giám sát</span>
                                    <span class="skills-badge">Đồ án tốt nghiệp PLC</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel 4: Quản Trị -->
                <div class="major-detail-panel" id="panel-qtn">
                    <div class="major-detail-header">
                        <div class="major-detail-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="major-detail-title">
                            <h3>Ngành Quản Trị Doanh Nghiệp</h3>
                            <span>Hệ Cao Đẳng Chính Quy · Mã ngành: 6340114</span>
                        </div>
                    </div>
                    <div class="major-detail-body" style="font-size: 15px; line-height: 1.7; color: #475569;">
                        <p style="margin-bottom: 20px;">Đào tạo về tư duy quản trị chiến lược, kỹ năng đàm phán, hoạch định kế hoạch marketing số và vận hành bộ máy tài chính, nhân sự trong doanh nghiệp vừa và nhỏ. Học viên được tham gia các buổi tọa đàm trực tiếp cùng CEO.</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div style="background: rgba(255,255,255,0.5); padding: 16px; border-radius: 12px; border:1px solid rgba(0,0,0,0.03);">
                                <strong style="color:var(--ink); display:block; margin-bottom:6px;"><i class="far fa-clock" style="color:var(--crimson);"></i> Thời gian đào tạo</strong>
                                2.5 năm (Mô phỏng doanh nghiệp 3D)
                            </div>
                            <div style="background: rgba(255,255,255,0.5); padding: 16px; border-radius: 12px; border:1px solid rgba(0,0,0,0.03);">
                                <strong style="color:var(--ink); display:block; margin-bottom:6px;"><i class="fas fa-award" style="color:var(--gold);"></i> Bằng cấp tốt nghiệp</strong>
                                Cử nhân thực hành Quản trị doanh nghiệp
                            </div>
                        </div>
                        <h4 style="font-family:'Playfair Display',serif; color:var(--ink); font-size:18px; margin-bottom:12px; font-weight:800;">Vị trí việc làm sau tốt nghiệp</h4>
                        <ul style="list-style:none; padding-left:0; display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:24px;">
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Trợ lý hoạch định chiến lược kinh doanh</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Chuyên viên phát triển thị trường</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Quản trị viên nhân sự</li>
                            <li><i class="fas fa-check" style="color:var(--teal); margin-right:8px;"></i> Chuyên viên Marketing & PR</li>
                        </ul>

                        <h5 class="skills-matrix-title">Ma Trận Kỹ Năng Theo Học Kỳ</h5>
                        <div class="skills-matrix-grid">
                            <div class="skills-semester">
                                <h5>Học Kỳ 1</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Kinh tế học cơ bản</span>
                                    <span class="skills-badge">Nguyên lý kế toán</span>
                                    <span class="skills-badge">Tin học văn phòng</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 2</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Quản trị nhân sự</span>
                                    <span class="skills-badge">Marketing căn bản</span>
                                    <span class="skills-badge">Luật thương mại</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 3</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Digital Marketing</span>
                                    <span class="skills-badge">Quản trị tài chính</span>
                                    <span class="skills-badge">Kỹ năng thương lượng</span>
                                </div>
                            </div>
                            <div class="skills-semester">
                                <h5>Học Kỳ 4</h5>
                                <div class="skills-badges-wrap">
                                    <span class="skills-badge">Kế hoạch Khởi nghiệp</span>
                                    <span class="skills-badge">CEO Mentorship</span>
                                    <span class="skills-badge">Thực tập tốt nghiệp</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dual Training Section -->
        <div class="dual-banner" style="margin-top: 80px;">
            <div class="dual-banner-text">
                <h3>Mô Hình Đào Tạo Kép</h3>
                <p>Trường Cao Đẳng Nghề Việt Nam - Hàn Quốc Cà Mau đi đầu áp dụng mô hình đào tạo kép chất lượng cao theo chuẩn quốc tế. Sinh viên dành 30% thời gian tiếp thu lý thuyết nền tảng tại trường và 70% thời gian thực hành thực tế trực tiếp tại các tập đoàn đối tác.</p>
                <ul class="dual-list">
                    <li><i class="fas fa-check-circle"></i> Nhận trợ cấp và thu nhập thực tập ngay từ năm 2</li>
                    <li><i class="fas fa-check-circle"></i> Trải nghiệm môi trường làm việc chuyên nghiệp thực tế</li>
                    <li><i class="fas fa-check-circle"></i> Doanh nghiệp cam kết nhận việc ngay khi tốt nghiệp</li>
                </ul>
            </div>
            <div class="dual-banner-img">
                <img src="/tkb/assets/img/bg_new.png" alt="Mô hình đào tạo kép">
            </div>
        </div>
    </div>
</section>

<!-- Tab Switcher Script -->
<script>
function switchMajorTab(e, majorId) {
    // 1. Remove active state from navigation buttons
    document.querySelectorAll('.major-nav-btn').forEach(btn => btn.classList.remove('active'));
    // 2. Hide all detail panels
    document.querySelectorAll('.major-detail-panel').forEach(panel => panel.classList.remove('active'));
    
    // 3. Add active state to clicked button
    e.currentTarget.classList.add('active');
    // 4. Show corresponding panel
    document.getElementById('panel-' + majorId).classList.add('active');
}
</script>

<?php require_once 'includes/public_footer.php'; ?>
