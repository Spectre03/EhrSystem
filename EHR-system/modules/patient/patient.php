<?php
session_start();
include '../../db/db_connect.php';

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM patients";
if (!empty($search)) {
    $search = "%{$search}%";
    $query .= " WHERE first_name LIKE ? OR last_name LIKE ? OR patient_id LIKE ?";
}
$query .= " ORDER BY last_name ASC";

try {
    $stmt = $conn->prepare($query);
    if (!empty($search)) {
        $stmt->bind_param("sss", $search, $search, $search);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
// Check if medical_records table exists, if not create it
$check_table = $conn->query("SHOW TABLES LIKE 'medical_records'");
if ($check_table->num_rows == 0) {
    $create_table = "CREATE TABLE medical_records (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patient_id INT,
        record_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        diagnosis TEXT,
        treatment TEXT,
        notes TEXT,
        status ENUM('Pending', 'Completed', 'Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
    )";
    $conn->query($create_table);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="patient.css">
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
                <i class="fas fa-users"></i> Patient Management
            </li>
        </ol>
    </nav>
<!-- Quick Actions -->
<div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Add New Patient -->
                        <div class="col-md-3">
                            <a href="add_patient.php" class="card h-100 text-decoration-none hover-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-plus fa-2x mb-2 text-primary"></i>
                                    <h6 class="card-title mb-0">Add New Patient</h6>
                                    <small class="text-muted">Register a new patient</small>
                                </div>
                            </a>
                        </div>
                        
                        <!-- View All Patients -->
                        <div class="col-md-3">
                            <a href="patient.php" class="card h-100 text-decoration-none hover-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-list fa-2x mb-2 text-info"></i>
                                    <h6 class="card-title mb-0">View All Patients</h6>
                                    <small class="text-muted">Manage patient records</small>
                                </div>
                            </a>
                        </div>
                        
                        <!-- Schedule Appointment -->
                        <div class="col-md-3">
                            <a href="../appointments/add_appointment.php" class="card h-100 text-decoration-none hover-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-plus fa-2x mb-2 text-success"></i>
                                    <h6 class="card-title mb-0">Schedule Appointment</h6>
                                    <small class="text-muted">Book patient visits</small>
                                </div>
                            </a>
                        </div>
                        
                         <!-- Medical Records -->
                         <div class="col-md-3">
                            <a href="../medical_records/medical_records.php?patient_id=<?php echo $row['id']; ?>" class="card h-100 text-decoration-none hover-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-medical fa-2x mb-2 text-warning"></i>
                                    <h6 class="card-title mb-0">Medical Records</h6>
                                    <small class="text-muted">Add/view medical records</small>
                                </div>
                            </a>
                        </div>
                        <!-- Patient Search -->
                        <div class="col-md-3">
                            <a href="#searchModal" data-bs-toggle="modal" class="card h-100 text-decoration-none hover-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-search fa-2x mb-2 text-secondary"></i>
                                    <h6 class="card-title mb-0">Advanced Search</h6>
                                    <small class="text-muted">Search patient database</small>
                                </div>
                            </a>
                        </div>
                        
                        <!-- Patient Reports -->
                        <div class="col-md-3">
                            <a href="../reports/patients_report.php" class="card h-100 text-decoration-none hover-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-bar fa-2x mb-2 text-danger"></i>
                                    <h6 class="card-title mb-0">Patient Reports</h6>
                                    <small class="text-muted">Generate reports</small>
                                </div>
                            </a>
                        </div>
                        
                        <!-- Patient History -->
                        <div class="col-md-3">
                            <a href="../history/patient_history.php" class="card h-100 text-decoration-none hover-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-history fa-2x mb-2 text-info"></i>
                                    <h6 class="card-title mb-0">Patient History</h6>
                                    <small class="text-muted">View complete history</small>
                                </div>
                            </a>
                        </div>
                        
                        <!-- Back to Dashboard -->
                        <div class="col-md-3">
                            <a href="../../index.php" class="card h-100 text-decoration-none hover-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-home fa-2x mb-2 text-dark"></i>
                                    <h6 class="card-title mb-0">Dashboard</h6>
                                    <small class="text-muted">Return to main menu</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h1 class="h2 mb-0">
                    <i class="fas fa-user-injured text-primary me-2"></i>
                    Patient Management
                </h1>
                <p class="text-muted">Manage and view patient records</p>
            </div>
            <div class="col-md-6 text-md-end">
                <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-file-import me-2"></i>Import Patients
                </button>
                <a href="add_patient.php" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>Add New Patient
                </a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Patients</h6>
                                <h2 class="mb-0">
                                    <?php 
                                        $total = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc();
                                        echo $total['count'];
                                    ?>
                                </h2>
                            </div>
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Active Patients</h6>
                                <h2 class="mb-0">
                                    <?php 
                                        $active = $conn->query("SELECT COUNT(*) as count FROM patients WHERE status = 'Active'")->fetch_assoc();
                                        echo $active['count'];
                                    ?>
                                </h2>
                            </div>
                            <i class="fas fa-user-check fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Today's Appointments</h6>
                                <h2 class="mb-0">
                                    <?php 
                                        $appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()")->fetch_assoc();
                                        echo $appointments['count'] ?? 0;
                                    ?>
                                </h2>
                            </div>
                            <i class="fas fa-calendar-check fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Pending Reports</h6>
                                <h2 class="mb-0">
                                    <?php 
                                        $reports = $conn->query("SELECT COUNT(*) as count FROM medical_records WHERE status = 'Pending'")->fetch_assoc();
                                        echo $reports['count'] ?? 0;
                                    ?>
                                </h2>
                            </div>
                            <i class="fas fa-file-medical fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-primary"></i>
                            </span>
                            <input type="text" name="search" class="form-control" placeholder="Search patients..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Patient List -->
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Last Visit</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold"><?php echo htmlspecialchars($row['patient_id']); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-soft rounded-circle me-3">
                                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-birthday-cake me-1"></i>
                                                    <?php echo htmlspecialchars($row['date_of_birth']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-phone-alt text-primary me-1"></i>
                                            <?php echo htmlspecialchars($row['phone']); ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?php echo htmlspecialchars($row['email']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($row['last_visit_date']): ?>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar-alt text-info me-2"></i>
                                                <?php echo date('M d, Y', strtotime($row['last_visit_date'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No visits yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] == 'Active' ? 'success' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view_patient.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_patient.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?php echo $row['id']; ?>)"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <a href="medical_records.php?patient_id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-success" title="Medical Records">
                                                <i class="fas fa-notes-medical"></i>
                                            </a>
                                        </div>
                                    </td>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Patients</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="import_patients.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Choose CSV File</label>
                            <input type="file" class="form-control" name="csv_file" accept=".csv">
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Delete patient function
        function deletePatient(id) {
            if (confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
                $.post('delete_patient.php', { id: id }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting patient: ' + response.message);
                    }
                });
            }
        }
    </script>
</body>
</html>