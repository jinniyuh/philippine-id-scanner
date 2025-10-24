<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$animal_id = $_GET['id'] ?? 0;
$client_id = $_SESSION['client_id'];

// Get animal details
$stmt = $conn->prepare("SELECT * FROM livestock_poultry WHERE animal_id = ? AND client_id = ?");
$stmt->bind_param("ii", $animal_id, $client_id);
$stmt->execute();
$animal = $stmt->get_result()->fetch_assoc();

if (!$animal) {
    header("Location: client_animals_owned.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Animal - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #6c63ff; }
        .container { 
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Animal</h2>
        <form id="editAnimalForm">
            <input type="hidden" name="animal_id" value="<?php echo $animal['animal_id']; ?>">
            
            <div class="mb-3">
                <label class="form-label">Type</label>
                <select class="form-select" name="type" required>
                    <option value="Livestock" <?php echo $animal['animal_type'] == 'Livestock' ? 'selected' : ''; ?>>Livestock</option>
                    <option value="Poultry" <?php echo $animal['animal_type'] == 'Poultry' ? 'selected' : ''; ?>>Poultry</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Species</label>
                <input type="text" class="form-control" name="species" value="<?php echo htmlspecialchars($animal['species']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Weight</label>
                <input type="text" class="form-control" name="weight" value="<?php echo htmlspecialchars($animal['weight']); ?>" <?php echo $animal['animal_type'] == 'Poultry' ? 'disabled' : ''; ?>>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control" name="quantity" value="<?php echo $animal['quantity']; ?>" <?php echo $animal['animal_type'] == 'Livestock' ? 'disabled' : ''; ?>>
            </div>

            <div class="mb-3">
                <label class="form-label">Source</label>
                <input type="text" class="form-control" name="source" value="<?php echo htmlspecialchars($animal['source']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Health Status</label>
                <select class="form-select" name="health_status" required>
                    <option value="Healthy" <?php echo $animal['health_status'] == 'Healthy' ? 'selected' : ''; ?>>Healthy</option>
                    <option value="Need Attention" <?php echo $animal['health_status'] == 'Need Attention' ? 'selected' : ''; ?>>Need Attention</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Last Vaccination</label>
                <input type="date" class="form-control" name="last_vaccination" value="<?php echo $animal['last_vaccination']; ?>" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="client_animals_owned.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script>
        document.getElementById('editAnimalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('client_update_animal.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'client_animals_owned.php';
                } else {
                    alert('Error updating animal: ' + (data.error || 'Unknown error'));
                }
            });
        });

        document.querySelector('select[name="type"]').addEventListener('change', function() {
            const quantityField = document.querySelector('input[name="quantity"]');
            const weightField = document.querySelector('input[name="weight"]');
            
            if (this.value === 'Poultry') {
                weightField.value = 'N/A';
                weightField.disabled = true;
                quantityField.disabled = false;
            } else {
                quantityField.value = '1';
                quantityField.disabled = true;
                weightField.disabled = false;
            }
        });
    </script>
</body>
</html>