SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `ai_account_orders`;
CREATE TABLE `ai_account_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(100) DEFAULT 'Sinh viên',
  `account_title` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `account_info` text NOT NULL,
  `payment_method` varchar(50) DEFAULT 'momo_qr',
  `status` enum('completed','pending','cancelled') DEFAULT 'completed',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `ai_accounts_store`;
CREATE TABLE `ai_accounts_store` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `ai_type` varchar(50) DEFAULT 'ChatGPT',
  `image_url` varchar(500) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'AI',
  `price` int(11) NOT NULL DEFAULT 50000,
  `variants` text DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 1,
  `account_info` text NOT NULL,
  `description` text DEFAULT NULL,
  `bank_info` varchar(255) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT 0,
  `teacher_name` varchar(100) DEFAULT 'Giảng viên',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ai_accounts_store` (`id`, `title`, `ai_type`, `image_url`, `category`, `price`, `variants`, `stock`, `account_info`, `description`, `bank_info`, `teacher_id`, `teacher_name`, `created_at`) VALUES ('4', 'Gemini Pro Chính Chủ 5TB+ Antigravity - Bhfull, 1 năm, 18thag', 'Gemini, Antigravity', '/tkb/uploads/mmo_banners/mmo_1785316375_4313.webp', 'AI', '65000', '[{\"name\":\"Pro 1 năm - Nâng chính chủ - bhFull - chủ fam\",\"price\":165000},{\"name\":\"Pro 18 tháng - Nâng chính chủ - Bhfull - chủ fam\",\"price\":250000},{\"name\":\"Pro 18 tháng - 1 slot gmail - Add chính chủ - BhFull\",\"price\":65000},{\"name\":\"[LINK] Nâng cấp 18 tháng + bh 24h\",\"price\":135000}]', '23', 'Chờ Duyệt | Đang Kiểm Duyệt | Chờ Lấy Tài Khoản | Hoàn Thành', '✅ Tài khoản cấp dùng riêng 100%\r\n✅ Bảo hành full tất cả các gói ( theo từng gói đã mua)\r\n✅ Lỗi trong thời gian bảo hành hỗ trợ đổi 1-1\r\nTHÔNG TIN SẢN PHẨM\r\n\r\n✅ 1. Nâng chính chủ\r\nKhách dùng chính tài khoản Google cá nhân để nâng cấp.\r\nCách xử lý: Khách gửi email cần nâng và mk → shop hỗ trợ nâng theo gói → hoàn tất và sử dụng trực tiếp.\r\n\r\n👨‍👩‍👧 2. CHủ Family / Add Fam\r\nPhù hợp: dùng nhanh, tiết kiệm chi phí.\r\nCách xử lý: Khách gửi email Google → shop add vào Family → khách kiểm tra quyền lợi trên tài khoản.\r\n\r\n📧 3. Slot (Fam) Gmail / Add chính chủ\r\nPhù hợp: dùng trên Gmail riêng, chi phí tối ưu hơn.\r\nCách xử lý: Khách gửi Gmail → shop xử lý add gói → bàn giao khi tài khoản hiển thị quyền lợi.\r\n\r\n🔗 4. Link nâng chính chủ\r\nShop cung cấp link nâng hoặc hướng dẫn thao tác nâng cấp theo đúng gói khách chọn.\r\nPhù hợp: thao tác nhanh, rõ ràng, dễ kiểm tra.\r\n\r\n🌟 Quyền lợi nổi bật:\r\n\r\n✨ Gemini Pro / Gemini Advanced: hỗ trợ viết nội dung, học tập, làm việc, phân tích tài liệu, lên ý tưởng, viết code.\r\n☁️ Google One 5TB: lưu trữ Drive, Gmail, Google Photos thoải mái hơn.\r\n📝 Tích hợp Google: hỗ trợ tốt hơn với Gmail, Docs, Drive và các công cụ Google.\r\n📚 NotebookLM: hỗ trợ tóm tắt, nghiên cứu và phân tích tài liệu nếu gói có hỗ trợ.\r\n🖼️ Google Photos AI: chỉnh sửa ảnh thông minh tùy tài khoản/khu vực.\r\n⚡ Antigravity: áp dụng với gói có kèm quyền sử dụng.\r\n⏳ Thời hạn: hỗ trợ gói 12 tháng và 18 tháng.\r\n\r\n📩 Thông tin bàn giao:\r\n\r\n🔐 Tài khoản cấp sẵn: Email + mật khẩu.+ 2fa\r\n👤 Nâng chính chủ: Khách gửi email cần nâng theo hướng dẫn.\r\n👨‍👩‍👧 Add Family / Add Slot: Khách gửi email Google cần add.\r\n🔗 Link nâng: Shop gửi link hoặc hướng dẫn thao tác.\r\n\r\n✅ Cam kết hàng chuẩn như mô tả\r\n✅ Hỗ trợ tận tâm, uy tín, lâu dài\r\n\r\nTrong thời gian bảo hành,Nếu tài khoản phát sinh lỗi đúng theo chính sách, bên em hỗ trợ xử lý hoặc đổi mới rõ ràng, nhanh chóng.', '0', '10', 'Phan Ngọc Tuyền', '2026-07-29 15:57:05');

DROP TABLE IF EXISTS `ai_prompts`;
CREATE TABLE `ai_prompts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tieu_de` varchar(100) NOT NULL,
  `prompt` text NOT NULL,
  `limit_count` int(11) DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ai_prompts` (`id`, `tieu_de`, `prompt`, `limit_count`, `created_at`) VALUES ('1', 'Trợ lý VKC', 'Bạn là trợ lý AI VKC hỗ trợ giảng dạy', '100', '2026-07-09 21:01:03');

DROP TABLE IF EXISTS `assignments`;
CREATE TABLE `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `giang_vien_id` int(11) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `lop` varchar(50) NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `han_nop` datetime DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quiz_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('2', '1', '9', 'K24CDCNT1', 'Phân tích cấu trúc phông chữ và công cụ tạo tài liệu', 'Dựa trên danh sách các phông chữ (Liberation Serif, TeX Gyre Termes) và công cụ (WeasyPrint) được liệt kê trong tài liệu, hãy viết một đoạn văn ngắn giải thích vai trò của các thành phần này trong việc định dạng văn bản kỹ thuật số. Sau đó, hãy liệt kê các bước cơ bản để thiết lập một môi trường sử dụng WeasyPrint để xuất tệp PDF từ HTML.', '2026-07-17 23:59:00', NULL, '2026-07-16 13:54:16', NULL);
INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('3', '1', '9', 'K24CDCNT1', 'Phân tích cấu trúc phông chữ và công cụ định dạng', 'Dựa trên danh sách các phông chữ (Liberation Serif, TeX Gyre Termes) và công cụ (WeasyPrint, Adobe UCS) được liệt kê trong tài liệu, hãy viết một đoạn văn ngắn (150-200 từ) giải thích vai trò của các thành phần này trong quy trình xuất bản tài liệu kỹ thuật số và tại sao việc kết hợp các phông chữ này lại quan trọng đối với tính nhất quán của văn bản.', '2026-07-17 23:59:00', NULL, '2026-07-16 14:06:37', NULL);
INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('4', '1', '9', 'K24CDCNT1', 'Phân tích cấu trúc phông chữ và công cụ xuất bản', 'Dựa trên danh sách các phông chữ (Liberation Serif, TeX Gyre Termes) và công cụ (WeasyPrint) được liệt kê trong tài liệu, hãy viết một đoạn văn ngắn (khoảng 150 từ) giải thích vai trò của các thành phần này trong quy trình tạo tài liệu kỹ thuật số chuyên nghiệp. Bạn cần nêu rõ tại sao việc kết hợp các phông chữ này lại quan trọng đối với tính nhất quán của văn bản.', '2026-07-17 23:59:00', NULL, '2026-07-16 14:06:50', NULL);
INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('5', '1', '9', 'K24CDCNT1', 'Phân tích cấu trúc Metadata của tệp PDF', 'Dựa trên các từ khóa như \'Adobe Identity\', \'Liberation Serif\', và \'WeasyPrint\', hãy viết một bài luận ngắn giải thích cách các công cụ tạo tài liệu (như WeasyPrint) quản lý thông tin font chữ và ánh xạ ký tự (UCS) trong tệp PDF đầu ra.', '2026-07-17 23:59:00', NULL, '2026-07-16 14:14:42', NULL);
INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('6', '1', '9', 'K24CDCNT1', 'Phân tích cấu trúc Metadata trong tệp PDF', 'Dựa trên các từ khóa như \'WeasyPrint\', \'Adobe Identity\', và các font chữ được liệt kê, hãy giải thích vai trò của các thông tin này trong việc định dạng và hiển thị một tệp PDF. Tại sao các thông tin này thường xuất hiện trong phần metadata của tài liệu?', '2026-07-17 23:59:00', NULL, '2026-07-16 14:14:56', NULL);
INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('7', '1', '9', 'K24CDCNT1', 'Phân tích cấu trúc Metadata của tài liệu', 'Dựa trên các từ khóa như \'WeasyPrint\', \'Adobe Identity\', và \'TeX Gyre Termes\', hãy viết một bài luận ngắn giải thích cách các thông tin này thường xuất hiện trong metadata của một tệp PDF và tầm quan trọng của việc xác định nguồn gốc phông chữ trong xuất bản kỹ thuật số.', '2026-07-17 23:59:00', NULL, '2026-07-16 14:19:52', NULL);
INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('8', '1', '9', 'K24CDCNT1', 'Phân tích vai trò của phông chữ trong hệ thống xuất bản TeX', 'Dựa trên các thành phần được liệt kê (Liberation Serif, TeX Gyre Termes, Adobe Identity), hãy viết một bài luận ngắn giải thích tại sao việc quản lý phông chữ lại quan trọng trong quá trình chuyển đổi tài liệu sang định dạng PDF thông qua các công cụ như WeasyPrint.', '2026-07-17 23:59:00', NULL, '2026-07-16 14:24:04', NULL);
INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('9', '1', '9', 'K24CDCNT1', 'Thiết kế Giao diện (CSS Layout)', 'Viết mã ngôn ngữ HTML và CSS để tạo ra một cấu trúc trang web đáp ứng yêu cầu sau: Gồm một thanh điều hướng (Header) cố định ở trên cùng màn hình; vùng nội dung chính ở giữa chia làm 2 cột bằng kỹ thuật CSS Flexbox hoặc CSS Grid; cột bên trái làm Sidebar chiếm 25% chiều rộng; cột bên phải làm Main Content chiếm 75% chiều rộng; và một chân trang (Footer) ở dưới cùng.', '2026-07-17 23:59:00', NULL, '2026-07-16 14:26:08', NULL);
INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('10', '1', '9', 'K24CDCNT1', 'Xử lý tương tác DOM (JavaScript)', 'Giả sử giao diện có một form đăng ký chứa hai ô nhập liệu (input) là \'password\' và \'confirm password\', cùng một nút nhấn \'Đăng ký\' (id: register-btn). Hãy viết đoạn mã xử lý sự kiện JavaScript để kiểm tra tính hợp lệ dữ liệu (Validate) khi người dùng click vào nút Đăng ký: Nếu một trong hai ô trống, hiển thị thông báo \'Vui lòng nhập đầy đủ thông tin\'; Nếu mật khẩu ở hai ô không trùng khớp, hiển thị thông báo \'Mật khẩu xác nhận không chính xác\'; Nếu tất cả thông tin hợp lệ, hiển thị thông báo \'Đăng ký thành công\'.', '2026-07-17 23:59:00', NULL, '2026-07-16 14:26:08', NULL);
INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('11', '1', '9', 'K24CDCNT1', 'Kiến thức Kiến trúc (Back-end Web)', 'Phân tích và mô tả quy trình xác thực dựa trên Session (Session-based Authentication). Mô tả chi tiết luồng dữ liệu tương tác từ thời điểm Client gửi thông tin đăng nhập (Username, Password), cách Server thiết lập trạng thái, cho đến khi Client gửi các request tiếp theo theo đúng quy chuẩn yêu cầu quyền bảo mật. Nêu vai trò của Session ID và Cookie trong mô hình này.', '2026-07-17 23:59:00', NULL, '2026-07-16 14:26:08', NULL);
INSERT INTO `assignments` (`id`, `giang_vien_id`, `mon_hoc_id`, `lop`, `tieu_de`, `mo_ta`, `han_nop`, `file_path`, `created_at`, `quiz_id`) VALUES ('17', '1', '9', 'K24CDCNT1', 'Xây dựng ứng dụng Windows Form cơ bản', 'Hãy tạo một dự án Windows Forms App trong Visual Studio. Thiết kế một Form bao gồm: 2 TextBox để nhập hai số nguyên, 1 Button để tính tổng và 1 Label để hiển thị kết quả. Viết mã xử lý sự kiện Click cho Button để thực hiện phép cộng hai số từ TextBox và hiển thị kết quả lên Label.', '2026-07-17 23:59:00', NULL, '2026-07-16 19:15:48', '14');

DROP TABLE IF EXISTS `diem`;
CREATE TABLE `diem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `hoc_ky` varchar(20) DEFAULT NULL,
  `nam_hoc` varchar(20) DEFAULT NULL,
  `diem_giua_ky` decimal(4,2) DEFAULT NULL,
  `diem_cuoi_ky` decimal(4,2) DEFAULT NULL,
  `diem_tong_ket` decimal(4,2) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sv_mon_hk` (`student_id`,`mon_hoc_id`,`hoc_ky`,`nam_hoc`),
  KEY `mon_hoc_id` (`mon_hoc_id`),
  CONSTRAINT `diem_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diem_ibfk_2` FOREIGN KEY (`mon_hoc_id`) REFERENCES `mon_hoc` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `diem_danh`;
CREATE TABLE `diem_danh` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `ngay_diem_danh` date DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'Có mặt',
  `ghi_chu` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `mon_hoc_id` (`mon_hoc_id`),
  CONSTRAINT `diem_danh_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diem_danh_ibfk_2` FOREIGN KEY (`mon_hoc_id`) REFERENCES `mon_hoc` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `do_an`;
CREATE TABLE `do_an` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `giang_vien_id` int(11) NOT NULL,
  `ten_do_an` varchar(255) NOT NULL,
  `sinh_vien_id` int(11) DEFAULT NULL,
  `nhom_id` int(11) DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'Chưa bắt đầu',
  `diem` decimal(4,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `link_nop_bai` varchar(255) DEFAULT NULL,
  `file_nop_bai` varchar(255) DEFAULT NULL,
  `nhan_xet` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `flashcards`;
CREATE TABLE `flashcards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `giang_vien_id` int(11) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `mat_truoc` text NOT NULL,
  `mat_sau` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `giang_vien`;
CREATE TABLE `giang_vien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_gv` varchar(20) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `khoa` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_gv` (`ma_gv`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_giang_vien_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `giang_vien` (`id`, `ma_gv`, `ho_ten`, `khoa`, `user_id`) VALUES ('1', 'GV001', 'Phan Ngọc Tuyền', 'Công Nghệ Thông Tin', '10');
INSERT INTO `giang_vien` (`id`, `ma_gv`, `ho_ten`, `khoa`, `user_id`) VALUES ('2', 'GV002', 'Trần Thị Bình', 'Công nghệ thông tin', '11');
INSERT INTO `giang_vien` (`id`, `ma_gv`, `ho_ten`, `khoa`, `user_id`) VALUES ('3', 'GV003', 'Lê Hoàng Cường', 'Toán - Lý', '12');
INSERT INTO `giang_vien` (`id`, `ma_gv`, `ho_ten`, `khoa`, `user_id`) VALUES ('4', 'GV004', 'Phạm Thị Dung', 'Ngoại ngữ', '13');

DROP TABLE IF EXISTS `lessons`;
CREATE TABLE `lessons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `giang_vien_id` int(11) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `noi_dung` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `mmo_coupons`;
CREATE TABLE `mmo_coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_percent` int(11) NOT NULL DEFAULT 10,
  `max_uses` int(11) DEFAULT 100,
  `used_count` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `mon_hoc`;
CREATE TABLE `mon_hoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_mon` varchar(20) NOT NULL,
  `ten_mon` varchar(100) NOT NULL,
  `so_tin_chi` int(11) DEFAULT 2,
  `khoa` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_mon` (`ma_mon`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `mon_hoc` (`id`, `ma_mon`, `ten_mon`, `so_tin_chi`, `khoa`) VALUES ('9', '01', 'Lập Trình Web', '1', 'Công Nghệ Thông Tin');

DROP TABLE IF EXISTS `music_library`;
CREATE TABLE `music_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `artist_or_description` varchar(255) DEFAULT NULL,
  `type` enum('file','youtube') NOT NULL DEFAULT 'youtube',
  `file_path` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `youtube_id` varchar(50) DEFAULT NULL,
  `genre` varchar(50) DEFAULT 'lofi',
  `media_type` varchar(20) DEFAULT 'audio',
  `views` int(11) DEFAULT 128,
  `likes` int(11) DEFAULT 15,
  `teacher_id` int(11) DEFAULT 0,
  `teacher_name` varchar(100) DEFAULT 'Giảng viên',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `music_library` (`id`, `title`, `artist_or_description`, `type`, `file_path`, `cover_image`, `youtube_url`, `youtube_id`, `genre`, `media_type`, `views`, `likes`, `teacher_id`, `teacher_name`, `created_at`) VALUES ('1', 'Mơ, Giấc Mơ Trưa, Từng Là, Có Em Chờ, Dấu Mưa', 'Những Bản Hits Nhẹ Nhàng Cực Chill Thư Giãn [COVER]', 'youtube', NULL, NULL, 'https://youtu.be/plrqhgNHgHY?si=Yb2VhCS9N8igZk-_', 'plrqhgNHgHY', 'lofi', 'audio', '128', '15', '10', 'Phan Ngọc Tuyền', '2026-07-29 13:03:51');

DROP TABLE IF EXISTS `nhom_do_an`;
CREATE TABLE `nhom_do_an` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_nhom` varchar(100) NOT NULL,
  `giang_vien_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `nhom_do_an_thanh_vien`;
CREATE TABLE `nhom_do_an_thanh_vien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nhom_id` int(11) NOT NULL,
  `sinh_vien_id` int(11) NOT NULL,
  `vai_tro` varchar(50) DEFAULT 'Thành viên',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sv_nhom` (`nhom_id`,`sinh_vien_id`),
  CONSTRAINT `nhom_do_an_thanh_vien_ibfk_1` FOREIGN KEY (`nhom_id`) REFERENCES `nhom_do_an` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `practice_sessions`;
CREATE TABLE `practice_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lop` varchar(50) NOT NULL,
  `mo_ta` varchar(255) DEFAULT NULL,
  `de_file_path` varchar(255) DEFAULT NULL,
  `de_file_name` varchar(255) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `practice_sessions` (`id`, `lop`, `mo_ta`, `de_file_path`, `de_file_name`, `start_time`, `end_time`, `is_enabled`, `created_at`) VALUES ('7', 'K24CDCNT1', 'PHP', '/tkb/assets/uploads/practice_problems/de_20260716_192847_38c6c3637316.pdf', 'De_Thi_Lap_Trinh_Web.pdf', '2026-07-16 19:29:00', '2026-07-16 20:00:00', '1', '2026-07-16 19:28:47');

DROP TABLE IF EXISTS `practice_submissions`;
CREATE TABLE `practice_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `diem` decimal(4,2) DEFAULT NULL,
  `nhan_xet` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_session` (`student_id`,`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `practice_submissions` (`id`, `session_id`, `student_id`, `file_path`, `submitted_at`, `diem`, `nhan_xet`) VALUES ('1', '2', '2', '/tkb/assets/uploads/practice_submissions/sv_2_session_2_1783951200.zip', '2026-07-13 21:00:00', '10.00', 'Giỏi');
INSERT INTO `practice_submissions` (`id`, `session_id`, `student_id`, `file_path`, `submitted_at`, `diem`, `nhan_xet`) VALUES ('2', '3', '2', '/tkb/assets/uploads/practice_submissions/lenhutkhanh_Le_Nhut_Khanh_session_3_1784038218.zip', '2026-07-14 21:10:18', NULL, NULL);
INSERT INTO `practice_submissions` (`id`, `session_id`, `student_id`, `file_path`, `submitted_at`, `diem`, `nhan_xet`) VALUES ('3', '4', '2', '/tkb/assets/uploads/practice_submissions/lenhutkhanh_Le_Nhut_Khanh_session_4_1784183060.zip', '2026-07-16 13:24:20', NULL, NULL);
INSERT INTO `practice_submissions` (`id`, `session_id`, `student_id`, `file_path`, `submitted_at`, `diem`, `nhan_xet`) VALUES ('4', '7', '2', '/tkb/assets/uploads/practice_submissions/lenhutkhanh_Le_Nhut_Khanh_session_7_1784205020.zip', '2026-07-16 19:30:20', '10.00', 'Giỏi');

DROP TABLE IF EXISTS `quiz_attempts`;
CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nhan_xet` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quiz_attempts` (`id`, `quiz_id`, `student_id`, `score`, `total_questions`, `attempted_at`, `nhan_xet`) VALUES ('7', '14', '2', '10', '40', '2026-07-16 19:31:40', 'Chào em, thầy/cô đã nhận được kết quả bài làm của em. Với 10/40 câu đúng, thầy/cô hiểu rằng nội dung này vẫn còn khá mới mẻ và thử thách đối với em, nhưng đừng quá lo lắng vì đây mới chỉ là bước khởi đầu thôi!\n\nĐể cải thiện kết quả trong lần tới, em hãy dành thời gian đọc lại kỹ phần lý thuyết trọng tâm và thử làm lại các câu hỏi mà mình chưa trả lời đúng để hiểu rõ bản chất vấn đề nhé. Thầy/cô tin rằng nếu kiên trì ôn tập, kết quả của em chắc chắn sẽ tiến bộ vượt bậc!');

DROP TABLE IF EXISTS `quiz_questions`;
CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `cau_hoi` text NOT NULL,
  `dap_an_a` varchar(255) DEFAULT NULL,
  `dap_an_b` varchar(255) DEFAULT NULL,
  `dap_an_c` varchar(255) DEFAULT NULL,
  `dap_an_d` varchar(255) DEFAULT NULL,
  `dap_an_dung` char(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=213 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('173', '14', 'Visual Studio .Net là:', 'Hệ điều hành mới của Microsoft', 'Trình soạn thảo văn bản', 'Môi trường phát triển tích hợp (IDE)', 'Trình duyệt web', 'C');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('174', '14', 'Phiên bản nào của Visual Studio .Net hỗ trợ phát triển ứng dụng đa nền tảng?', 'Visual Studio 2010', 'Visual Studio 2013', 'Visual Studio 2017', 'Visual Studio 2015', 'C');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('175', '14', 'Trong quá trình cài đặt Visual Studio .Net, bước nào sau đây là cần thiết?', 'Chọn các ngôn ngữ lập trình và công cụ phát triển', 'Cài đặt thư viện Python', 'Cấu hình trình duyệt web', 'Cập nhật trình điều khiển đồ họa', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('176', '14', 'Thành phần nào bắt buộc phải cài đặt để Visual Studio .Net hoạt động đúng cách?', '.Net Framework', 'SQL Server', 'Microsoft Office', 'Adobe Flash', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('177', '14', 'Phiên bản miễn phí của Visual Studio .Net được gọi là:', 'Visual Studio Community', 'Visual Studio Professional', 'Visual Studio Ultimate', 'Visual Studio Code', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('178', '14', 'Trong quá trình cài đặt Visual Studio .Net, tùy chọn Modify cho phép:', 'Gỡ bỏ Visual Studio', 'Thêm hoặc bớt các thành phần sau khi cài đặt', 'Tăng dung lượng ổ đĩa', 'Thay đổi ngôn ngữ lập trình', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('179', '14', 'Môi trường phát triển tích hợp (IDE) trong Visual Studio .Net bao gồm các công cụ chính nào?', 'Trình soạn thảo mã, trình gỡ lỗi, và trình biên dịch', 'Trình phát video và nhạc', 'Trình quản lý file hệ thống', 'Trình duyệt web', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('180', '14', 'Trong Visual Studio, cửa sổ nào hiển thị các lỗi sau khi biên dịch?', 'Solution Explorer', 'Error List', 'Output', 'Properties', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('181', '14', 'Solution Explorer trong Visual Studio .Net có vai trò gì?', 'Quản lý các tệp và dự án trong giải pháp', 'Hiển thị mã nguồn', 'Tạo và sửa lỗi lập trình', 'Quản lý cơ sở dữ liệu', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('182', '14', 'Khi lập trình trong Visual Studio, bạn có thể dùng phím tắt nào để chạy chương trình?', 'Ctrl + F4', 'F5', 'F12', 'Alt + Tab', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('183', '14', 'Cửa sổ Properties trong Visual Studio dùng để:', 'Hiển thị và chỉnh sửa các thuộc tính của đối tượng được chọn', 'Quản lý các thư viện mã nguồn', 'Hiển thị kết quả biên dịch', 'Kiểm tra lỗi lập trình', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('184', '14', '.NET Framework là một:', 'Bộ thư viện mã nguồn mở', 'Nền tảng phát triển ứng dụng đa ngôn ngữ', 'Trình duyệt web', 'Phần mềm diệt virus', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('185', '14', '.NET Framework hoạt động trên hệ điều hành nào?', 'Chỉ Windows', 'Windows và macOS', 'Đa nền tảng (Windows, macOS, và Linux)', 'Chỉ Linux', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('186', '14', '.NET Framework hỗ trợ những ngôn ngữ lập trình nào sau đây?', 'C# và VB.Net', 'Python và Java', 'HTML và CSS', 'SQL và MongoDB', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('187', '14', 'Để tạo một Project mới trong VB.Net, ta cần thực hiện thao tác nào đầu tiên?', 'Chọn File > New > Project', 'Chọn View > Toolbox', 'Chọn Edit > New File', 'Chọn Tools > Options', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('188', '14', 'Khi tạo Project mới trong VB.Net, bạn cần chọn loại dự án nào để tạo ứng dụng Windows Form?', 'Console App', 'Windows Forms App', 'Class Library', 'WPF Application', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('189', '14', 'Trong Windows Form Designer, công cụ nào giúp bạn kéo và thả các thành phần giao diện?', 'Solution Explorer', 'Toolbox', 'Properties Window', 'Output Window', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('190', '14', 'Để tạo một dự án sử dụng nhiều Forms, làm thế nào để mở một Form từ một Form khác?', 'Sử dụng lệnh Form2.Show()', 'Sử dụng lệnh Form2.Display()', 'Sử dụng lệnh Form2.Open()', 'Sử dụng lệnh Form2.Load()', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('191', '14', 'Kiểu dữ liệu nào trong VB.Net được sử dụng để lưu trữ chuỗi ký tự?', 'String', 'Char', 'Integer', 'DateTime', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('192', '14', 'Cú pháp để khai báo biến trong VB.Net là gì?', 'Set tên biến as Kiểu dữ liệu', 'Declare tên biến Kiểu dữ liệu', 'Dim tên biến As Kiểu dữ liệu', 'Var tên biến = Kiểu dữ liệu', 'C');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('193', '14', 'Sự khác biệt giữa tham trị (ByVal) và tham chiếu (ByRef) trong việc truyền biến vào hàm là gì?', 'ByVal truyền địa chỉ của biến, ByRef truyền giá trị', 'ByVal truyền bản sao của biến, ByRef truyền địa chỉ của biến', 'ByRef sử dụng ít bộ nhớ hơn ByVal', 'ByRef không thể thay đổi giá trị gốc', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('194', '14', 'Mảng trong VB.Net là gì?', 'Một tập hợp các biến có kiểu dữ liệu khác nhau', 'Một tập hợp các biến có cùng kiểu dữ liệu', 'Một hàm chứa nhiều giá trị', 'Một đối tượng lưu trữ duy nhất một giá trị', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('195', '14', 'Cú pháp để khai báo mảng trong VB.Net là gì?', 'Dim arrayName As datatype()', 'Var arrayName = datatype()', 'Define arrayName As datatype()', 'Declare arrayName As datatype()', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('196', '14', 'Cú pháp để truy cập phần tử thứ 3 của mảng trong VB.Net là gì?', 'arrayName(2)', 'arrayName(3)', 'arrayName[3]', 'arrayName[2]', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('197', '14', 'Câu lệnh nào dùng để thay đổi kích thước của mảng trong VB.Net?', 'Resize(arrayName, newSize)', 'Redim arrayName(newSize)', 'Array.Resize(arrayName, newSize)', 'Array.ReSize(arrayName)', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('198', '14', 'Trong VB.Net, toán tử nào dùng để tính lũy thừa?', '^', '*', '%', '+', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('199', '14', 'Toán tử Mod trong VB.Net dùng để làm gì?', 'Chia lấy phần nguyên', 'Chia lấy phần dư', 'Tính lũy thừa', 'Nhân hai số', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('200', '14', 'Kết quả của biểu thức 5 + 2 * 3 trong VB.Net là gì?', '21', '11', '7', '10', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('201', '14', 'Câu lệnh If trong VB.Net dùng để làm gì?', 'Lặp lại một khối lệnh', 'Thực hiện khối lệnh khi điều kiện đúng', 'Thực hiện phép tính toán học', 'Tạo một biến', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('202', '14', 'Câu lệnh If...Else có cú pháp nào đúng?', 'If condition Then...Else...End If', 'If condition...Else...End', 'If condition Then...Else If...End', 'If condition Then...Else Then...End', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('203', '14', 'Điều kiện x = 10 trong If sẽ thực hiện khối lệnh nào sau đây? If x = 10 Then Console.WriteLine(\"Equal to 10\") Else Console.WriteLine(\"Not equal to 10\") End If', 'Hiển thị \"Equal to 10\"', 'Hiển thị \"Not equal to 10\"', 'Lỗi cú pháp', 'Không có gì hiển thị', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('204', '14', 'Câu lệnh sau sẽ hiển thị kết quả gì nếu x = 7? If x > 10 Then Console.WriteLine(\"Greater than 10\") ElseIf x > 5 Then Console.WriteLine(\"Greater than 5\") Else Console.WriteLine(\"Less than or equal to 5\") End If', 'Hiển thị \"Greater than 10\"', 'Hiển thị \"Greater than 5\"', 'Hiển thị \"Less than or equal to 5\"', 'Lỗi chương trình', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('205', '14', 'Câu lệnh If lồng nhau là gì?', 'Câu lệnh If có nhiều điều kiện', 'Câu lệnh If nằm trong một câu lệnh If khác', 'Câu lệnh If nằm trong vòng lặp', 'Câu lệnh If với toán tử logic', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('206', '14', 'Kết quả của câu lệnh If lồng nhau này là gì nếu x = 3 và y = 10? If x > 0 Then If y > 5 Then Console.WriteLine(\"Both conditions true\") Else Console.WriteLine(\"x is true, y is false\") End If Else Console.WriteLine(\"x is false\") End If', '\"Both conditions true\"', '\"x is true, y is false\"', '\"x is false\"', 'Lỗi chương trình', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('207', '14', 'Câu lệnh Select Case nào dưới đây là đúng cú pháp?', 'Select Case x Case 1 MessageBox.Show(\"One\") Case 2 MessageBox.Show (\"Two\") End Select', 'Switch Case x Case 1 MessageBox.Show(\"One\") End Case', 'Select x Case 1 MessageBox.Show(\"One\") End Select', 'If Case 1 Then MessageBox.Show(\"One\") End Select', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('208', '14', 'Câu lệnh Select Case kiểm tra giá trị của x = 3 sẽ cho kết quả gì? Select Case x Case 1 MessageBox.Show(\"One\") Case 2 MessageBox.Show(\"Two\") Case 3 MessageBox.Show(\"Three\") Case Else MessageBox.Show(\"Other\") End Select', 'Hiển thị \"One\"', 'Hiển thị \"Two\"', 'Hiển thị \"Three\"', 'Hiển thị \"Other\"', 'C');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('209', '14', 'Từ khóa nào trong Select Case được sử dụng để kiểm tra trường hợp mặc định khi không có điều kiện nào thỏa mãn?', 'Else', 'Otherwise', 'Case Else', 'Default', 'C');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('210', '14', 'Trong vòng lặp For i = 1 To 5, giá trị ban đầu của biến đếm là:', '1', '5', '0', '-1', 'A');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('211', '14', 'Trong câu lệnh For i = 1 To 10 Step 2, giá trị của i tăng mỗi lần lặp là:', '1', '2', '3', '4', 'B');
INSERT INTO `quiz_questions` (`id`, `quiz_id`, `cau_hoi`, `dap_an_a`, `dap_an_b`, `dap_an_c`, `dap_an_d`, `dap_an_dung`) VALUES ('212', '14', 'Lệnh For i = 10 To 1 Step -1 sẽ:', 'Lặp từ 1 đến 10', 'Lặp từ 10 đến 1', 'Lặp từ 1 đến 10 với bước nhảy 1', 'Lặp từ 1 đến 10 với bước nhảy -1', 'B');

DROP TABLE IF EXISTS `quizzes`;
CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `giang_vien_id` int(11) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quizzes` (`id`, `giang_vien_id`, `mon_hoc_id`, `tieu_de`, `created_at`) VALUES ('14', '1', '9', 'Quiz tạo tự động bởi AI - 16/07/2026 19:15', '2026-07-16 19:15:48');

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ma_sv` varchar(20) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `lop` varchar(50) DEFAULT NULL,
  `khoa` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sdt` varchar(15) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `face_descriptor` text DEFAULT NULL,
  `banner` longtext DEFAULT NULL,
  `gioi_tinh` varchar(20) DEFAULT 'Nam',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_sv` (`ma_sv`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `students` (`id`, `user_id`, `ma_sv`, `ho_ten`, `ngay_sinh`, `lop`, `khoa`, `email`, `sdt`, `avatar`, `face_descriptor`, `banner`, `gioi_tinh`) VALUES ('2', '3', 'lenhutkhanh', 'Lê Nhựt Khánh', '2006-08-16', 'K24CDCNT1', 'Công Nghệ Thông Tin', 'lenhutkhanh.dvfb@gmail.com', '0373690565', NULL, '[-0.12687739729881287,0.11277750879526138,0.04492964595556259,0.006927445996552706,-0.0673842802643776,-0.06968624889850616,-0.06840413063764572,-0.1423778086900711,0.09565138071775436,-0.07337528467178345,0.29065588116645813,-0.026341751217842102,-0.22075164318084717,-0.1620970368385315,-0.08866340667009354,0.1836588978767395,-0.13606499135494232,-0.13130100071430206,-0.04604850709438324,0.05512750521302223,0.1209261566400528,-0.02363789826631546,0.06354798376560211,0.027821030467748642,-0.0195741169154644,-0.3713713586330414,-0.11652223020792007,-0.05897967889904976,0.028274090960621834,-0.005206659901887178,-0.06719711422920227,-0.00895854365080595,-0.20122385025024414,-0.08911295980215073,0.0574365071952343,0.054693613201379776,0.007914099842309952,-0.04080652445554733,0.19316120445728302,-0.017985330894589424,-0.22899846732616425,0.012442683801054955,0.03741888701915741,0.21353338658809662,0.16227330267429352,0.09327041357755661,0.07287950813770294,-0.1605440229177475,0.12403620779514313,-0.12915286421775818,0.12140947580337524,0.14821766316890717,0.14055009186267853,0.06323564797639847,0.011439663358032703,-0.16033406555652618,-0.02553766779601574,0.1406547576189041,-0.08384250849485397,0.04748411849141121,0.05882622301578522,-0.049076180905103683,-0.02139708772301674,-0.12169837206602097,0.21341994404792786,0.07433857768774033,-0.13519056141376495,-0.20370548963546753,0.1764078289270401,-0.10365431010723114,-0.07163526862859726,0.06704147160053253,-0.18043097853660583,-0.17646673321723938,-0.31206363439559937,0.05959107726812363,0.38914817571640015,0.09909353405237198,-0.18758094310760498,0.00029567204182967544,-0.06589934974908829,0.014468376524746418,0.05682291463017464,0.1606770157814026,0.002158011542633176,0.03558770939707756,-0.09339024871587753,-0.05718237906694412,0.22155362367630005,-0.0681786984205246,-0.07007269561290741,0.23459739983081818,-0.05245178937911987,0.10758573561906815,0.00259145675227046,0.0315011702477932,-0.03849339485168457,0.05089696869254112,-0.1107730120420456,-0.005747310817241669,0.013016141019761562,-0.04280215874314308,-0.0030329222790896893,0.08349190652370453,-0.10524426400661469,0.038348082453012466,-0.03643620014190674,0.11145579069852829,0.011193822138011456,0.002883776556700468,-0.11638692021369934,-0.08069677650928497,0.08417462557554245,-0.18760289251804352,0.3044377267360687,0.15600234270095825,0.10464012622833252,0.1102592796087265,0.15831853449344635,0.11243200302124023,0.0535515733063221,-0.05535522475838661,-0.17347626388072968,-0.046218398958444595,0.11078246682882309,-0.0101974718272686,0.0777319073677063,0.01589214615523815]', NULL, 'Nam');
INSERT INTO `students` (`id`, `user_id`, `ma_sv`, `ho_ten`, `ngay_sinh`, `lop`, `khoa`, `email`, `sdt`, `avatar`, `face_descriptor`, `banner`, `gioi_tinh`) VALUES ('6', '15', 'leduykhanh', 'Lê Duy Khánh', NULL, 'CNTT24A', 'Công nghệ thông tin', 'lenhutkhanh.tricker@gmail.com', '0373690560', NULL, NULL, NULL, 'Nữ');

DROP TABLE IF EXISTS `submissions`;
CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submission_text` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `grade` decimal(4,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assignment_id` (`assignment_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `hanh_dong` text NOT NULL,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('1', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-10 18:59:04', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('2', '1', 'admin', 'Đăng nhập hệ thống (Vai trò: admin)', '2026-07-10 19:03:36', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('3', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-10 19:06:05', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('4', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-10 19:09:37', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('5', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-10 19:25:37', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('6', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-10 19:30:52', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('7', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-10 20:00:46', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('8', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-11 19:39:14', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('9', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-12 15:20:42', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('10', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-12 15:26:46', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('11', '1', 'admin', 'Đăng nhập hệ thống (Vai trò: admin)', '2026-07-12 15:27:25', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('12', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-12 15:28:42', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('13', '1', 'admin', 'Đăng nhập hệ thống (Vai trò: admin)', '2026-07-12 15:37:36', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('14', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-12 15:40:43', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('15', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-12 15:45:26', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('16', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-12 15:48:54', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('17', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-12 15:49:29', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('18', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-12 16:02:04', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('19', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-12 16:08:26', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('20', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-12 16:35:28', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('21', '1', 'admin', 'Đăng nhập hệ thống (Vai trò: admin)', '2026-07-12 16:36:59', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('22', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-12 16:40:15', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('23', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-12 16:41:45', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('24', '10', 'gv001', 'Cập nhật thông tin giảng viên ID: 1', '2026-07-12 16:42:01', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('25', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-13 13:16:11', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('26', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-13 19:09:50', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('27', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-13 19:40:20', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('28', '10', 'gv001', 'Cập nhật sinh viên ID: 2', '2026-07-13 19:41:45', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('29', '10', 'gv001', 'Cập nhật sinh viên ID: 2', '2026-07-13 19:41:47', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('30', '10', 'gv001', 'Cập nhật sinh viên ID: 2', '2026-07-13 19:41:49', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('31', '10', 'gv001', 'Cập nhật sinh viên ID: 2', '2026-07-13 19:41:49', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('32', '10', 'gv001', 'Cập nhật sinh viên ID: 2', '2026-07-13 19:41:49', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('33', '10', 'gv001', 'Cập nhật sinh viên ID: 2', '2026-07-13 19:41:50', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('34', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-13 19:43:21', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('35', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-13 20:02:15', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('36', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-13 20:13:56', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('37', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-13 20:19:07', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('38', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-13 20:28:38', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('39', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-13 20:32:28', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('40', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-13 20:49:16', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('41', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-13 20:49:52', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('42', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-13 20:51:31', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('43', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-13 21:00:21', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('44', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-13 21:00:37', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('45', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-14 12:04:14', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('46', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-14 12:13:19', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('47', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-14 20:29:39', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('48', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-14 21:04:59', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('49', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-14 21:05:35', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('50', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-14 21:05:55', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('51', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-14 21:07:05', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('52', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-14 21:11:20', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('53', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 13:19:33', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('54', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 13:22:50', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('55', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 13:27:22', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('56', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 14:33:13', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('57', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 14:37:35', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('58', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 14:42:56', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('59', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 14:43:39', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('60', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 14:44:39', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('61', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 14:45:30', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('62', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 15:03:26', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('63', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 15:12:30', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('64', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 15:19:33', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('65', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 19:13:25', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('66', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 19:14:25', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('67', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 19:15:59', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('68', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 19:17:16', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('69', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 19:17:33', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('70', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 19:18:30', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('71', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 19:19:20', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('72', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 19:24:23', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('73', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 19:25:24', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('74', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 19:29:17', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('75', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 19:29:38', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('76', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 19:32:19', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('77', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 19:53:01', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('78', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 19:53:59', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('79', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 19:54:47', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('80', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-16 19:55:19', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('81', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 20:00:17', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('82', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-16 20:46:43', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('83', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-18 14:01:40', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('84', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 12:22:59', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('85', '1', 'admin', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-29 13:01:01', '127.0.0.1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('86', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-29 13:01:20', '127.0.0.1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('87', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-29 13:02:08', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('88', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 13:04:09', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('89', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-29 14:34:59', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('90', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 14:47:00', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('91', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-29 15:01:21', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('92', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 15:58:09', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('93', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-29 16:07:22', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('94', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 16:13:08', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('95', '10', 'gv001', 'Đăng nhập hệ thống (Vai trò: teacher)', '2026-07-29 16:56:40', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('96', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 17:16:40', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('97', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 21:24:26', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('98', '15', 'leduykhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 21:33:23', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('99', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 21:41:20', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('100', '15', 'leduykhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 21:41:51', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('101', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 21:51:56', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('102', '15', 'leduykhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 21:52:28', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('103', '3', 'lenhutkhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 22:36:52', '::1');
INSERT INTO `system_logs` (`id`, `user_id`, `username`, `hanh_dong`, `ngay_tao`, `ip_address`) VALUES ('104', '15', 'leduykhanh', 'Đăng nhập hệ thống (Vai trò: student)', '2026-07-29 22:37:20', '::1');

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `key` varchar(50) NOT NULL,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`key`, `value`) VALUES ('default_lang', 'vi');
INSERT INTO `system_settings` (`key`, `value`) VALUES ('default_theme', 'light');
INSERT INTO `system_settings` (`key`, `value`) VALUES ('site_banner', '');
INSERT INTO `system_settings` (`key`, `value`) VALUES ('site_logo', '');
INSERT INTO `system_settings` (`key`, `value`) VALUES ('site_name', 'Hệ thống Quản lý LMS VKC');
INSERT INTO `system_settings` (`key`, `value`) VALUES ('system_email', 'admin@vkc.edu.vn');
INSERT INTO `system_settings` (`key`, `value`) VALUES ('timezone', 'Asia/Ho_Chi_Minh');

DROP TABLE IF EXISTS `tai_chinh`;
CREATE TABLE `tai_chinh` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `noi_dung` varchar(255) NOT NULL,
  `so_tien` decimal(15,2) NOT NULL,
  `ngay_nop` date DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'Chưa nộp',
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `tai_chinh_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tai_chinh` (`id`, `student_id`, `noi_dung`, `so_tien`, `ngay_nop`, `trang_thai`) VALUES ('1', '2', 'Đóng học phí', '2000000.00', '2026-04-30', 'Đã nộp');

DROP TABLE IF EXISTS `tai_lieu`;
CREATE TABLE `tai_lieu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `giang_vien_id` int(11) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `ten_tai_lieu` varchar(255) NOT NULL,
  `link_download` varchar(555) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `thoi_khoa_bieu`;
CREATE TABLE `thoi_khoa_bieu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mon_hoc_id` int(11) NOT NULL,
  `giang_vien_id` int(11) DEFAULT NULL,
  `khoa` varchar(100) NOT NULL,
  `thu` int(11) NOT NULL,
  `tiet_bat_dau` int(11) NOT NULL,
  `tiet_ket_thuc` int(11) NOT NULL,
  `phong_hoc` varchar(20) DEFAULT NULL,
  `hoc_ky` varchar(20) DEFAULT NULL,
  `nam_hoc` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mon_hoc_id` (`mon_hoc_id`),
  KEY `giang_vien_id` (`giang_vien_id`),
  CONSTRAINT `thoi_khoa_bieu_ibfk_1` FOREIGN KEY (`mon_hoc_id`) REFERENCES `mon_hoc` (`id`) ON DELETE CASCADE,
  CONSTRAINT `thoi_khoa_bieu_ibfk_2` FOREIGN KEY (`giang_vien_id`) REFERENCES `giang_vien` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `thoi_khoa_bieu` (`id`, `mon_hoc_id`, `giang_vien_id`, `khoa`, `thu`, `tiet_bat_dau`, `tiet_ket_thuc`, `phong_hoc`, `hoc_ky`, `nam_hoc`) VALUES ('1', '9', '1', 'Công Nghệ Thông Tin', '2', '1', '4', '302', 'HK1', '2026-2027');
INSERT INTO `thoi_khoa_bieu` (`id`, `mon_hoc_id`, `giang_vien_id`, `khoa`, `thu`, `tiet_bat_dau`, `tiet_ket_thuc`, `phong_hoc`, `hoc_ky`, `nam_hoc`) VALUES ('3', '9', '2', 'Công Nghệ Thông Tin', '2', '5', '8', '302', 'HK1', '2026-2027');

DROP TABLE IF EXISTS `thong_bao`;
CREATE TABLE `thong_bao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tieu_de` varchar(255) NOT NULL,
  `noi_dung` text DEFAULT NULL,
  `ngay_dang` date DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'Đã xuất bản',
  `khoa` varchar(100) DEFAULT NULL,
  `gui_den_loai` varchar(50) DEFAULT 'toan_truong',
  `gui_den_gia_tri` varchar(255) DEFAULT NULL,
  `ngay_dang_dat_lich` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `thong_bao` (`id`, `tieu_de`, `noi_dung`, `ngay_dang`, `trang_thai`, `khoa`, `gui_den_loai`, `gui_den_gia_tri`, `ngay_dang_dat_lich`) VALUES ('1', 'Thời Hạn Học Phí', 'Đóng học phí trễ nhất 5/5/2026', '2026-04-30', 'Đã xuất bản', 'Công Nghệ Thông Tin', 'toan_truong', NULL, NULL);

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `ho_ten` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student','teacher','principal') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL,
  `face_descriptor` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sdt` varchar(15) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `ho_ten`, `password`, `role`, `created_at`, `avatar`, `face_descriptor`, `email`, `sdt`, `reset_token`, `reset_token_expires`) VALUES ('1', 'admin', 'Lê Nhựt Khánh', '$2y$10$sQlrzpzQtudJg4YGXXi8PevCO5msZAzlG94f8i4RstxdzaJls1n32', 'teacher', '2026-04-29 19:35:30', 'admin_1_1777525453.jpg', '[-0.10822830349206924,0.12030898034572601,0.050043340772390366,-0.04293946921825409,-0.06332093477249146,-0.09492220729589462,-0.056549280881881714,-0.12498536705970764,0.13380418717861176,-0.06516928225755692,0.2998863160610199,-0.027897309511899948,-0.22547787427902222,-0.15491768717765808,-0.049988798797130585,0.17561650276184082,-0.1674271523952484,-0.12637336552143097,-0.02679765596985817,0.055446431040763855,0.102005235850811,-0.02540951408445835,0.022709960117936134,0.04903966933488846,-0.009002125822007656,-0.3479013442993164,-0.1361556500196457,-0.01997693069279194,0.053366098552942276,-0.028834139928221703,-0.06839770078659058,-0.003826749976724386,-0.21576474606990814,-0.1264239102602005,0.03951887786388397,0.07135499268770218,0.0024668106343597174,-0.05077136680483818,0.16000692546367645,-0.05036322772502899,-0.21576027572155,0.021075783297419548,0.05357852578163147,0.21775206923484802,0.19073887169361115,0.09243602305650711,0.06800871342420578,-0.152298241853714,0.11270463466644287,-0.1118725910782814,0.10464818775653839,0.1459800899028778,0.1331517994403839,0.06159723550081253,-0.014540446922183037,-0.17864394187927246,-0.03522689640522003,0.16477574408054352,-0.10694406926631927,0.03944471850991249,0.07053107023239136,-0.057218167930841446,-0.023778775706887245,-0.10685442388057709,0.20618987083435059,0.05512259900569916,-0.13261182606220245,-0.22212642431259155,0.1702360361814499,-0.1301264464855194,-0.07661289721727371,0.05082640424370766,-0.17507652938365936,-0.16657216846942902,-0.32279330492019653,0.062239356338977814,0.3483308255672455,0.06657767295837402,-0.18184617161750793,0.06359949707984924,-0.03914494812488556,0.007416948210448027,0.049205657094717026,0.15366636216640472,-0.0033262264914810658,0.05669611692428589,-0.11596052348613739,-0.04223201796412468,0.22026021778583527,-0.05033858120441437,-0.030733149498701096,0.21709932386875153,-0.050204962491989136,0.08530648052692413,0.03277280181646347,0.04801274836063385,-0.03205031529068947,0.05254349112510681,-0.13361699879169464,0.0008149929344654083,0.03182945400476456,-0.06709203124046326,-0.025371050462126732,0.08730700612068176,-0.11837742477655411,0.08284533768892288,-0.01017078012228012,0.1120714619755745,-0.006220655515789986,-0.01679919846355915,-0.09132231771945953,-0.08075983822345734,0.11481103301048279,-0.194565549492836,0.29635509848594666,0.15543785691261292,0.08556100726127625,0.1235140934586525,0.13439412415027618,0.13135573267936707,0.04152664542198181,-0.04913688451051712,-0.1663709133863449,-0.035936009138822556,0.09170816838741302,-0.029949042946100235,0.12053248286247253,0.03244199976325035]', 'admin@vkc.edu.vn', '', NULL, NULL);
INSERT INTO `users` (`id`, `username`, `ho_ten`, `password`, `role`, `created_at`, `avatar`, `face_descriptor`, `email`, `sdt`, `reset_token`, `reset_token_expires`) VALUES ('3', 'lenhutkhanh', 'Lê Nhựt Khánh', '$2y$10$sQlrzpzQtudJg4YGXXi8PevCO5msZAzlG94f8i4RstxdzaJls1n32', 'student', '2026-04-30 10:41:22', NULL, NULL, 'lenhutkhanh.dvfb@gmail.com', NULL, NULL, NULL);
INSERT INTO `users` (`id`, `username`, `ho_ten`, `password`, `role`, `created_at`, `avatar`, `face_descriptor`, `email`, `sdt`, `reset_token`, `reset_token_expires`) VALUES ('6', 'admin2', 'Lê Anh Thư', '$2y$10$D2Mycwl/VxNDSo3NmptheuX8PjtcU73c60wejBSqnfaGcHmgob67i', 'teacher', '2026-04-30 14:19:21', NULL, NULL, 'admin2@vkc.edu.vn', '', NULL, NULL);
INSERT INTO `users` (`id`, `username`, `ho_ten`, `password`, `role`, `created_at`, `avatar`, `face_descriptor`, `email`, `sdt`, `reset_token`, `reset_token_expires`) VALUES ('10', 'gv001', 'Phan Ngọc Tuyền', '$2y$10$Z6UDNt87iXdDanH1LNxA1OYBEgpHvHzVIC5.10OjGy0wpZoGm4rQ6', 'teacher', '2026-06-16 07:19:47', NULL, NULL, 'gv001@vkc.edu.vn', NULL, NULL, NULL);
INSERT INTO `users` (`id`, `username`, `ho_ten`, `password`, `role`, `created_at`, `avatar`, `face_descriptor`, `email`, `sdt`, `reset_token`, `reset_token_expires`) VALUES ('11', 'gv002', 'Trần Thị Bình', '$2y$10$Z6UDNt87iXdDanH1LNxA1OYBEgpHvHzVIC5.10OjGy0wpZoGm4rQ6', 'teacher', '2026-06-16 07:19:47', NULL, NULL, 'gv002@vkc.edu.vn', NULL, NULL, NULL);
INSERT INTO `users` (`id`, `username`, `ho_ten`, `password`, `role`, `created_at`, `avatar`, `face_descriptor`, `email`, `sdt`, `reset_token`, `reset_token_expires`) VALUES ('12', 'gv003', 'Lê Hoàng Cường', '$2y$10$Z6UDNt87iXdDanH1LNxA1OYBEgpHvHzVIC5.10OjGy0wpZoGm4rQ6', 'teacher', '2026-06-16 07:19:47', NULL, NULL, 'gv003@vkc.edu.vn', NULL, NULL, NULL);
INSERT INTO `users` (`id`, `username`, `ho_ten`, `password`, `role`, `created_at`, `avatar`, `face_descriptor`, `email`, `sdt`, `reset_token`, `reset_token_expires`) VALUES ('13', 'gv004', 'Phạm Thị Dung', '$2y$10$Z6UDNt87iXdDanH1LNxA1OYBEgpHvHzVIC5.10OjGy0wpZoGm4rQ6', 'teacher', '2026-06-16 07:19:47', NULL, NULL, 'gv004@vkc.edu.vn', NULL, NULL, NULL);
INSERT INTO `users` (`id`, `username`, `ho_ten`, `password`, `role`, `created_at`, `avatar`, `face_descriptor`, `email`, `sdt`, `reset_token`, `reset_token_expires`) VALUES ('15', 'leduykhanh', NULL, '$2y$10$dFS3NXH4dJ54/2ZqL0dJU.rmDILxG6PuP7vCyrcGKzQexPigBv82W', 'student', '2026-07-29 21:32:53', NULL, NULL, NULL, NULL, NULL, NULL);

SET FOREIGN_KEY_CHECKS=1;
