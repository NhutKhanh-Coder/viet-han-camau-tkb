@echo off
chcp 65001 > nul
echo ======================================================================
echo  [SmartEdu AI] Chạy Kiểm Thử Mô Hình & Đánh Giá AI Metrics (Bảng C)
echo ======================================================================
echo.
echo 1. Đang kiểm tra môi trường Python...
python --version
if %errorlevel% neq 0 (
    echo [LỖI] Không tìm thấy Python. Vui lòng cài đặt Python 3.10+
    pause
    exit /b
)

echo.
echo 2. Đang chạy pipeline huấn luyện & đánh giá...
python ai_engine/train_evaluate.py

echo.
echo 3. Đang kiểm tra service suy luận thời gian thực...
python ai_engine/predict_service.py

echo.
echo ======================================================================
echo  [Hoàn tất] Kết quả đã được lưu tại: ai_engine/model_evaluation_results.json
echo ======================================================================
pause
