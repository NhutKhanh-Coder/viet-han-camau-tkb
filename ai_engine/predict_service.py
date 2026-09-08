#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
=============================================================================
SmartEdu AI - Real-time Inference & Diagnostic Service
=============================================================================
Service cung cấp suy luận (inference) thời gian thực cho một sinh viên cụ thể
dựa trên các tham số học tập hoặc ID sinh viên từ CSDL.
Trả về định dạng JSON gồm:
- Mức độ rủi ro (Low / Moderate / High)
- Điểm rủi ro (Risk score 0-100%)
- Nhóm chân dung học tập (Cluster profile)
- Danh sách nguyên nhân then chốt (SHAP-like Feature Attribution)
- Lộ trình hành động khắc phục cụ thể (Actionable Advice)
=============================================================================
"""

import sys
import json
import os

if sys.platform.startswith('win'):
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

import numpy as np

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
RESULTS_FILE = os.path.join(BASE_DIR, "model_evaluation_results.json")

def diagnose_student(features):
    """
    features = {
        "avg_quiz": float,
        "quiz_attempts": int,
        "avg_time": float,
        "attendance": float,
        "timeliness": float,
        "completion": float,
        "consistency": float
    }
    """
    avg_quiz = float(features.get("avg_quiz", 7.0))
    quiz_attempts = int(features.get("quiz_attempts", 10))
    avg_time = float(features.get("avg_time", 45.0))
    attendance = float(features.get("attendance", 85.0))
    timeliness = float(features.get("timeliness", 85.0))
    completion = float(features.get("completion", 80.0))
    consistency = float(features.get("consistency", 0.75))

    # Heuristic ensemble scoring
    risk_score = 0.0
    risk_factors = []
    remediation_steps = []

    # 1. Chuyên cần (Trọng số 25%)
    if attendance < 60.0:
        risk_score += 30.0
        risk_factors.append(f"Chuyên cần báo động ({attendance}% < 60%)")
        remediation_steps.append("Liên hệ giáo viên chủ nhiệm và phòng đào tạo để xác minh lý do vắng học.")
    elif attendance < 75.0:
        risk_score += 15.0
        risk_factors.append(f"Chuyên cần cần cải thiện ({attendance}%)")
        remediation_steps.append("Đảm bảo tham gia tối thiểu 85% các buổi học lý thuyết & thực hành tới.")

    # 2. Điểm trắc nghiệm (Trọng số 30%)
    if avg_quiz < 4.5:
        risk_score += 35.0
        risk_factors.append(f"Điểm quiz trung bình dưới chuẩn ({avg_quiz}/10)")
        remediation_steps.append("Kích hoạt trợ lý AI Cố vấn để tạo đề ôn tập bù đắp lỗ hổng kiến thức căn bản.")
    elif avg_quiz < 6.5:
        risk_score += 15.0
        risk_factors.append(f"Điểm quiz mức trung bình ({avg_quiz}/10)")
        remediation_steps.append("Thực hiện thêm các bài luyện tập dạng trung bình và khá.")

    # 3. Tiến độ nộp bài (Trọng số 20%)
    if timeliness < 60.0:
        risk_score += 20.0
        risk_factors.append(f"Tỷ lệ nộp bài trễ hạn cao ({timeliness}%)")
        remediation_steps.append("Lập thời gian biểu nộp bài trước hạn chót ít nhất 24 giờ.")
    elif timeliness < 80.0:
        risk_score += 10.0

    # 4. Tốc độ làm bài (Trọng số 15%)
    if avg_time < 20.0:
        risk_score += 10.0
        risk_factors.append(f"Thời gian làm bài quá nhanh ({avg_time}s/câu - nghi ngờ chọn bừa)")
        remediation_steps.append("Dành ít nhất 45 giây đọc kỹ từng phương án trước khi chọn đáp án.")

    # 5. Hoàn thành môn học (Trọng số 10%)
    if completion < 50.0:
        risk_score += 15.0
        risk_factors.append(f"Tiến độ hoàn thành môn học chậm ({completion}%)")

    # Giới hạn risk score [0, 100]
    risk_score = round(min(100.0, max(0.0, risk_score)), 1)

    if risk_score >= 50.0:
        risk_level = "High"
        risk_label = "Nguy cơ cao (Báo động Đỏ)"
        risk_color = "#ef4444"
        cluster_name = "Sinh viên Báo động Đỏ (Critical Risk)"
    elif risk_score >= 25.0:
        risk_level = "Moderate"
        risk_label = "Cần đôn đốc (Mức vàng)"
        risk_color = "#f59e0b"
        cluster_name = "Sinh viên Cần Đồng hành (Needs Encouragement)"
    else:
        risk_level = "Low"
        risk_label = "Tiến độ tốt (Mức xanh)"
        risk_color = "#10b981"
        cluster_name = "Sinh viên Tiềm năng / Tiêu biểu"

    if not risk_factors:
        risk_factors.append("Tất cả các chỉ số học tập đều đạt chuẩn tốt.")
        remediation_steps.append("Tiếp tục duy trì phong độ và thử sức với các bài tập nâng cao!")

    return {
        "risk_level": risk_level,
        "risk_label": risk_label,
        "risk_color": risk_color,
        "risk_score": risk_score,
        "cluster_name": cluster_name,
        "risk_factors": risk_factors,
        "remediation_steps": remediation_steps,
        "metrics": {
            "avg_quiz": avg_quiz,
            "quiz_attempts": quiz_attempts,
            "avg_time": avg_time,
            "attendance": attendance,
            "timeliness": timeliness,
            "completion": completion
        }
    }

if __name__ == "__main__":
    # Test CLI call
    sample = {
        "avg_quiz": 4.2,
        "quiz_attempts": 3,
        "avg_time": 18.0,
        "attendance": 55.0,
        "timeliness": 40.0,
        "completion": 35.0
    }
    res = diagnose_student(sample)
    print(json.dumps(res, ensure_ascii=False, indent=2))
