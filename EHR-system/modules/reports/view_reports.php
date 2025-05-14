<?php
session_start();
include '../../db/db_connect.php';

// Get filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$report_type = isset($_GET['type']) ? $_GET['type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query
$query = "SELECT r.*, u.username as created_by 
          FROM saved_reports r 
          LEFT JOIN users u ON r.user_id = u.user_id 
          WHERE 1=1";

if ($search) {
    $query .= " AND (r.report_name LIKE '%$search%' OR r.description LIKE '%$search%')";
}
if ($report_type) {
    $query .= " AND r.report_type = '$report_type'";
}
if ($start_date) {
    $query .= " AND r.created_at >= '$start_date'";
}
if ($end_date) {
    $query .= " AND r.created_at <= '$end_date 23:59:59'";
}

$query .= " ORDER BY r.created_at DESC";

$reports = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports - EHR System</title>
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
                <li class="breadcrumb-item active" aria-current="page">
                    View Reports
                </li>
            </ol>
        </nav>

        <!-- Search and Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="<?php echo $search; ?>" 
                               placeholder="Search reports...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Report Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="patient" <?php echo $report_type === 'patient' ? 'selected' : ''; ?>>Patient Report</option>
                            <option value="appointment" <?php echo $report_type === 'appointment' ? 'selected' : ''; ?>>Appointment Report</option>
                            <option value="financial" <?php echo $report_type === 'financial' ? 'selected' : ''; ?>>Financial Report</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reports List -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Saved Reports</h5>
                <a href="create_report.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Report
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Report Name</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">No reports found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($report['report_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $report['report_type'] === 'patient' ? 'primary' : 
                                                    ($report['report_type'] === 'appointment' ? 'success' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($report['report_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($report['description']); ?></td>
                                        <td><?php echo htmlspecialchars($report['created_by']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?php echo $report['report_type']; ?>_report.php?report_id=<?php echo $report['report_id']; ?>" 
                                                   class="btn btn-sm btn-primary" title="View Report">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_reports.php?id=<?php echo $report['report_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit Report">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" title="Delete Report"
                                                        onclick="confirmDelete(<?php echo $report['report_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                    Are you sure you want to delete this report? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" action="delete_report.php" method="POST" class="d-inline">
                        <input type="hidden" name="report_id" id="deleteReportId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(reportId) {
            document.getElementById('deleteReportId').value = reportId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>