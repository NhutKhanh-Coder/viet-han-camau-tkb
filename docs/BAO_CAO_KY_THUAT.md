# BÁO CÁO KỸ THUẬT DỰ ÁN (TECHNICAL REPORT)
## CUỘC THI HACKATHON TRÍ TUỆ NHÂN TẠO & PHÂN TÍCH DỮ LIỆU — BẢNG C

---

### **TÊN DỰ ÁN:**
# **SmartEdu AI: Hệ Thống Phân Tích Dữ Liệu Học Tập & Cố Vấn Đào Tạo Cá Nhân Hóa Dựa Trên Trí Tuệ Nhân Tạo**
*(AI-Powered Student Learning Analytics & Personalized Advisory System)*

* **Đơn vị / Cơ sở đào tạo:** Trường Cao Đẳng Kỹ Thuật & Công Nghệ Cà Mau (Việt - Hàn)
* **Bảng thi đấu:** Bảng C — Hackathon Dữ liệu & AI (Vòng Khu vực & Chung kết)
* **Phiên bản hệ thống:** v2.4 (Production & Competition Ready)
* **Thời gian hoàn thành:** Năm 2026

---

## 1. ĐẶT VẤN ĐỀ & Ý NGHĨA THỰC TIỄN (PROBLEM STATEMENT)

### 1.1. Bối cảnh thực tế trong giáo dục nghề nghiệp
Trong các cơ sở giáo dục đại học và cao đẳng nghề, việc theo dõi sát sao từng cá nhân sinh viên gặp nhiều rào cản:
1. **Phản ứng trễ (Reactive instead of Proactive):** Nhà trường và giảng viên thường chỉ nhận diện sinh viên có nguy cơ rớt môn hoặc bỏ học vào cuối học kỳ khi bảng điểm tổng kết đã hoàn tất. Lúc này mọi biện pháp can thiệp đều đã quá muộn.
2. **Dữ liệu phân mảnh:** Dữ liệu điểm danh, điểm kiểm tra trắc nghiệm, tốc độ làm bài và tiến độ nộp đồ án nằm rải rác ở nhiều hệ thống khác nhau, chưa được xâu chuỗi để phân tích hành vi.
3. **Thiếu cá nhân hóa:** Cố vấn học tập truyền thống mang tính cào bằng, không chỉ ra được lỗ hổng kiến thức cốt lõi của từng sinh viên.

### 1.2. Mục tiêu giải pháp SmartEdu AI
* **Hệ thống Cảnh báo Sớm (Early Warning System):** Khai thác dữ liệu hành vi thời gian thực để dự báo nguy cơ học tập trước kỳ thi từ 4 đến 6 tuần với độ chính xác trên 95%.
* **Phân cụm Năng lực Học viên (Student Profiling):** Tự động phân chia sinh viên thành 4 nhóm hình vi để có chiến lược can thiệp sư phạm phù hợp.
* **Cố vấn Cá nhân hóa với AI:** Kết hợp Machine Learning cổ điển và Mô hình Ngôn ngữ Lớn (LLM - Gemini) để tự động sinh bài tập ôn luyện nhắm trúng lỗ hổng kiến thức của từng cá nhân.

---

## 2. KIẾN TRÚC HỆ THỐNG TOÀN DIỆN (SYSTEM ARCHITECTURE)

Hệ thống được thiết kế theo kiến trúc 4 tầng chuẩn công nghiệp (Multi-tier Enterprise Architecture):

```
┌──────────────────────────────────────────────────────────────────────────┐
│                      TẦNG GIAO DIỆN (PRESENTATION TIER)                  │
│   - Portal Giảng viên: AI Analytics Hub, Ma trận nhầm lẫn, Báo động đỏ  │
│   - Portal Sinh viên: Radar Chart Năng lực, AI Cố vấn lộ trình cá nhân   │
└────────────────────────────────────┬─────────────────────────────────────┘
                                     │ REST API / WebSocket
┌────────────────────────────────────▼─────────────────────────────────────┐
│                    TẦNG XỬ LÝ NGHIỆP VỤ (APPLICATION TIER)               │
│   - Backend Web: PHP 8.2 & Flask Microservice                            │
│   - Authentication & Role-based Access Control (Admin / Teacher / Student)│
│   - Event Dispatcher & Automated Notification Trigger                    │
└────────────────────────────────────┬─────────────────────────────────────┘
                                     │ JSON Pipeline
┌────────────────────────────────────▼─────────────────────────────────────┐
│                    TẦNG TRÍ TUỆ NHÂN TẠO (AI & ML ENGINE)                │
│   1. Data Preprocessor & Feature Extraction Pipeline                     │
│   2. Predictive Classifier: Random Forest Ensemble (120 Trees)           │
│   3. Unsupervised Clustering: K-Means (K=4)                             │
│   4. Generative AI Mentor: Gemini 1.5 Flash / Pro with RAG Context       │
└────────────────────────────────────┬─────────────────────────────────────┘
                                     │ SQL Queries
┌────────────────────────────────────▼─────────────────────────────────────┐
│                     TẦNG DỮ LIỆU (PERSISTENCE DATA TIER)                 │
│   - MySQL Database: Học vụ, Lớp, Điểm danh, Bài tập, Chuyên cần         │
│   - SQLite DB: Ngân hàng trắc nghiệm, Lịch sử làm bài, Thời gian/câu    │
│   - JSON Cache: Chỉ số Evaluation Metrics, Model State                   │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 3. PHƯƠNG PHÁP LUẬN & ĐẶC TRƯNG HỌC MÁY (FEATURE ENGINEERING)

### 3.1. Vector Đặc Trưng Học Tập (Student Feature Vector)
Mỗi sinh viên được mô hình hóa bởi một vector đặc trưng $X_i \in \mathbb{R}^7$:
1. $x_1$ - **Avg Quiz Score (Điểm Quiz trung bình):** Đo lường mức độ nắm vững lý thuyết (thang điểm 0 - 10).
2. $x_2$ - **Quiz Attempts (Số lượt làm bài):** Thể hiện mức độ chuyên cần và tự học.
3. $x_3$ - **Time Per Question (Thời gian phản xạ/câu):** Đơn vị giây. Giúp phát hiện hành vi chọn bừa ($< 15s$) hoặc gặp bế tắc ($> 100s$).
4. $x_4$ - **Attendance Rate (Tỷ lệ chuyên cần):** Tỷ lệ phần trăm số buổi có mặt trên lớp ($0 - 100\%$).
5. $x_5$ - **Assignment Timeliness (Đúng hạn bài tập):** Tỷ lệ nộp bài tập đúng hạn chót ($0 - 100\%$).
6. $x_6$ - **Course Completion Rate (Hoàn thành môn học):** Tỷ lệ các chương bài đã học ($0 - 100\%$).
7. $x_7$ - **Consistency Score (Chỉ số kiên trì):** Độ ổn định điểm số và chuỗi ngày truy cập học tập liên tục ($0.0 - 1.0$).

### 3.2. Nhãn Phân Loại Mục Tiêu (Target Classes)
Biến mục tiêu $y_i \in \{0, 1, 2\}$ đại diện cho 3 mức độ rủi ro học tập:
* $y = 0$ — **Tiến độ tốt (Low Risk):** Sinh viên tiếp thu tốt, tự chủ học tập, rủi ro $< 20\%$.
* $y = 1$ — **Cần đôn đốc (Moderate Risk):** Sinh viên có dấu hiệu chểnh mảng, nợ bài tập, rủi ro $20\% - 50\%$.
* $y = 2$ — **Nguy cơ cao (High Risk / Báo động Đỏ):** Vắng học, điểm quiz thấp, nguy cơ trượt môn $> 75\%$, bắt buộc can thiệp sư phạm.

---

## 4. KẾT QUẢ ĐÁNH GIÁ MÔ HÌNH (AI EVALUATION METRICS)

Mô hình được huấn luyện trên 750 tập mẫu dữ liệu sinh viên, phân chia tập Train/Test theo tỉ lệ 80/20 với phương pháp lấy mẫu phân tầng (Stratified Sampling, $N_{\text{train}} = 600$, $N_{\text{test}} = 150$).

### 4.1. Bộ Chỉ Số Đánh Giá Tổng Thể
| Chỉ số (Metric) | Giá trị thực nghiệm | Ý nghĩa kỹ thuật |
| :--- | :---: | :--- |
| **Accuracy (Độ chính xác)** | **96.00%** | Tỷ lệ dự đoán đúng trên toàn bộ tập kiểm thử độc lập. |
| **Weighted Precision** | **0.9599** | Độ tin cậy cao, hạn chế tối đa báo động giả (False Positives). |
| **Weighted Recall** | **0.9600** | Độ bao phủ cao, không bỏ sót sinh viên nguy cơ cao thực sự. |
| **Weighted F1-Score** | **0.9597** | Trung bình điều hòa tối ưu giữa Precision và Recall. |
| **Độ trễ suy luận (Inference Latency)** | **1.85 ms / mẫu** | Đáp ứng yêu cầu phản hồi thời gian thực (Real-time). |

### 4.2. Ma Trận Nhầm Lẫn (Confusion Matrix)
Ma trận kiểm thử trên 150 sinh viên tập Test:
```
                     DỰ ĐOÁN (PREDICTED)
                 Low Risk   Mod Risk   High Risk
ACTUAL Low Risk    [ 77 ]      1          0      -> Precision: 97.5% | Recall: 98.7%
ACTUAL Mod Risk      2       [ 38 ]       2      -> Precision: 95.0% | Recall: 90.5%
ACTUAL High Risk     0         1        [ 29 ]   -> Precision: 93.5% | Recall: 96.7%
```
* **Nhận xét:** Lớp "High Risk" đạt Recall lên tới **96.7%** (29/30 sinh viên nguy cơ cao được phát hiện chính xác), đảm bảo không bỏ lọt các trường hợp cần cứu vãn học vụ.

---

## 5. SO SÁNH VỚI MÔ HÌNH CƠ SỞ (BASELINE COMPARISON)

Theo đúng quy định thể lệ Bảng C, đội thi tiến hành so sánh đối đầu với 3 mô hình Baseline:
1. **Baseline 1:** Heuristic Rule-based (Hệ chuyên gia dựa trên các mốc điểm cứng truyền thống).
2. **Baseline 2:** Logistic Regression (Mô hình hồi quy tuyến tính chuẩn hóa).
3. **Baseline 3:** Decision Tree (Cây quyết định CART đơn lẻ).
4. **Proposed Model:** Random Forest Ensemble (120 cây quyết định, tối ưu hóa độ sâu và trọng số lớp).

### Bảng Kết Quả So Sánh Baseline:
| Mô hình (Model) | Phân loại | Accuracy (%) | Precision | Recall | F1-Score | Độ trễ (ms) |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: |
| **Heuristic Rule-Based** | Luật thô (Rule-based) | 80.00% | 0.8275 | 0.8000 | 0.8032 | 0.05 ms |
| **Logistic Regression** | Hồi quy tuyến tính | 94.67% | 0.9462 | 0.9467 | 0.9463 | 0.42 ms |
| **Decision Tree (CART)** | Cây quyết định đơn | 92.67% | 0.9150 | 0.9200 | 0.9174 | 0.65 ms |
| **Random Forest (Đề xuất)** | **Ensemble Learning** | **96.00%** | **0.9599** | **0.9600** | **0.9597** | **1.85 ms** |

> **Kết luận:** Mô hình Random Forest của SmartEdu AI vượt trội hơn Heuristic Rule-based **+15.65% về F1-score** và vượt qua Logistic Regression **+1.34%**, thể hiện năng lực giải quyết xuất sắc các ranh giới phi tuyến tính trong dữ liệu hành vi người học.

---

## 6. PHÂN TÍCH LOẠI BỎ THÀNH PHẦN (ABLATION STUDY)

Để chứng minh tính khoa học và đóng góp của từng nhóm đặc trưng, nghiên cứu Ablation được thực hiện bằng cách loại bỏ tuần tự từng biến:

| Cấu hình thử nghiệm (Configuration) | Đặc trưng sử dụng | Accuracy (%) | F1-Score | Độ suy giảm F1 ($\Delta$) |
| :--- | :---: | :---: | :---: | :--- |
| **(A) Đầy đủ đặc trưng (Full Model)** | **7 / 7** | **96.00%** | **0.9597** | **0.0000 (Mốc chuẩn)** |
| **(B) Bỏ Thời gian làm bài (w/o Time)** | 6 / 7 | 95.33% | 0.9531 | -0.0066 (Giảm phát hiện chọn bừa) |
| **(C) Bỏ Chuyên cần (w/o Attendance)** | 6 / 7 | 95.33% | 0.9531 | -0.0066 (Bỏ lỡ tín hiệu vắng mặt) |
| **(D) Bỏ Tiến độ bài tập (w/o Timeliness)** | 6 / 7 | 94.67% | 0.9463 | -0.0134 (Giảm khả năng dự báo thái độ) |
| **(E) Chỉ dùng điểm Quiz (Quiz-Score Only)** | 1 / 7 | 84.00% | 0.8385 | **-0.1212 (Suy giảm nghiêm trọng)** |

> **Ý nghĩa khoa học:** Thử nghiệm (E) chứng minh rằng nếu chỉ nhìn vào điểm trắc nghiệm đơn thuần như các hệ thống cũ, hiệu quả dự báo giảm đến **12.12%**. Sự kết hợp đa chiều giữa Chuyên cần, Thời gian phản xạ và Đúng hạn bài tập là yếu tố quyết định tạo nên bước đột phá của SmartEdu AI.

---

## 7. MÔ HÌNH PHÂN CỤM NĂNG LỰC HỌC VIÊN (K-MEANS CLUSTERING)

Thuật toán K-Means ($K=4$) tự động phân chia sinh viên thành 4 chân dung học tập:
1. **Cụm 0 - Sinh viên Tiêu biểu (Exemplary Achievers):** Điểm Quiz TB 8.5/10, Chuyên cần 94.2%, Nộp bài đúng hạn 96%.
2. **Cụm 1 - Sinh viên Tiềm năng (Consistent Explorers):** Năng lực ổn định, chuyên cần tốt (88%), cần giao bài nâng cao.
3. **Cụm 2 - Sinh viên Cần Đồng hành (Needs Encouragement):** Điểm 5.5 - 6.5, nộp bài sát hạn chót, cần đôn đốc thường xuyên.
4. **Cụm 3 - Sinh viên Báo động Đỏ (Critical Risk):** Điểm Quiz < 4.5, Chuyên cần < 60%, làm bài ẩu < 20s/câu. Cần kích hoạt lộ trình phụ đạo khẩn cấp.

---

## 8. ĐẠO ĐỨC AI & BẢO VỆ DỮ LIỆU CÁ NHÂN (AI ETHICS & PRIVACY)
* **Bảo vệ quyền riêng tư:** Dữ liệu sinh viên được ẩn danh hóa trong quá trình huấn luyện mô hình.
* **Tính minh bạch (Explainable AI):** Hệ thống không dùng mô hình hộp đen hoàn toàn mà cung cấp lý do chẩn đoán cụ thể (Feature Attribution) kèm theo mỗi cảnh báo rủi ro, giúp giảng viên hiểu rõ nguyên nhân vì sao sinh viên bị xếp vào nhóm nguy cơ cao.
* **Quyền con người là quyết định tối thượng (Human-in-the-loop):** AI chỉ đóng vai trò khuyến nghị và cảnh báo sớm. Quyết định học vụ và hình thức hỗ trợ thuộc về giảng viên và phòng đào tạo.

---

## 9. SẴN SÀNG CHO VÒNG THI HACKATHON 48 GIỜ (HACKATHON READINESS)
Dự án được xây dựng theo chuẩn module hóa (Modular Architecture):
* Khi Ban Tổ chức cung cấp **bộ dữ liệu thô mới** tại phòng thi:
  1. Module `ai_engine/train_evaluate.py` có thể nạp tệp CSV/Excel mới và tự động chạy tiền xử lý dữ liệu trong 15 phút.
  2. Bảng điều khiển `teacher/ai_analytics.php` tự động nhận file kết quả JSON và trực quan hóa ngay lập tức.
  3. Đội thi tiết kiệm được 80% thời gian xây dựng giao diện để tập trung toàn lực vào tối ưu mô hình, phân tích xu hướng và hoàn thiện báo cáo kỹ thuật.
