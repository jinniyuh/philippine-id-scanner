<?php
session_start();

// Redirect to admin_clients.php with users view
// This file is deprecated - user management is now under admin_clients.php
header("Location: admin_clients.php?view=users");
exit();

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get user counts
$total_users_result = $conn->query("SELECT COUNT(*) as count FROM users");
$total_users = $total_users_result->fetch_assoc()['count'];

$active_users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'Active'");
$active_users = $active_users_result->fetch_assoc()['count'];

$inactive_users = $total_users - $active_users;

$admin_users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$admin_users = $admin_users_result->fetch_assoc()['count'];

$staff_users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff'");
$staff_users = $staff_users_result->fetch_assoc()['count'];

$active_staff_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'Active'");
$active_staff = $active_staff_result->fetch_assoc()['count'];

$inactive_staff = $staff_users - $active_staff;

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY username");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 150px;
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .stats-card h5 {
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
            
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .search-container {
            margin-bottom: 5px;
            position: relative;
        }
        .search-container input {
            width: 100%;
            padding: 10px 15px;
            border-radius: 30px;
            border: 1px solid #ddd;
            padding-right: 50px;
        }
        .search-container button {
            position: absolute;
            right: 5px;
            top: 5px;
            background: #4e73df;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
        }
        .filter-dropdown {
            padding: 10px 15px;
            border-radius: 30px;
            border: 1px solid #ddd;
            background: white;
            color: #333;
            font-size: 14px;
            min-width: 120px;
        }
        .filter-dropdown:focus {
            outline: none;
            border-color: #4e73df;
            box-shadow: 0 0 0 2px rgba(78, 115, 223, 0.25);
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .users-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .users-table tr:hover {
            background-color: #f8f9fa;
        }
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-inactive {
            color: red;
            font-weight: bold;
        }
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
            color: white;
        }
        .edit-btn {
            background-color: #4e73df;
        }
        .disable-btn {
            background-color: #f39c12;
        }
        .add-btn {
            background-color: #4e73df;
            color: white;
            border-radius: 5px;
            padding: 8px 20px;
            font-weight: bold;
        }
        .enable-btn {
            background-color: #28a745;
            color: white;
        }
        .enable-btn:hover {
            background-color: #218838;
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
                    <h2>Users Management</h2>
                    <div class="admin-profile">
                        <img src="assets/default-avatar.png" alt="Admin Profile">
                        <div>
                            <div><?php echo $_SESSION['name']; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Display Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stats-card d-flex flex-column align-items-center justify-content-center">
                            <h5>Total Users</h5>
                            <h3><?php echo $total_users; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card d-flex flex-column align-items-center justify-content-center">
                            <h5>Admin</h5>
                            <h3><?php echo $admin_users; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card d-flex flex-column align-items-center justify-content-center">
                            <h5>Staff</h5>
                            <h3><?php echo $staff_users; ?></h3>
                        </div>
                    </div>
                </div>
                
                <!-- Search and Add User -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="search-container">
                            <input type="text" id="searchUser" placeholder="Search user by name, role or status...">
                            <button type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <select class="filter-dropdown" id="filterRole">
                                <option value="">Filter by Role</option>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                            </select>
                            <select class="filter-dropdown" id="filterStatus">
                                <option value="">Filter by Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="add-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            Add Staff
                        </button>
                    </div>
                </div>
                
                <!-- Users Table -->
                <div class="table-responsive mt-3">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Fullname</th>
                                <th>Contact Number</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): ?>
                                <?php while($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['name']; ?></td>
                                    <td><?php echo $user['contact_number']; ?></td>
                                    <td><?php echo ucfirst($user['role']); ?></td>
                                    <td class="<?php echo $user['status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['status']; ?>
                                    </td>
                                    <td><?php echo $user['created_at'] ? date('Y-m-d h:i A', strtotime($user['created_at'])) : 'Never'; ?></td>
                                    <td>
                                        <button type="button" class="action-btn edit-btn" 
                                            onclick="editUser(
                                                '<?php echo $user['user_id']; ?>', 
                                                '<?php echo $user['username']; ?>', 
                                                '<?php echo $user['name']; ?>', 
                                                '<?php echo $user['contact_number']; ?>', 
                                                '<?php echo $user['role']; ?>', 
                                                '<?php echo $user['status']; ?>'
                                            )">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['status'] === 'Active'): ?>
                                            <button type="button" class="action-btn disable-btn" 
                                                onclick="disableUser('<?php echo $user['user_id']; ?>', '<?php echo $user['name']; ?>')">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="action-btn enable-btn" 
                                                onclick="enableUser('<?php echo $user['user_id']; ?>', '<?php echo $user['name']; ?>')">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin_add_user.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm" action="admin_update_user.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_fullname" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="edit_fullname" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_contact" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="edit_contact" name="contact_number">
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Combined search and filter functionality
        function applyFilters() {
            const searchValue = document.getElementById('searchUser').value.toLowerCase();
            const selectedRole = document.getElementById('filterRole').value;
            const selectedStatus = document.getElementById('filterStatus').value;
            const tableRows = document.querySelectorAll('.users-table tbody tr');
            
            tableRows.forEach(row => {
                const username = row.cells[0].textContent.toLowerCase();
                const fullname = row.cells[1].textContent.toLowerCase();
                const contact = row.cells[2].textContent.toLowerCase();
                const role = row.cells[3].textContent.toLowerCase();
                const status = row.cells[4].textContent.toLowerCase();
                
                // Search filter
                let searchMatch = searchValue === '' || 
                    username.includes(searchValue) || 
                    fullname.includes(searchValue) || 
                    contact.includes(searchValue) || 
                    role.includes(searchValue) || 
                    status.includes(searchValue);
                
                // Role filter
                let roleMatch = selectedRole === '' || role === selectedRole.toLowerCase();
                
                // Status filter
                let statusMatch = selectedStatus === '' || status === selectedStatus.toLowerCase();
                
                if (searchMatch && roleMatch && statusMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Search functionality
        document.getElementById('searchUser').addEventListener('keyup', applyFilters);
        
        // Filter functionality
        document.getElementById('filterRole').addEventListener('change', applyFilters);
        document.getElementById('filterStatus').addEventListener('change', applyFilters);
        
        // Edit user functionality
        function editUser(userId, username, name, contact, role) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_fullname').value = name;
            document.getElementById('edit_contact').value = contact;
            document.getElementById('edit_role').value = role;
            
            // Open the modal
            var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
        }
        
        // Delete user functionality
        function deleteUser(userId, userName) {
            if (confirm('Are you sure you want to delete user: ' + userName + '?')) {
                window.location.href = 'admin_delete_user.php?id=' + userId;
            }
        }
        
        // Disable user functionality
        function disableUser(userId, userName) {
            if (confirm('Are you sure you want to disable this user: ' + userName + '?')) {
                // Create a form to submit the disable request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin_disable_user.php';
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                
                form.appendChild(userIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        
        // Enable user functionality
        function enableUser(userId, userName) {
            if (confirm('Are you sure you want to enable this user: ' + userName + '?')) {
                // Create a form to submit the enable request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin_enable_user.php';
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                
                form.appendChild(userIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>