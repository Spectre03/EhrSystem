<?php
session_start();
include '../../db/db_connect.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;
// Fetch all active doctors
$doctors_query = "SELECT id, CONCAT(first_name, ' ', last_name) as doctor_name FROM doctors WHERE status = 'Active' ORDER BY first_name";
$doctors_result = $conn->query($doctors_query);

// In the fetch appointment query, update to:
$query = "SELECT a.*, 
          CONCAT(p.first_name, ' ', p.last_name) as patient_name,
          CONCAT(d.first_name, ' ', d.last_name) as doctor_name
          FROM appointments a
          LEFT JOIN patients p ON a.patient_id = p.id
          LEFT JOIN doctors d ON a.doctor_id = d.id
          WHERE a.id = ?";

// In the update query, add doctor_id:
$update_query = "UPDATE appointments SET 
                 patient_id = ?, 
                 doctor_id = ?,
                 appointment_date = ?, 
                 appointment_time = ?, 
                 purpose = ?, 
                 status = ? 
                 WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("iissssi", $patient_id, $doctor_id, $appointment_date, $appointment_time, $purpose, $status, $id);

// Fetch appointment data
$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header('Location: appointment.php?error=1');
    exit;
}

// Fetch all patients for dropdown
$patients_query = "SELECT id, patient_id, first_name, last_name FROM patients ORDER BY first_name, last_name";
$patients_result = $conn->query($patients_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("UPDATE appointments SET 
                               patient_id = ?, 
                               doctor_name = ?, 
                               appointment_date = ?, 
                               appointment_time = ?, 
                               purpose = ?, 
                               notes = ?, 
                               status = ? 
                               WHERE id = ?");
        
        $patient_id = $_POST['patient_id'];
        $doctor_name = $_POST['doctor_name'];
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $purpose = $_POST['purpose'];
        $notes = $_POST['notes'];
        $status = $_POST['status'];
        
        $stmt->bind_param("issssssi", 
            $patient_id, 
            $doctor_name, 
            $appointment_date, 
            $appointment_time, 
            $purpose, 
            $notes, 
            $status,
            $id
        );
        
        if ($stmt->execute()) {
            header('Location: appointment.php?success=2');
            exit;
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $error = "Error updating appointment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment - EHR System</title>
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
                    <i class="fas fa-edit"></i> Edit Appointment
                </li>
            </ol>
        </nav>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>Edit Appointment
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" id="appointmentForm">
                            <!-- Patient Selection -->
                            <div class="mb-3">
                                <label for="patient_id" class="form-label">Patient</label>
                                <select class="form-select" id="patient_id" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php while ($patient = $patients_result->fetch_assoc()): ?>
                                        <option value="<?php echo $patient['id']; ?>" 
                                                <?php echo ($patient['id'] == $appointment['patient_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . 
                                                  ' (ID: ' . $patient['patient_id'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <!-- doctor selection: -->
                            <div class="mb-3">
                                <label for="doctor_id" class="form-label">Doctor</label>
                                <select class="form-select" id="doctor_id" name="doctor_id" required>
                                     <option value="">Select Doctor</option>
                                    <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
                                        <option value="<?php echo $doctor['id']; ?>" 
                                 <?php echo ($appointment['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($doctor['doctor_name']); ?>
                             </option>
                            <?php endwhile; ?>
                            </select>
                            </div>
                            <!-- Appointment Date and Time -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="appointment_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="appointment_date" 
                                           name="appointment_date" required
                                           value="<?php echo $appointment['appointment_date']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="appointment_time" class="form-label">Time</label>
                                    <input type="time" class="form-control" id="appointment_time" 
                                           name="appointment_time" required
                                           value="<?php echo $appointment['appointment_time']; ?>">
                                </div>
                            </div>

                            <!-- Purpose -->
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose</label>
                                <input type="text" class="form-control" id="purpose" name="purpose" 
                                       value="<?php echo htmlspecialchars($appointment['purpose']); ?>" required>
                            </div>

                            <!-- Notes -->
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" 
                                          rows="3"><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Scheduled" <?php echo $appointment['status'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="Completed" <?php echo $appointment['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $appointment['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="No Show" <?php echo $appointment['status'] == 'No Show' ? 'selected' : ''; ?>>No Show</option>
                                </select>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="appointment.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Appointment
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
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            const selectedDate = new Date(document.getElementById('appointment_date').value);
            const selectedTime = document.getElementById('appointment_time').value;
            const now = new Date();
            const status = document.getElementById('status').value;
            
            // Only validate future date for scheduled appointments
            if (status === 'Scheduled') {
                const appointmentDateTime = new Date(
                    selectedDate.getFullYear(),
                    selectedDate.getMonth(),
                    selectedDate.getDate(),
                    ...selectedTime.split(':')
                );
                
                if (appointmentDateTime < now) {
                    e.preventDefault();
                    alert('Cannot schedule appointments in the past');
                }
            }
        });
    </script>
</body>
</html>