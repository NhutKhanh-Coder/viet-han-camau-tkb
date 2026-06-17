<?php require_once 'includes/public_header.php'; ?>

<!-- Glowing Background Orbs -->
<div class="glowing-orb orb-1"></div>
<div class="glowing-orb orb-2"></div>
<div class="glowing-orb orb-3"></div>

<section class="section bg-alt" style="min-height: 80vh; position: relative; overflow: hidden; background: transparent;">
    <div class="container" style="position: relative; z-index: 2;">
        <div class="section-header centered">
            <div class="section-tag"><i class="fas fa-book-reader"></i> Thư viện</div>
            <h2 class="section-title">Thư Viện <em>Số</em></h2>
            <div class="divider-line center"></div>
            <p class="section-desc">Kết nối tri thức, khơi nguồn sáng tạo. Truy cập hàng ngàn tư liệu số chất lượng cao.</p>
        </div>
        
        <div class="lib-feature-grid-mod">
            <div class="feature-box-mod">
                <i class="fas fa-search-plus"></i>
                <h3>Tra cứu sách trực tuyến</h3>
                <p>Tìm kiếm nhanh chóng hơn 50.000 đầu sách giấy và điện tử có tại hệ thống thư viện của nhà trường.</p>
            </div>
            <div class="feature-box-mod accent">
                <i class="fas fa-book-reader"></i>
                <h3>Đọc sách điện tử</h3>
                <p>Truy cập hàng ngàn ấn bản eBook, giáo trình điện tử và tạp chí nghiên cứu khoa học chuyên ngành miễn phí.</p>
            </div>
            <div class="feature-box-mod">
                <i class="fas fa-id-card"></i>
                <h3>Quản lý thẻ mượn</h3>
                <p>Gia hạn thời gian mượn, tra cứu lịch sử mượn trả và nhận thông báo nhắc nhở tự động từ thủ thư.</p>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1.5px solid rgba(15,23,42,0.06); padding-bottom: 15px;">
            <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 800; color: var(--ink);">Sách Mới Cập Nhật</h3>
            <a href="#" class="btn-event-mod">Xem tất cả &rarr;</a>
        </div>

        <div class="lib-grid-mod">
            <div class="book-card-mod">
                <div class="book-cover-mod">
                    <img src="https://images.unsplash.com/photo-1555949963-ff9fe0c870eb?w=400&q=80" alt="Cơ sở dữ liệu" loading="lazy">
                </div>
                <div class="book-info-mod">
                    <div class="book-title-mod">Cơ sở dữ liệu & Phân tích thiết kế hệ thống</div>
                    <div class="book-author-mod">PGS.TS Nguyễn Văn A</div>
                    <div class="book-tags-mod">
                        <span class="tag">CNTT</span>
                        <span class="tag">Giáo trình</span>
                    </div>
                </div>
            </div>
            <div class="book-card-mod">
                <div class="book-cover-mod">
                    <img src="https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=400&q=80" alt="Công nghệ Ô tô" loading="lazy">
                </div>
                <div class="book-info-mod">
                    <div class="book-title-mod">Công nghệ Ô tô Hiện đại - Tập 1</div>
                    <div class="book-author-mod">TS. Lê Văn B</div>
                    <div class="book-tags-mod">
                        <span class="tag">Cơ khí Động lực</span>
                        <span class="tag">Thực hành</span>
                    </div>
                </div>
            </div>
            <div class="book-card-mod">
                <div class="book-cover-mod">
                    <img src="https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&q=80" alt="Mạch Điện tử" loading="lazy">
                </div>
                <div class="book-info-mod">
                    <div class="book-title-mod">Mạch Điện tử Ứng dụng Thực hành</div>
                    <div class="book-author-mod">ThS. Trần Thị C</div>
                    <div class="book-tags-mod">
                        <span class="tag">Điện - Điện tử</span>
                    </div>
                </div>
            </div>
            <div class="book-card-mod">
                <div class="book-cover-mod">
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&q=80" alt="Quản trị Nhà hàng" loading="lazy">
                </div>
                <div class="book-info-mod">
                    <div class="book-title-mod">Nghiệp vụ Quản trị Nhà hàng Khách sạn</div>
                    <div class="book-author-mod">Khoa Du lịch - VKC</div>
                    <div class="book-tags-mod">
                        <span class="tag">Du lịch</span>
                        <span class="tag">Dịch vụ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/public_footer.php'; ?>