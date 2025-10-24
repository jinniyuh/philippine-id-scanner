@echo off
echo Starting Philippine ID Scanner Flask API...
echo.

REM Check if Python is available
python --version >nul 2>&1
if errorlevel 1 (
    echo Error: Python is not installed or not in PATH
    echo Please install Python 3.8+ and try again
    pause
    exit /b 1
)

REM Check if virtual environment exists
if not exist "venv" (
    echo Creating virtual environment...
    python -m venv venv
)

REM Activate virtual environment
echo Activating virtual environment...
call venv\Scripts\activate.bat

REM Install requirements
echo Installing requirements...
pip install -r flask_requirements.txt

REM Start Flask API
echo.
echo Starting Flask API server...
echo API will be available at: http://localhost:5000
echo.
echo Available endpoints:
echo   POST /api/scan-id - Upload image file
echo   POST /api/scan-id-base64 - Send base64 encoded image  
echo   POST /api/validate-name - Validate name and barangay
echo   GET /api/health - Health check
echo.
echo Press Ctrl+C to stop the server
echo.

python flask_id_scanner.py

pause
