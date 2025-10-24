<?php
session_start();
include 'includes/conn.php';

// Check admin session
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get parameters
$format = $_GET['format'] ?? 'pdf';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Fetch data based on date range
$topMedicines = [];
$res = $conn->query("SELECT p.name, SUM(t.quantity) as total_dispensed FROM transactions t JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id WHERE t.status='Approved' AND t.issued_date BETWEEN '$startDate' AND '$endDate' GROUP BY t.pharma_id ORDER BY total_dispensed DESC LIMIT 5");
if($res) while($row=$res->fetch_assoc()) $topMedicines[]=$row;

// Summary Statistics
$summaryStats = [];
$res = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE issued_date BETWEEN '$startDate' AND '$endDate'");
if($res) $summaryStats['total_transactions'] = $res->fetch_assoc()['total'];

$res = $conn->query("SELECT SUM(quantity) as total FROM transactions WHERE status='Approved' AND issued_date BETWEEN '$startDate' AND '$endDate'");
if($res) $summaryStats['total_medicines'] = $res->fetch_assoc()['total'] ?? 0;

$res = $conn->query("SELECT COUNT(DISTINCT client_id) as total FROM transactions WHERE issued_date BETWEEN '$startDate' AND '$endDate'");
if($res) $summaryStats['total_clients'] = $res->fetch_assoc()['total'];

// Livestock & Poultry
$livestockCount = 0; $poultryCount = 0;
$res = $conn->query("SELECT animal_type,SUM(quantity) as total FROM livestock_poultry GROUP BY animal_type");
if($res) while($row=$res->fetch_assoc()){
    if($row['animal_type']=='Livestock') $livestockCount=(int)$row['total'];
    if($row['animal_type']=='Poultry') $poultryCount=(int)$row['total'];
}

// Status Breakdown
$statusBreakdown = ['Pending'=>0,'Approved'=>0];
$res = $conn->query("SELECT status, COUNT(*) as total FROM transactions WHERE status IN ('Pending', 'Approved') AND issued_date BETWEEN '$startDate' AND '$endDate' GROUP BY status");
if($res) while($row=$res->fetch_assoc()) $statusBreakdown[$row['status']] = (int)$row['total'];

switch($format) {
    case 'csv':
        exportCSV($startDate, $endDate, $summaryStats, $topMedicines, $livestockCount, $poultryCount, $statusBreakdown);
        break;
    case 'excel':
        exportExcel($startDate, $endDate, $summaryStats, $topMedicines, $livestockCount, $poultryCount, $statusBreakdown);
        break;
    case 'pdf':
    default:
        exportPDF($startDate, $endDate, $summaryStats, $topMedicines, $livestockCount, $poultryCount, $statusBreakdown);
        break;
}

function exportCSV($startDate, $endDate, $summaryStats, $topMedicines, $livestockCount, $poultryCount, $statusBreakdown) {
    $filename = "reports_" . date('Y-m-d_H-i-s') . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, ['Bago City Veterinary Office - Reports Export']);
    fputcsv($output, ['Date Range: ' . $startDate . ' to ' . $endDate]);
    fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Summary Statistics
    fputcsv($output, ['SUMMARY STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Transactions', $summaryStats['total_transactions']]);
    fputcsv($output, ['Total Medicines Dispensed', $summaryStats['total_medicines']]);
    fputcsv($output, ['Total Clients Served', $summaryStats['total_clients']]);
    fputcsv($output, []);
    
    // Top Medicines
    fputcsv($output, ['TOP 5 MEDICINES DISPENSED']);
    fputcsv($output, ['Rank', 'Medicine Name', 'Total Dispensed']);
    foreach($topMedicines as $index => $medicine) {
        fputcsv($output, [$index + 1, $medicine['name'], $medicine['total_dispensed']]);
    }
    fputcsv($output, []);
    
    // Animal Distribution
    fputcsv($output, ['ANIMAL DISTRIBUTION']);
    fputcsv($output, ['Type', 'Count']);
    fputcsv($output, ['Livestock', $livestockCount]);
    fputcsv($output, ['Poultry', $poultryCount]);
    fputcsv($output, []);
    
    // Status Breakdown
    fputcsv($output, ['REQUEST STATUS BREAKDOWN']);
    fputcsv($output, ['Status', 'Count']);
    fputcsv($output, ['Pending', $statusBreakdown['Pending']]);
    fputcsv($output, ['Approved', $statusBreakdown['Approved']]);
    
    fclose($output);
}

function exportExcel($startDate, $endDate, $summaryStats, $topMedicines, $livestockCount, $poultryCount, $statusBreakdown) {
    $filename = "reports_" . date('Y-m-d_H-i-s') . ".xlsx";
    
    // Simple Excel export using HTML table (for basic functionality)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo '<table border="1">';
    echo '<tr><td colspan="2"><b>Bago City Veterinary Office - Reports Export</b></td></tr>';
    echo '<tr><td colspan="2">Date Range: ' . $startDate . ' to ' . $endDate . '</td></tr>';
    echo '<tr><td colspan="2">Generated: ' . date('Y-m-d H:i:s') . '</td></tr>';
    echo '<tr><td colspan="2"></td></tr>';
    
    echo '<tr><td colspan="2"><b>SUMMARY STATISTICS</b></td></tr>';
    echo '<tr><td><b>Metric</b></td><td><b>Value</b></td></tr>';
    echo '<tr><td>Total Transactions</td><td>' . $summaryStats['total_transactions'] . '</td></tr>';
    echo '<tr><td>Total Medicines Dispensed</td><td>' . $summaryStats['total_medicines'] . '</td></tr>';
    echo '<tr><td>Total Clients Served</td><td>' . $summaryStats['total_clients'] . '</td></tr>';
    echo '<tr><td colspan="2"></td></tr>';
    
    echo '<tr><td colspan="2"><b>TOP 5 MEDICINES DISPENSED</b></td></tr>';
    echo '<tr><td><b>Rank</b></td><td><b>Medicine Name</b></td><td><b>Total Dispensed</b></td></tr>';
    foreach($topMedicines as $index => $medicine) {
        echo '<tr><td>' . ($index + 1) . '</td><td>' . $medicine['name'] . '</td><td>' . $medicine['total_dispensed'] . '</td></tr>';
    }
    echo '<tr><td colspan="3"></td></tr>';
    
    echo '<tr><td colspan="2"><b>ANIMAL DISTRIBUTION</b></td></tr>';
    echo '<tr><td><b>Type</b></td><td><b>Count</b></td></tr>';
    echo '<tr><td>Livestock</td><td>' . $livestockCount . '</td></tr>';
    echo '<tr><td>Poultry</td><td>' . $poultryCount . '</td></tr>';
    echo '<tr><td colspan="2"></td></tr>';
    
    echo '<tr><td colspan="2"><b>REQUEST STATUS BREAKDOWN</b></td></tr>';
    echo '<tr><td><b>Status</b></td><td><b>Count</b></td></tr>';
    echo '<tr><td>Pending</td><td>' . $statusBreakdown['Pending'] . '</td></tr>';
    echo '<tr><td>Approved</td><td>' . $statusBreakdown['Approved'] . '</td></tr>';
    
    echo '</table>';
}

function exportPDF($startDate, $endDate, $summaryStats, $topMedicines, $livestockCount, $poultryCount, $statusBreakdown) {
    $filename = "reports_" . date('Y-m-d_H-i-s') . ".pdf";
    
    // Simple PDF export using HTML (for basic functionality)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Bago City Veterinary Office - Reports</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #6c63ff; text-align: center; }
            h2 { color: #2c3e50; border-bottom: 2px solid #6c63ff; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <h1>Bago City Veterinary Office</h1>
        <h2>Reports Export</h2>
        <p><strong>Date Range:</strong> ' . $startDate . ' to ' . $endDate . '</p>
        <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
        
        <div class="summary">
            <h2>Summary Statistics</h2>
            <table>
                <tr><th>Metric</th><th>Value</th></tr>
                <tr><td>Total Transactions</td><td>' . number_format($summaryStats['total_transactions']) . '</td></tr>
                <tr><td>Total Medicines Dispensed</td><td>' . number_format($summaryStats['total_medicines']) . '</td></tr>
                <tr><td>Total Clients Served</td><td>' . number_format($summaryStats['total_clients']) . '</td></tr>
            </table>
        </div>
        
        <h2>Top 5 Medicines Dispensed</h2>
        <table>
            <tr><th>Rank</th><th>Medicine Name</th><th>Total Dispensed</th></tr>';
    
    foreach($topMedicines as $index => $medicine) {
        $html .= '<tr><td>' . ($index + 1) . '</td><td>' . htmlspecialchars($medicine['name']) . '</td><td>' . number_format($medicine['total_dispensed']) . '</td></tr>';
    }
    
    $html .= '
        </table>
        
        <h2>Animal Distribution</h2>
        <table>
            <tr><th>Type</th><th>Count</th></tr>
            <tr><td>Livestock</td><td>' . number_format($livestockCount) . '</td></tr>
            <tr><td>Poultry</td><td>' . number_format($poultryCount) . '</td></tr>
        </table>
        
        <h2>Request Status Breakdown</h2>
        <table>
            <tr><th>Status</th><th>Count</th></tr>
            <tr><td>Pending</td><td>' . number_format($statusBreakdown['Pending']) . '</td></tr>
            <tr><td>Approved</td><td>' . number_format($statusBreakdown['Approved']) . '</td></tr>
        </table>
    </body>
    </html>';
    
    echo $html;
}
?>
