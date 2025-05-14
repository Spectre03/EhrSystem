<?php
session_start();
include '../../db/db_connect.php';

// Fetch summary statistics
$stats = [
    'total_patients' => $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'],
    'total_appointments' => $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'],
    'total_bills' => $conn->query("SELECT COUNT(*) as count FROM bills")->fetch_assoc()['count'],
    'pending_payments' => $conn->query("SELECT COUNT(*) as count FROM bills WHERE status = 'Pending'")->fetch_assoc()['count']
];

// Get monthly appointment statistics for the current year
$monthly_appointments = $conn->query("
    SELECT MONTH(appointment_date) as month, 
           COUNT(*) as total,
           SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
           SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
           SUM(CASE WHEN status = 'No Show' THEN 1 ELSE 0 END) as no_show
    FROM appointments 
    WHERE YEAR(appointment_date) = YEAR(CURRENT_DATE)
    GROUP BY MONTH(appointment_date)
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

// Get revenue statistics
$revenue_stats = $conn->query("
    SELECT 
        SUM(total_amount) as total_revenue,
        SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) as collected_revenue,
        SUM(CASE WHEN status = 'Pending' THEN total_amount ELSE 0 END) as pending_revenue
    FROM bills
    WHERE YEAR(bill_date) = YEAR(CURRENT_DATE)
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="reports.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-chart-bar"></i> Reports
                </li>
            </ol>
        </nav>
        <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-chart-bar me-2"></i>Reports Dashboard</h2>
                    <div class="btn-group">
                        <a href="create_report.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> New Report
                        </a>
                        <a href="view_reports.php" class="btn btn-info">
                            <i class="fas fa-list me-1"></i> View All
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Report Management Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="report-action-card">
                    <div class="icon-wrapper bg-primary">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h5>Create Report</h5>
                    <p>Generate a new customized report</p>
                    <a href="create_report.php" class="btn btn-primary btn-sm">Create Now</a>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="report-action-card">
                    <div class="icon-wrapper bg-info">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h5>View Reports</h5>
                    <p>Access your saved reports</p>
                    <a href="view_reports.php" class="btn btn-info btn-sm">View All</a>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="report-action-card">
                    <div class="icon-wrapper bg-warning">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h5>Edit Reports</h5>
                    <p>Modify existing reports</p>
                    <a href="view_reports.php?action=edit" class="btn btn-warning btn-sm">Edit Reports</a>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="report-action-card">
                    <div class="icon-wrapper bg-success">
                        <i class="fas fa-history"></i>
                    </div>
                    <h5>Recent Reports</h5>
                    <p>View recently generated reports</p>
                    <a href="view_reports.php?filter=recent" class="btn btn-success btn-sm">View Recent</a>
                </div>
            </div>
        </div>

        <!-- Recent Reports Table -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Reports</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_reports)): ?>
                            <p class="text-center text-muted my-4">No reports generated yet</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Report Name</th>
                                            <th>Type</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_reports as $report): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($report['report_name']); ?></td>
                                                <td><span class="badge bg-<?php echo $report['report_type'] === 'patient' ? 'primary' : ($report['report_type'] === 'appointment' ? 'success' : 'info'); ?>"><?php echo ucfirst($report['report_type']); ?></span></td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view_reports.php?id=<?php echo $report['report_id']; ?>" class="btn btn-primary"><i class="fas fa-eye"></i></a>
                                                        <a href="edit_reports.php?id=<?php echo $report['report_id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                                                        <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $report['report_id']; ?>)"><i class="fas fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
                    Are you sure you want to delete this report?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" action="delete_report.php" method="POST">
                        <input type="hidden" name="report_id" id="deleteReportId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Patients</h6>
                                <h2 class="card-title mb-0"><?php echo $stats['total_patients']; ?></h2>
                            </div>
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Appointments</h6>
                                <h2 class="card-title mb-0"><?php echo $stats['total_appointments']; ?></h2>
                            </div>
                            <div class="stat-icon bg-success">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Bills</h6>
                                <h2 class="card-title mb-0"><?php echo $stats['total_bills']; ?></h2>
                            </div>
                            <div class="stat-icon bg-info">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Pending Payments</h6>
                                <h2 class="card-title mb-0"><?php echo $stats['pending_payments']; ?></h2>
                            </div>
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Types -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-alt me-2"></i>Available Reports
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="report-card">
                                    <div class="report-icon bg-primary">
                                        <i class="fas fa-user-injured"></i>
                                    </div>
                                    <h5>Patient Reports</h5>
                                    <p>View detailed patient statistics and demographics</p>
                                    <a href="create_report.php?type=patient" class="btn btn-primary">Generate Report</a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="report-card">
                                    <div class="report-icon bg-success">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <h5>Appointment Reports</h5>
                                    <p>Analyze appointment trends and statistics</p>
                                    <a href="create_report.php?type=appointment" class="btn btn-success">Generate Report</a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="report-card">
                                    <div class="report-icon bg-info">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                    <h5>Financial Reports</h5>
                                    <p>Track revenue, payments, and financial metrics</p>
                                    <a href="create_report.php?type=financial" class="btn btn-info">Generate Report</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <!-- Appointment Trends -->
            <div class="col-md-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Monthly Appointment Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="appointmentChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Revenue Overview -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Revenue Overview</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Revenue:</span>
                                <strong>$<?php echo number_format($revenue_stats['total_revenue'], 2); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Collected:</span>
                                <strong>$<?php echo number_format($revenue_stats['collected_revenue'], 2); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Pending:</span>
                                <strong>$<?php echo number_format($revenue_stats['pending_revenue'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare data for appointment chart
        const appointmentData = <?php echo json_encode($monthly_appointments); ?>;
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const chartLabels = appointmentData.map(data => months[data.month - 1]);
        const completedData = appointmentData.map(data => data.completed);
        const cancelledData = appointmentData.map(data => data.cancelled);
        const noShowData = appointmentData.map(data => data.no_show);

        // Create appointment chart
        new Chart(document.getElementById('appointmentChart'), {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Completed',
                    data: completedData,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true
                }, {
                    label: 'Cancelled',
                    data: cancelledData,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true
                }, {
                    label: 'No Show',
                    data: noShowData,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Create revenue chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'doughnut',
            data: {
                labels: ['Collected', 'Pending'],
                datasets: [{
                    data: [
                        <?php echo $revenue_stats['collected_revenue']; ?>,
                        <?php echo $revenue_stats['pending_revenue']; ?>
                    ],
                    backgroundColor: ['#28a745', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>