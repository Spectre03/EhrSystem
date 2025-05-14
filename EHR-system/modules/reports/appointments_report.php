<?php
session_start();
include '../../db/db_connect.php';

// Get date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get appointment statistics
$appointment_stats = $conn->query("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'No Show' THEN 1 ELSE 0 END) as no_show,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM appointments
    WHERE appointment_date BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc();

// Get daily appointment counts
$daily_appointments = $conn->query("
    SELECT 
        DATE(appointment_date) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'No Show' THEN 1 ELSE 0 END) as no_show
    FROM appointments
    WHERE appointment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(appointment_date)
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);

// Get appointment distribution by hour
$hourly_distribution = $conn->query("
    SELECT 
        HOUR(appointment_date) as hour,
        COUNT(*) as count
    FROM appointments
    WHERE appointment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY HOUR(appointment_date)
    ORDER BY hour
")->fetch_all(MYSQLI_ASSOC);

// Get top patients by appointments
$top_patients = $conn->query("
    SELECT 
        p.patient_id,
        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
        COUNT(*) as appointment_count,
        SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN a.status = 'No Show' THEN 1 ELSE 0 END) as no_show_count
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY p.patient_id
    ORDER BY appointment_count DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Calculate completion rate
$completion_rate = $appointment_stats['total_appointments'] > 0 
    ? ($appointment_stats['completed'] / $appointment_stats['total_appointments']) * 100 
    : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Reports - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="reports.css">
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
                <li class="breadcrumb-item">
                    <a href="reports.php" class="text-decoration-none">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Appointment Reports
                </li>
            </ol>
        </nav>

        <!-- Date Range Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                        <a href="?export=true&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                           class="btn btn-success">
                            <i class="fas fa-file-export"></i> Export Report
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Appointments</h6>
                                <h2 class="mb-0"><?php echo number_format($appointment_stats['total_appointments']); ?></h2>
                            </div>
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Completion Rate</h6>
                                <h2 class="mb-0"><?php echo round($completion_rate, 1); ?>%</h2>
                            </div>
                            <div class="stat-icon bg-success">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Cancelled</h6>
                                <h2 class="mb-0"><?php echo number_format($appointment_stats['cancelled']); ?></h2>
                            </div>
                            <div class="stat-icon bg-danger">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">No Shows</h6>
                                <h2 class="mb-0"><?php echo number_format($appointment_stats['no_show']); ?></h2>
                            </div>
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-user-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Daily Appointments Trend -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daily Appointment Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Hourly Distribution -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Appointment Time Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="hourlyDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Patients Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Top Patients by Appointments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Total Appointments</th>
                                <th>Completed</th>
                                <th>No Shows</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_patients as $patient): ?>
                                <tr>
                                    <td><?php echo $patient['patient_name']; ?></td>
                                    <td><?php echo $patient['appointment_count']; ?></td>
                                    <td><?php echo $patient['completed_count']; ?></td>
                                    <td><?php echo $patient['no_show_count']; ?></td>
                                    <td>
                                        <?php 
                                        $patient_completion_rate = ($patient['appointment_count'] > 0) 
                                            ? ($patient['completed_count'] / $patient['appointment_count']) * 100 
                                            : 0;
                                        echo round($patient_completion_rate, 1) . '%';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Daily Trend Chart
        new Chart(document.getElementById('dailyTrendChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_appointments, 'date')); ?>,
                datasets: [{
                    label: 'Total',
                    data: <?php echo json_encode(array_column($daily_appointments, 'total')); ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    fill: true
                }, {
                    label: 'Completed',
                    data: <?php echo json_encode(array_column($daily_appointments, 'completed')); ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Hourly Distribution Chart
        new Chart(document.getElementById('hourlyDistributionChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($hourly_distribution, 'hour')); ?>.map(hour => 
                    hour.toString().padStart(2, '0') + ':00'),
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode(array_column($hourly_distribution, 'count')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>