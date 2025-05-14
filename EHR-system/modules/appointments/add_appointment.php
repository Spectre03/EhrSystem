<?php
session_start();
include '../../db/db_connect.php';
// Fetch all patients for the dropdown
$patients_query = "SELECT id, CONCAT(first_name, ' ', last_name) as patient_name FROM patients ORDER BY first_name";
$patients_result = $conn->query($patients_query);

// Fetch all active doctors for the dropdown
$doctors_query = "SELECT id, CONCAT(first_name, ' ', last_name) as doctor_name FROM doctors WHERE status = 'Active' ORDER BY first_name";
$doctors_result = $conn->query($doctors_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $purpose = $_POST['reason']; // Changed from reason to purpose to match DB column
    $notes = $_POST['notes'];
    $status = 'Scheduled'; // Changed from 'pending' to 'Scheduled' to match ENUM values

    $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, purpose, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iissss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $purpose, $status);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Appointment scheduled successfully!";
        header("Location: appointment.php");
        exit();
    } else {
        $_SESSION['error'] = "Error scheduling appointment: " . $conn->error;
    }
}
// Fetch all patients for the dropdown
$patients_query = "SELECT id, CONCAT(first_name, ' ', last_name) as patient_name FROM patients ORDER BY first_name";
$patients_result = $conn->query($patients_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $purpose = $_POST['reason']; // Changed from reason to purpose to match DB column
    $notes = $_POST['notes'];
    $status = 'Scheduled'; // Changed from 'pending' to 'Scheduled' to match ENUM values

    $insert_query = "INSERT INTO appointments (patient_id, appointment_date, appointment_time, purpose, status) 
                    VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("issss", $patient_id, $appointment_date, $appointment_time, $purpose, $status);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Appointment scheduled successfully!";
        header("Location: appointment.php");
        exit();
    } else {
        $_SESSION['error'] = "Error scheduling appointment: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointment - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="appointment.css">
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
                    <a href="appointment.php" class="text-decoration-none">
                        <i class="fas fa-calendar"></i> Appointments
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-plus"></i> Schedule Appointment
                </li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-plus me-2"></i>Schedule New Appointment
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                    echo $_SESSION['error'];
                                    unset($_SESSION['error']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" class="needs-validation" novalidate>
                            <!-- Patient Selection -->
                            <div class="mb-3">
                                <label for="patient_id" class="form-label">Patient</label>
                                <select class="form-select" id="patient_id" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php while ($patient = $patients_result->fetch_assoc()): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['patient_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <!-- Doctor Selection -->
                            <div class="mb-3">
                                <label for="doctor_id" class="form-label">Doctor</label>
                                <select class="form-select" id="doctor_id" name="doctor_id" required>
                                    <option value="">Select Doctor</option>
                                    <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
                                        <option value="<?php echo $doctor['id']; ?>">
                                            <?php echo htmlspecialchars($doctor['doctor_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Date and Time -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="appointment_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="appointment_date" 
                                           name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                                    <div class="invalid-feedback">Please select a valid date.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="appointment_time" class="form-label">Time</label>
                                    <input type="time" class="form-control" id="appointment_time" 
                                           name="appointment_time" required>
                                    <div class="invalid-feedback">Please select a valid time.</div>
                                </div>
                            </div>

                            <!-- Reason -->
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Visit</label>
                                <input type="text" class="form-control" id="reason" name="reason" 
                                       required placeholder="Brief description of the visit">
                                <div class="invalid-feedback">Please provide a reason for the visit.</div>
                            </div>

                            <!-- Notes -->
                            <div class="mb-3">
                                <label for="notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Any additional information"></textarea>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-flex justify-content-end gap-2">
                                <a href="appointment.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-calendar-check me-1"></i>Schedule Appointment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>