<?php
session_start();
include '../../db/db_connect.php';

// Handle Add/Edit/Delete operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                
                $sql = "INSERT INTO specializations (name, description) VALUES ('$name', '$description')";
                if ($conn->query($sql)) {
                    $_SESSION['success'] = "Specialization added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding specialization: " . $conn->error;
                }
                break;

            case 'edit':
                $id = mysqli_real_escape_string($conn, $_POST['id']);
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                
                $sql = "UPDATE specializations SET name = '$name', description = '$description' WHERE id = '$id'";
                if ($conn->query($sql)) {
                    $_SESSION['success'] = "Specialization updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating specialization: " . $conn->error;
                }
                break;

            case 'delete':
                $id = mysqli_real_escape_string($conn, $_POST['id']);
                
                // Check if specialization is in use
                $check_sql = "SELECT COUNT(*) as count FROM doctors WHERE specialization = (SELECT name FROM specializations WHERE id = '$id')";
                $result = $conn->query($check_sql);
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $_SESSION['error'] = "Cannot delete: This specialization is assigned to $count doctor(s)";
                } else {
                    $sql = "DELETE FROM specializations WHERE id = '$id'";
                    if ($conn->query($sql)) {
                        $_SESSION['success'] = "Specialization deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Error deleting specialization: " . $conn->error;
                    }
                }
                break;
        }
        header('Location: specializations.php');
        exit();
    }
}

// Fetch all specializations
$query = "SELECT s.*, COUNT(d.id) as doctor_count 
          FROM specializations s 
          LEFT JOIN doctors d ON s.name = d.specialization 
          GROUP BY s.id 
          ORDER BY s.name";
$specializations = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Specializations - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <style>
        .specialization-card {
            transition: transform 0.2s;
            border-radius: 10px;
        }
        
        .specialization-card:hover {
            transform: translateY(-5px);
        }

        .doctor-count {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.1);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            border-radius: 15px 15px 0 0;
        }
    </style>
</head>
<body>
    <?php include '../../header.php'; ?>

    <div class="container-fluid py-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../../index.php" class="text-decoration-none">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="doctor.php" class="text-decoration-none">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    <i class="fas fa-stethoscope"></i> Specializations
                </li>
            </ol>
        </nav>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add Specialization Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus-circle me-2"></i>Add New Specialization
            </button>
        </div>

        <!-- Specializations Grid -->
        <div class="row g-4">
            <?php while($spec = $specializations->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card specialization-card h-100">
                        <div class="card-body position-relative">
                            <span class="doctor-count">
                                <i class="fas fa-user-md me-1"></i><?php echo $spec['doctor_count']; ?> Doctors
                            </span>
                            <h5 class="card-title mb-3">
                                <i class="fas fa-stethoscope me-2 text-primary"></i>
                                <?php echo htmlspecialchars($spec['name']); ?>
                            </h5>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($spec['description']); ?>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <button class="btn btn-sm btn-primary" 
                                    onclick="editSpecialization(<?php echo $spec['id']; ?>, '<?php echo htmlspecialchars($spec['name']); ?>', '<?php echo htmlspecialchars($spec['description']); ?>')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <?php if ($spec['doctor_count'] == 0): ?>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="deleteSpecialization(<?php echo $spec['id']; ?>, '<?php echo htmlspecialchars($spec['name']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Add Specialization Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add New Specialization</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Specialization Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Specialization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Specialization Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Specialization</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editId">
                        <div class="mb-3">
                            <label class="form-label">Specialization Name</label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <p>Are you sure you want to delete the specialization "<span id="deleteName"></span>"?</p>
                        <p class="text-danger mb-0"><small>This action cannot be undone.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Specialization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        function editSpecialization(id, name, description) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDescription').value = description;
            editModal.show();
        }

        function deleteSpecialization(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteName').textContent = name;
            deleteModal.show();
        }
    </script>
</body>
</html>