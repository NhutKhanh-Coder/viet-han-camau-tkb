# NHẬT KÝ CÂU LỆNH & DANH MỤC NGUỒN TÀI NGUYÊN (PROMPT LOG)
## CUỘC THI HACKATHON TRÍ TUỆ NHÂN TẠO & PHÂN TÍCH DỮ LIỆU — BẢNG C

---

### **DỰ ÁN:** SmartEdu AI — Learning Analytics & Personalized Advisory System
* **Đội thi:** SmartEdu AI (viet-han-camau-tkb)
* **Quy chuẩn tài liệu:** Tuân thủ mục Yêu cầu Bảng C về Prompt Log, System Prompt, Conversation History và Minh bạch nguồn gốc mã nguồn (Attribution Transparency).

---

## 1. BẢNG PHÂN ĐỊNH NGUỒN GỐC MÃ NGUỒN (ATTRIBUTION MATRIX)

Theo yêu cầu của Ban Tổ chức Cuộc thi, đội thi phân định rõ 3 thành phần cấu thành sản phẩm:

| Thành phần hệ thống | Mã nguồn / Tệp tin | Bản quyền & Nguồn gốc | Phân loại đóng góp |
| :--- | :--- | :--- | :--- |
| **Pipeline Học Máy & Đánh giá AI** | `ai_engine/train_evaluate.py`<br>`ai_engine/predict_service.py` | Đội thi tự xây dựng | **Tự xây dựng 100%**: Thiết kế đặc trưng, huấn luyện Random Forest, K-Means, tính toán ma trận nhầm lẫn & Ablation. |
| **Trung tâm Phân tích AI Giảng viên** | `teacher/ai_analytics.php` | Đội thi tự xây dựng + Trợ lý AI hỗ trợ | **Tự phát triển & tích hợp**: Giao diện KPI, ma trận nhầm lẫn interactive, bảng cảnh báo sớm rớt môn. |
| **Cố vấn Học tập Sinh viên** | `student/ai_advisor.php` | Đội thi tự xây dựng + Trợ lý AI hỗ trợ | **Tự phát triển**: Bản đồ radar năng lực cá nhân, phát hiện lỗ hổng kiến thức. |
| **Giao diện Web Nền tảng** | `teacher/`, `student/`, `assets/` | Dự án cơ sở trường CĐ Việt - Hàn | **Mã nguồn tự xây dựng của đội thi** kết hợp tài nguyên có sẵn của trường. |
| **Thư viện Toán học & Học máy** | `scikit-learn`, `numpy`, `scipy` | Nguồn mở (BSD-3-Clause) | **Kế thừa mã nguồn mở chuẩn** |
| **Thư viện Trực quan hóa** | `Chart.js`, `FontAwesome 6` | Nguồn mở (MIT License) | **Kế thừa mã nguồn mở** |
| **Mô hình Ngôn ngữ Lớn (LLM)** | `Gemini 1.5 Flash` / `Gemini Pro` | Google AI Studio REST API | **API Dịch vụ tích hợp** |

---

## 2. DANH MỤC CÔNG CỤ AI, MÔ HÌNH, THƯ VIỆN & API

### 2.1. Danh mục Mô hình Trí tuệ Nhân tạo (Models)
1. **Random Forest Classifier (Ensemble Model):** Mô hình máy học phân loại nguy cơ học tập (120 cây quyết định, tối ưu hóa độ sâu và trọng số lớp `balanced`).
2. **K-Means Clustering Algorithm:** Mô hình học không giám sát phân chia 4 chân dung sinh viên.
3. **Google Gemini 1.5 Flash (Generative AI):** Mô hình ngôn ngữ lớn đóng vai trò AI Mentor, phân tích ngữ cảnh học tập và sinh câu hỏi trắc nghiệm tương thích.

### 2.2. Danh mục Thư viện & Frameworks
* **Python 3.12:** `scikit-learn` (v1.9.0), `numpy` (v2.5.3), `scipy` (v1.8.1), `Flask` (v3.1.3).
* **Frontend:** Vanilla CSS, `Chart.js` (v4.4.x), `FontAwesome 6.5.1`, Google Fonts (`Outfit`).
* **Backend Database:** PHP 8.2, MySQL / MariaDB, SQLite3 (`quiz_vhcm.db`).

---

## 3. TOÀN BỘ SYSTEM PROMPT (CÂU LỆNH HỆ THỐNG)

Dưới đây là các System Prompt chính được nạp vào các tác vụ AI trong hệ thống:

### 3.1. System Prompt cho Trợ lý Cố vấn Học tập Cá nhân hóa (AI Student Mentor)
```text
[ROLE & CONTEXT]
Bạn là SmartEdu Mentor - Trợ lý Trí tuệ Nhân tạo cố vấn đào tạo chuyên sâu tại trường Cao đẳng Kỹ thuật và Công nghệ Cà Mau (Việt - Hàn).
Nhiệm vụ của bạn là đồng hành, giải đáp kiến thức chuyên môn (Lập trình, Công nghệ thông tin, Cơ khí, Điện tử) và cố vấn lộ trình học tập thích ứng cho sinh viên.

[STUDENT CONTEXT INJECTION]
Hệ thống sẽ cung cấp thông tin học tập của sinh viên hiện tại:
- Họ tên, Mã sinh viên, Lớp, Môn học
- Tỷ lệ chuyên cần (Attendance rate)
- Điểm trung bình trắc nghiệm (Quiz score)
- Các lỗ hổng kiến thức được phát hiện từ bài làm trắc nghiệm sai gần nhất

[BEHAVIORAL PRINCIPLES]
1. Luôn giữ thái độ thân thiện, khích lệ và sư phạm chuẩn mực.
2. Không giải bài hộ hoàn toàn; thay vào đó, đặt câu hỏi gợi mở theo phương pháp Socratic để sinh viên tự tư duy và tìm ra đáp án.
3. Khi sinh viên gặp bế tắc về lỗi code, hãy giải thích bản chất của thông báo lỗi (Compiler error / Runtime error), phân tích nguyên nhân gốc rễ và đưa ra giải pháp từng bước.
4. Tích cực liên hệ lý thuyết với các bài thực hành và dự án thực tế tại trường CĐ Việt - Hàn Cà Mau.
```

### 3.2. System Prompt cho Bộ Phân Tích Đề Trắc Nghiệm & Lỗ Hổng Kiến Thức
```text
[ROLE]
Bạn là Chuyên gia Khảo thí và Đánh giá Năng lực Học viên trong lĩnh vực Khoa học Máy tính.

[TASK]
Phân tích danh sách câu hỏi trắc nghiệm mà sinh viên đã trả lời sai trong bài thi gần nhất. 
Xác định:
1. Chủ đề / Khái niệm cốt lõi bị hổng (Core Missing Concept).
2. Mức độ nghiêm trọng của lỗ hổng (Căn bản / Trung cấp / Nâng cao).
3. Đề xuất 3 câu hỏi trắc nghiệm tương đương để sinh viên luyện tập củng cố ngay lập tức.

[OUTPUT FORMAT]
BẮT BUỘC trả về đúng định dạng JSON thuần túy (Valid JSON Object), không có markdown thừa:
{
  "gap_topic": "Tên chủ đề bị hổng",
  "difficulty_level": "basic|intermediate|advanced",
  "diagnostic_reason": "Giải thích ngắn gọn tại sao sinh viên hay nhầm lẫn",
  "recommended_quiz": [
    {
      "question": "Nội dung câu hỏi ôn tập",
      "options": ["A", "B", "C", "D"],
      "correct_answer": "A",
      "explanation": "Giải thích chi tiết vì sao phương án này đúng"
    }
  ]
}
```

---

## 4. LỊCH SỬ HỘI THOẠI MẪU VỚI CÔNG CỤ AI (CONVERSATION HISTORY)

### Tình huống 1: Sinh viên nhờ giải thích lỗi vòng lặp trong PHP
* **User (Sinh viên):** "Thầy ơi, đoạn code này của em duyệt mảng sinh viên nhưng nó cứ báo lỗi `Warning: Undefined array key 'diem'` là sao ạ?"
* **AI Mentor:** 
  > "Chào em! Lỗi `Undefined array key 'diem'` xuất hiện khi em cố gắng truy cập vào một phần tử trong mảng kết hợp với khóa `'diem'`, nhưng trong cấu trúc mảng của sinh viên đó lại không tồn tại khóa này (hoặc em viết sai chính tả, ví dụ `'diem_thi'` thay vì `'diem'`).
  > 
  > **Các bước em hãy kiểm tra ngay nhé:**
  > 1. Dùng lệnh `var_dump($sinh_vien);` ngay trước vòng lặp để in ra xem mảng thực tế có chứa trường `'diem'` hay không.
  > 2. Sử dụng toán tử kiểm tra an toàn Null Coalescing của PHP: `$diem = $sinh_vien['diem'] ?? 0;` để tránh phát sinh cảnh báo khi dữ liệu bị thiếu.
  > 
  > Em thử in ra rồi gửi lại thầy cấu trúc mảng xem sao nhé!"

### Tình huống 2: Đội thi tương tác trong quá trình xây dựng mô hình AI (Ablation Study)
* **Đội thi (Developer):** "Hãy đề xuất thiết kế thực nghiệm Ablation Study cho bài toán dự báo sinh viên rớt môn có 7 đặc trưng học tập."
* **AI Coding Assistant:**
  > "Để bài báo cáo kỹ thuật Bảng C đạt điểm tối đa từ Hội đồng Giám khảo, bạn nên xây dựng Ablation Study gồm 5 kịch bản sau:
  > 1. **Full Model (Benchmark):** Chạy toàn bộ 7 đặc trưng với Random Forest.
  > 2. **w/o Time Per Question:** Lược bỏ biến thời gian để chứng minh tầm quan trọng của việc phát hiện sinh viên chọn bừa/đoán mò.
  > 3. **w/o Attendance Rate:** Lược bỏ biến chuyên cần để xem sự sụt giảm F1-score khi mất tín hiệu đi học.
  > 4. **w/o Timeliness:** Lược bỏ biến nộp bài đúng hạn.
  > 5. **Quiz-Score Only:** Chỉ dùng duy nhất điểm số - chứng minh mô hình truyền thống kém hiệu quả hơn hẳn mô hình đa đặc trưng."
