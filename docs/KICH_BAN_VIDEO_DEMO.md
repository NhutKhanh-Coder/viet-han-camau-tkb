# KỊCH BẢN VIDEO DEMO SẢN PHẨM (TỐI ĐA 10 PHÚT)
## CUỘC THI HACKATHON TRÍ TUỆ NHÂN TẠO & PHÂN TÍCH DỮ LIỆU — BẢNG C

---

### **QUY ĐỊNH BẮT BUỘC CỦA BAN TỔ CHỨC:**
* Thời lượng video: **Tối đa 10 phút** (Khuyến nghị thực hiện từ 8:30 đến 9:30).
* Yêu cầu nhân sự: **BẮT BUỘC CÓ MẶT TẤT CẢ CÁC THÀNH VIÊN TRONG ĐỘI THI** (Cả đội cùng xuất hiện chào đầu video và mỗi thành viên đều trình bày phần việc chuyên môn của mình).
* Nội dung: Giới thiệu bài toán, phương pháp luận AI, demo sản phẩm thực tế, chứng minh chỉ số kỹ thuật (Evaluation Metrics, Baseline, Ablation) và kết luận.

---

## 1. PHÂN CÔNG VAI TRÒ TRONG VIDEO

Giả định đội thi gồm 3 thành viên:
1. **Thành viên 1 (Trưởng nhóm - Team Leader):** Giới thiệu đội, bài toán thực tiễn, kiến trúc tổng thể và tổng kết giá trị ứng dụng.
2. **Thành viên 2 (Kỹ sư AI / Data Scientist):** Trình bày tập dữ liệu, kỹ thuật Feature Engineering, mô hình Random Forest & K-Means, bộ chỉ số F1/Accuracy, ma trận nhầm lẫn và Ablation Study.
3. **Thành viên 3 (Kỹ sư Fullstack / System Engineer):** Demo trực tiếp thao tác trên Web: Bảng điều khiển Giảng viên (AI Analytics Hub), Cảnh báo sớm nguy cơ rớt môn, và Giao diện Cố vấn học tập cá nhân hóa của Sinh viên (Radar Chart).

---

## 2. DÒNG THỜI GIAN CHI TIẾT (TIMELINE 09 PHÚT 30 GIÂY)

```
00:00 ── 01:00 : Mở đầu & Giới thiệu toàn đội (Cả đội cùng xuất hiện)
01:00 ── 02:30 : Đặt vấn đề & Ý nghĩa thực tiễn của bài toán
02:30 ── 05:00 : Trình bày Khoa học AI: Mô hình, Metrics, Baseline & Ablation
05:00 ── 08:00 : Demo trực tiếp sản phẩm thực tế trên Web (Teacher & Student)
08:00 ── 09:30 : Đạo đức AI, Khả năng mở rộng cho Hackathon 48h & Lời cảm ơn
```

---

## 3. LỜI THOẠI VÀ HƯỚNG DẪN TỪNG PHÂN CẢNH

### Phân cảnh 1: Giới thiệu toàn đội (00:00 - 01:00)
* **Khung hình:** Camera quay toàn cảnh cả 3 thành viên đứng cạnh nhau, trang phục lịch sự/áo đồng phục trường, phía sau là màn hình hiển thị logo dự án SmartEdu AI.
* **Thành viên 1 (Trưởng nhóm):**
  > "Kính chào Ban Giám khảo và Ban Tổ chức Cuộc thi Hackathon Trí tuệ Nhân tạo - Bảng C. Chúng tôi là đội thi SmartEdu AI đến từ Trường Cao đẳng Kỹ thuật và Công nghệ Cà Mau (Việt - Hàn). Hôm nay, đội thi chúng tôi gồm: [Tên thành viên 1] - Trưởng nhóm, [Tên thành viên 2] - Phụ trách Mô hình AI & Dữ liệu, và [Tên thành viên 3] - Phụ trách Phát triển Hệ thống Web & Tích hợp."
* **Cả 3 thành viên:** Cúi đầu chào:
  > "Xin hân hạnh mang đến giải pháp: **SmartEdu AI - Hệ thống Phân tích Dữ liệu Học tập và Cố vấn Đào tạo Cá nhân hóa dựa trên Trí tuệ Nhân tạo**."

---

### Phân cảnh 2: Đặt vấn đề & Ý nghĩa thực tế (01:00 - 02:30)
* **Khung hình:** Thành viên 1 trình bày, màn hình trình chiếu Slide tóm tắt các nỗi đau (Pain points) trong giáo dục nghề nghiệp.
* **Thành viên 1 (Trưởng nhóm):**
  > "Kính thưa Ban Giám khảo, trong môi trường đào tạo nghề, tỷ lệ sinh viên chểnh mảng, hổng kiến thức hoặc có nguy cơ bỏ học thường chỉ được phát hiện khi học kỳ đã kết thúc — lúc này việc cứu vãn điểm số gần như bất khả thi.
  > Dữ liệu học tập hiện nay như điểm danh, bài tập trắc nghiệm, thời gian nộp đồ án chưa được khai thác có chiều sâu.
  > Vì vậy, đội chúng tôi đã giải quyết bài toán này bằng việc ứng dụng AI để xây dựng: **Hệ thống Cảnh báo Sớm (Early Warning Radar)** giúp giảng viên phát hiện nguy cơ trượt môn trước kỳ thi từ 4 đến 6 tuần, và **AI Mentor Cá nhân hóa** đồng hành cùng từng sinh viên."

---

### Phân cảnh 3: Phương pháp luận AI & Chỉ số Kỹ thuật (02:30 - 05:00)
* **Khung hình:** Thành viên 2 ngồi trước màn hình hiển thị biểu đồ kiến trúc hệ thống, file `model_evaluation_results.json` và bảng ma trận nhầm lẫn.
* **Thành viên 2 (Kỹ sư AI):**
  > "Kính thưa Giám khảo, về mặt phương pháp luận, chúng tôi mô hình hóa hành vi người học qua vector 7 đặc trưng cốt lõi: Điểm trắc nghiệm, số lần làm bài, thời gian phản xạ trên từng câu hỏi, tỷ lệ chuyên cần, tính đúng hạn bài tập, tiến độ hoàn thành môn và chỉ số kiên trì.
  > 
  > Chúng tôi triển khai mô hình **Random Forest Ensemble với 120 cây quyết định** có cân bằng trọng số lớp, kết hợp thuật toán **K-Means phân cụm 4 chân dung sinh viên**.
  > 
  > **Kết quả đánh giá thực nghiệm vô cùng ấn tượng:**
  > - Độ chính xác (Accuracy) đạt **96.0%** trên tập kiểm thử độc lập 150 mẫu.
  > - Weighted F1-Score đạt **0.9597**, Precision đạt **0.9599**, và Recall đạt **0.9600**.
  > - Đặc biệt ở lớp sinh viên Nguy cơ cao (High Risk), Recall đạt **96.7%**, tức phát hiện chính xác 29/30 trường hợp báo động đỏ.
  > 
  > **Về so sánh Baseline:** Mô hình của chúng tôi vượt trội hơn phương pháp Heuristic truyền thống **15.65% về F1-score**, vượt qua Decision Tree và Logistic Regression.
  > **Về Ablation Study:** Khi loại bỏ biến chuyên cần và thời gian làm bài, F1-score suy giảm rõ rệt, và nếu chỉ dùng duy nhất điểm số như cách truyền thống, hiệu năng giảm sâu đến **12.12%**. Điều này khẳng định tính ưu việt của mô hình đa đặc trưng."

---

### Phân cảnh 4: Demo Trực Tiếp Sản Phẩm Trên Hệ Thống (05:00 - 08:00)
* **Khung hình:** Quay màn hình thao tác trực tiếp trên trình duyệt Web (Screen record kết hợp webcam của Thành viên 3).
* **Thành viên 3 (Kỹ sư Fullstack):**
  > "Bây giờ, tôi xin phép trình diễn giải pháp SmartEdu AI đang vận hành thực tế:
  > 
  > **Đầu tiên là Phía Giảng viên — AI Analytics Hub (`teacher/ai_analytics.php`):**
  > - Ngay tại thanh điều hướng, giảng viên chỉ cần 1 click vào **AI Analytics Hub**.
  > - Toàn bộ 4 thẻ KPI hiện lên trực quan: Độ chính xác 96%, F1-Score 0.9597, và phát hiện ngay 30 sinh viên thuộc diện Báo động Đỏ.
  > - Bên dưới là **Ma trận Nhầm lẫn (Confusion Matrix)** tương tác và biểu đồ **Ablation Study**.
  > - Đặc biệt, bảng **Early Warning Radar** liệt kê chính xác từng sinh viên có nguy cơ rớt môn, kèm nguyên nhân AI chẩn đoán rõ ràng: ví dụ 'Chuyên cần thấp 52%, thời gian làm bài quá nhanh 18s/câu'. Giảng viên chỉ cần bấm nút **Nhắc nhở** để hệ thống tự động gửi thông báo điều chỉnh.
  > - Nút **Chạy Quét AI Tức Thì** cho phép chạy lại toàn bộ pipeline học máy theo thời gian thực.
  > 
  > **Tiếp theo là Phía Sinh viên — AI Cố Vấn Năng Lực (`student/ai_advisor.php`):**
  > - Sinh viên đăng nhập sẽ thấy ngay **Bản đồ Radar Năng lực Cá nhân 6 chiều**.
  > - AI tự động chỉ ra: Điểm mạnh cần phát huy và các **Lỗ hổng kiến thức cốt lõi** cần bù đắp.
  > - Sinh viên có thể bấm 1-click để làm Quiz ôn luyện đúng vào phần kiến thức bị yếu hoặc trao đổi trực tiếp với Trợ lý AI Mentor 1-1."

---

### Phân cảnh 5: Khả năng mở rộng cho Vòng Hackathon & Kết luận (08:00 - 09:30)
* **Khung hình:** Cả 3 thành viên cùng xuất hiện trở lại khung hình.
* **Thành viên 1 (Trưởng nhóm):**
  > "Kính thưa Ban Giám khảo, điểm cốt lõi của SmartEdu AI là tính **Module hóa cao (Modular Architecture)**. Khi bước vào Vòng thi Hackathon 48 giờ trực tiếp tại điểm thi, bất kể Ban Tổ chức cung cấp bộ dữ liệu thô thuộc lĩnh vực giáo dục, y tế, giao thông hay dịch vụ công, toàn bộ kiến trúc pipeline xử lý dữ liệu và bảng điều khiển trực quan hóa này đều có thể tái thích ứng chỉ trong vòng 30 phút.
  > 
  > Sản phẩm của chúng tôi tuân thủ nghiêm ngặt chuẩn mực Đạo đức AI, bảo vệ quyền riêng tư sinh viên và mang lại giá trị thực tiễn to lớn cho ngành giáo dục.
  > 
  > Đội thi SmartEdu AI xin trân trọng cảm ơn Ban Giám khảo và Ban Tổ chức đã lắng nghe!"
* **Cả 3 thành viên:** Cúi đầu chào cảm ơn kết thúc video.
