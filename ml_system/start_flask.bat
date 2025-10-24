@echo off
REM Batch file to start Flask ML API Server
REM For Windows

echo ============================================================
echo Starting Flask ML API Server
echo ============================================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8+ from https://www.python.org/
    pause
    exit /b 1
)

echo Python found!
echo.

REM Check if Flask is installed
python -c "import flask" >nul 2>&1
if errorlevel 1 (
    echo Flask is not installed. Installing dependencies...
    echo.
    python -m pip install -r requirements.txt
    if errorlevel 1 (
        echo ERROR: Failed to install dependencies
        pause
        exit /b 1
    )
    echo.
    echo Dependencies installed successfully!
    echo.
)

echo Starting Flask server...
echo Server will run on http://localhost:5000
echo Press Ctrl+C to stop the server
echo.
echo ============================================================
echo.

REM Change to API directory
cd api

REM Start Flask app
python ml_flask_api.py

pause

