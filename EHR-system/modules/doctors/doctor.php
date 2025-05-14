<?php
session_start();
include '../../db/db_connect.php';

// Fetch doctors with their details
$query = "SELECT * FROM doctors ORDER BY first_name, last_name";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors Management - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="doctor.css">
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
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-user-md"></i> Doctors
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

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Add New Doctor -->
                            <div class="col-md-3">
                                <a href="add_doctor.php" class="card h-100 text-decoration-none hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-plus fa-2x mb-2 text-primary"></i>
                                        <h6 class="card-title mb-0">New Doctor</h6>
                                        <small class="text-muted">Add a new doctor</small>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- View All Doctors -->
                            <div class="col-md-3">
                                <a href="#doctorsList" class="card h-100 text-decoration-none hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-list fa-2x mb-2 text-success"></i>
                                        <h6 class="card-title mb-0">View All</h6>
                                        <small class="text-muted">List all doctors</small>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Edit Doctor -->
                            <div class="col-md-3">
                                <a href="#" class="card h-100 text-decoration-none hover-card" onclick="showEditOptions()">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-edit fa-2x mb-2 text-info"></i>
                                        <h6 class="card-title mb-0">Quick Edit</h6>
                                        <small class="text-muted">Edit doctor details</small>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Delete Doctor -->
                            <div class="col-md-3">
                                <a href="#" class="card h-100 text-decoration-none hover-card" onclick="showDeleteOptions()">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-times fa-2x mb-2 text-danger"></i>
                                        <h6 class="card-title mb-0">Quick Delete</h6>
                                        <small class="text-muted">Remove doctor</small>
                                    </div>
                                </a>
                            </div>
                            <!-- Specializations -->
                            <div class="col-md-4">
                                <a href="specializations.php" class="card h-100 text-decoration-none hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-stethoscope fa-2x mb-2 text-info"></i>
                                        <h6 class="card-title mb-0">Specializations</h6>
                                        <small class="text-muted">Manage specializations</small>
                                    </div>
                                </a>
                            </div>
                            <!-- Doctor Schedule -->
                            <div class="col-md-4">
                                <a href="schedule.php" class="card h-100 text-decoration-none hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-alt fa-2x mb-2 text-warning"></i>
                                        <h6 class="card-title mb-0">Schedules</h6>
                                        <small class="text-muted">View doctor schedules</small>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Doctor Reports -->
                            <div class="col-md-4">
                            <a href="../reports/doctors_report.php" class="card h-100 text-decoration-none hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-bar fa-2x mb-2 text-secondary"></i>
                                        <h6 class="card-title mb-0">Reports</h6>
                                        <small class="text-muted">Generate reports</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctors List -->
        <div class="card shadow-sm" id="doctorsList">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Doctors List</h5>
                    <div class="input-group" style="width: 300px;">
                        <input type="text" class="form-control" id="searchDoctor" placeholder="Search doctors...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Specialization</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-3 bg-primary rounded-circle text-center text-white">
                                                    <?php 
                                                        $initials = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
                                                        echo $initials;
                                                    ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">Dr. <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($row['phone']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['address']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['status'] == 'Active' ? 'success' : 'danger'; ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view_doctor.php?id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_doctor.php?id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger"
                                                        onclick="deleteDoctor(<?php echo $row['id']; ?>)"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-user-md fa-3x mb-3 text-muted"></i>
                                        <p class="mb-0 text-muted">No doctors found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this doctor? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Edit Modal -->
    <div class="modal fade" id="quickEditModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Edit Doctor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="quickEditDoctor" class="form-label">Select Doctor</label>
                        <select class="form-select" id="quickEditDoctor">
                            <option value="">Choose a doctor...</option>
                            <?php 
                            $result->data_seek(0);
                            while ($row = $result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $row['id']; ?>">
                                    Dr. <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="quickEdit()">Edit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Delete Modal -->
    <div class="modal fade" id="quickDeleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Delete Doctor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="quickDeleteDoctor" class="form-label">Select Doctor</label>
                        <select class="form-select" id="quickDeleteDoctor">
                            <option value="">Choose a doctor...</option>
                            <?php 
                            $result->data_seek(0);
                            while ($row = $result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $row['id']; ?>">
                                    Dr. <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="quickDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let doctorToDelete = null;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const quickEditModal = new bootstrap.Modal(document.getElementById('quickEditModal'));
        const quickDeleteModal = new bootstrap.Modal(document.getElementById('quickDeleteModal'));

        function deleteDoctor(id) {
            doctorToDelete = id;
            deleteModal.show();
        }

        function showEditOptions() {
            quickEditModal.show();
        }

        function showDeleteOptions() {
            quickDeleteModal.show();
        }

        function quickEdit() {
            const doctorId = document.getElementById('quickEditDoctor').value;
            if (doctorId) {
                window.location.href = 'edit_doctor.php?id=' + doctorId;
            }
        }

        function quickDelete() {
            const doctorId = document.getElementById('quickDeleteDoctor').value;
            if (doctorId) {
                deleteDoctor(doctorId);
                quickDeleteModal.hide();
            }
        }

        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (doctorToDelete) {
                fetch('delete_doctor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: doctorToDelete
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting doctor: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the doctor');
                });
            }
            deleteModal.hide();
        });

        // Search functionality
        document.getElementById('searchDoctor').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });
    </script>
</body>
</html>