<?php
session_start();
include 'includes/conn.php';
include 'includes/activity_logger.php';

// Check if admin or staff
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
$activity_message = "Printed Poultry Disseminated Report for period {$displayStartDate} to {$displayEndDate}";
logActivity($conn, $user_id, $activity_message);

// Query: Poultry dissemination with date filter
$sql = "SELECT 
            c.full_name,
            c.barangay AS address,
            lp.species,
            lp.quantity
        FROM livestock_poultry lp
        LEFT JOIN clients c ON lp.client_id = c.client_id
        WHERE (lp.animal_type = 'Poultry' OR lp.animal_type = 'Both')
          AND UPPER(COALESCE(lp.source,'')) = 'DISSEMINATED'
          AND DATE(lp.created_at) BETWEEN '$startDate' AND '$endDate'
        ORDER BY lp.created_at ASC";

$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}
$data = $result->fetch_all(MYSQLI_ASSOC);

// Pagination: 10 records per page
$perPage = 10;
$total = count($data);
$pages = ceil($total / $perPage);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Poultry Dissemination Report</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    @media print { .no-print { display:none } }
    table { font-size:12px }
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
<?php if (!empty($data)): ?>
    <?php for ($page = 0; $page < $pages; $page++): ?>
        <div class="container-fluid p-4 print-page">
            <!-- Letterhead -->
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
              <hr style="border:1px solid black; margin-top:10px;">
            </div>
           
            <?php if ($page === 0): ?>
            <div class="d-flex justify-content-between align-items-center no-print">
              <div class="w-100 text-center">
                <h5 class="mb-0">POULTRY DISSEMINATION REPORT</h5>
              </div>
              <button onclick="window.print()" class="btn btn-primary no-print">
                <i class="fas fa-print"></i> Print
              </button>
            </div>
            <div class="text-center" style="font-size:14px; margin-top:10px; margin-bottom:15px;">
                <strong>Period: <?= $displayStartDate ?> to <?= $displayEndDate ?></strong>
            </div>
            <?php else: ?>
            <div class="text-center" style="font-size:16px; font-weight:bold; margin-bottom:5px;">POULTRY DISSEMINATION REPORT</div>
            <div class="text-center" style="font-size:14px; margin-bottom:15px;">
                <strong>Period: <?= $displayStartDate ?> to <?= $displayEndDate ?></strong>
            </div>
            <?php endif; ?>
            <!-- Report Table -->
            <table class="table table-bordered mt-3">
              <thead class="table-light">
                <tr>
                  <th>Farmer</th>
                  <th>Barangay</th>
                  <th>Species</th>
                  <th style="width:12%">Quantity</th>
                  <th style="width:18%">Signature</th>
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
                    <td><?= htmlspecialchars($r['full_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['address'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['species'] ?? '') ?></td>
                    <td><?= htmlspecialchars((string)($r['quantity'] ?? 0)) ?></td>
                    <td></td>
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
                <img src="bagocity.png" alt="Logo 2" style="height:70px; margin:0 20px;">
                <img src="bagongpilipinas.jpg" alt="Logo 3" style="height:70px; margin:0 20px;">
            </div>
            <div style="font-size:14px;">Republic of the Philippines</div>
            <div style="font-size:14px;">City of Bago</div>
            <div style="font-weight:bold; font-size:16px;">OFFICE OF THE CITY VETERINARIAN</div>
            <div style="font-size:13px;">vetbagoCity@gmail.com</div>
            <div style="font-size:13px;">(034) 454-3115</div>
            <hr style="border:1px solid black; margin-top:10px;">
        </div>
        <div class="d-flex justify-content-between align-items-center no-print">
          <div>
            <h5 class="mb-0">Poultry Dissemination Report</h5>
          </div>
          <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="fas fa-print"></i> Print
          </button>
        </div>
        <div class="text-center" style="font-size:14px; margin-top:10px; margin-bottom:15px;">
            <strong>Period: <?= $displayStartDate ?> to <?= $displayEndDate ?></strong>
        </div>
        <table class="table table-bordered mt-3">
            <thead class="table-light">
                <tr>
                  <th>Farmer</th>
                  <th>Barangay</th>
                  <th>Species</th>
                  <th style="width:12%">Quantity</th>
                  <th style="width:18%">Signature</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="5" class="text-center">No records found</td></tr>
            </tbody>
        </table>
        <div class="mt-4 row">
            <div class="col-6">Submitted by: _________________________</div>
            <div class="col-6 text-end">Certified correct: _____________________</div>
        </div>
    </div>
<?php endif; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
window.addEventListener('load', () => { setTimeout(() => window.print(), 300); });
</script>
</body>
</html>
