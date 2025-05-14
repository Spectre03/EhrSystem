<?php
session_start();
include '../../db/db_connect.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("UPDATE patients SET 
            first_name = ?, 
            last_name = ?, 
            date_of_birth = ?, 
            email = ?, 
            phone = ?, 
            address = ?, 
            status = ?
            WHERE id = ?");
        
        $stmt->bind_param("sssssssi", 
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['date_of_birth'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['status'],
            $id
        );
        
        if ($stmt->execute()) {
            header('Location: patient.php?success=2');
            exit;
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $error = "Error updating patient: " . $e->getMessage();
    }
}

// Fetch patient data
try {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    
    if (!$patient) {
        header('Location: patient.php?error=1');
        exit;
    }
} catch (Exception $e) {
    $error = "Error fetching patient data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient - EHR System</title>
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
            <li class="breadcrumb-item">
                <a href="patient.php" class="text-decoration-none">
                    <i class="fas fa-users"></i> Patient Management
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <i class="fas fa-user-edit"></i> Edit Patient
            </li>
        </ol>
    </nav>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-user-edit me-2"></i>Edit Patient
                            </h4>
                            <span class="badge bg-light text-primary">
                                ID: <?php echo htmlspecialchars($patient['patient_id']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <!-- Personal Information -->
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                                    <div class="invalid-feedback">Please enter first name</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                                    <div class="invalid-feedback">Please enter last name</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>" required>
                                    <div class="invalid-feedback">Please select date of birth</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        <option value="Active" <?php echo $patient['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo $patient['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>

                                <!-- Contact Information -->
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
                                    <div class="invalid-feedback">Please enter phone number</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($patient['address']); ?></textarea>
                                    <div class="invalid-feedback">Please enter address</div>
                                </div>

                                <!-- Form Actions -->
                                <div class="col-12 text-end">
                                    <a href="patient.php" class="btn btn-secondary me-2">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Patient
                                    </button>
                                </div>
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