-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: sql308.infinityfree.com
-- Thời gian đã tạo: Th6 06, 2026 lúc 09:39 PM
-- Phiên bản máy phục vụ: 11.4.12-MariaDB
-- Phiên bản PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `if0_41796593_truong_caodang`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `diem`
--

CREATE TABLE `diem` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `hoc_ky` varchar(20) DEFAULT NULL,
  `nam_hoc` varchar(20) DEFAULT NULL,
  `diem_giua_ky` decimal(4,2) DEFAULT NULL,
  `diem_cuoi_ky` decimal(4,2) DEFAULT NULL,
  `diem_tong_ket` decimal(4,2) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `diem`
--

INSERT INTO `diem` (`id`, `student_id`, `mon_hoc_id`, `hoc_ky`, `nam_hoc`, `diem_giua_ky`, `diem_cuoi_ky`, `diem_tong_ket`, `ghi_chu`) VALUES
(7, 2, 7, 'HK1', '2024-2025', '10.00', '10.00', '10.00', 'Xuất Sắc');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `diem_danh`
--

CREATE TABLE `diem_danh` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `ngay_diem_danh` date DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'Có mặt',
  `ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `diem_danh`
--

INSERT INTO `diem_danh` (`id`, `student_id`, `mon_hoc_id`, `ngay_diem_danh`, `trang_thai`, `ghi_chu`) VALUES
(2, 2, 7, '2026-04-30', 'Có mặt', ''),
(4, 2, 7, '2026-05-11', 'Có mặt', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giang_vien`
--

CREATE TABLE `giang_vien` (
  `id` int(11) NOT NULL,
  `ma_gv` varchar(20) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `khoa` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `giang_vien`
--

INSERT INTO `giang_vien` (`id`, `ma_gv`, `ho_ten`, `khoa`) VALUES
(1, 'GV001', 'Nguyễn Văn An', 'Công nghệ thông tin'),
(2, 'GV002', 'Trần Thị Bình', 'Công nghệ thông tin'),
(3, 'GV003', 'Lê Hoàng Cường', 'Toán - Lý'),
(4, 'GV004', 'Phạm Thị Dung', 'Ngoại ngữ');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `mon_hoc`
--

CREATE TABLE `mon_hoc` (
  `id` int(11) NOT NULL,
  `ma_mon` varchar(20) NOT NULL,
  `ten_mon` varchar(100) NOT NULL,
  `so_tin_chi` int(11) DEFAULT 2,
  `khoa` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `mon_hoc`
--

INSERT INTO `mon_hoc` (`id`, `ma_mon`, `ten_mon`, `so_tin_chi`, `khoa`) VALUES
(7, '01', 'Lập Trình Web', 2, 'Công Nghệ Thông Tin'),
(8, '02', 'SQL Severr', 2, 'Công Nghệ Thông Tin');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ma_sv` varchar(20) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `lop` varchar(50) DEFAULT NULL,
  `khoa` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sdt` varchar(15) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `face_descriptor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `students`
--

INSERT INTO `students` (`id`, `user_id`, `ma_sv`, `ho_ten`, `ngay_sinh`, `lop`, `khoa`, `email`, `sdt`, `avatar`, `face_descriptor`) VALUES
(2, 3, 'lenhutkhanh', 'Lê Nhựt Khánh', NULL, 'CNTT24A', 'Công nghệ thông tin', 'lenhutkhanh.dvfb@gmail.com', '0373690565', 'avatar_2_1777522196.jpg', '[-0.11217739433050156,0.1311175376176834,0.03594674542546272,-0.03868484869599342,-0.07105693221092224,-0.014139031991362572,-0.09347783774137497,-0.13061778247356415,0.1091151013970375,-0.08335938304662704,0.2697084844112396,0.00548799941316247,-0.24187014997005463,-0.15878506004810333,-0.057707834988832474,0.16560561954975128,-0.12023930996656418,-0.14635157585144043,-0.03718794509768486,0.06786872446537018,0.11842922866344452,-0.022392669692635536,0.016275689005851746,-0.00039426932926289737,-0.03768352419137955,-0.34459537267684937,-0.10381238162517548,-0.026734396815299988,-0.005370498634874821,-0.038145482540130615,-0.08508774638175964,0.01809483952820301,-0.17847409844398499,-0.08151115477085114,0.06184184551239014,0.11118544638156891,0.007587866857647896,-0.0223881546407938,0.16846366226673126,-0.0305729229003191,-0.23671726882457733,0.02356438897550106,0.040309444069862366,0.2452673465013504,0.15786205232143402,0.0532044991850853,0.06608694046735764,-0.15338100492954254,0.12774929404258728,-0.11617610603570938,0.10358196496963501,0.16908620297908783,0.18872733414173126,0.06359297037124634,0.019260387867689133,-0.1487763226032257,-0.01420630607753992,0.12462513893842697,-0.06985054910182953,0.008335324004292488,0.0832696482539177,-0.0768192857503891,-0.021746044978499413,-0.14808432757854462,0.22156046330928802,0.04789525642991066,-0.1589883267879486,-0.2055019587278366,0.12349972128868103,-0.07223942130804062,-0.09290792793035507,0.041248537600040436,-0.18936826288700104,-0.16788747906684875,-0.3107164204120636,0.0628572404384613,0.3347381055355072,0.09978633373975754,-0.24398273229599,0.04345285892486572,-0.06690697371959686,0.0045965914614498615,0.05018116533756256,0.17545881867408752,0.007826665416359901,0.08967557549476624,-0.09324756264686584,-0.05154845491051674,0.22586679458618164,-0.09394580870866776,-0.022144554182887077,0.205999493598938,-0.02230829745531082,0.09562699496746063,0.014001134783029556,0.03127487003803253,-0.04758508875966072,0.03618064150214195,-0.13012781739234924,-0.006951410323381424,0.001301841693930328,-0.06945069879293442,-0.002548226621001959,0.09989196062088013,-0.1246376782655716,0.07986103743314743,-0.012898650020360947,0.11143545806407928,0.020601438358426094,-0.012139098718762398,-0.09147608280181885,-0.1165824607014656,0.09115052968263626,-0.2116125375032425,0.24367336928844452,0.15002819895744324,0.07959340512752533,0.10665715485811234,0.17144852876663208,0.14144060015678406,0.03619835525751114,-0.020381653681397438,-0.16265378892421722,-0.050896819680929184,0.1097908541560173,0.00382254202850163,0.06958508491516113,0.011769254691898823]');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tai_chinh`
--

CREATE TABLE `tai_chinh` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `noi_dung` varchar(255) NOT NULL,
  `so_tien` decimal(15,2) NOT NULL,
  `ngay_nop` date DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'Chưa nộp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tai_chinh`
--

INSERT INTO `tai_chinh` (`id`, `student_id`, `noi_dung`, `so_tien`, `ngay_nop`, `trang_thai`) VALUES
(1, 2, 'Đóng học phí', '2000000.00', '2026-04-30', 'Đã nộp');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thoi_khoa_bieu`
--

CREATE TABLE `thoi_khoa_bieu` (
  `id` int(11) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `giang_vien_id` int(11) DEFAULT NULL,
  `khoa` varchar(100) NOT NULL,
  `thu` int(11) NOT NULL,
  `tiet_bat_dau` int(11) NOT NULL,
  `tiet_ket_thuc` int(11) NOT NULL,
  `phong_hoc` varchar(20) DEFAULT NULL,
  `hoc_ky` varchar(20) DEFAULT NULL,
  `nam_hoc` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `thoi_khoa_bieu`
--

INSERT INTO `thoi_khoa_bieu` (`id`, `mon_hoc_id`, `giang_vien_id`, `khoa`, `thu`, `tiet_bat_dau`, `tiet_ket_thuc`, `phong_hoc`, `hoc_ky`, `nam_hoc`) VALUES
(7, 7, 3, 'Công Nghệ Thông Tin', 2, 1, 4, '201', 'HK1', '2026-2027');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thong_bao`
--

CREATE TABLE `thong_bao` (
  `id` int(11) NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `noi_dung` text DEFAULT NULL,
  `ngay_dang` date DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'Đã xuất bản',
  `khoa` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `thong_bao`
--

INSERT INTO `thong_bao` (`id`, `tieu_de`, `noi_dung`, `ngay_dang`, `trang_thai`, `khoa`) VALUES
(2, 'Keria Yêu Mimeo', 'Lê Nhựt Khánh Yêu Lê Anh Thư', '2026-05-12', 'Đã xuất bản', 'Công Nghệ Thông Tin');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ho_ten` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL,
  `face_descriptor` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sdt` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `ho_ten`, `password`, `role`, `created_at`, `avatar`, `face_descriptor`, `email`, `sdt`) VALUES
(1, 'admin', 'Lê Nhựt Khánh', '$2y$10$1O99AdBofLxk8dpJZq40oeZ4JOBOT9f/WpLEBhG4QMdegqL3y.mO.', 'admin', '2026-04-29 12:35:30', 'admin_1_1777525453.jpg', '[-0.13224250078201294,0.11037543416023254,0.045630425214767456,0.003224755870178342,-0.06751459091901779,-0.03254717215895653,-0.08349376916885376,-0.14011918008327484,0.12779290974140167,-0.056525055319070816,0.29781949520111084,0.0037909923121333122,-0.23219718039035797,-0.1809883862733841,-0.07029233127832413,0.1836707890033722,-0.14438463747501373,-0.15182684361934662,-0.03184220939874649,0.06554815173149109,0.11332261562347412,-0.03395230323076248,0.012457279488444328,0.023258449509739876,-0.04257046803832054,-0.34775957465171814,-0.11050740629434586,-0.059012189507484436,-0.004398427437990904,-0.034024812281131744,-0.10140923410654068,0.04026021063327789,-0.16257621347904205,-0.09567691385746002,0.05798414722084999,0.09988052397966385,0.01682499423623085,-0.009491879492998123,0.14540840685367584,-0.060170404613018036,-0.22542838752269745,0.021412154659628868,0.05797658860683441,0.2198619693517685,0.1923442780971527,0.09584358334541321,0.03806880861520767,-0.1464122086763382,0.11019265651702881,-0.14463591575622559,0.10173863172531128,0.15578119456768036,0.18233737349510193,0.0653674528002739,0.027551518753170967,-0.17670240998268127,-0.0466625802218914,0.13001157343387604,-0.0883762463927269,0.02104181796312332,0.038145676255226135,-0.08064109086990356,-0.041974786669015884,-0.15051138401031494,0.20846502482891083,0.09510068595409393,-0.14876261353492737,-0.19285959005355835,0.14578214287757874,-0.11615747958421707,-0.10925986617803574,0.03691764920949936,-0.18035800755023956,-0.19299645721912384,-0.3306941092014313,0.05436459183692932,0.34896349906921387,0.11387286335229874,-0.2556503713130951,0.038909051567316055,-0.013321870937943459,0.020076708868145943,0.05224579572677612,0.16262412071228027,-0.011727813631296158,0.059592243283987045,-0.10784227401018143,-0.0946728065609932,0.19299103319644928,-0.061982665210962296,-0.013729959726333618,0.22633571922779083,-0.04332689940929413,0.1063373014330864,0.030730683356523514,0.030062923207879066,-0.01007999386638403,0.03660601004958153,-0.1473807990550995,-0.02698289044201374,0.007200732361525297,-0.04216907173395157,0.0008529082406312227,0.07747925072908401,-0.0897732675075531,0.044405627995729446,-0.020319269970059395,0.12895941734313965,0.020009322091937065,-0.008561782538890839,-0.08605644106864929,-0.10315600037574768,0.11135297268629074,-0.2056857943534851,0.2531665861606598,0.14928805828094482,0.06606917083263397,0.12318325787782669,0.15628094971179962,0.15242455899715424,0.0220278799533844,-0.03443396836519241,-0.17055855691432953,-0.031172534450888634,0.12391331046819687,-0.03966549038887024,0.0654270276427269,0.020606689155101776]', '', ''),
(3, 'lenhutkhanh', NULL, '$2y$10$Ibh5QHSNAtXoOsmDGZTlHu80UmYMjEFJ6M62.tqB9/M.yY6p7OXYu', 'student', '2026-04-30 03:41:22', NULL, NULL, NULL, NULL),
(6, 'admin2', 'Lê Anh Thư', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-04-30 07:19:21', NULL, NULL, '', '');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `diem`
--
ALTER TABLE `diem`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sv_mon_hk` (`student_id`,`mon_hoc_id`,`hoc_ky`,`nam_hoc`),
  ADD KEY `mon_hoc_id` (`mon_hoc_id`);

--
-- Chỉ mục cho bảng `diem_danh`
--
ALTER TABLE `diem_danh`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `mon_hoc_id` (`mon_hoc_id`);

--
-- Chỉ mục cho bảng `giang_vien`
--
ALTER TABLE `giang_vien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_gv` (`ma_gv`);

--
-- Chỉ mục cho bảng `mon_hoc`
--
ALTER TABLE `mon_hoc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_mon` (`ma_mon`);

--
-- Chỉ mục cho bảng `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_sv` (`ma_sv`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `tai_chinh`
--
ALTER TABLE `tai_chinh`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Chỉ mục cho bảng `thoi_khoa_bieu`
--
ALTER TABLE `thoi_khoa_bieu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mon_hoc_id` (`mon_hoc_id`),
  ADD KEY `giang_vien_id` (`giang_vien_id`);

--
-- Chỉ mục cho bảng `thong_bao`
--
ALTER TABLE `thong_bao`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `diem`
--
ALTER TABLE `diem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `diem_danh`
--
ALTER TABLE `diem_danh`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `giang_vien`
--
ALTER TABLE `giang_vien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `mon_hoc`
--
ALTER TABLE `mon_hoc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `tai_chinh`
--
ALTER TABLE `tai_chinh`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `thoi_khoa_bieu`
--
ALTER TABLE `thoi_khoa_bieu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `thong_bao`
--
ALTER TABLE `thong_bao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `diem`
--
ALTER TABLE `diem`
  ADD CONSTRAINT `diem_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diem_ibfk_2` FOREIGN KEY (`mon_hoc_id`) REFERENCES `mon_hoc` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `diem_danh`
--
ALTER TABLE `diem_danh`
  ADD CONSTRAINT `diem_danh_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diem_danh_ibfk_2` FOREIGN KEY (`mon_hoc_id`) REFERENCES `mon_hoc` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `tai_chinh`
--
ALTER TABLE `tai_chinh`
  ADD CONSTRAINT `tai_chinh_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `thoi_khoa_bieu`
--
ALTER TABLE `thoi_khoa_bieu`
  ADD CONSTRAINT `thoi_khoa_bieu_ibfk_1` FOREIGN KEY (`mon_hoc_id`) REFERENCES `mon_hoc` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `thoi_khoa_bieu_ibfk_2` FOREIGN KEY (`giang_vien_id`) REFERENCES `giang_vien` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
