<?php
session_start();
include '../../db/db_connect.php';

// Get doctor ID from URL
$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch doctor details
$query = "SELECT * FROM doctors WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

// If doctor not found, redirect to doctors list
if (!$doctor) {
    $_SESSION['error'] = "Doctor not found!";
    header("Location: doctor.php");
    exit();
}

// Fetch doctor's appointments
$appointments_query = "SELECT a.*, p.first_name as patient_first_name, p.last_name as patient_last_name 
                      FROM appointments a 
                      LEFT JOIN patients p ON a.patient_id = p.id 
                      WHERE a.doctor_id = ? 
                      ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                      LIMIT 5";
$appointments_stmt = $conn->prepare($appointments_query);
$appointments_stmt->bind_param("i", $doctor_id);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Doctor - EHR System</title>
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
                <li class="breadcrumb-item">
                    <a href="doctor.php" class="text-decoration-none">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-eye"></i> View Doctor
                </li>
            </ol>
        </nav>

        <div class="row">
            <!-- Doctor Details Card -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="avatar-lg mx-auto mb-3 bg-primary rounded-circle text-center text-white">
                            <?php 
                                $initials = strtoupper(substr($doctor['first_name'], 0, 1) . substr($doctor['last_name'], 0, 1));
                                echo $initials;
                            ?>
                        </div>
                        <h4 class="mb-1">Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h4>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                        <span class="badge bg-<?php echo $doctor['status'] == 'Active' ? 'success' : 'danger'; ?> mb-3">
                            <?php echo htmlspecialchars($doctor['status']); ?>
                        </span>
                        <div class="d-grid gap-2">
                            <a href="edit_doctor.php?id=<?php echo $doctor['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i>Edit Profile
                            </a>
                            <button type="button" class="btn btn-danger" onclick="deleteDoctor(<?php echo $doctor['id']; ?>)">
                                <i class="fas fa-trash me-1"></i>Delete Doctor
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-address-card me-2"></i>Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <i class="fas fa-envelope text-muted me-2"></i>
                                <a href="mailto:<?php echo htmlspecialchars($doctor['email']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($doctor['email']); ?>
                                </a>
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-phone text-muted me-2"></i>
                                <a href="tel:<?php echo htmlspecialchars($doctor['phone']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($doctor['phone']); ?>
                                </a>
                            </li>
                            <li>
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                <?php echo nl2br(htmlspecialchars($doctor['address'])); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-calendar-check me-2"></i>Recent Appointments</h5>
                            <a href="../appointments/appointment.php?doctor_id=<?php echo $doctor_id; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-calendar me-1"></i>View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($appointments_result && $appointments_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Patient</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">
                                                        <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php 
                                                        echo htmlspecialchars($appointment['patient_first_name'] . ' ' . 
                                                                           $appointment['patient_last_name']); 
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['purpose']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $appointment['status'] == 'Completed' ? 'success' : 
                                                            ($appointment['status'] == 'Cancelled' ? 'danger' : 
                                                            ($appointment['status'] == 'No Show' ? 'warning' : 'info')); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x mb-3 text-muted"></i>
                                <p class="mb-0 text-muted">No appointments found</p>
                            </div>
                        <?php endif; ?>
                    </div>
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

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let doctorToDelete = null;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        function deleteDoctor(id) {
            doctorToDelete = id;
            deleteModal.show();
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
                        window.location.href = 'doctor.php';
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
    </script>
</body>
</html>