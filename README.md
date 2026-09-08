# 🎓 SmartEdu AI — Learning Analytics & Personalized Advisory System
### Hệ Thống Phân Tích Dữ Liệu Học Tập & Cố Vấn Đào Tạo Cá Nhân Hóa Dựa Trên Trí Tuệ Nhân Tạo

> **DỰ THI: HACKATHON TRÍ TUỆ NHÂN TẠO & PHÂN TÍCH DỮ LIỆU — BẢNG C**  
> **Đơn vị:** Trường Cao Đẳng Kỹ Thuật & Công Nghệ Cà Mau (Việt - Hàn)  
> **Phiên bản:** v2.4 (Production & Hackathon Ready)

---

![Python](https://img.shields.io/badge/Python-3.12-3776AB?style=for-the-badge&logo=python&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Accuracy](https://img.shields.io/badge/Accuracy-96.0%25-10B981?style=for-the-badge)
![F1-Score](https://img.shields.io/badge/F1--Score-0.9597-8B5CF6?style=for-the-badge)
![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)

---

## 🌟 1. TRẢI NGHIỆM TRỰC TUYẾN NGAY (LIVE DEMO)

Ban Giám khảo và người dùng có thể trải nghiệm toàn diện hệ thống đang chạy thực tế mà **KHÔNG CẦN CÀI ĐẶT**:

🔗 **Đường dẫn Live Demo:** [https://viethan.free.nf/tkb/](https://viethan.free.nf/tkb/)

### 🔑 Tài khoản Đăng nhập Dành cho Ban Giám Khảo:
| Phân hệ | Tài khoản (Username) | Mật khẩu | Đường dẫn truy cập trực tiếp |
| :--- | :---: | :---: | :--- |
| **Quản trị viên (Admin)** | `admin` | `admin123` | [https://viethan.free.nf/tkb/admin/dashboard.php](https://viethan.free.nf/tkb/admin/dashboard.php) |
| **Giảng viên (Teacher)** | `phanngoctuyen` | `123456` | [https://viethan.free.nf/tkb/teacher/ai_analytics.php](https://viethan.free.nf/tkb/teacher/ai_analytics.php) |
| **Sinh viên (Student)** | `lenhutkhanh` | `123456` | [https://viethan.free.nf/tkb/student/ai_advisor.php](https://viethan.free.nf/tkb/student/ai_advisor.php) |

---

## 🚀 2. HƯỚNG DẪN CÀI ĐẶT & CHẠY LOCAL (DÀNH CHO GIÁM KHẢO KIỂM TRA MÃ NGUỒN)

Nếu Ban Giám khảo muốn tải về và khởi chạy kiểm thử mã nguồn trên máy cá nhân:

### 2.1. Kiểm thử Nhanh Pipeline Học Máy (Machine Learning & AI Metrics)
Yêu cầu: Máy tính đã cài đặt Python 3.10+.

```powershell
# 1. Cài đặt các thư viện cần thiết
pip install -r requirements.txt

# 2. Chạy pipeline huấn luyện, tính toán F1-score, Ma trận nhầm lẫn & Ablation
python ai_engine/train_evaluate.py

# 3. Chạy thử nghiệm service suy luận thời gian thực
python ai_engine/predict_service.py
```
*(Trên Windows, có thể nhấp đúp trực tiếp vào file `run_ai_pipeline.bat` để chạy 1-click).*

---

### 2.2. Khởi chạy Toàn Bộ Web App trên Máy Local
Yêu cầu: Đã cài đặt XAMPP (Apache + MySQL + PHP 8.0+).

1. **Đặt thư mục dự án:** Sao chép thư mục `tkb` vào thư mục `C:\xampp\htdocs\tkb`.
2. **Khởi động dịch vụ:** Mở XAMPP Control Panel, bấm **Start** cả Apache và MySQL.
3. **Cơ sở dữ liệu:**
   - Truy cập `http://localhost/phpmyadmin/`.
   - Tạo cơ sở dữ liệu tên: `truong_caodang`.
   - Bấm **Import** và chọn file dữ liệu mẫu có sẵn: `if0_41796593_truong_caodang.sql`.
4. **Mở trình duyệt:**
   - Trang Giảng viên AI Analytics: `http://localhost/tkb/teacher/ai_analytics.php`
   - Trang Sinh viên AI Advisor: `http://localhost/tkb/student/ai_advisor.php`

---

## 📊 3. BẢNG TỔNG HỢP CHỈ SỐ KỸ THUẬT AI (EVALUATION METRICS)

Mô hình học máy phân loại rủi ro học tập của sinh viên dựa trên 7 đặc trưng hành vi (Điểm Quiz, Số lượt làm, Thời gian/câu, Chuyên cần, Đúng hạn bài tập, Tiến độ môn, Kiên trì):

| Chỉ số (Metric) | Kết quả Đạt được | Đánh giá Kỹ thuật |
| :--- | :---: | :--- |
| **Accuracy (Độ chính xác)** | **96.00%** | Kiểm thử trên 150 mẫu test độc lập (Stratified Split) |
| **Weighted F1-Score** | **0.9597** | Đạt sự cân bằng tối ưu giữa Precision và Recall |
| **Weighted Precision** | **0.9599** | Hạn chế tối đa các ca báo động giả |
| **Weighted Recall** | **0.9600** | Độ bao phủ cao, lớp High Risk đạt Recall **96.7%** |
| **Độ trễ suy luận** | **1.85 ms** | Phản hồi tức thì theo thời gian thực |

### 🔬 Bảng So Sánh Baseline & Ablation Study:
* **Baseline Comparison:** Mô hình Random Forest đề xuất (F1: **0.9597**) vượt trội hoàn toàn so với Heuristic Rule-based truyền thống (F1: 0.8032) và Logistic Regression (F1: 0.9463).
* **Ablation Study:** Khi loại bỏ biến Chuyên cần và Thời gian làm bài, F1 giảm rõ rệt. Đặc biệt khi chỉ dùng điểm trắc nghiệm đơn thuần (Quiz-only), F1 sụt giảm tới **-12.12%**.

---

## 📁 4. HỒ SƠ DỰ THI ĐẦY ĐỦ THEO THỂ LỆ BẢNG C

Các tài liệu bắt buộc theo quy định cuộc thi được lưu trữ đầy đủ trong thư mục `docs/`:

| Tài liệu | Đường dẫn | Nội dung chính |
| :--- | :--- | :--- |
| **Báo cáo Kỹ thuật (Technical Report)** | [`docs/BAO_CAO_KY_THUAT.md`](docs/BAO_CAO_KY_THUAT.md) | Báo cáo khoa học 9 phần chuẩn học thuật, sẵn sàng xuất PDF |
| **Nhật ký Câu lệnh (Prompt Log)** | [`docs/PROMPT_LOG.md`](docs/PROMPT_LOG.md) | Toàn bộ System Prompt, Conversation History, Bảng phân định mã nguồn |
| **Kịch bản Video Demo (10 phút)** | [`docs/KICH_BAN_VIDEO_DEMO.md`](docs/KICH_BAN_VIDEO_DEMO.md) | Kịch bản chi tiết, phân vai bắt buộc cho tất cả thành viên trong đội |
| **Chiến thuật Hackathon 48h** | [`docs/CHIEN_THUAT_HACKATHON_48H.md`](docs/CHIEN_THUAT_HACKATHON_48H.md) | Cẩm nang tác chiến thực tế theo các mốc mở đề của BTC |

---

## 👥 5. THÔNG TIN ĐỘI THI

* **Đội thi:** SmartEdu AI
* **Đơn vị:** Trường Cao Đẳng Kỹ Thuật & Công Nghệ Cà Mau (Việt - Hàn)
* **Kho lưu trữ GitHub:** [NhutKhanh-Coder/viet-han-camau-tkb](https://github.com/NhutKhanh-Coder/viet-han-camau-tkb)
* **Mọi thắc mắc kỹ thuật:** Liên hệ qua GitHub Issues hoặc Email của Trưởng nhóm.
