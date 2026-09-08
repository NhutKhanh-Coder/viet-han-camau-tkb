import unittest
import json
import sys
import os

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from app import app
from database import get_db_connection

class QuizAppTestCase(unittest.TestCase):
    def setUp(self):
        self.app = app.test_client()
        self.app.testing = True

    def test_01_login_page(self):
        response = self.app.get('/login')
        self.assertEqual(response.status_code, 200)
        self.assertIn('ĐĂNG NHẬP HỆ THỐNG'.encode('utf-8'), response.data)

    def test_02_student_login_and_dashboard(self):
        response = self.app.post('/login', data=dict(
            username='sinhvien',
            password='123456'
        ), follow_redirects=True)
        self.assertEqual(response.status_code, 200)
        self.assertIn('DANH SÁCH MÔN HỌC ÔN TẬP'.encode('utf-8'), response.data)

    def test_03_teacher_login_and_question_bank(self):
        response = self.app.post('/login', data=dict(
            username='giaovien',
            password='123456'
        ), follow_redirects=True)
        self.assertEqual(response.status_code, 200)
        self.assertIn('DASHBOARD GIÁO VIÊN'.encode('utf-8'), response.data)

        # Test question bank
        response_qb = self.app.get('/teacher/question-bank')
        self.assertEqual(response_qb.status_code, 200)

        # Test completeness
        response_comp = self.app.get('/teacher/completeness')
        self.assertEqual(response_comp.status_code, 200)
        self.assertIn('BÁO CÁO MỨC ĐỘ HOÀN THIỆN WEB'.encode('utf-8'), response_comp.data)

    def test_04_weak_chapter_statistics(self):
        with self.app.session_transaction() as sess:
            sess['user_id'] = 1
            sess['username'] = 'sinhvien'
            sess['full_name'] = 'Nguyễn Văn An'
            sess['role'] = 'student'

        response = self.app.get('/student/statistics')
        self.assertEqual(response.status_code, 200)
        self.assertIn('PHÂN TÍCH CHƯƠNG SAI NHIỀU NHẤT'.encode('utf-8'), response.data)

if __name__ == '__main__':
    unittest.main()
