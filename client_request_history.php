<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}


$client_id = $_SESSION['client_id'];

// Fetch pharmaceutical request history
$sql = "SELECT pr.request_id, pr.species, pr.symptoms, pr.status, pr.request_date, t.issued_date
        FROM pharmaceutical_requests pr
        LEFT JOIN transactions t ON pr.client_id = t.client_id AND pr.pharma_id = t.pharma_id
        WHERE pr.client_id = ?
        ORDER BY pr.request_date DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Prepare Failed: " . $conn->error);
} 
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request History - Bago City Veterinary Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #6c63ff;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-wrapper {
            background: white;
            margin-left: 312px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            min-height: calc(100vh - 40px);
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            max-width: calc(100vw - 332px);
        }
        
        /* Tablet responsive styles */
        @media (max-width: 1024px) {
            .main-wrapper {
                margin-left: 312px;
                left: 20px;
                right: 20px;
                max-width: calc(100vw - 332px);
            }
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                top: 80px;
                left: 15px;
                right: 15px;
                bottom: 15px;
                max-width: calc(100vw - 30px);
                padding: 20px;
            }
            
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .table th, .table td {
                padding: 0.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-wrapper {
                left: 10px;
                right: 10px;
                top: 80px;
                bottom: 10px;
                max-width: calc(100vw - 20px);
                padding: 15px;
            }
            
            .table th, .table td {
                padding: 0.3rem;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .main-wrapper {
                left: 5px;
                right: 5px;
                top: 80px;
                bottom: 5px;
                max-width: calc(100vw - 10px);
                padding: 10px;
            }
            
            .table th, .table td {
                padding: 0.2rem;
                font-size: 0.75rem;
            }
        }
        
        /* Table styling */
        .table-container {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-top: 20px;
        }
        
        .table {
            margin-bottom: 0;
            border: none;
            width: 100%;
        }
        
        .table th {
            background-color: #6c63ff;
            color: white;
            font-weight: 500;
            padding: 15px;
            border: none;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #f0f0f0;
            font-size: 0.95rem;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,0.02);
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(108, 99, 255, 0.05) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.03);
        }
        
        /* Status badges */
        .badge {
            padding: 8px 12px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .bg-success {
            background-color:rgb(44, 207, 82) !important;
            background-image: linear-gradient(45deg,rgb(39, 202, 77),rgb(32, 201, 54));
        }
        
        .bg-warning {
            background-color: #ffc107 !important;
            background-image: linear-gradient(45deg, #ffc107, #ffb400);
        }
        
        .bg-danger {
            background-color: #dc3545 !important;
            background-image: linear-gradient(45deg, #dc3545, #c82333);
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php include 'includes/client_sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 main-wrapper main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-1">Pharmaceutical Request History</h3>
                </div>
            </div>

            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Species</th>
                                    <th>Symptoms</th>
                                    <th>Status</th>
                                    <th>Request Date</th>
                                    <th>Issued Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['species']); ?></td>
                                        <td>
                                            <div class="text-wrap" style="max-width: 200px;">
                                                <?= htmlspecialchars($row['symptoms']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $row['status'] === 'Approved' ? 'success' : 
                                                ($row['status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                                <?= $row['status']; ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($row['request_date'])); ?></td>
                                        <td><?= $row['issued_date'] ? date('M d, Y', strtotime($row['issued_date'])) : '<span class="text-muted">Pending</span>'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5 my-4">
                    <i class="fas fa-clipboard-list text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">No Requests Found</h5>
                    <p class="text-muted">You haven't made any pharmaceutical requests yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
