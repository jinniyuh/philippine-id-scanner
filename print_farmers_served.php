<?php
session_start();
include 'includes/conn.php';
include 'includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: login.php');
    exit();
}

// Get date range from GET parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Format dates for display
$displayStartDate = date('F d, Y', strtotime($startDate));
$displayEndDate = date('F d, Y', strtotime($endDate));

// Log activity when report is accessed
$user_id = $_SESSION['user_id'];
$activity_message = "Printed Farmers Served Report for period {$displayStartDate} to {$displayEndDate}";
logActivity($conn, $user_id, $activity_message);

// Use your exact SQL with date filtering
$sql = "
SELECT t.*, 
       c.full_name AS client_name, 
       c.barangay AS address, 
       p.name AS pharma_name
FROM transactions t
JOIN clients c ON t.client_id = c.client_id
JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
WHERE COALESCE(t.issued_date, t.request_date) BETWEEN '$startDate' AND '$endDate'
ORDER BY t.transaction_id DESC
";

$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}
$data = $result->fetch_all(MYSQLI_ASSOC);

// Pagination for printing: 10 transactions per page
$perPage = 10;
$total = count($data);
$pages = ceil($total / $perPage);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Farmers Served Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        @media print { .no-print { display:none; } }
        body { font-family: Arial, sans-serif; }
        .sheet-title { text-align:center; margin: 10px 0 5px; font-weight:700; font-size: 20px; }
        table { font-size: 12px; }
        .print-page {
            page-break-after: always;
            padding-bottom: 30px;
        }
        .print-page:last-child {
            page-break-after: auto;
        }
    </style>
</head>
<body>
<div class="d-flex justify-content-between align-items-center no-print" style="padding: 20px;">
    <h5 class="mb-0"></h5>
    <button onclick="window.print()" class="btn btn-primary no-print"><i class="fas fa-print"></i> Print</button>
</div>
<?php if (!empty($data)): ?>
    <?php for ($page = 0; $page < $pages; $page++): ?>
        <div class="container-fluid p-4 print-page">
            <!-- Header with logos and office info, as in print_livestock_disseminated.php -->
            <div class="mb-4 text-center">
                <!-- Logos -->
                <div class="d-flex justify-content-center align-items-center mb-2">
                    <img src="bcvo.png" alt="Logo 1" style="height:70px; margin:0 20px;">
                    <img src="bagocity.png" alt="Logo 2" style="height:70px; margin:0 20px;">
                    <img src="bagongpilipinas.jpg" alt="Logo 3" style="height:70px; margin:0 20px;">
                </div>
                <!-- Header text -->
                <div style="font-size:14px;">Republic of the Philippines</div>
                <div style="font-size:14px;">City of Bago</div>
                <div style="font-weight:bold; font-size:16px;">OFFICE OF THE CITY VETERINARIAN</div>
                <div style="font-size:13px;">vetbagoCity@gmail.com</div>
                <div style="font-size:13px;">(034) 454-3115</div>
            </div>
            <div class="sheet-title">FARMERS SERVED</div>
            <div class="text-center" style="font-size:14px; margin-bottom:15px;">
                <strong>Period: <?= $displayStartDate ?> to <?= $displayEndDate ?></strong>
            </div>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Client Name</th>
                        <th>Barangay</th>
                        <th>Medicine</th>
                        <th>Quantity</th>
                        <th>Issued Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $start = $page * $perPage;
                    $end = min($start + $perPage, $total);
                    for ($i = $start; $i < $end; $i++):
                        $r = $data[$i];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($r['client_name']) ?></td>
                            <td><?= htmlspecialchars($r['address']) ?></td>
                            <td><?= htmlspecialchars($r['pharma_name']) ?></td>
                            <td><?= htmlspecialchars($r['quantity']) ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($r['issued_date']))) ?></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <div class="mt-4 row">
                <div class="col-6">Submitted by: _________________________</div>
                <div class="col-6 text-end">Certified correct: _____________________</div>
            </div>
        </div>
    <?php endfor; ?>
<?php else: ?>
    <div class="container-fluid p-4">
        <div class="mb-4 text-center">
            <div class="d-flex justify-content-center align-items-center mb-2">
                <img src="bcvo.png" alt="Logo 1" style="height:70px; margin:0 20px;">
                <img src="bagocity.jpg" alt="Logo 2" style="height:70px; margin:0 20px;">
                <img src="bagongpilipinas.jpg" alt="Logo 3" style="height:70px; margin:0 20px;">
            </div>
            <div style="font-size:14px;">Republic of the Philippines</div>
            <div style="font-size:14px;">City of Bago</div>
            <div style="font-weight:bold; font-size:16px;">OFFICE OF THE CITY VETERINARIAN</div>
            <div style="font-size:13px;">vetbagoCity@gmail.com</div>
            <div style="font-size:13px;">(034) 454-3115</div>
        </div>
        <div class="sheet-title">FARMERS SERVED</div>
        <div class="text-center" style="font-size:14px; margin-bottom:15px;">
            <strong>Period: <?= $displayStartDate ?> to <?= $displayEndDate ?></strong>
        </div>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Client Name</th>
                    <th>Barangay</th>
                    <th>Medicine</th>
                    <th>Quantity</th>
                    <th>Issued Date</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="5" class="text-center">No records</td></tr>
            </tbody>
        </table>
        <div class="mt-4 row">
            <div class="col-6">Submitted by: _________________________</div>
            <div class="col-6 text-end">Certified correct: _____________________</div>
        </div>
    </div>
<?php endif; ?>
<script>
window.addEventListener('load', () => { setTimeout(() => window.print(), 300); });
</script>
</body>
</html>