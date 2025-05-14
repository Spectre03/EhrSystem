<?php
session_start();
include '../../db/db_connect.php';

$report_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_name = $_POST['report_name'];
    $report_type = $_POST['report_type'];
    $description = $_POST['description'];
    $parameters = json_encode($_POST['parameters']);
    
    try {
        $stmt = $conn->prepare("
            UPDATE saved_reports 
            SET report_name = ?, 
                report_type = ?, 
                description = ?, 
                parameters = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE report_id = ?
        ");
        
        $stmt->bind_param("ssssi", $report_name, $report_type, $description, $parameters, $report_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Report updated successfully!";
            header("Location: view_reports.php");
            exit();
        } else {
            throw new Exception("Failed to update report");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get report data
$report = $conn->query("
    SELECT * FROM saved_reports WHERE report_id = $report_id
")->fetch_assoc();

if (!$report) {
    $_SESSION['error'] = "Report not found";
    header("Location: view_reports.php");
    exit();
}

$parameters = json_decode($report['parameters'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="reports.css">
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
                    <a href="reports.php" class="text-decoration-none">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="view_reports.php" class="text-decoration-none">
                        View Reports
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Edit Report
                </li>
            </ol>
        </nav>

        <!-- Edit Form -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit Report</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Report Name</label>
                                <input type="text" name="report_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($report['report_name']); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a report name
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Report Type</label>
                                <select name="report_type" class="form-select" required>
                                    <option value="patient" <?php echo $report['report_type'] === 'patient' ? 'selected' : ''; ?>>
                                        Patient Report
                                    </option>
                                    <option value="appointment" <?php echo $report['report_type'] === 'appointment' ? 'selected' : ''; ?>>
                                        Appointment Report
                                    </option>
                                    <option value="financial" <?php echo $report['report_type'] === 'financial' ? 'selected' : ''; ?>>
                                        Financial Report
                                    </option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a report type
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php 
                                    echo htmlspecialchars($report['description']); 
                                ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Report Parameters -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="mb-3">Report Parameters</h6>
                        </div>
                        <div class="col-md-6">
                            <!-- Date Range -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-3">Date Range</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="parameters[start_date]" class="form-control"
                                                   value="<?php echo $parameters['start_date'] ?? ''; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="parameters[end_date]" class="form-control"
                                                   value="<?php echo $parameters['end_date'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Filters -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-3">Filters</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="parameters[status]" class="form-select">
                                            <option value="">All Statuses</option>
                                            <option value="active" <?php echo ($parameters['status'] ?? '') === 'active' ? 'selected' : ''; ?>>
                                                Active
                                            </option>
                                            <option value="inactive" <?php echo ($parameters['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>
                                                Inactive
                                            </option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Sort By</label>
                                        <select name="parameters[sort_by]" class="form-select">
                                            <option value="date" <?php echo ($parameters['sort_by'] ?? '') === 'date' ? 'selected' : ''; ?>>
                                                Date
                                            </option>
                                            <option value="name" <?php echo ($parameters['sort_by'] ?? '') === 'name' ? 'selected' : ''; ?>>
                                                Name
                                            </option>
                                            <option value="amount" <?php echo ($parameters['sort_by'] ?? '') === 'amount' ? 'selected' : ''; ?>>
                                                Amount
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Display Options -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-3">Display Options</h6>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="parameters[show_charts]" class="form-check-input" 
                                                   value="1" <?php echo ($parameters['show_charts'] ?? false) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Show Charts</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="parameters[show_summary]" class="form-check-input"
                                                   value="1" <?php echo ($parameters['show_summary'] ?? false) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Show Summary</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="parameters[show_details]" class="form-check-input"
                                                   value="1" <?php echo ($parameters['show_details'] ?? false) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Show Details</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Export Options -->
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-3">Export Options</h6>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="parameters[export_pdf]" class="form-check-input"
                                                   value="1" <?php echo ($parameters['export_pdf'] ?? false) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Enable PDF Export</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="parameters[export_excel]" class="form-check-input"
                                                   value="1" <?php echo ($parameters['export_excel'] ?? false) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Enable Excel Export</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="view_reports.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
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