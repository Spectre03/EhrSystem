<?php
session_start();
include '../../db/db_connect.php';


$id = isset($_GET['id']) ? $_GET['id'] : 0;

try {
    // Fetch patient data
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    
    if (!$patient) {
        header('Location: patient.php?error=1');
        exit;
    }

    // Fetch patient's appointments
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC LIMIT 5");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $appointments = $stmt->get_result();

    // Fetch patient's medical records
    $stmt = $conn->prepare("SELECT * FROM medical_records WHERE patient_id = ? ORDER BY record_date DESC LIMIT 5");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $medical_records = $stmt->get_result();

} catch (Exception $e) {
    $error = "Error fetching patient data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="patient.css">
</head>
<body>
    <?php include '../../header.php'; ?>
    <a href="../medical_records/medical_records.php?patient_id=<?php echo $patient['id']; ?>" 
                               class="btn btn-sm btn-success" title="Medical Records">
                                <i class="fas fa-notes-medical"></i>
                            </a>
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
                <a href="patient.php" class="text-decoration-none">
                    <i class="fas fa-users"></i> Patient Management
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <i class="fas fa-user"></i> View Patient
            </li>
        </ol>
    </nav>
    <div class="container-fluid py-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <!-- Patient Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-lg bg-white rounded-circle me-4">
                                    <i class="fas fa-user-circle fa-3x text-primary"></i>
                                </div>
                                <div>
                                    <h2 class="mb-1"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>
                                    <p class="mb-0">
                                        <i class="fas fa-id-card me-2"></i>
                                        Patient ID: <?php echo htmlspecialchars($patient['patient_id']); ?>
                                        <span class="ms-3 badge bg-<?php echo $patient['status'] == 'Active' ? 'success' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($patient['status']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="ms-auto">
                                    <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-light">
                                        <i class="fas fa-edit me-2"></i>Edit Patient
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Patient Information -->
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Patient Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <small class="text-muted d-block">Date of Birth</small>
                                    <div>
                                        <i class="fas fa-birthday-cake text-primary me-2"></i>
                                        <?php echo date('F d, Y', strtotime($patient['date_of_birth'])); ?>
                                        (<?php 
                                            $age = date_diff(date_create($patient['date_of_birth']), date_create('today'))->y;
                                            echo $age . ' years old';
                                        ?>)
                                    </div>
                                </li>
                                <li class="mb-3">
                                    <small class="text-muted d-block">Email</small>
                                    <div>
                                        <i class="fas fa-envelope text-primary me-2"></i>
                                        <a href="mailto:<?php echo htmlspecialchars($patient['email']); ?>">
                                            <?php echo htmlspecialchars($patient['email']); ?>
                                        </a>
                                    </div>
                                </li>
                                <li class="mb-3">
                                    <small class="text-muted d-block">Phone</small>
                                    <div>
                                        <i class="fas fa-phone text-primary me-2"></i>
                                        <a href="tel:<?php echo htmlspecialchars($patient['phone']); ?>">
                                            <?php echo htmlspecialchars($patient['phone']); ?>
                                        </a>
                                    </div>
                                </li>
                                <li class="mb-3">
                                    <small class="text-muted d-block">Address</small>
                                    <div>
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <?php echo nl2br(htmlspecialchars($patient['address'])); ?>
                                    </div>
                                </li>
                                <li>
                                    <small class="text-muted d-block">Last Visit</small>
                                    <div>
                                        <i class="fas fa-calendar-check text-primary me-2"></i>
                                        <?php 
                                            echo $patient['last_visit_date'] 
                                                ? date('F d, Y', strtotime($patient['last_visit_date']))
                                                : 'No visits recorded';
                                        ?>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="../appointments/add_appointment.php?patient_id=<?php echo $patient['id']; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-calendar-plus me-2"></i>Schedule Appointment
                                </a>
                                <a href="../medical_records/add_record.php?patient_id=<?php echo $patient['id']; ?>" 
                                   class="btn btn-outline-success">
                                    <i class="fas fa-file-medical me-2"></i>Add Medical Record
                                </a>
                                <a href="../prescriptions/add_prescription.php?patient_id=<?php echo $patient['id']; ?>" 
                                   class="btn btn-outline-info">
                                    <i class="fas fa-prescription me-2"></i>Write Prescription
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <!-- Recent Appointments -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>Recent Appointments
                            </h5>
                            <a href="../appointments/patient_appointments.php?id=<?php echo $patient['id']; ?>" 
                               class="btn btn-sm btn-primary">
                                View All
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if ($appointments->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Doctor</th>
                                                <th>Purpose</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                                    <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                                    <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Not Assigned'); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['purpose']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $appointment['status'] == 'Completed' ? 'success' : 
                                                                ($appointment['status'] == 'Cancelled' ? 'danger' : 'warning'); 
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
                                <p class="text-muted mb-0">No appointments found.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Medical Records -->
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-medical me-2"></i>Recent Medical Records
                            </h5>
                            <a href="../medical_records/patient_records.php?id=<?php echo $patient['id']; ?>" 
                               class="btn btn-sm btn-primary">
                                View All
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if ($medical_records->num_rows > 0): ?>
                                <div class="accordion" id="recordsAccordion">
                                    <?php while ($record = $medical_records->fetch_assoc()): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#record<?php echo $record['id']; ?>">
                                                    <div class="d-flex align-items-center w-100">
                                                        <div class="me-auto">
                                                            <strong><?php echo date('M d, Y', strtotime($record['record_date'])); ?></strong>
                                                            - <?php echo htmlspecialchars($record['diagnosis']); ?>
                                                        </div>
                                                        <span class="badge bg-info ms-2">
                                                            Dr. <?php echo htmlspecialchars($record['doctor_name']); ?>
                                                        </span>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="record<?php echo $record['id']; ?>" class="accordion-collapse collapse" 
                                                 data-bs-parent="#recordsAccordion">
                                                <div class="accordion-body">
                                                    <p class="mb-2">
                                                        <strong>Symptoms:</strong><br>
                                                        <?php echo nl2br(htmlspecialchars($record['symptoms'])); ?>
                                                    </p>
                                                    <p class="mb-2">
                                                        <strong>Treatment:</strong><br>
                                                        <?php echo nl2br(htmlspecialchars($record['treatment'])); ?>
                                                    </p>
                                                    <p class="mb-0">
                                                        <strong>Notes:</strong><br>
                                                        <?php echo nl2br(htmlspecialchars($record['notes'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No medical records found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>