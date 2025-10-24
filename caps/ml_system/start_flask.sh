#!/bin/bash
# Bash script to start Flask ML API Server
# For Linux/Mac

echo "============================================================"
echo "Starting Flask ML API Server"
echo "============================================================"
echo ""

# Check if Python is installed
if ! command -v python3 &> /dev/null
then
    echo "ERROR: Python 3 is not installed"
    echo "Please install Python 3.8+ from your package manager"
    exit 1
fi

echo "Python found!"
echo ""

# Check if Flask is installed
python3 -c "import flask" 2>/dev/null
if [ $? -ne 0 ]; then
    echo "Flask is not installed. Installing dependencies..."
    echo ""
    python3 -m pip install -r requirements.txt
    if [ $? -ne 0 ]; then
        echo "ERROR: Failed to install dependencies"
        exit 1
    fi
    echo ""
    echo "Dependencies installed successfully!"
    echo ""
fi

echo "Starting Flask server..."
echo "Server will run on http://localhost:5000"
echo "Press Ctrl+C to stop the server"
echo ""
echo "============================================================"
echo ""

# Change to API directory
cd api

# Start Flask app
python3 ml_flask_api.py

