<?php
session_start();
include '../../db/db_connect.php';

// Check if record ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Record ID not provided";
    header("Location: medical_records.php");
    exit();
}

$record_id = $_GET['id'];

try {
    // Fetch record details with patient and doctor information
    $stmt = $conn->prepare("
        SELECT mr.*, 
               p.first_name as patient_first_name, 
               p.last_name as patient_last_name,
               p.id as patient_id
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.id
        WHERE mr.id = ?
    ");
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();

    if (!$record) {
        throw new Exception("Medical record not found");
    }

    // Fetch all doctors for the dropdown
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM doctors ORDER BY first_name, last_name");
    $stmt->execute();
    $doctors = $stmt->get_result();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: medical_records.php");
    exit();
}

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

        // Update medical record
        $stmt = $conn->prepare("
            UPDATE medical_records 
            SET doctor_id = ?, 
                diagnosis = ?, 
                treatment = ?, 
                status = ?, 
                notes = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("issssi", $doctor_id, $diagnosis, $treatment, $status, $notes, $record_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $_SESSION['success'] = "Medical record updated successfully";
        header("Location: medical_records.php?patient_id=" . $record['patient_id']);
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
    <title>Edit Medical Record - <?php echo htmlspecialchars($record['patient_first_name'] . ' ' . $record['patient_last_name']); ?></title>
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
                <li class="breadcrumb-item">
                    <a href="medical_records.php?patient_id=<?php echo $record['patient_id']; ?>">
                        <?php echo htmlspecialchars($record['patient_first_name'] . ' ' . $record['patient_last_name']); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active">Edit Record</li>
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

        <!-- Edit Record Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Medical Record</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="doctor" class="form-label">Doctor</label>
                            <select class="form-select" id="doctor" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                    <option value="<?php echo $doctor['id']; ?>" 
                                            <?php echo $doctor['id'] == $record['doctor_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Pending" <?php echo $record['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Completed" <?php echo $record['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo $record['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="diagnosis" class="form-label">Diagnosis</label>
                        <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required><?php echo htmlspecialchars($record['diagnosis']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="treatment" class="form-label">Treatment</label>
                        <textarea class="form-control" id="treatment" name="treatment" rows="3" required><?php echo htmlspecialchars($record['treatment']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($record['notes']); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="medical_records.php?patient_id=<?php echo $record['patient_id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Record
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