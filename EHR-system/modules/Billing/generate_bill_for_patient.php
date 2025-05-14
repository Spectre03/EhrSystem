<?php
session_start();
include '../../db/db_connect.php';

// Fetch all patients for dropdown
$patients_query = "SELECT id, patient_id, first_name, last_name FROM patients ORDER BY first_name, last_name";
$patients_result = $conn->query($patients_query);

// If patient is selected, fetch their appointments
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;
$appointments = [];

if ($patient_id) {
    $stmt = $conn->prepare("SELECT * FROM appointments 
                           WHERE patient_id = ? AND status = 'Completed' 
                           AND id NOT IN (SELECT appointment_id FROM bill_items WHERE appointment_id IS NOT NULL)
                           ORDER BY appointment_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Bill for Patient - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="billing.css">
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
                    <a href="billing.php" class="text-decoration-none">
                        <i class="fas fa-file-invoice-dollar"></i> Billing
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-user-plus"></i> Generate Bill for Patient
                </li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-plus me-2"></i>Generate Bill for Patient
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Patient Selection Form -->
                        <form action="" method="GET" class="mb-4">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label for="patient_id" class="form-label">Select Patient</label>
                                    <select class="form-select" id="patient_id" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php while ($patient = $patients_result->fetch_assoc()): ?>
                                            <option value="<?php echo $patient['id']; ?>" 
                                                    <?php echo $patient_id == $patient['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . 
                                                      ' (ID: ' . $patient['patient_id'] . ')'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if ($patient_id): ?>
                            <?php if (count($appointments) > 0): ?>
                                <!-- Appointment Selection Form -->
                                <form action="add_bill.php" method="POST" id="generateBillForm">
                                    <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                                    
                                    <div class="table-responsive mb-4">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   id="selectAll">
                                                            <label class="form-check-label" for="selectAll">
                                                                Select All
                                                            </label>
                                                        </div>
                                                    </th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Doctor</th>
                                                    <th>Purpose</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($appointments as $appointment): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="form-check">
                                                                <input class="form-check-input appointment-check" 
                                                                       type="checkbox" 
                                                                       name="appointments[]" 
                                                                       value="<?php echo $appointment['id']; ?>">
                                                            </div>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                                        <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                                        <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($appointment['purpose']); ?></td>
                                                        <td><?php echo htmlspecialchars($appointment['notes']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="billing.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary" id="generateBill">
                                            <i class="fas fa-file-invoice-dollar me-2"></i>Generate Bill
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No unbilled completed appointments found for this patient.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const appointmentChecks = document.querySelectorAll('.appointment-check');
            const generateBillForm = document.getElementById('generateBillForm');
            
            if (selectAll) {
                // Handle select all checkbox
                selectAll.addEventListener('change', function() {
                    appointmentChecks.forEach(check => {
                        check.checked = this.checked;
                    });
                });
                
                // Update select all when individual checkboxes change
                appointmentChecks.forEach(check => {
                    check.addEventListener('change', function() {
                        selectAll.checked = Array.from(appointmentChecks)
                            .every(check => check.checked);
                    });
                });
                
                // Form validation
                generateBillForm.addEventListener('submit', function(e) {
                    const checkedAppointments = document.querySelectorAll('.appointment-check:checked');
                    if (checkedAppointments.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one appointment to generate a bill.');
                    }
                });
            }
        });
    </script>
</body>
</html>