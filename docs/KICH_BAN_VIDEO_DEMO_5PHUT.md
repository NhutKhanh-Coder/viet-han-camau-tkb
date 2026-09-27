# KỊCH BẢN VIDEO DEMO SẢN PHẨM — THỜI LƯỢNG TỐI ĐA 05 PHÚT (300 GIÂY)
## DỰ ÁN: SMARTEDU AI — HỆ THỐNG PHÂN TÍCH DỮ LIỆU HỌC TẬP & CỐ VẤN ĐÀO TẠO CÁ NHÂN HÓA

---

> **MỤC TIÊU VIDEO:**  
> Trình diễn trọn vẹn 5 tiêu chí cốt lõi theo quy chế chấm thi:  
> **1. Quá trình vận hành** ➔ **2. Các chức năng chính** ➔ **3. Kết quả xử lý (Metrics)** ➔ **4. Khả năng tích hợp** ➔ **5. Khả năng ứng dụng thực tế**.

---

## ⏱️ BẢNG PHÂN BỔ THỜI LƯỢNG CHUẨN 05 PHÚT (TIMELINE)

| Phân đoạn | Thời lượng | Nội dung trọng tâm | Góc quay / Màn hình |
| :--- | :---: | :--- | :--- |
| **Phần 1** | **00:00 - 00:30** *(30s)* | Chào đầu, Giới thiệu đội thi & Đặt vấn đề thực tiễn | Webcam cả đội + Slide tiêu đề |
| **Phần 2** | **00:30 - 01:45** *(75s)* | Vận hành & Chức năng Giảng viên (AI Analytics Hub & Cảnh báo sớm) | Quay màn hình `teacher/ai_analytics.php` |
| **Phần 3** | **01:45 - 02:45** *(60s)* | Chức năng Sinh viên (Radar năng lực & Cố vấn học tập AI) | Quay màn hình `student/ai_advisor.php` & `student/ai.php` |
| **Phần 4** | **02:45 - 03:45** *(60s)* | Kết quả xử lý AI & Chứng minh khoa học (Metrics, Baseline, Ablation) | Quay cụm biểu đồ AI Hub & Chạy quét AI Real-time |
| **Phần 5** | **03:45 - 04:30** *(45s)* | Khả năng tích hợp hệ thống (Kiến trúc 4 tầng, PHP + Python + Gemini) | Sơ đồ Kiến trúc & Code/Terminal API Microservice |
| **Phần 6** | **04:30 - 05:00** *(30s)* | Ứng dụng thực tế, Sẵn sàng Hackathon 48h & Chào kết | Cả đội xuất hiện kết luận, cảm ơn BGK |

---

## 🎬 CHI TIẾT TỪNG PHÂN CẢNH & LỜI THOẠI (SHOOTING SCRIPT)

### 📌 PHẦN 1: MỞ ĐẦU & ĐẶT VẤN ĐỀ (00:00 – 00:30 | 30 giây)
* **Góc máy:** Camera trực diện cả nhóm đứng cùng nhau (đồng phục trường/trang phục lịch sự).
* **Lời thoại:**
  > *"Kính chào Ban Giám khảo! Chúng tôi là đội thi SmartEdu AI đến từ Trường Cao đẳng Kỹ thuật và Công nghệ Cà Mau (Việt - Hàn).  
  > Trong giáo dục nghề nghiệp, việc phát hiện sinh viên có nguy cơ rớt môn thường diễn ra quá trễ khi kỳ thi đã kết thúc.  
  > Để giải quyết triệt để vấn đề này, chúng tôi phát triển **SmartEdu AI**: giải pháp phân tích hành vi học tập đa chiều, phát hiện nguy cơ sớm trước 4-6 tuần và cá nhân hóa lộ trình bồi dưỡng cho từng sinh viên."*

---

### 📌 PHẦN 2: QUÁ TRÌNH VẬN HÀNH & CHỨC NĂNG GIẢNG VIÊN (00:30 – 01:45 | 75 giây)
* **Góc máy:** Quay màn hình thao tác trực tiếp trên Portal Giảng viên (`teacher/ai_analytics.php`).
* **Thao tác trên màn hình:**
  1. Giảng viên vào **AI Analytics Hub** (giao diện Dark Mode tím - xanh hiện đại).
  2. Rê chuột chỉ vào 4 thẻ KPI tổng quan: Độ chính xác 96%, F1-Score 0.9597, và phát hiện 30 sinh viên nguy cơ cao.
  3. Cuộn xuống bảng **Early Warning Radar (Cảnh báo sớm sinh viên nguy cơ cao)**:
     - Chọn 1 sinh viên diện **Báo động Đỏ** (High Risk).
     - Chỉ vào cột **Lý do AI chẩn đoán (Explainable AI)**: *"Chuyên cần thấp 52%, thời gian làm bài bất thường 18s/câu, nợ 3 bài tập"*.
  4. Bấm nút **"Gửi cảnh báo / Nhắc nhở"** hoặc kích hoạt giao đề ôn luyện tăng cường.
* **Lời thoại:**
  > *"Đây là quá trình vận hành thực tế tại Phía Giảng viên. Ngay khi đăng nhập vào AI Analytics Hub, hệ thống tự động tổng hợp toàn bộ dữ liệu học vụ, chuyên cần và làm bài tập.  
  > Không chỉ đưa ra cảnh báo thô, AI ứng dụng nguyên lý Explainable AI để chỉ rõ nguyên nhân cho từng sinh viên: ví dụ bạn này vắng học 48% và có dấu hiệu chọn bừa trắc nghiệm dưới 18 giây/câu. Giảng viên chỉ cần 1 click để kích hoạt can thiệp sư phạm ngay lập tức."*

---

### 📌 PHẦN 3: CHỨC NĂNG CỐ VẤN SINH VIÊN (01:45 – 02:45 | 60 giây)
* **Góc máy:** Chuyển sang màn hình Portal Sinh viên (`student/ai_advisor.php`).
* **Thao tác trên màn hình:**
  1. Hiển thị **Biểu đồ Radar Năng lực 6 chiều** (Điểm Quiz, Chuyên cần, Đúng hạn, Tốc độ, Kiên trì, Tiến độ).
  2. Xem danh sách **"Điểm mạnh"** và **"Lỗ hổng kiến thức cần khắc phục"**.
  3. Bấm vào nút **"Làm bài Quiz ôn luyện bù điểm yếu"** hoặc mở cửa sổ **Trợ lý AI Mentor (Gemini)** để hỏi bài tập lập trình/lý thuyết 1-1.
* **Lời thoại:**
  > *"Ở phía Sinh viên, hệ thống mang đến một AI Cố vấn đào tạo cá nhân hóa.  
  > Sinh viên được trực quan hóa năng lực qua Bản đồ Radar 6 chiều, nhìn thấy ngay lỗ hổng kiến thức cốt lõi thay vì điểm số chung chung.  
  > Đồng thời, Trợ lý AI Mentor đóng vai trò gia sư 24/7, tự động sinh đề trắc nghiệm bổ trợ đúng vào vùng kiến thức sinh viên đang bị hổng."*

---

### 📌 PHẦN 4: KẾT QUẢ XỬ LÝ & BẰNG CHỨNG KHOA HỌC (02:45 – 03:45 | 60 giây)
* **Góc máy:** Quay lại AI Analytics Hub, zoom vào khu vực **Chỉ số Kỹ thuật**, **Ma trận nhầm lẫn** và **Biểu đồ Baseline & Ablation**.
* **Thao tác trên màn hình:**
  1. Phóng to **Ma trận Nhầm lẫn (Confusion Matrix 3x3)**: Nhấn mạnh lớp High Risk đạt Recall **96.7%** (29/30 em).
  2. Chỉ vào biểu đồ **Baseline Comparison**: Nhấn mạnh mô hình đề xuất (Random Forest 120 cây) vượt trội hơn Heuristic **+15.65% F1-Score**.
  3. Chỉ vào bảng **Ablation Study**: Chứng minh khi bỏ dữ liệu Chuyên cần và Thời gian làm bài, F1-Score giảm sâu tới **-12.12%**.
  4. Bấm nút **"Chạy Quét AI Tức Thì" (Run AI Pipeline)**: Hệ thống thực thi Python script và reload kết quả real-time trong tích tắc.
* **Lời thoại:**
  > *"Về kết quả xử lý và chứng minh kỹ thuật:  
  > Trên tập kiểm thử độc lập 150 mẫu, mô hình Random Forest Ensemble của chúng tôi đạt độ chính xác **96.0%**, Weighted F1-Score đạt **0.9597**. Đặc biệt, tỷ lệ bao phủ lớp Nguy cơ cao đạt **96.7%**, triệt tiêu nguy cơ bỏ sót sinh viên rớt môn.  
  > Kết quả so sánh Baseline chứng minh mô hình vượt trội hơn phương pháp Heuristic truyền thống **15.65% F1-Score**. Nghiên cứu Ablation khẳng định việc kết hợp đặc trưng hành vi và thời gian phản xạ giúp cải thiện hiệu quả tới 12.12% so với chỉ nhìn điểm số đơn thuần."*

---

### 📌 PHẦN 5: KHẢ NĂNG TÍCH HỢP HỆ THỐNG (03:45 – 04:30 | 45 giây)
* **Góc máy:** Chiếu sơ đồ kiến trúc hệ thống (10s) kết hợp quay lướt qua code/terminal chạy microservice (20s).
* **Thao tác trên màn hình:**
  - Sơ đồ 4 tầng: Web Portal (PHP) ➔ REST API / JSON Pipe ➔ Python ML Engine (`predict_service.py`) ➔ CSDL kép MySQL & SQLite.
  - Tích hợp Gemini LLM cho phân hệ Generative AI Mentor.
* **Lời thoại:**
  > *"Về khả năng tích hợp:  
  > SmartEdu AI được xây dựng theo kiến trúc 4 tầng chuẩn công nghiệp. Tầng giao diện PHP giao tiếp mượt mà với Microservice Python qua JSON Pipeline thời gian thực, độ trễ suy luận chỉ **1.85 mili-giây/mẫu**.  
  > Hệ thống tích hợp song song CSDL quan hệ MySQL lưu trữ học vụ và SQLite lưu log làm bài chi tiết, đồng thời kết nối API Google Gemini với cơ chế RAG để gia sư thông minh cho người học."*

---

### 📌 PHẦN 6: ỨNG DỤNG THỰC TẾ & SẴN SÀNG CHO HACKATHON 48H (04:30 – 05:00 | 30 giây)
* **Góc máy:** Quay lại webcam cả đội chào kết thúc.
* **Lời thoại:**
  > *"Sản phẩm đã được thử nghiệm thực tế tại nhà trường và hoàn toàn sẵn sàng cho vòng thi Hackathon 48 giờ trực tiếp.  
  > Nhờ thiết kế module hóa cao (Modular Architecture), hệ thống có khả năng 'Plug-and-Play' — chỉ cần nạp bất kỳ bộ dữ liệu mới nào từ BTC, toàn bộ pipeline huấn luyện và dashboard phân tích sẽ tự động vận hành trong vòng chưa đầy 15 phút.  
  > Đội thi SmartEdu AI xin trân trọng cảm ơn Ban Giám khảo!"*  
*(Cả đội cùng cúi đầu chào).*

---

## 🎯 DANH SÁCH CHECKLIST: NÊN QUAY GÌ & KHÔNG NÊN QUAY GÌ?

### ✅ BẮT BUỘC PHẢI QUAY (CHIẾM 90% ĐIỂM SỐ):
1. **AI Analytics Hub (`teacher/ai_analytics.php`):** Thẻ KPI (96% Accuracy, 0.9597 F1), Ma trận nhầm lẫn, Biểu đồ Baseline và Ablation Study.
2. **Early Warning Radar:** Bảng sinh viên Báo động đỏ + Lý do AI chẩn đoán minh bạch (Explainable AI) + Nút can thiệp.
3. **Nút Quét AI Thời Gian Thực:** Thao tác bấm nút chạy lại pipeline để chứng minh code AI đang thực thi thật 100%.
4. **AI Advisor Phía Sinh Viên (`student/ai_advisor.php`):** Radar Chart 6 chiều, điểm mạnh/yếu và gợi ý ôn tập.
5. **Khả năng tích hợp:** Terminal/Code Python AI kết nối với CSDL và Web PHP.

### ❌ TUYỆT ĐỐI TRÁNH QUAY (GÂY LÃNG PHÍ THỜI GIAN):
1. **Đăng ký / Đăng nhập / Quên mật khẩu:** Không quay thao tác gõ username/password (nên đăng nhập sẵn trên 2 tab trình duyệt).
2. **Các trang CRUD thông thường:** Không quay thêm/sửa/xóa môn học, đổi ảnh đại diện, danh sách phòng học.
3. **Làm trắc nghiệm từng câu:** Không quay sinh viên ngồi đọc và bấm từng câu hỏi trắc nghiệm (chỉ lướt 2-3 giây kết quả).
4. **Slide lý thuyết dài dòng:** Không dùng quá 30 giây cho slide tĩnh; hãy ưu tiên quay sản phẩm thực tế đang chạy.
