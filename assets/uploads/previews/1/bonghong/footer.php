    </main>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3><?= sanitize($settings['website_name']) ?></h3>
                    <p><?= sanitize($settings['slogan']) ?></p>
                    <p>Chào mừng bạn đến với Roselia, nơi cung cấp những đóa hoa tươi đẹp nhất với thông điệp tình yêu trọn vẹn.</p>
                </div>
                
                <div class="footer-column">
                    <h3>Liên hệ</h3>
                    <p><strong>Địa chỉ:</strong> <?= sanitize($settings['address']) ?></p>
                    <p><strong>Điện thoại:</strong> <?= sanitize($settings['phone']) ?></p>
                    <p><strong>Email:</strong> <?= sanitize($settings['email']) ?></p>
                </div>
                
                <div class="footer-column">
                    <h3>Theo dõi chúng tôi</h3>
                    <p>Kết nối qua các mạng xã hội để cập nhật những mẫu hoa mới nhất.</p>
                    <div class="footer-socials">
                        <?php if (!empty($settings['facebook'])): ?>
                            <a href="<?= sanitize($settings['facebook']) ?>" target="_blank" class="social-icon" title="Facebook">FB</a>
                        <?php endif; ?>
                        <?php if (!empty($settings['zalo'])): ?>
                            <a href="<?= sanitize($settings['zalo']) ?>" target="_blank" class="social-icon" title="Zalo">ZL</a>
                        <?php endif; ?>
                        <?php if (!empty($settings['youtube'])): ?>
                            <a href="<?= sanitize($settings['youtube']) ?>" target="_blank" class="social-icon" title="Youtube">YT</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= sanitize($settings['website_name']) ?>. Tất cả quyền được bảo lưu. Thiết kế bởi <?= sanitize($_SESSION['user_fullname'] ?? 'Lê Nhựt Khánh') ?>.</p>
            </div>
        </div>
    </footer>

    <!-- Load Main JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>
