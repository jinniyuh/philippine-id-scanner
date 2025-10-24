<?php
session_start();
include 'includes/conn.php';

// Restrict access to admins
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch role of current user
$userId = $_SESSION['user_id'];
$roleQuery = "SELECT role FROM users WHERE user_id = $userId";
$roleResult = mysqli_query($conn, $roleQuery);
if ($roleResult && mysqli_num_rows($roleResult) > 0) {
    $user = mysqli_fetch_assoc($roleResult);
    if ($user['role'] !== 'admin') {
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}

// ✅ Logging function (or include from file if centralized)
function logActivity($conn, $userId, $action) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $userId, $action);
    $stmt->execute();
    $stmt->close();
}

// ✅ Log viewing the Activity Logs page
logActivity($conn, $userId, 'Viewed Activity Logs');

// ✅ Fetch activity logs for the current logged-in user only
$logs_query = "SELECT activity_logs.*, users.name FROM activity_logs 
               JOIN users ON activity_logs.user_id = users.user_id 
               WHERE activity_logs.user_id = '$userId'
               ORDER BY timestamp DESC";
$logs_result = $conn->query($logs_query);
if (!$logs_result) {
    die("Query error: " . $conn->error);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #6c63ff;
            font-family: Arial, sans-serif;
        }
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
        }
        .wrapper {
            display: flex;
            align-items: flex-start;
        }
        .main-content {
            background: white;
            margin: 20px;
            margin-left: 312px;
            padding: 0 25px 25px 25px;
            border-radius: 10px;
            min-height: 600px;
            height: calc(100vh - 40px);
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: calc(100% + 50px);
            min-height: 80px;
            margin: 0 -25px 0 -25px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 10px 25px 0 25px;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        .admin-header h2 {
            margin: 0;
            font-weight: bold;
        }
        .admin-profile {
            display: flex;
            align-items: center;
        }
        .admin-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .table thead {
            background-color: #343a40;
            color: white;
        }
        /* Thin scrollbar for Webkit browsers */
        .table-responsive::-webkit-scrollbar {
            width: 3px;
            height: 4px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #bdbdbd;
            border-radius: 4px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        /* Thin scrollbar for Firefox */
        .table-responsive {
            scrollbar-width: thin;
            scrollbar-color: #bdbdbd #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="wrapper">
            <!-- Sidebar -->
            <div class="sidebar">
                <?php include 'includes/admin_sidebar.php'; ?>
            </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="admin-header">
            <h2>Admin Activity Logs</h2>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
        </div>

        <div class="mb-3">
            <input type="text" id="logSearch" class="form-control" placeholder="Search by action or date...">
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="logTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Admin Name</th>
                        <th>Action</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs_result->num_rows > 0): ?>
                        <?php $count = 1; ?>
                        <?php while ($log = $logs_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($log['name']); ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($log['timestamp'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search filter
        document.getElementById('logSearch').addEventListener('keyup', function () {
            const value = this.value.toLowerCase();
            const rows = document.querySelectorAll('#logTable tbody tr');
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(value) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
 