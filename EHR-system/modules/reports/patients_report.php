<?php
session_start();
include '../../db/db_connect.php';

// Get date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get patient statistics
$patient_stats = $conn->query("
    SELECT 
        COUNT(*) as total_patients,
        SUM(CASE WHEN created_at BETWEEN '$start_date' AND '$end_date' THEN 1 ELSE 0 END) as new_patients,
        AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())) as avg_age
    FROM patients
")->fetch_assoc();

// Get gender distribution
$gender_distribution = $conn->query("
    SELECT gender, COUNT(*) as count
    FROM patients
    GROUP BY gender
")->fetch_all(MYSQLI_ASSOC);

// Get appointment statistics by patient
$appointment_stats = $conn->query("
    SELECT 
        p.patient_id,
        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
        COUNT(a.appointment_id) as total_appointments,
        SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) as completed_appointments,
        SUM(CASE WHEN a.status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
        SUM(CASE WHEN a.status = 'No Show' THEN 1 ELSE 0 END) as no_show_appointments
    FROM patients p
    LEFT JOIN appointments a ON p.patient_id = a.patient_id
    WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY p.patient_id
    ORDER BY total_appointments DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get billing statistics by patient
$billing_stats = $conn->query("
    SELECT 
        p.patient_id,
        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
        COUNT(b.bill_id) as total_bills,
        SUM(b.total_amount) as total_billed,
        SUM(CASE WHEN b.status = 'Paid' THEN b.total_amount ELSE 0 END) as total_paid
    FROM patients p
    LEFT JOIN bills b ON p.patient_id = b.patient_id
    WHERE b.bill_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY p.patient_id
    ORDER BY total_billed DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Age group distribution
$age_groups = $conn->query("
    SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 'Under 18'
            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50 THEN '31-50'
            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 51 AND 70 THEN '51-70'
            ELSE 'Over 70'
        END as age_group,
        COUNT(*) as count
    FROM patients
    GROUP BY age_group
    ORDER BY 
        CASE age_group
            WHEN 'Under 18' THEN 1
            WHEN '18-30' THEN 2
            WHEN '31-50' THEN 3
            WHEN '51-70' THEN 4
            WHEN 'Over 70' THEN 5
        END
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Reports - EHR System</title>
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
                    Patient Reports
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
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Patients</h6>
                                <h2 class="mb-0"><?php echo number_format($patient_stats['total_patients']); ?></h2>
                            </div>
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">New Patients</h6>
                                <h2 class="mb-0"><?php echo number_format($patient_stats['new_patients']); ?></h2>
                            </div>
                            <div class="stat-icon bg-success">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Average Age</h6>
                                <h2 class="mb-0"><?php echo round($patient_stats['avg_age']); ?></h2>
                            </div>
                            <div class="stat-icon bg-info">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Age Distribution -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Age Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ageDistributionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gender Distribution -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Gender Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="genderDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointment and Billing Stats -->
        <div class="row">
            <!-- Top Patients by Appointments -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top Patients by Appointments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Total</th>
                                        <th>Completed</th>
                                        <th>Cancelled</th>
                                        <th>No Show</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($appointment_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo $stat['patient_name']; ?></td>
                                            <td><?php echo $stat['total_appointments']; ?></td>
                                            <td><?php echo $stat['completed_appointments']; ?></td>
                                            <td><?php echo $stat['cancelled_appointments']; ?></td>
                                            <td><?php echo $stat['no_show_appointments']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Patients by Billing -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top Patients by Billing</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Total Bills</th>
                                        <th>Total Billed</th>
                                        <th>Total Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($billing_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo $stat['patient_name']; ?></td>
                                            <td><?php echo $stat['total_bills']; ?></td>
                                            <td>$<?php echo number_format($stat['total_billed'], 2); ?></td>
                                            <td>$<?php echo number_format($stat['total_paid'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Age Distribution Chart
        new Chart(document.getElementById('ageDistributionChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($age_groups, 'age_group')); ?>,
                datasets: [{
                    label: 'Number of Patients',
                    data: <?php echo json_encode(array_column($age_groups, 'count')); ?>,
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

        // Gender Distribution Chart
        new Chart(document.getElementById('genderDistributionChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($gender_distribution, 'gender')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($gender_distribution, 'count')); ?>,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(75, 192, 192, 0.5)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
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