#!/usr/bin/env python3
"""
Test script to verify Flask API setup
"""

import sys
import os

def test_imports():
    """Test if required modules can be imported"""
    print("Testing imports...")
    
    try:
        import flask
        print("✅ Flask imported successfully")
    except ImportError as e:
        print(f"❌ Flask import failed: {e}")
        return False
    
    try:
        from flask_cors import CORS
        print("✅ Flask-CORS imported successfully")
    except ImportError as e:
        print(f"❌ Flask-CORS import failed: {e}")
        return False
    
    try:
        from doctr.io import DocumentFile
        from doctr.models import ocr_predictor
        print("✅ DocTR imported successfully")
    except ImportError as e:
        print(f"❌ DocTR import failed: {e}")
        print("   Install with: pip install python-doctr[torch]")
        return False
    
    return True

def test_directories():
    """Test if required directories exist or can be created"""
    print("\nTesting directories...")
    
    upload_dir = 'uploads/id_scans'
    if not os.path.exists(upload_dir):
        try:
            os.makedirs(upload_dir, exist_ok=True)
            print(f"✅ Created directory: {upload_dir}")
        except Exception as e:
            print(f"❌ Failed to create directory {upload_dir}: {e}")
            return False
    else:
        print(f"✅ Directory exists: {upload_dir}")
    
    return True

def test_flask_app():
    """Test if Flask app can be created"""
    print("\nTesting Flask app creation...")
    
    try:
        from flask import Flask
        from flask_cors import CORS
        
        app = Flask(__name__)
        CORS(app)
        
        @app.route('/test')
        def test_route():
            return {'status': 'ok'}
        
        print("✅ Flask app created successfully")
        return True
        
    except Exception as e:
        print(f"❌ Flask app creation failed: {e}")
        return False

def main():
    """Run all tests"""
    print("🔍 Flask API Setup Test")
    print("=" * 40)
    
    tests = [
        test_imports,
        test_directories,
        test_flask_app
    ]
    
    passed = 0
    total = len(tests)
    
    for test in tests:
        if test():
            passed += 1
        print()
    
    print("=" * 40)
    print(f"Tests passed: {passed}/{total}")
    
    if passed == total:
        print("🎉 All tests passed! Flask API is ready to run.")
        print("\nTo start the API:")
        print("  python flask_id_scanner.py")
        print("  or")
        print("  start_flask_api.bat (Windows)")
        print("  ./start_flask_api.sh (Linux)")
    else:
        print("❌ Some tests failed. Please fix the issues above.")
        print("\nCommon fixes:")
        print("  pip install -r flask_requirements.txt")
        print("  pip install python-doctr[torch]")

if __name__ == '__main__':
    main()
