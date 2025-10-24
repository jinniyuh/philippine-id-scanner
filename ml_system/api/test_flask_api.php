<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flask API Tester</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
        }
        .test-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 0;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
        }
        .endpoint-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-card">
            <h1 class="mb-4">🧪 Flask ML API Tester</h1>
            <p class="lead">Test your Flask ML API endpoints to ensure everything is working correctly.</p>
            
            <div class="alert alert-info">
                <strong>⚠️ Important:</strong> Make sure Flask server is running on port 5000 before testing.
                <br>Start it with: <code>start_flask.bat</code> (Windows) or <code>./start_flask.sh</code> (Linux/Mac)
            </div>
        </div>

        <!-- Server Status -->
        <div class="test-card">
            <h3>🔌 Server Status</h3>
            <button class="btn btn-primary" onclick="testHealth()">Check Flask Server</button>
            <div id="health-result" class="mt-3"></div>
        </div>

        <!-- API Info -->
        <div class="test-card">
            <h3>📋 API Information</h3>
            <button class="btn btn-primary" onclick="testApiInfo()">Get API Info</button>
            <div id="info-result" class="mt-3"></div>
        </div>

        <!-- Full Insights Test -->
        <div class="test-card">
            <h3>🧠 ML Insights Test</h3>
            <button class="btn btn-success" onclick="testInsights()">Get Full ML Insights</button>
            <div id="insights-result" class="mt-3"></div>
        </div>

        <!-- Custom Forecast Test -->
        <div class="test-card">
            <h3>📈 Custom Forecast Test</h3>
            <div class="endpoint-box">
                <strong>POST /api/forecast</strong><br>
                Test with sample historical data
            </div>
            <button class="btn btn-info" onclick="testForecast()">Run Forecast Test</button>
            <div id="forecast-result" class="mt-3"></div>
        </div>

        <!-- All Tests -->
        <div class="test-card">
            <h3>🚀 Run All Tests</h3>
            <button class="btn btn-lg btn-success" onclick="runAllTests()">Run All Tests</button>
            <div id="all-tests-result" class="mt-3"></div>
        </div>
    </div>

    <script>
        const FLASK_URL = 'http://localhost:5000';
        
        function showResult(elementId, status, title, message, data = null) {
            const el = document.getElementById(elementId);
            const statusClass = status === 'success' ? 'status-success' : status === 'error' ? 'status-error' : 'status-pending';
            
            let html = `
                <div class="status-badge ${statusClass}">
                    ${status === 'success' ? '✅ SUCCESS' : status === 'error' ? '❌ ERROR' : '⏳ TESTING...'}
                </div>
                <h5>${title}</h5>
                <p>${message}</p>
            `;
            
            if (data) {
                html += `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            }
            
            el.innerHTML = html;
        }

        async function testHealth() {
            showResult('health-result', 'pending', 'Testing...', 'Checking Flask server health...');
            
            try {
                const response = await fetch(`${FLASK_URL}/health`);
                const data = await response.json();
                
                if (data.success && data.status === 'healthy') {
                    showResult('health-result', 'success', 
                        'Flask Server is Running!', 
                        `Server is healthy and responding on port 5000.`, 
                        data);
                } else {
                    showResult('health-result', 'error', 
                        'Unexpected Response', 
                        'Server responded but with unexpected data.', 
                        data);
                }
            } catch (error) {
                showResult('health-result', 'error', 
                    'Flask Server Not Running', 
                    `Could not connect to Flask server. Error: ${error.message}<br><br>
                    <strong>Solution:</strong> Start Flask server with <code>start_flask.bat</code>`);
            }
        }

        async function testApiInfo() {
            showResult('info-result', 'pending', 'Testing...', 'Fetching API information...');
            
            try {
                const response = await fetch(`${FLASK_URL}/`);
                const data = await response.json();
                
                if (data.success) {
                    showResult('info-result', 'success', 
                        'API Information Retrieved', 
                        `Version: ${data.version}`, 
                        data);
                } else {
                    showResult('info-result', 'error', 'API Error', 'Failed to get API info', data);
                }
            } catch (error) {
                showResult('info-result', 'error', 'Connection Error', error.message);
            }
        }

        async function testInsights() {
            showResult('insights-result', 'pending', 'Testing...', 'Generating ML insights (this may take a few seconds)...');
            
            try {
                const startTime = Date.now();
                const response = await fetch(`${FLASK_URL}/api/insights`);
                const data = await response.json();
                const duration = ((Date.now() - startTime) / 1000).toFixed(2);
                
                if (data.success) {
                    const insightKeys = Object.keys(data.insights || {});
                    showResult('insights-result', 'success', 
                        'ML Insights Generated Successfully!', 
                        `Generated ${insightKeys.length} insight categories in ${duration} seconds.<br>
                        Categories: ${insightKeys.join(', ')}`, 
                        data);
                } else {
                    showResult('insights-result', 'error', 'Insights Generation Failed', 
                        data.error || 'Unknown error', data);
                }
            } catch (error) {
                showResult('insights-result', 'error', 'Connection Error', error.message);
            }
        }

        async function testForecast() {
            showResult('forecast-result', 'pending', 'Testing...', 'Running custom forecast...');
            
            const testData = {
                type: 'pharmaceutical',
                historical_data: [10, 12, 15, 18, 20, 22, 25, 28, 30, 32, 35, 38],
                months_ahead: 3
            };
            
            try {
                const response = await fetch(`${FLASK_URL}/api/forecast`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(testData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showResult('forecast-result', 'success', 
                        'Forecast Generated!', 
                        `Forecast: ${data.result.forecast.join(', ')}<br>
                        Trend: ${data.result.trend} (${data.result.trend_percentage}%)`, 
                        data);
                } else {
                    showResult('forecast-result', 'error', 'Forecast Failed', 
                        data.error || 'Unknown error', data);
                }
            } catch (error) {
                showResult('forecast-result', 'error', 'Connection Error', error.message);
            }
        }

        async function runAllTests() {
            const resultsEl = document.getElementById('all-tests-result');
            resultsEl.innerHTML = '<div class="status-badge status-pending">⏳ Running all tests...</div>';
            
            const tests = [
                { name: 'Health Check', fn: testHealth, id: 'health-result' },
                { name: 'API Info', fn: testApiInfo, id: 'info-result' },
                { name: 'ML Insights', fn: testInsights, id: 'insights-result' },
                { name: 'Custom Forecast', fn: testForecast, id: 'forecast-result' }
            ];
            
            for (const test of tests) {
                await test.fn();
                await new Promise(resolve => setTimeout(resolve, 1000)); // Wait 1 second between tests
            }
            
            resultsEl.innerHTML = `
                <div class="status-badge status-success">✅ All tests completed!</div>
                <p>Check individual test results above.</p>
            `;
        }
    </script>
</body>
</html>

