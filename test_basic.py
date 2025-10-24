#!/usr/bin/env python3
"""
Basic test script
"""
import sys
import json

print("Starting basic test...")
print(f"Arguments: {sys.argv}")

# Simple test
result = {
    "status": "accepted",
    "full_name": "MA. MONIZA MAGNO RODRIGAZO",
    "barangay": "BACONG-MONTILLA",
    "message": "Test successful"
}

print(json.dumps(result))
print("Test completed")
