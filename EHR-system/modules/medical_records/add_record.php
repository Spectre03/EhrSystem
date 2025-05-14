<?php
session_start();
include '../../db/db_connect.php';

// Fetch all patients for the dropdown if patient_id is not provided
if (!isset($_GET['patient_id'])) {
    $patients_query = "SELECT id, first_name, last_name, patient_id as patient_number FROM patients ORDER BY first_name, last_name";
    $patients_result = $conn->query($patients_query);
    
    // Debug patients query
    if (!$patients_result) {
        die("Patients Query Error: " . $conn->error);
    }
    echo "Number of patients: " . $patients_result->num_rows . "<br>";
}

$patient_id = $_GET['patient_id'] ?? null;

// Fetch patient details if patient_id is provided
if ($patient_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();

        if (!$patient) {
            throw new Exception("Patient not found");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: medical_records.php");
        exit();
    }
}

// Fetch all doctors for the dropdown
$doctors_query = "SELECT id, first_name, last_name, specialization FROM doctors WHERE status = 'Active' ORDER BY first_name, last_name";
$doctors_result = $conn->query($doctors_query);

// Debug doctors query
if (!$doctors_result) {
    die("Doctors Query Error: " . $conn->error);
}
echo "Number of doctors: " . $doctors_result->num_rows . "<br>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        if (empty($_POST['diagnosis']) || empty($_POST['treatment']) || empty($_POST['doctor_id'])) {
            throw new Exception("All fields are required");
        }

        $diagnosis = $_POST['diagnosis'];
        $treatment = $_POST['treatment'];
        $doctor_id = $_POST['doctor_id'];
        $status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';

        // Start transaction
        $conn->begin_transaction();

        // Insert new medical record
        $stmt = $conn->prepare("
            INSERT INTO medical_records 
            (patient_id, doctor_id, diagnosis, treatment, status, notes, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iissss", $patient_id, $doctor_id, $diagnosis, $treatment, $status, $notes);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $_SESSION['success'] = "Medical record added successfully";
        header("Location: medical_records.php?patient_id=" . $patient_id);
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical Record - <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="medical_records.css">
</head>
<body>
    <?php include '../../header.php'; ?>

    <div class="container-fluid py-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li class="breadcrumb-item"><a href="medical_records.php"><i class="fas fa-file-medical"></i> Medical Records</a></li>
                <li class="breadcrumb-item"><a href="medical_records.php?patient_id=<?php echo $patient_id; ?>"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></a></li>
                <li class="breadcrumb-item active">Add Record</li>
            </ol>
        </nav>

        <!-- Alerts -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Add Record Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle text-primary me-2"></i>Add Medical Record</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row">
                        <?php if (!$patient_id): ?>
                        <!-- Patient Selection (only show if patient_id is not provided) -->
                        <div class="col-md-6 mb-3">
                            <label for="patient" class="form-label">Patient</label>
                            <select class="form-select" id="patient" name="patient_id" required>
                                <option value="">Select Patient</option>
                                <?php while ($p = $patients_result->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?> 
                                        (ID: <?php echo htmlspecialchars($p['patient_number']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Doctor Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="doctor" class="form-label">Doctor</label>
                            <select class="form-select" id="doctor" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
                                    <option value="<?php echo $doctor['id']; ?>">
                                        <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                        (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="diagnosis" class="form-label">Diagnosis</label>
                        <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="treatment" class="form-label">Treatment</label>
                        <textarea class="form-control" id="treatment" name="treatment" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="medical_records.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>