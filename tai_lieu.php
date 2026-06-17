<?php require_once 'includes/public_header.php'; ?>

<!-- Glowing Background Orbs -->
<div class="glowing-orb orb-1"></div>
<div class="glowing-orb orb-2"></div>
<div class="glowing-orb orb-3"></div>

<section class="section bg-alt" style="min-height: 80vh; position: relative; overflow: hidden; background: transparent;">
    <div class="container" style="position: relative; z-index: 2;">
        <div class="section-header centered">
            <div class="section-tag"><i class="fas fa-file-alt"></i> Học liệu</div>
            <h2 class="section-title">Tài Liệu <em>Học Tập</em></h2>
            <div class="divider-line center"></div>
            <p class="section-desc">Kho lưu trữ bài giảng điện tử, giáo trình và tài liệu tham khảo chất lượng dành cho sinh viên.</p>
        </div>
        
        <!-- Glassmorphic Search Container -->
        <div class="glass-panel" style="padding: 24px; border-radius: var(--r-md); margin-bottom: 50px; display: flex; gap: 15px; border-color: rgba(255, 255, 255, 0.45);">
            <input type="text" class="cinput" style="flex: 1; padding: 14px 20px; font-size: 15px; border-radius: var(--r-sm);" placeholder="Tìm kiếm giáo trình, tài liệu, đề thi, bài giảng...">
            <button class="btn-primary" style="padding: 0 32px; border: none; border-radius: var(--r-sm); font-size: 14px; cursor: pointer; height: 48px;"><i class="fas fa-search"></i> Tìm Kiếm</button>
        </div>

        <div class="doc-grid-mod">
            <div class="doc-card-mod">
                <i class="fas fa-book doc-icon-mod"></i>
                <h3 class="doc-title-mod">Giáo trình chính quy</h3>
                <p class="doc-desc-mod">Hệ thống giáo trình điện tử chuẩn do nhà trường biên soạn cho các chuyên ngành đào tạo.</p>
                <div class="doc-stats-mod">
                    <span><i class="fas fa-file-pdf"></i> 1,245 tài liệu</span>
                    <a href="#">Xem chi tiết &rarr;</a>
                </div>
            </div>
            
            <div class="doc-card-mod accent">
                <i class="fas fa-chalkboard-teacher doc-icon-mod"></i>
                <h3 class="doc-title-mod">Bài giảng điện tử</h3>
                <p class="doc-desc-mod">Slide bài giảng, video hướng dẫn thực hành từ các giảng viên trực tiếp giảng dạy.</p>
                <div class="doc-stats-mod">
                    <span><i class="fas fa-video"></i> 850 bài giảng</span>
                    <a href="#">Xem chi tiết &rarr;</a>
                </div>
            </div>

            <div class="doc-card-mod">
                <i class="fas fa-tasks doc-icon-mod"></i>
                <h3 class="doc-title-mod">Đề thi & Đề cương</h3>
                <p class="doc-desc-mod">Ngân hàng đề thi tham khảo, đề cương ôn tập các học phần qua các năm học.</p>
                <div class="doc-stats-mod">
                    <span><i class="fas fa-file-alt"></i> 520 đề mục</span>
                    <a href="#">Xem chi tiết &rarr;</a>
                </div>
            </div>

            <div class="doc-card-mod accent">
                <i class="fas fa-microscope doc-icon-mod"></i>
                <h3 class="doc-title-mod">Tài liệu NCKH</h3>
                <p class="doc-desc-mod">Các báo cáo nghiên cứu khoa học, đồ án tốt nghiệp xuất sắc của sinh viên các khóa.</p>
                <div class="doc-stats-mod">
                    <span><i class="fas fa-flask"></i> 340 báo cáo</span>
                    <a href="#">Xem chi tiết &rarr;</a>
                </div>
            </div>
            
            <div class="doc-card-mod">
                <i class="fas fa-globe-asia doc-icon-mod"></i>
                <h3 class="doc-title-mod">Tài liệu Ngoại ngữ</h3>
                <p class="doc-desc-mod">Tài liệu ôn thi chứng chỉ tiếng Anh, tiếng Hàn và các chuyên ngành kỹ thuật.</p>
                <div class="doc-stats-mod">
                    <span><i class="fas fa-language"></i> 410 tài liệu</span>
                    <a href="#">Xem chi tiết &rarr;</a>
                </div>
            </div>
            
            <div class="doc-card-mod accent">
                <i class="fas fa-laptop-code doc-icon-mod"></i>
                <h3 class="doc-title-mod">Phần mềm Kỹ thuật</h3>
                <p class="doc-desc-mod">Nơi tải về và hướng dẫn cài đặt các phần mềm học tập, thiết kế, lập trình.</p>
                <div class="doc-stats-mod">
                    <span><i class="fas fa-download"></i> 85 phần mềm</span>
                    <a href="#">Xem chi tiết &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/public_footer.php'; ?>
