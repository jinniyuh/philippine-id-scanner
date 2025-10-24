#!/bin/bash

echo "Starting Philippine ID Scanner Flask API..."
echo

# Check if Python is available
if ! command -v python3 &> /dev/null; then
    echo "Error: Python 3 is not installed or not in PATH"
    echo "Please install Python 3.8+ and try again"
    exit 1
fi

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo "Creating virtual environment..."
    python3 -m venv venv
fi

# Activate virtual environment
echo "Activating virtual environment..."
source venv/bin/activate

# Install requirements
echo "Installing requirements..."
pip install -r flask_requirements.txt

# Start Flask API
echo
echo "Starting Flask API server..."
echo "API will be available at: http://localhost:5000"
echo
echo "Available endpoints:"
echo "  POST /api/scan-id - Upload image file"
echo "  POST /api/scan-id-base64 - Send base64 encoded image"
echo "  POST /api/validate-name - Validate name and barangay"
echo "  GET /api/health - Health check"
echo
echo "Press Ctrl+C to stop the server"
echo

python3 flask_id_scanner.py
