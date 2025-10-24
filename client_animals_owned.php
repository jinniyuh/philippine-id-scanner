<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in
if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
} 

// Get client information
$client_id = $_SESSION['client_id'];
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();

// Check if client has disseminated animals (for upload photos permission)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM livestock_poultry WHERE client_id = ? AND UPPER(source) = 'DISSEMINATED'");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$disseminated_count = $stmt->get_result()->fetch_assoc()['count'];
$has_disseminated_animals = ($disseminated_count > 0);

// Get animals count 
$stmt = $conn->prepare("SELECT 
    SUM(CASE WHEN animal_type = 'Livestock' THEN quantity END) as livestock_count,
    SUM(CASE WHEN animal_type = 'Poultry' THEN quantity END) as poultry_count,
    SUM(CASE WHEN health_status = 'Healthy' AND animal_type = 'Livestock' THEN quantity END) as healthy_livestock,
    SUM(CASE WHEN health_status = 'Needs Attention' AND animal_type = 'Livestock' THEN quantity END) as attention_livestock,
    SUM(CASE WHEN health_status = 'Healthy' AND animal_type = 'Poultry' THEN quantity END) as healthy_poultry,
    SUM(CASE WHEN health_status = 'Needs Attention' AND animal_type = 'Poultry' THEN quantity END) as attention_poultry
    FROM livestock_poultry WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT * FROM livestock_poultry WHERE client_id = ? ORDER BY created_at DESC, animal_id DESC");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$all_animals = $stmt->get_result();

$livestock = [];
$poultry = [];
while ($animal = $all_animals->fetch_assoc()) {
    if ($animal['animal_type'] === 'Livestock') {
        $livestock[] = $animal;
    } elseif ($animal['animal_type'] === 'Poultry') {
        $poultry[] = $animal;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animals Owned - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Base styles */
        body {
            background-color: #6c63ff;
        }
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
        }
        
        /* Sidebar styles handled by client_sidebar.php */
        .main-wrapper {
            background: white;
            margin-left: 312px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: calc(100vh - 40px);
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
        }

        /* Add hover effect for table rows */
        .table tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        /* Update stat box styling to match dashboard */
        .stat-box {
            background: white;
            border: 1px solid #000;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.18s cubic-bezier(.4,2,.6,1), box-shadow 0.18s cubic-bezier(.4,2,.6,1);
            position: relative;
            overflow: hidden;
        }
        .stat-box:hover {
            transform: scale(1.045) translateY(-4px);
            box-shadow: 0 8px 32px rgba(108,99,255,0.18), 0 3px 12px rgba(0,0,0,0.10);
            z-index: 2;
        }
        .row.stat-row {
            margin-left: 0;
            margin-right: 0;
        }
        .row.stat-row > .col-md-6 {
            padding-left: 0;
            padding-right: 5px;
        }
        .row.stat-row > .col-md-6:last-child {
            padding-right: 0;
            padding-left: 5px;
        }
        /* Stats display */
        .stats-display {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #000;
            transition: transform 0.18s cubic-bezier(.4,2,.6,1), box-shadow 0.18s cubic-bezier(.4,2,.6,1);
            position: relative;
            overflow: hidden;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 500;
            margin: 10px 0;
        }

        .stat-label {
            color: #333;
            margin-bottom: 10px;
        }

        /* Badges */
        .badge {
            padding: 5px 15px;
            border-radius: 15px;
            margin-right: 10px;
            font-weight: normal;
        }

        .badge-healthy {
            background-color: #28a745;
            color: white;
        }

        .badge-attention {
            background-color: #ffc107;
            color: black;
        }

        /* Action buttons */
        .action-btn {
            margin-top: 40px;
            padding: 8px 20px;
            border-radius: 5px;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-add {
            background-color: #28a745;
            margin-right: 10px;
        }

        .btn-upload {
            background-color: #0d6efd;
        }

        /* Table styles */
        .table {
            margin-top: 10px;
            width: 100%;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-collapse: collapse;
        }

        .table th {
            background-color: #6c63ff;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border-bottom: 2px solid #e9ecef;
            vertical-align: middle;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .table tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.15s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table td {
            padding: 14px 12px;
            color: #495057;
            font-size: 14px;
            vertical-align: middle;
        }

        .btn-edit, .btn-delete {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.15s ease;
            margin: 0 2px;
        }

        .btn-edit {
            background-color: #007bff;
            color: white;
        }

        .btn-edit:hover {
            background-color: #0056b3;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Copy sidebar from client_dashboard.php but update the active class -->
            <!-- Replace the existing sidebar section with: -->
            <div class="col-md-3">
                <?php include 'includes/client_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-wrapper main-content">
                <!-- Sticky Header Section -->
                <div class="sticky-header" style="position: sticky; top: 0; background: white; z-index: 100; padding: 0px 0; margin-bottom: 0px;">
                    <h2 style="margin: 0; color: black  ;">Animals Owned</h2>
                    
                    <div style="display: flex; justify-content: flex-end; margin-top: -70px;">
                        <button class="action-btn btn-add" type="button">
                            <i class="fas fa-plus"></i> Add Animals
                        </button>
                        <?php if ($has_disseminated_animals): ?>
                        <button class="action-btn btn-upload" type="button" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                            <i class="fas fa-upload"></i> Upload Photos
                        </button>
                        <?php else: ?>
                        <button class="action-btn btn-upload" type="button" disabled title="Upload photos is only available for disseminated animals">
                            <i class="fas fa-upload"></i> Upload Photos
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <h4 style="margin-top: 30px;">List of Your Owned Animals</h4>
                
                <ul class="nav nav-tabs" id="animalTabs" role="tablist" style="margin-top: 30px;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="livestock-tab" data-bs-toggle="tab" data-bs-target="#livestock" type="button" role="tab" aria-controls="livestock" aria-selected="true">Livestock</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="poultry-tab" data-bs-toggle="tab" data-bs-target="#poultry" type="button" role="tab" aria-controls="poultry" aria-selected="false">Poultry</button>
                    </li>
                </ul>
                <div class="tab-content" id="animalTabsContent" style="height: calc(100vh - 220px); overflow: auto; position: relative; overflow: auto; max-height: calc(100vh - 280px); ">
                    <div class="tab-pane fade show active" id="livestock" role="tabpanel" aria-labelledby="livestock-tab">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Species</th>
                                    <th>Sex</th>
                                    <th>Weight</th>
                                    <th>Health Status</th>
                                    <th>Source</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($livestock as $animal): ?>
                                <tr>
                                    <td><?php echo $animal['species']; ?></td>
                                    <td><?php echo isset($animal['sex']) ? $animal['sex'] : 'N/A'; ?></td>
                                    <td><?php echo $animal['weight']; ?></td>
                                    <td><?php echo $animal['health_status']; ?></td>
                                    <td><?php echo isset($animal['source']) ? $animal['source'] : 'Owned'; ?></td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-primary edit-animal"
                                            data-id="<?php echo $animal['animal_id']; ?>"
                                            data-type="<?php echo htmlspecialchars($animal['animal_type']); ?>"
                                            data-species="<?php echo htmlspecialchars($animal['species']); ?>"
                                            data-sex="<?php echo htmlspecialchars(isset($animal['sex']) ? $animal['sex'] : 'N/A'); ?>"
                                            data-weight="<?php echo htmlspecialchars($animal['weight']); ?>"
                                            data-quantity="<?php echo htmlspecialchars($animal['quantity']); ?>"
                                            data-health="<?php echo htmlspecialchars($animal['health_status']); ?>"
                                            data-source="<?php echo isset($animal['source']) ? htmlspecialchars($animal['source']) : ''; ?>"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-animal" data-id="<?php echo $animal['animal_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="poultry" role="tabpanel" aria-labelledby="poultry-tab">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Species</th>
                                    <th>Sex</th>
                                    <th>Quantity</th>
                                    <th>Source</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($poultry as $animal): ?>
                                <tr>
                                    <td><?php echo $animal['species']; ?></td>
                                    <td><?php echo isset($animal['sex']) ? $animal['sex'] : 'N/A'; ?></td>
                                    <td><?php echo $animal['quantity']; ?></td>
                                    <td><?php echo isset($animal['source']) ? $animal['source'] : 'Owned'; ?></td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-primary edit-animal"
                                            data-id="<?php echo $animal['animal_id']; ?>"
                                            data-type="<?php echo htmlspecialchars($animal['animal_type']); ?>"
                                            data-species="<?php echo htmlspecialchars($animal['species']); ?>"
                                            data-sex="<?php echo htmlspecialchars(isset($animal['sex']) ? $animal['sex'] : 'N/A'); ?>"
                                            data-weight="<?php echo htmlspecialchars($animal['weight']); ?>"
                                            data-quantity="<?php echo htmlspecialchars($animal['quantity']); ?>"
                                            data-health="<?php echo htmlspecialchars($animal['health_status']); ?>"
                                            data-source="<?php echo isset($animal['source']) ? htmlspecialchars($animal['source']) : 'Owned'; ?>"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-animal" data-id="<?php echo $animal['animal_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addAnimalModal" tabindex="-1">
        <div class="modal-dialog">
  
        <div class="modal-content">
                <div class="modal-header" style="background-color: #6c63ff; color: white">
                    <h5 class="modal-title">Add your animals</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addAnimalForm">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" id="add_type" required>
                                <option value="">Select type</option>
                                <option value="Livestock">Livestock</option>
                                <option value="Poultry">Poultry</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Species</label>
                            <select class="form-select" name="species" id="add_species" required>
                                <option value="">Select species</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sex</label>
                            <select class="form-select" name="sex" id="add_sex" required>
                                <option value="">Select sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="mb-3" id="group_add_quantity">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" required>
                        </div>
                        <div class="mb-3" id="group_add_weight">
                            <label class="form-label">Est. Weight</label>
                            <input type="number" class="form-control" name="weight" id="add_weight" required>
                        </div>
                        <div class="mb-3" id="group_add_health_status">
                            <label class="form-label">Health Status</label>
                            <select class="form-select" name="health_status" id="add_health_status" required>
                                <option value="">Select status</option>
                                <option value="Healthy">Healthy</option>
                                <option value="Need Attention">Need Attention</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.btn-add').addEventListener('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('addAnimalModal'));
            modal.show();
        });

        // Add type selection handler + species options + health status toggle
        (function(){
            const typeSelect = document.getElementById('add_type');
            const speciesSelect = document.getElementById('add_species');
            const quantityField = document.querySelector('input[name="quantity"]');
            const quantityContainer = document.getElementById('group_add_quantity');
            const weightField = document.getElementById('add_weight');
            const healthSelect = document.getElementById('add_health_status');

            const livestockSpecies = [
                'Cattle','Carabao','Goat','Sheep','Swine','Water Buffalo','Horse'
            ];
            const poultrySpecies = [
                'Chicken','Duck','Turkey','Goose','Quail'
            ];

            function fillSpecies(options) {
                speciesSelect.innerHTML = '<option value="">Select species</option>' + options.map(s => `<option value="${s}">${s}</option>`).join('');
            }

            function onTypeChange() {
                if (!typeSelect) return;
                
                // Get the field containers
                const weightContainer = document.getElementById('group_add_weight');
                const healthContainer = document.getElementById('group_add_health_status');
                
                if (typeSelect.value === 'Poultry') {
                    fillSpecies(poultrySpecies);
                    // Hide the fields for Poultry
                    weightContainer.style.display = 'none';
                    healthContainer.style.display = 'none';
                    // Show quantity field for Poultry
                    quantityContainer.style.display = 'block';
                    // Clear and disable the fields
                    weightField.value = '';
                    weightField.disabled = true;
                    healthSelect.value = '';
                    healthSelect.disabled = true;
                    quantityField.disabled = false;
                } else if (typeSelect.value === 'Livestock') {
                    fillSpecies(livestockSpecies);
                    // Show the fields for Livestock
                    weightContainer.style.display = 'block';
                    healthContainer.style.display = 'block';
                    // Hide quantity field for Livestock (automatically 1)
                    quantityContainer.style.display = 'none';
                    // Enable the fields
                    weightField.disabled = false;
                    healthSelect.disabled = false;
                    // Automatically set quantity to 1 for Livestock
                    quantityField.value = '1';
                } else {
                    speciesSelect.innerHTML = '<option value="">Select species</option>';
                    // Hide all fields when no type is selected
                    weightContainer.style.display = 'none';
                    healthContainer.style.display = 'none';
                    quantityContainer.style.display = 'none';
                    // Disable all fields
                    weightField.disabled = true;
                    healthSelect.disabled = true;
                    quantityField.disabled = false;
                }
            }

            if (typeSelect) {
                typeSelect.addEventListener('change', onTypeChange);
                onTypeChange();
            }
        })();

        // Add form submission handler
        document.getElementById('addAnimalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            
            fetch('client_add_animal_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success - refresh the page to show new data
                    window.location.reload();
                } else {
                    // Show error message
                    showMessageModal('Error', 'Error adding animal: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessageModal('Error', 'Error adding animal. Please try again.', 'error');
            });
        });
    </script>

    <!-- Add this modal before the closing body tag -->
    <div class="modal fade" id="editAnimalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Animal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editAnimalForm">
                        <input type="hidden" name="animal_id" id="edit_animal_id">
                        <input type="hidden" name="type" id="edit_type">
                        <input type="hidden" name="species" id="edit_species">
                        <input type="hidden" name="sex" id="edit_sex">
                        <input type="hidden" name="source" id="edit_source">
                        <div class="mb-3" id="group_edit_weight">
                            <label class="form-label">Weight</label>
                            <input type="text" class="form-control" name="weight" id="edit_weight">
                        </div>
                        <div class="mb-3" id="group_edit_quantity">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" id="edit_quantity">
                        </div>
                        <div class="mb-3" id="group_edit_health_status">
                            <label class="form-label">Health Status</label>
                            <select class="form-select" name="health_status" id="edit_health_status" required>
                                <option value="Healthy">Healthy</option>
                                <option value="Need Attention">Need Attention</option>
                            </select>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditButton">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add this JavaScript before the closing body tag -->
    <script>
    document.querySelectorAll('.delete-animal').forEach(button => {
        button.addEventListener('click', function() {
            const animalId = this.getAttribute('data-id');
            const row = this.closest('tr'); 
                
                // Show confirmation modal
                const confirmModal = document.createElement('div');
                confirmModal.className = 'modal fade';
                confirmModal.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-body text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                                </div>
                                <h6 class="mb-3">Delete Animal</h6>
                                <p class="text-muted mb-4">Are you sure you want to delete this animal?</p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-danger px-4 confirm-delete">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(confirmModal);
                const modal = new bootstrap.Modal(confirmModal);
                modal.show();
                
                // Handle delete confirmation
                confirmModal.querySelector('.confirm-delete').addEventListener('click', function() {
                    modal.hide();
                    
                    // Original delete logic
                    fetch('client_delete_animal.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'animal_id=' + animalId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            row.remove();
                            // Show success modal
                            showMessageModal('Success', 'Animal deleted successfully!', 'success');
                        } else {
                            showMessageModal('Error', 'Error deleting animal: ' + data.error, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showMessageModal('Error', 'Error deleting animal', 'error');
                    });
                });
                
                // Clean up modal when hidden
                confirmModal.addEventListener('hidden.bs.modal', function() {
                    document.body.removeChild(confirmModal);
                });
            });
        });

    document.querySelectorAll('.edit-animal').forEach(button => {
        button.addEventListener('click', function() {
            const animalId = this.getAttribute('data-id');
            const type = this.getAttribute('data-type') || '';
            const species = this.getAttribute('data-species') || '';
            const sex = this.getAttribute('data-sex') || 'N/A';
            const weight = this.getAttribute('data-weight') || '';
            const quantity = this.getAttribute('data-quantity') || '';
            const health = this.getAttribute('data-health') || '';
            const source = this.getAttribute('data-source') || 'Owned';


            document.getElementById('edit_animal_id').value = animalId;
            document.getElementById('edit_type').value = type;
            document.getElementById('edit_species').value = species;
            document.getElementById('edit_sex').value = sex;
            document.getElementById('edit_weight').value = weight;
            document.getElementById('edit_quantity').value = quantity;
            document.getElementById('edit_health_status').value = health;
            document.getElementById('edit_source').value = source;

            // Toggle fields depending on type (Poultry vs Livestock) - AFTER setting values
            toggleEditFields(type);

            const editModal = new bootstrap.Modal(document.getElementById('editAnimalModal'));
            editModal.show();
        });
    });

    document.getElementById('saveEditButton').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('editAnimalForm'));
        
        // Ensure quantity is always included, even if field is hidden
        const quantityField = document.getElementById('edit_quantity');
        if (quantityField && quantityField.disabled) {
            formData.set('quantity', quantityField.value);
        }
        
        fetch('client_update_animal.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error updating animal: ' + (data.error || 'Unknown error'));
            }
        });
    });


    function toggleEditFields(typeValue) {
        const quantityField = document.getElementById('edit_quantity');
        const weightField = document.getElementById('edit_weight');
        const groupWeight = document.getElementById('group_edit_weight');
        const groupQuantity = document.getElementById('group_edit_quantity');
        const groupHealth = document.getElementById('group_edit_health_status');

        if (typeValue === 'Poultry') {
            if (weightField) { weightField.value = 'N/A'; weightField.disabled = true; }
            if (quantityField) { quantityField.disabled = false; }
            if (groupWeight) groupWeight.style.display = 'none';
            if (groupQuantity) groupQuantity.style.display = 'block';
            if (groupHealth) groupHealth.style.display = 'none';
        } else {
            if (quantityField) { quantityField.disabled = true; }
            if (weightField) { weightField.disabled = false; if (weightField.value === 'N/A') weightField.value = ''; }
            if (groupWeight) groupWeight.style.display = '';
            if (groupQuantity) groupQuantity.style.display = 'none';
            if (groupHealth) groupHealth.style.display = '';
        }
    }



    // Camera functionality
    let stream = null;
    let capturedPhotos = [];

    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, setting up camera functionality');
        
        // Check if elements exist
        const cameraBtn = document.getElementById('cameraBtn');
        const captureBtn = document.getElementById('captureBtn');
        const retakeBtn = document.getElementById('retakeBtn');
        const photoUploadForm = document.getElementById('photoUploadForm');
        
        if (!cameraBtn) {
            console.error('Camera button not found!');
            return;
        }
        
        console.log('Camera elements found successfully');
        
        // Toggle camera section
        cameraBtn.addEventListener('click', function() {
            console.log('Camera button clicked');
            const cameraSection = document.getElementById('cameraSection');
            if (cameraSection) {
                cameraSection.style.display = 'block';
                startCamera();
            } else {
                console.error('Camera section not found!');
            }
        });

        // Capture photo
        if (captureBtn) {
            captureBtn.addEventListener('click', function() {
                const video = document.getElementById('cameraVideo');
                const canvas = document.getElementById('cameraCanvas');
                const context = canvas.getContext('2d');

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Add timestamp to the captured image
                const timestamp = new Date().toLocaleString('en-US', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
                
                // Draw timestamp on the image
                context.fillStyle = 'rgba(0, 0, 0, 0.7)';
                context.fillRect(canvas.width - 200, 10, 190, 30);
                context.fillStyle = 'white';
                context.font = '14px Arial';
                context.fillText(timestamp, canvas.width - 195, 30);

                // Convert canvas to blob
                canvas.toBlob(function(blob) {
                    const photoId = Date.now();
                    capturedPhotos.push({ id: photoId, blob: blob, timestamp: timestamp });

                    // Create preview
                    const preview = document.createElement('div');
                    preview.className = 'captured-photo mb-2';
                    preview.innerHTML = `
                        <img src="${URL.createObjectURL(blob)}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                        <div class="small text-muted mt-1">${timestamp}</div>
                        <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removePhoto(${photoId})">
                            <i class="fas fa-trash"></i>
                        </button>
                        <input type="hidden" name="camera_photos[]" value="${photoId}">
                    `;
                    document.getElementById('capturedPhotos').appendChild(preview);

                    // Show retake button
                    document.getElementById('retakeBtn').style.display = 'inline-block';
                }, 'image/jpeg', 0.8);
            });
        }

        // Retake photo
        if (retakeBtn) {
            retakeBtn.addEventListener('click', function() {
                document.getElementById('capturedPhotos').innerHTML = '';
                capturedPhotos = [];
                this.style.display = 'none';
            });
        }

        // Handle form submission
        if (photoUploadForm) {
            photoUploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Form submitted');
                
                // Disable submit button to prevent multiple submissions
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Uploading...';
                }
                
                const formData = new FormData();
                
                // Add animal_id
                const animalId = document.getElementById('upload_animal_id').value;
                if (!animalId) {
                    alert('Please select an animal first.');
                    return;
                }
                formData.append('animal_id', animalId);
                
                // Check if we have any captured photos to upload
                if (capturedPhotos.length === 0) {
                    alert('Please capture photos using the camera before uploading.');
                    // Re-enable submit button
                    const submitBtn = document.querySelector('#photoUploadForm button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Upload Photos';
                    }
                    return;
                }

                // Add captured photos
                console.log('Captured photos:', capturedPhotos.length);
                capturedPhotos.forEach(photo => {
                    formData.append('animal_photos[]', photo.blob, `camera_photo_${photo.id}.jpg`);
                });

                // Submit form
                console.log('Submitting form...');
                fetch('client_upload_animal_photos.php', {
                    method: 'POST',
                    body: formData,
                    signal: AbortSignal.timeout(30000) // 30 second timeout
                })
                .then(response => {
                    console.log('Response received');
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        // Close modal first
                        const modal = bootstrap.Modal.getInstance(document.getElementById('uploadPhotoModal'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Reset everything after modal is closed
                        setTimeout(() => {
                            // Reset form
                            const form = document.getElementById('photoUploadForm');
                            if (form) form.reset();
                            
                            // Clear captured photos
                            const capturedPhotosDiv = document.getElementById('capturedPhotos');
                            if (capturedPhotosDiv) capturedPhotosDiv.innerHTML = '';
                            capturedPhotos = [];
                            
                            // Stop camera and reset camera section
                            stopCamera();
                            const cameraSection = document.getElementById('cameraSection');
                            if (cameraSection) cameraSection.style.display = 'none';
                            
                            // Re-enable submit button
                            const submitBtn = document.querySelector('#photoUploadForm button[type="submit"]');
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'Upload Photos';
                            }
                            
                            // Show success message
                            console.log('Attempting to show success modal...');
                            
                            // Create and show success modal
                            showSuccessModal();
                            
                            // Refresh sidebar photos
                            if (typeof refreshSidebarPhotos === 'function') {
                                refreshSidebarPhotos();
                            }
                        }, 300);
                    } else {
                        // Show error modal
                        var errorModal = new bootstrap.Modal(document.getElementById('photoErrorModal'));
                        document.getElementById('errorMessage').textContent = 'Error uploading photos: ' + (data.error || 'Unknown error');
                        errorModal.show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show error modal
                    var errorModal = new bootstrap.Modal(document.getElementById('photoErrorModal'));
                    document.getElementById('errorMessage').textContent = 'Error uploading photos. Please try again.';
                    errorModal.show();
                    // Reset form and modal state
                    const capturedPhotosDiv = document.getElementById('capturedPhotos');
                    if (capturedPhotosDiv) capturedPhotosDiv.innerHTML = '';
                    capturedPhotos = [];
                    stopCamera();
                    const cameraSection = document.getElementById('cameraSection');
                    if (cameraSection) cameraSection.style.display = 'none';
                    
                    // Re-enable submit button
                    const submitBtn = document.querySelector('#photoUploadForm button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Upload Photos';
                    }
                });
            });
        }

        // Disable upload when not allowed based on latest photo status
        const animalSelect = document.getElementById('upload_animal_id');
        const uploadBtn = document.getElementById('uploadSubmitBtn');
        const permMsg = document.getElementById('uploadPermissionMsg');
        function checkPermission() {
            const id = animalSelect.value;
            if (!id) {
                uploadBtn.disabled = true;
                permMsg.textContent = 'Please select an animal.';
                return;
            }
            fetch('check_upload_permission.php?animal_id=' + encodeURIComponent(id))
                .then(r => r.json())
                .then(data => {
                    if (data && data.can_upload) {
                        uploadBtn.disabled = false;
                        permMsg.textContent = '';
                    } else {
                        uploadBtn.disabled = true;
                        permMsg.textContent = data && data.reason ? data.reason : 'Uploading is not allowed right now.';
                    }
                })
                .catch(() => {
                    uploadBtn.disabled = true;
                    permMsg.textContent = 'Unable to verify upload permission.';
                });
        }
        if (animalSelect && uploadBtn) {
            uploadBtn.disabled = true;
            animalSelect.addEventListener('change', checkPermission);
            // Also check once when modal opens
            const uploadPhotoModal = document.getElementById('uploadPhotoModal');
            if (uploadPhotoModal) {
                uploadPhotoModal.addEventListener('shown.bs.modal', checkPermission);
            }
        }

        // Clean up camera when modal is closed
        const uploadPhotoModal = document.getElementById('uploadPhotoModal');
        if (uploadPhotoModal) {
            uploadPhotoModal.addEventListener('hidden.bs.modal', function() {
                console.log('Modal hidden - cleaning up');
                stopCamera();
                const capturedPhotosDiv = document.getElementById('capturedPhotos');
                if (capturedPhotosDiv) capturedPhotosDiv.innerHTML = '';
                capturedPhotos = [];
                const cameraSection = document.getElementById('cameraSection');
                if (cameraSection) cameraSection.style.display = 'none';
                const form = document.getElementById('photoUploadForm');
                if (form) form.reset();
            });
            
            // Also add event listener for when modal is shown
            uploadPhotoModal.addEventListener('shown.bs.modal', function() {
                console.log('Modal shown - resetting');
                // Reset everything when modal opens
                const capturedPhotosDiv = document.getElementById('capturedPhotos');
                if (capturedPhotosDiv) capturedPhotosDiv.innerHTML = '';
                capturedPhotos = [];
                const cameraSection = document.getElementById('cameraSection');
                if (cameraSection) cameraSection.style.display = 'none';
                const form = document.getElementById('photoUploadForm');
                if (form) form.reset();
            });
        }
    });

            // Start camera
        function startCamera() {
            console.log('Starting camera...');
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Camera not supported in this browser');
                return;
            }
            
            const videoElement = document.getElementById('cameraVideo');
            if (!videoElement) {
                console.error('Camera video element not found!');
                return;
            }
            
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(mediaStream) {
                    console.log('Camera access granted');
                    stream = mediaStream;
                    videoElement.srcObject = stream;
                    
                    // Start real-time timestamp
                    updateTimestamp();
                    setInterval(updateTimestamp, 1000);
                })
                .catch(function(error) {
                    console.error('Error accessing camera:', error);
                    alert('Unable to access camera. Please check permissions.');
                });
        }
        
        // Update timestamp
        function updateTimestamp() {
            const timestampDiv = document.getElementById('timestamp');
            if (timestampDiv) {
                const now = new Date();
                const timestamp = now.toLocaleString('en-US', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
                timestampDiv.textContent = timestamp;
            } else {
                console.error('Timestamp div not found!');
            }
        }

    // Stop camera
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    }

    // Remove individual photo (global function)
    function removePhoto(photoId) {
        capturedPhotos = capturedPhotos.filter(photo => photo.id !== photoId);
        const previews = document.querySelectorAll('.captured-photo');
        previews.forEach(preview => {
            if (preview.querySelector(`input[value="${photoId}"]`)) {
                preview.remove();
            }
        });
    }

    // Show message modal (global function)
    function showMessageModal(title, message, type) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow">
                    <div class="modal-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-${type === 'success' ? 'check-circle text-success' : 'exclamation-circle text-danger'}" style="font-size: 2rem;"></i>
                        </div>
                        <h6 class="mb-3">${title}</h6>
                        <p class="text-muted mb-4">${message}</p>
                        <button type="button" class="btn btn-${type === 'success' ? 'success' : 'danger'} px-4" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.removeChild(modal);
        });
    }

    // Refresh page function
    function refreshPage() {
        window.location.reload();
    }
    
    // Show success modal function
    function showSuccessModal() {
        // Remove any existing success modals
        const existingModals = document.querySelectorAll('.success-modal');
        existingModals.forEach(modal => modal.remove());
        
        // Create success modal dynamically
        const modalHTML = `
            <div class="success-modal modal fade show" style="display: block; z-index: 99999; position: fixed; top: 0; left: 0; width: 100%; height: 100%;">
                <div class="modal-dialog modal-dialog-centered modal-sm" style="z-index: 100000;">
                    <div class="modal-content">
                        <div class="modal-header" style="background-color: #28a745; color: white; border: none;">
                            <h5 class="modal-title">
                                <i class="fas fa-check-circle me-2"></i>Success
                            </h5>
                            <button type="button" class="btn-close btn-close-white" onclick="closeSuccessModal()" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h6>Photos uploaded successfully!</h6>
                            <p class="text-muted">Your photos have been uploaded and are ready for review.</p>
                        </div>
                        <div class="modal-footer justify-content-center" style="border: none;">
                            <button type="button" class="btn btn-success px-4" onclick="closeSuccessModal()">OK</button>
                        </div>
                    </div>
                </div>
                <div class="modal-backdrop fade show" style="z-index: 99998;"></div>
            </div>
        `;
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Auto-close after 3 seconds
        setTimeout(() => {
            closeSuccessModal();
        }, 3000);
    }
    
    // Close success modal function
    function closeSuccessModal() {
        // Remove all success modals
        const modals = document.querySelectorAll('.success-modal');
        modals.forEach(modal => {
            modal.remove();
        });
        
        // Remove ALL modal backdrops to ensure nothing blocks clicks
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.remove();
        });
        
        // Also remove any body classes that might interfere
        document.body.classList.remove('modal-open');
        
        // Force enable pointer events on body
        document.body.style.pointerEvents = 'auto';
        
        console.log('Success modal closed and all backdrops removed');
    }

    // Photo gallery functionality
    document.querySelectorAll('.view-photos').forEach(button => {
        button.addEventListener('click', function() {
            const animalId = this.getAttribute('data-id');
            const species = this.getAttribute('data-species');
            
            document.getElementById('animalSpecies').textContent = species;
            
            // Load photos for this animal
            fetch(`get_animal_photos.php?animal_id=${animalId}`)
                .then(response => response.json())
                .then(data => {
                    const photoGallery = document.getElementById('photoGallery');
                    const noPhotos = document.getElementById('noPhotos');
                    
                    if (data.success && data.photos.length > 0) {
                        photoGallery.innerHTML = '';
                        noPhotos.style.display = 'none';
                        
                        data.photos.forEach(photo => {
                            const photoCol = document.createElement('div');
                            photoCol.className = 'col-md-4 mb-3';
                            photoCol.innerHTML = `
                                <div class="card">
                                    <img src="${photo.photo_path}" class="card-img-top" alt="Animal Photo" 
                                         style="height: 200px; object-fit: cover; cursor: pointer;"
                                         onclick="openPhotoModal('${photo.photo_path}')">
                                    <div class="card-body">
                                        <small class="text-muted">Uploaded: ${photo.uploaded_at}</small>
                                    </div>
                                </div>
                            `;
                            photoGallery.appendChild(photoCol);
                        });
                    } else {
                        photoGallery.innerHTML = '';
                        noPhotos.style.display = 'block';
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('photoGalleryModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error loading photos:', error);
                    alert('Error loading photos');
                });
        });
    });

    // Open photo in full size modal
    function openPhotoModal(photoPath) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Photo View</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="${photoPath}" class="img-fluid" alt="Animal Photo">
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        const photoModal = new bootstrap.Modal(modal);
        photoModal.show();
        
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.removeChild(modal);
        });
    }
    </script>

    <!-- Photo Upload Modal -->
    <div class="modal fade" id="uploadPhotoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style= "background-color: #6c63ff">
                    <h5 class="modal-title" style= "color: white !important">Upload a photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="photoUploadForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Select Animal:</label>
                            <select class="form-select" name="animal_id" id="upload_animal_id" required>
                                <option value="">Choose an animal...</option>
                                <?php
                                // Fetch only disseminated animals for this client
                                $client_id = $_SESSION['client_id'];
                                $animal_query = "SELECT animal_id, species, animal_type FROM livestock_poultry WHERE client_id = ? AND UPPER(source) = 'DISSEMINATED' ORDER BY created_at DESC, species";
                                $animal_stmt = $conn->prepare($animal_query);
                                $animal_stmt->bind_param("i", $client_id);
                                $animal_stmt->execute();
                                $animal_result = $animal_stmt->get_result();
                                
                                while ($animal = $animal_result->fetch_assoc()) {
                                    echo '<option value="' . $animal['animal_id'] . '">' . $animal['species'] . ' (' . $animal['animal_type'] . ') - Disseminated</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Photos:</label>
                            <div class="d-flex gap-2 mb-3">
                                <button type="button" class="btn btn-outline-primary" id="cameraBtn">
                                    <i class="fas fa-camera"></i> Use Camera
                                </button>
                            </div>
                            
                            <!-- Camera Section -->
                            <div id="cameraSection" style="display: none;">
                                <div class="mb-3">
                                    <div style="position: relative; display: inline-block;">
                                        <video id="cameraVideo" autoplay playsinline style="width: 100%; max-width: 400px; height: 300px; background: #000;"></video>
                                        <div id="timestamp" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold;"></div>
                                    </div>
                                    <canvas id="cameraCanvas" style="display: none;"></canvas>
                                </div>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-success me-2" id="captureBtn">
                                        <i class="fas fa-camera"></i> Capture Photo
                                    </button>
                                    <button type="button" class="btn btn-warning" id="retakeBtn" style="display: none;">
                                        <i class="fas fa-redo"></i> Retake
                                    </button>
                                </div>
                                <div id="capturedPhotos" class="mb-3"></div>
                            </div>
                            
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <small id="uploadPermissionMsg" class="text-muted"></small>
                            <button type="submit" id="uploadSubmitBtn" class="btn btn-primary">UPLOAD</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>

    <!-- Photo Upload Success Modal -->
    <div class="modal fade" id="photoSuccessModal" aria-labelledby="photoSuccessModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #28a745; color: white;">
            <h5 class="modal-title" id="photoSuccessModalLabel">
              <i class="fas fa-check-circle me-2"></i>Success
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <i class="fas fa-check-circle text-success" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h6>Photos uploaded successfully!</h6>
            <p class="text-muted">Your photos have been uploaded and are ready for review.</p>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Photo Upload Error Modal -->
    <div class="modal fade" id="photoErrorModal" tabindex="-1" aria-labelledby="photoErrorModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="photoErrorModalLabel">Error</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <span id="errorMessage"></span>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>
</body>
</html>