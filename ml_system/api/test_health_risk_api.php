<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Risk ML API Tester</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .test-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .status-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 8px; }
        .status-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; }
        .status-pending { background: #fff3cd; color: #856404; padding: 10px; border-radius: 8px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 8px; max-height: 400px; overflow-y: auto; }
        .risk-badge { padding: 5px 15px; border-radius: 20px; font-weight: bold; display: inline-block; }
        .risk-critical { background: #dc3545; color: white; }
        .risk-high { background: #fd7e14; color: white; }
        .risk-medium { background: #ffc107; color: #333; }
        .risk-low { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-card">
            <h1><i class="fas fa-heartbeat"></i> Health Risk ML API Tester</h1>
            <p class="lead">Test health risk prediction endpoints</p>
            <div class="alert alert-info">
                <strong>Note:</strong> Flask server must be running on port 5000
            </div>
        </div>

        <!-- Server Status -->
        <div class="test-card">
            <h3><i class="fas fa-server"></i> Flask Server Status</h3>
            <button class="btn btn-primary" onclick="checkServer()">Check Server</button>
            <div id="server-status" class="mt-3"></div>
        </div>

        <!-- Test Health Prediction (Custom Data) -->
        <div class="test-card">
            <h3><i class="fas fa-brain"></i> Test Health Prediction (Custom Data)</h3>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Symptoms (comma-separated):</label>
                    <input type="text" class="form-control" id="symptoms" 
                           value="fever,lethargy,loss_of_appetite" 
                           placeholder="fever,lethargy,loss_of_appetite">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Temperature (°C):</label>
                    <input type="number" class="form-control" id="temperature" 
                           value="39.5" step="0.1">
                </div>
            </div>
            <button class="btn btn-success" onclick="testHealthPrediction()">
                <i class="fas fa-stethoscope"></i> Predict Health Risk
            </button>
            <div id="prediction-result" class="mt-3"></div>
        </div>

        <!-- Test Animal Assessment (By ID) -->
        <div class="test-card">
            <h3><i class="fas fa-paw"></i> Test Animal Assessment (By ID)</h3>
            <div class="mb-3">
                <label class="form-label">Animal ID:</label>
                <input type="number" class="form-control" id="animal_id" 
                       value="1" placeholder="Enter animal ID">
            </div>
            <button class="btn btn-info" onclick="testAnimalAssessment()">
                <i class="fas fa-search"></i> Assess Animal
            </button>
            <div id="assessment-result" class="mt-3"></div>
        </div>

        <!-- All Tests -->
        <div class="test-card">
            <h3><i class="fas fa-rocket"></i> Run All Tests</h3>
            <button class="btn btn-lg btn-success" onclick="runAllTests()">
                <i class="fas fa-play"></i> Run All Health Risk Tests
            </button>
            <div id="all-results" class="mt-3"></div>
        </div>
    </div>

    <script>
        const FLASK_URL = 'http://localhost:5000';
        
        async function checkServer() {
            showResult('server-status', 'pending', 'Checking...', 'Testing Flask server...');
            
            try {
                const response = await fetch(`${FLASK_URL}/health`);
                const data = await response.json();
                
                if (data.success) {
                    showResult('server-status', 'success', 
                        'Flask Server is Online! ✅', 
                        `Server is healthy and ready for ML predictions.`, 
                        data);
                } else {
                    showResult('server-status', 'error', 
                        'Server Responded (Unusual)', 
                        'Server is running but returned unexpected data.', 
                        data);
                }
            } catch (error) {
                showResult('server-status', 'error', 
                    'Flask Server Offline ❌', 
                    `Cannot connect to Flask server.<br><br>
                    <strong>Start it with:</strong><br>
                    <code>cd ml_system && start_flask.bat</code>`);
            }
        }

        async function testHealthPrediction() {
            showResult('prediction-result', 'pending', 'Predicting...', 'Running ML health risk prediction...');
            
            const symptoms = document.getElementById('symptoms').value.split(',').map(s => s.trim());
            const temperature = parseFloat(document.getElementById('temperature').value) || 38.5;
            
            const testData = {
                symptoms: symptoms,
                vital_signs: {
                    temperature: temperature,
                    weight: 350,
                    heart_rate: 75
                },
                environment: {
                    temperature: 28.0,
                    humidity: 65.0,
                    season: 'summer'
                },
                animal_characteristics: {
                    species: 'cattle',
                    breed: 'native',
                    age: 2,
                    type: 'livestock'
                },
                health_status: 'under observation'
            };
            
            try {
                const response = await fetch(`${FLASK_URL}/api/health/predict`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(testData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const riskClass = getRiskClass(data.risk_level);
                    showResult('prediction-result', 'success', 
                        'Health Risk Predicted Successfully! ✅', 
                        `<div class="mb-3">
                            <strong>Risk Level:</strong> <span class="risk-badge ${riskClass}">${data.risk_level}</span><br>
                            <strong>Risk Score:</strong> ${data.risk_score}/100<br>
                            <strong>Confidence:</strong> ${(data.confidence * 100).toFixed(1)}%<br>
                            <strong>Model:</strong> ${data.model_version}
                         </div>
                         <strong>Recommendations:</strong>
                         <ul>
                            ${data.recommendations.map(r => `<li>${r}</li>`).join('')}
                         </ul>`, 
                        data);
                } else {
                    showResult('prediction-result', 'error', 
                        'Prediction Failed', 
                        data.error || 'Unknown error', 
                        data);
                }
            } catch (error) {
                showResult('prediction-result', 'error', 
                    'Connection Error', 
                    error.message);
            }
        }

        async function testAnimalAssessment() {
            const animalId = document.getElementById('animal_id').value;
            showResult('assessment-result', 'pending', 'Assessing...', `Assessing animal ID ${animalId}...`);
            
            try {
                const response = await fetch(`${FLASK_URL}/api/health/assess/${animalId}`);
                const data = await response.json();
                
                if (data.success) {
                    const riskClass = getRiskClass(data.risk_level);
                    const animalInfo = data.animal_info || {};
                    
                    showResult('assessment-result', 'success', 
                        'Animal Assessment Complete! ✅', 
                        `<div class="mb-3">
                            <strong>Animal:</strong> ${animalInfo.species || 'Unknown'} (ID: ${animalInfo.animal_id})<br>
                            <strong>Client:</strong> ${animalInfo.client_name || 'Unknown'}<br>
                            <strong>Barangay:</strong> ${animalInfo.barangay || 'Unknown'}<br>
                            <strong>Risk Level:</strong> <span class="risk-badge ${riskClass}">${data.risk_level}</span><br>
                            <strong>Risk Score:</strong> ${data.risk_score}/100<br>
                            <strong>Confidence:</strong> ${(data.confidence * 100).toFixed(1)}%
                         </div>
                         <strong>Recommendations:</strong>
                         <ul>
                            ${data.recommendations.map(r => `<li>${r}</li>`).join('')}
                         </ul>`, 
                        data);
                } else {
                    showResult('assessment-result', 'error', 
                        'Assessment Failed', 
                        data.error || 'Animal not found or error occurred', 
                        data);
                }
            } catch (error) {
                showResult('assessment-result', 'error', 
                    'Connection Error', 
                    error.message);
            }
        }

        async function runAllTests() {
            document.getElementById('all-results').innerHTML = 
                '<div class="status-pending">⏳ Running all health risk tests...</div>';
            
            await checkServer();
            await new Promise(r => setTimeout(r, 1000));
            
            await testHealthPrediction();
            await new Promise(r => setTimeout(r, 1000));
            
            await testAnimalAssessment();
            
            document.getElementById('all-results').innerHTML = 
                '<div class="status-success">✅ All tests completed! Check results above.</div>';
        }

        function showResult(elementId, status, title, message, data = null) {
            const el = document.getElementById(elementId);
            const statusClass = `status-${status}`;
            
            let html = `
                <div class="${statusClass} mb-3">
                    <h5>${title}</h5>
                    <p>${message}</p>
                </div>
            `;
            
            if (data) {
                html += `<details><summary>View Raw JSON Response</summary><pre>${JSON.stringify(data, null, 2)}</pre></details>`;
            }
            
            el.innerHTML = html;
        }

        function getRiskClass(riskLevel) {
            const map = {
                'Critical': 'risk-critical',
                'High': 'risk-high',
                'Medium': 'risk-medium',
                'Low': 'risk-low'
            };
            return map[riskLevel] || 'risk-medium';
        }

        // Auto-check server on load
        window.addEventListener('load', checkServer);
    </script>
</body>
</html>

