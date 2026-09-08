# CẨM NANG CHIẾN THUẬT TÁC CHIẾN HACKATHON 48 GIỜ (BẢNG C)
## BÍ QUYẾT GIÀNH ĐIỂM TỐI ĐA TẠI VÒNG THI TRỰC TIẾP (CHIẾM 60% TỔNG ĐIỂM)

---

### **BỐI CẢNH VÒNG THI KHU VỰC:**
* Thời gian: **02 ngày liên tục (Hackathon on-site 48 giờ)**.
* Đề bài: Ban Tổ chức mở đề tại chỗ gồm **01 bộ dữ liệu thô (raw dataset)** và **01 bài toán thực tế** thuộc nhóm chủ đề công bố.
* Điểm xét chọn vào Chung kết: **60% điểm Vòng Khu vực + 40% điểm Hồ sơ ban đầu**.

---

## 1. PHÂN CÔNG TÁC CHIẾN TRONG PHÒNG THI (3 THÀNH VIÊN)

| Vị trí | Phụ trách chính | Trọng tâm công việc trong 48h |
| :--- | :--- | :--- |
| **Thành viên 1 (Data / AI Lead)** | Khoa học Dữ liệu & Huấn luyện Mô hình | Làm sạch dữ liệu (Data Cleaning), EDA, huấn luyện Baseline, tối ưu mô hình chính, tính toán các chỉ số AI Metrics (F1, Accuracy, MAE/RMSE) và chạy Ablation Study. |
| **Thành viên 2 (Fullstack / Web Lead)** | Tích hợp Hệ thống & Trực quan hóa | Cắm API kết quả phân tích vào giao diện Dashboard Web (tái sử dụng bộ khung có sẵn của dự án này), tạo biểu đồ Chart.js, bảng tra cứu và demo tương tác. |
| **Thành viên 3 (Documentation & Video Lead)** | Báo cáo Kỹ thuật, Prompt Log & Video | Ghi chép Prompt Log liên tục, soạn thảo Báo cáo kỹ thuật PDF theo đúng mẫu chuẩn, thiết kế slide thuyết trình, chuẩn bị kịch bản và đạo diễn quay video demo 10 phút. |

---

## 2. LỘ TRÌNH 48 GIỜ THEO CÁC MỐC MỞ ĐỀ & KIỂM TRA TIẾN ĐỘ

### Mốc 1: Giờ 00:00 — 04:00 (Mở đề & Khám phá dữ liệu thô - EDA)
* **Việc cần làm ngay:**
  1. Khởi tạo kho Git mới trên GitHub/GitLab theo đúng hướng dẫn BTC, cấp quyền cho Ban Giám khảo.
  2. Tạo commit đầu tiên: `Initial commit: Project setup and raw dataset inspection`.
  3. Mở file dữ liệu thô (CSV/JSON/SQL) của BTC bằng Python (`pandas` / `numpy`).
  4. Phân tích phân phối dữ liệu (Missing values, Outliers, Skewness, Class balance).
  5. Xác định rõ ràng: Biến độc lập (Features) và Biến phụ thuộc mục tiêu (Target variable).

---

### Mốc 2: Giờ 04:00 — 14:00 (Huấn luyện Baseline & Mô hình AI Đề xuất)
* **Việc cần làm:**
  1. Tiền xử lý dữ liệu: Điền giá trị khuyết (Imputation), chuẩn hóa thang đo (`StandardScaler` / `MinMaxScaler`), mã hóa biến phân loại (`OneHotEncoder`).
  2. Xây dựng ngay **Baseline 1** (Rule-based hoặc Logistic Regression / Simple Linear Regression). Ghi nhận chỉ số ban đầu làm mốc so sánh.
  3. Huấn luyện các mô hình mạnh hơn: Random Forest, XGBoost, LightGBM, hoặc MLP Neural Network.
  4. Thực hiện Cross-validation 5-Fold để chống Overfitting.
  5. Commit tiến độ lên Git: `feat(ai): Implement data preprocessing and baseline models`.

---

### Mốc 3: Giờ 14:00 — 24:00 (Mốc kiểm tra tiến độ 1 của BTC & Ablation Study)
* **Việc cần làm:**
  1. Báo cáo tiến độ sơ bộ với Ban Giám khảo / Cố vấn chuyên môn khi có phiên góp ý.
  2. Chạy thực nghiệm **Ablation Study**: Lần lượt loại bỏ từng nhóm đặc trưng để đo lường mức độ sụt giảm của F1-Score/Accuracy.
  3. Xuất toàn bộ kết quả ra file `model_evaluation_results.json` theo đúng cấu trúc của module `ai_engine`.
  4. Commit tiến độ lên Git: `feat(ai): Compute evaluation metrics and ablation study`.

---

### Mốc 4: Giờ 24:00 — 34:00 (Ráp giao diện Web & Dashboard Trực quan)
* **Việc cần làm:**
  1. Tận dụng giao diện `teacher/ai_analytics.php` đã dựng sẵn trong dự án này:
     - Đổi tiêu đề phù hợp với đề bài thực tế của BTC (ví dụ: "Dự báo quá tải giao thông", "Phân tích rủi ro y tế", v.v.).
     - Trỏ đọc dữ liệu từ file JSON kết quả vừa sinh ra.
  2. Tinh chỉnh biểu đồ Chart.js (Confusion matrix, biểu đồ cột Ablation, phân bố cụm K-Means).
  3. Kiểm tra tính mượt mà của giao diện demo trên localhost / server thử nghiệm.
  4. Commit tiến độ lên Git: `feat(web): Integrated AI analytics dashboard with real-time charts`.

---

### Mốc 5: Giờ 34:00 — 42:00 (Viết Báo cáo Kỹ thuật PDF & Hoàn thiện Prompt Log)
* **Việc cần làm:**
  1. Mở file `docs/BAO_CAO_KY_THUAT.md` có sẵn bộ khung chuẩn học thuật:
     - Điền chính xác các con số thực tế đạt được từ bài toán của BTC vào bảng Baseline và Ablation.
     - Xuất file sang định dạng **PDF chất lượng cao**.
  2. Rà soát file `docs/PROMPT_LOG.md`: Đảm bảo lưu lại đầy đủ System Prompt, các câu lệnh đã chat với AI trong 48h, và bảng phân định nguồn gốc mã nguồn.
  3. Commit tiến độ lên Git: `docs: Finalize technical report PDF and prompt log`.

---

### Mốc 6: Giờ 42:00 — 48:00 (Quay Video Demo 10 phút & Nộp bài chính thức)
* **Việc cần làm:**
  1. Tập dượt kịch bản video theo mẫu `docs/KICH_BAN_VIDEO_DEMO.md`.
  2. **BẮT BUỘC: Tất cả các thành viên trong đội đều phải xuất hiện trước camera.**
  3. Quay màn hình demo sản phẩm chạy thật (không dùng slide tĩnh hoặc ảnh chụp màn hình).
  4. Cắt dựng nhanh, kiểm tra âm thanh rõ ràng, thời lượng **dưới 10 phút**.
  5. Nén file hoặc tải video lên Google Drive/YouTube theo yêu cầu BTC.
  6. Nộp bài trước hạn chót ít nhất 45 phút để phòng ngừa sự cố mạng.

---

## 3. CHECKLIST CÁC FILE BẮT BUỘC TRONG TẬP TIN NỘP BÀI

- [x] **Đường dẫn Kho lưu trữ Git:** Có commit history phân bố đều đặn trong 48h.
- [x] **Báo cáo Kỹ thuật (PDF):** Đầy đủ Kiến trúc, Thuật toán, Metrics, Baseline & Ablation.
- [x] **Video Demo (Tối đa 10 phút):** Có mặt 100% thành viên đội thi.
- [x] **Prompt Log (Markdown / PDF):** System Prompt, Conversation History, Phân định mã nguồn.
- [x] **Thư mục Mã nguồn Thực thi:** Có file hướng dẫn cài đặt `README.md` rõ ràng.
