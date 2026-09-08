import sqlite3
import json
import os
from werkzeug.security import generate_password_hash, check_password_hash

DB_PATH = os.path.join(os.path.dirname(__file__), 'quiz_vhcm.db')

def get_db_connection():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    conn = get_db_connection()
    cursor = conn.cursor()

    # Users table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'student', -- 'student' or 'teacher'
            student_code TEXT,
            class_name TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')

    # Subjects table (Môn học)
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS subjects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            icon TEXT DEFAULT 'bi-journal-code'
        )
    ''')

    # Chapters table (Bài học / Chương)
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS chapters (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            subject_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            order_index INTEGER DEFAULT 1,
            FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE
        )
    ''')

    # Topics table (Nội dung nhỏ / Chủ đề trong bài)
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS topics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chapter_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            FOREIGN KEY (chapter_id) REFERENCES chapters (id) ON DELETE CASCADE
        )
    ''')

    # Questions table (Ngân hàng câu hỏi)
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            topic_id INTEGER NOT NULL,
            question_type TEXT NOT NULL, -- 'single', 'multiple', 'true_false', 'fill_code'
            question_text TEXT NOT NULL,
            code_snippet TEXT,
            options_json TEXT, -- JSON array of options for single/multiple choice
            correct_answer TEXT NOT NULL, -- String or JSON string for correct answer(s)
            explanation TEXT NOT NULL,
            difficulty TEXT DEFAULT 'medium',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE CASCADE
        )
    ''')

    # Quiz Attempts table (Lần làm bài)
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS quiz_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject_id INTEGER NOT NULL,
            chapter_id INTEGER,
            topic_id INTEGER,
            score REAL NOT NULL,
            total_questions INTEGER NOT NULL,
            correct_count INTEGER NOT NULL,
            percentage REAL NOT NULL,
            time_spent_seconds INTEGER DEFAULT 0,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects (id),
            FOREIGN KEY (chapter_id) REFERENCES chapters (id),
            FOREIGN KEY (topic_id) REFERENCES topics (id)
        )
    ''')

    # Quiz Attempt Details table (Chi tiết câu trả lời từng câu trong lần làm)
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS quiz_details (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            attempt_id INTEGER NOT NULL,
            question_id INTEGER NOT NULL,
            user_answer TEXT,
            is_correct INTEGER NOT NULL, -- 1 for True, 0 for False, 2 for Partial
            score_earned REAL DEFAULT 0,
            FOREIGN KEY (attempt_id) REFERENCES quiz_attempts (id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES questions (id)
        )
    ''')

    conn.commit()
    conn.close()

def seed_db():
    conn = get_db_connection()
    cursor = conn.cursor()

    # Check if data already exists
    cursor.execute('SELECT COUNT(*) FROM users')
    if cursor.fetchone()[0] > 0:
        conn.close()
        return

    print("Seeding initial data...")

    # Default passwords (hashed)
    pass_hash = generate_password_hash('123456')

    # Seed Default Users
    cursor.execute('''
        INSERT INTO users (username, password_hash, full_name, role, student_code, class_name)
        VALUES (?, ?, ?, ?, ?, ?)
    ''', ('sinhvien', pass_hash, 'Nguyễn Văn An', 'student', 'SV2024001', 'CĐ CNTT K16'))

    cursor.execute('''
        INSERT INTO users (username, password_hash, full_name, role, student_code, class_name)
        VALUES (?, ?, ?, ?, ?, ?)
    ''', ('giaovien', pass_hash, 'ThS. Trần Thị Kim Anh', 'teacher', 'GV001', 'Khoa CNTT'))

    cursor.execute('''
        INSERT INTO users (username, password_hash, full_name, role, student_code, class_name)
        VALUES (?, ?, ?, ?, ?, ?)
    ''', ('sinhvien2', pass_hash, 'Lê Quốc Bảo', 'student', 'SV2024002', 'CĐ CNTT K16'))

    # Seed Subjects
    subjects_data = [
        ('PYTHON', 'Lập trình Python', 'Ôn tập cú pháp, hàm, mảng, cấu trúc dữ liệu và OOP trong Python.', 'bi-filetype-py'),
        ('CPP', 'Lập trình C/C++', 'Kiến thức cốt lõi về con trỏ, vòng lặp, mảng và lập trình hướng đối tượng C++.', 'bi-cpu'),
        ('WEB', 'Thiết kế Web (HTML/CSS/JS)', 'Xây dựng trang web động, xử lý DOM JavaScript và định dạng CSS3.', 'bi-code-slash'),
        ('SQL', 'Cơ sở dữ liệu (SQL)', 'Truy vấn cơ sở dữ liệu, SQL JOINs, khóa chính, khóa ngoại và chỉ mục.', 'bi-database')
    ]

    subject_ids = {}
    for code, name, desc, icon in subjects_data:
        cursor.execute('INSERT INTO subjects (code, name, description, icon) VALUES (?, ?, ?, ?)',
                       (code, name, desc, icon))
        subject_ids[code] = cursor.lastrowid

    # Seed Chapters & Topics for Python
    py_id = subject_ids['PYTHON']
    
    # Python Chapter 1: Biến & Vòng lặp
    cursor.execute('INSERT INTO chapters (subject_id, name, order_index) VALUES (?, ?, ?)',
                   (py_id, 'Chương 1: Biến, Kiểu dữ liệu & Vòng lặp', 1))
    ch1_py = cursor.lastrowid

    cursor.execute('INSERT INTO topics (chapter_id, name, description) VALUES (?, ?, ?)',
                   (ch1_py, 'Vòng lặp (for, while)', 'Các bài tập về cấu trúc lặp for và while trong Python'))
    tp_loop = cursor.lastrowid

    cursor.execute('INSERT INTO topics (chapter_id, name, description) VALUES (?, ?, ?)',
                   (ch1_py, 'Kiểu dữ liệu & Cú pháp', 'Cú pháp cơ bản, ép kiểu và biến'))
    tp_syntax = cursor.lastrowid

    # Python Chapter 2: Hàm & Mảng (List/Dict)
    cursor.execute('INSERT INTO chapters (subject_id, name, order_index) VALUES (?, ?, ?)',
                   (py_id, 'Chương 2: Hàm & Cấu trúc dữ liệu (Mảng/List)', 2))
    ch2_py = cursor.lastrowid

    cursor.execute('INSERT INTO topics (chapter_id, name, description) VALUES (?, ?, ?)',
                   (ch2_py, 'Hàm (Functions)', 'Định nghĩa hàm def, tham số, giá trị trả về return'))
    tp_func = cursor.lastrowid

    cursor.execute('INSERT INTO topics (chapter_id, name, description) VALUES (?, ?, ?)',
                   (ch2_py, 'Mảng & List/Dictionary', 'Thao tác với List, Tuple, Dictionary trong Python'))
    tp_list = cursor.lastrowid

    # Python Chapter 3: Lập trình Hướng đối tượng (OOP)
    cursor.execute('INSERT INTO chapters (subject_id, name, order_index) VALUES (?, ?, ?)',
                   (py_id, 'Chương 3: Lập trình hướng đối tượng (OOP)', 3))
    ch3_py = cursor.lastrowid

    cursor.execute('INSERT INTO topics (chapter_id, name, description) VALUES (?, ?, ?)',
                   (ch3_py, 'Lớp & Đối tượng (OOP)', 'Khái niệm Class, Object, __init__, Kế thừa'))
    tp_oop = cursor.lastrowid


    # Seed Questions (Covering all 4 types: single, multiple, true_false, fill_code)

    questions_list = [
        # --- 1. SINGLE CHOICE (TRẮC NGHIỆM 1 LỰA CHỌN) ---
        (
            tp_loop,
            'single',
            'Kết quả xuất ra màn hình của đoạn mã Python sau đây là gì?',
            'for i in range(1, 5):\n    if i == 3:\n        continue\n    print(i, end=" ")',
            json.dumps(['1 2 3 4 5', '1 2 4', '1 2 3', '1 2 4 5']),
            '1 2 4',
            'Vòng lặp `range(1, 5)` sẽ duyệt các giá trị 1, 2, 3, 4. Khi `i == 3`, câu lệnh `continue` được thực thi bỏ qua lệnh `print(3)` và chuyển sang vòng lặp kế tiếp với `i = 4`. Do đó kết quả in ra là `1 2 4`.',
            'medium'
        ),
        (
            tp_func,
            'single',
            'Trong Python, từ khóa nào được sử dụng để khai báo một hàm?',
            '# Chọn câu trả lời đúng bên dưới',
            json.dumps(['function', 'def', 'void', 'func']),
            'def',
            'Trong ngôn ngữ Python, từ khóa `def` (viết tắt của define) được sử dụng để bắt đầu khai báo định nghĩa một hàm.',
            'easy'
        ),
        (
            tp_list,
            'single',
            'Phương thức nào sau đây được dùng để thêm một phần tử vào cuối danh sách (List) trong Python?',
            'numbers = [1, 2, 3]\n# Thêm số 4 vào cuối danh sách',
            json.dumps(['numbers.add(4)', 'numbers.push(4)', 'numbers.append(4)', 'numbers.insert_end(4)']),
            'numbers.append(4)',
            'Hàm `append()` thêm chính xác một phần tử vào cuối của danh sách List trong Python. Ví dụ `numbers.append(4)` sẽ biến danh sách thành `[1, 2, 3, 4]`.',
            'easy'
        ),

        # --- 2. MULTIPLE CHOICE (TRẮC NGHIỆM NHIỀU LỰA CHỌN) ---
        (
            tp_syntax,
            'multiple',
            'Những tên biến nào sau đây là HỢP LỆ trong ngôn ngữ lập trình Python? (Chọn tất cả các đáp án đúng)',
            None,
            json.dumps(['_student_name', '2nd_score', 'total_amount$', 'user_age_2024']),
            json.dumps(['_student_name', 'user_age_2024']),
            'Tên biến hợp lệ trong Python phải bắt đầu bằng chữ cái hoặc dấu gạch dưới `_`, không được bắt đầu bằng chữ số (`2nd_score` sai) và không chứa ký tự đặc biệt như `$` (`total_amount$` sai). Do đó `_student_name` và `user_age_2024` là đúng.',
            'medium'
        ),
        (
            tp_oop,
            'multiple',
            'Các đặc tính cơ bản nào sau đây thuộc về Lập trình hướng đối tượng (OOP)? (Chọn tất cả đáp án đúng)',
            None,
            json.dumps(['Tính đóng gói (Encapsulation)', 'Tính kế thừa (Inheritance)', 'Tính tuần tự (Sequentialism)', 'Tính đa hình (Polymorphism)']),
            json.dumps(['Tính đóng gói (Encapsulation)', 'Tính kế thừa (Inheritance)', 'Tính đa hình (Polymorphism)']),
            '4 trụ cột chính của OOP gồm: Tính đóng gói (Encapsulation), Tính trừu tượng (Abstraction), Tính kế thừa (Inheritance), và Tính đa hình (Polymorphism). Tính tuần tự không phải là đặc tính của OOP.',
            'medium'
        ),

        # --- 3. TRUE / FALSE (ĐÚNG / SAI) ---
        (
            tp_loop,
            'true_false',
            'Vòng lặp `while` trong Python có thể đi kèm với khối `else`. Khối `else` này sẽ thực thi khi điều kiện của `while` trở thành False.',
            'count = 0\nwhile count < 3:\n    print(count)\n    count += 1\nelse:\n    print("Hoàn thành vòng lặp")',
            json.dumps(['Đúng', 'Sai']),
            'Đúng',
            'Đúng. Trong Python, vòng lặp `while` (và cả `for`) có thể có khối `else`. Khối `else` chạy khi vòng lặp kết thúc bình thường (không bị ngắt bởi câu lệnh `break`).',
            'medium'
        ),
        (
            tp_list,
            'true_false',
            'Kiểu dữ liệu Tuple trong Python có thể thay đổi được (Mutable) các phần tử sau khi đã khởi tạo.',
            'my_tuple = (1, 2, 3)\n# my_tuple[0] = 10',
            json.dumps(['Đúng', 'Sai']),
            'Sai',
            'Sai. Tuple là kiểu dữ liệu Immutable (không thể thay đổi). Việc cố tình gán lại giá trị cho phần tử của Tuple như `my_tuple[0] = 10` sẽ gây ra lỗi `TypeError`.',
            'easy'
        ),

        # --- 4. FILL IN CODE / BLANK (ĐIỀN CODE / ĐIỀN TỪ) ---
        (
            tp_loop,
            'fill_code',
            'Điền từ khóa/cú pháp còn thiếu vào chỗ trống `[___]` để tính tổng các số từ 1 đến 5 bằng vòng lặp Python.',
            'total = 0\nfor i in [___](1, 6):\n    total += i\nprint(total)',
            None,
            'range',
            'Hàm `range(1, 6)` trả về các số nguyên từ 1 đến 5 (không bao gồm 6). Do đó từ cần điền vào chỗ trống chính xác là `range`.',
            'medium'
        ),
        (
            tp_oop,
            'fill_code',
            'Điền tên hàm khởi tạo đặc biệt của Class trong Python vào chỗ trống `[___]`.',
            'class Student:\n    def [___](self, name):\n        self.name = name',
            None,
            '__init__',
            'Hàm khởi tạo (constructor) trong Python luôn có tên cố định là `__init__` (với 2 dấu gạch dưới ở đầu và cuối).',
            'medium'
        ),
        (
            tp_func,
            'fill_code',
            'Điền từ khóa trả về kết quả từ một hàm trong Python vào chỗ trống `[___]`.',
            'def square(x):\n    [___] x * x',
            None,
            'return',
            'Từ khóa `return` được dùng trong hàm để trả về giá trị cho vị trí gọi hàm.',
            'easy'
        )
    ]

    for q in questions_list:
        cursor.execute('''
            INSERT INTO questions (topic_id, question_type, question_text, code_snippet, options_json, correct_answer, explanation, difficulty)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ''', q)

    # Seed demo attempts for student so statistics have instant graphs!
    cursor.execute('''
        INSERT INTO quiz_attempts (user_id, subject_id, chapter_id, topic_id, score, total_questions, correct_count, percentage, time_spent_seconds, completed_at)
        VALUES (1, ?, ?, ?, 8.0, 10, 8, 80.0, 180, DATETIME('now', '-3 days'))
    ''', (py_id, ch1_py, tp_loop))

    cursor.execute('''
        INSERT INTO quiz_attempts (user_id, subject_id, chapter_id, topic_id, score, total_questions, correct_count, percentage, time_spent_seconds, completed_at)
        VALUES (1, ?, ?, ?, 4.0, 10, 4, 40.0, 210, DATETIME('now', '-2 days'))
    ''', (py_id, ch1_py, tp_loop))

    cursor.execute('''
        INSERT INTO quiz_attempts (user_id, subject_id, chapter_id, topic_id, score, total_questions, correct_count, percentage, time_spent_seconds, completed_at)
        VALUES (1, ?, ?, ?, 9.0, 10, 9, 90.0, 150, DATETIME('now', '-1 days'))
    ''', (py_id, ch2_py, tp_func))

    conn.commit()
    conn.close()
    print("Database seeded successfully!")

if __name__ == '__main__':
    init_db()
    seed_db()
