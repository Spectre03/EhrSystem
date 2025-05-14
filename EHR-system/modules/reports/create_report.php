<?php
session_start();
include '../../db/db_connect.php';

$report_type = isset($_GET['type']) ? $_GET['type'] : 'patient';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

function getReportData($conn, $type, $start_date, $end_date) {
    switch($type) {
        case 'patient':
            return $conn->query("
                SELECT 
                    p.*,
                    COUNT(DISTINCT a.appointment_id) as total_appointments,
                    COUNT(DISTINCT b.bill_id) as total_bills,
                    SUM(b.total_amount) as total_billed
                FROM patients p
                LEFT JOIN appointments a ON p.patient_id = a.patient_id
                LEFT JOIN bills b ON p.patient_id = b.patient_id
                WHERE (a.appointment_date BETWEEN '$start_date' AND '$end_date'
                    OR b.bill_date BETWEEN '$start_date' AND '$end_date'
                    OR p.created_at BETWEEN '$start_date' AND '$end_date')
                GROUP BY p.patient_id
                ORDER BY p.last_name, p.first_name
            ")->fetch_all(MYSQLI_ASSOC);

        case 'appointment':
            return $conn->query("
                SELECT 
                    a.*,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    b.total_amount as billed_amount
                FROM appointments a
                LEFT JOIN patients p ON a.patient_id = p.patient_id
                LEFT JOIN bills b ON a.appointment_id = b.appointment_id
                WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date'
                ORDER BY a.appointment_date DESC
            ")->fetch_all(MYSQLI_ASSOC);

        case 'financial':
            return $conn->query("
                SELECT 
                    b.*,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    a.appointment_date
                FROM bills b
                LEFT JOIN patients p ON b.patient_id = p.patient_id
                LEFT JOIN appointments a ON b.appointment_id = a.appointment_id
                WHERE b.bill_date BETWEEN '$start_date' AND '$end_date'
                ORDER BY b.bill_date DESC
            ")->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

// Handle export
if(isset($_GET['export']) && $_GET['export'] === 'true') {
    $data = getReportData($conn, $report_type, $start_date, $end_date);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add CSV headers based on report type
    switch($report_type) {
        case 'patient':
            fputcsv($output, ['Patient ID', 'Name', 'DOB', 'Email', 'Phone', 'Total Appointments', 'Total Bills', 'Total Billed']);
            foreach($data as $row) {
                fputcsv($output, [
                    $row['patient_id'],
                    $row['first_name'] . ' ' . $row['last_name'],
                    $row['date_of_birth'],
                    $row['email'],
                    $row['phone'],
                    $row['total_appointments'],
                    $row['total_bills'],
                    $row['total_billed']
                ]);
            }
            break;
            
        case 'appointment':
            fputcsv($output, ['Appointment ID', 'Patient Name', 'Date', 'Time', 'Status', 'Billed Amount']);
            foreach($data as $row) {
                fputcsv($output, [
                    $row['appointment_id'],
                    $row['patient_name'],
                    date('Y-m-d', strtotime($row['appointment_date'])),
                    date('H:i', strtotime($row['appointment_date'])),
                    $row['status'],
                    $row['billed_amount']
                ]);
            }
            break;
            
        case 'financial':
            fputcsv($output, ['Bill ID', 'Patient Name', 'Date', 'Amount', 'Status', 'Appointment Date']);
            foreach($data as $row) {
                fputcsv($output, [
                    $row['bill_id'],
                    $row['patient_name'],
                    $row['bill_date'],
                    $row['total_amount'],
                    $row['status'],
                    $row['appointment_date']
                ]);
            }
            break;
    }
    
    fclose($output);
    exit();
}

$report_data = getReportData($conn, $report_type, $start_date, $end_date);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Report - EHR System</title>
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
                    Create Report
                </li>
            </ol>
        </nav>

        <!-- Report Controls -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Report Type</label>
                        <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="patient" <?php echo $report_type === 'patient' ? 'selected' : ''; ?>>Patient Report</option>
                            <option value="appointment" <?php echo $report_type === 'appointment' ? 'selected' : ''; ?>>Appointment Report</option>
                            <option value="financial" <?php echo $report_type === 'financial' ? 'selected' : ''; ?>>Financial Report</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="?type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=true" 
                           class="btn btn-success">
                            <i class="fas fa-file-export"></i> Export CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Content -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?php 
                    switch($report_type) {
                        case 'patient':
                            echo '<i class="fas fa-user-injured me-2"></i>Patient Report';
                            break;
                        case 'appointment':
                            echo '<i class="fas fa-calendar-alt me-2"></i>Appointment Report';
                            break;
                        case 'financial':
                            echo '<i class="fas fa-dollar-sign me-2"></i>Financial Report';
                            break;
                    }
                    ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover report-table">
                        <?php if($report_type === 'patient'): ?>
                            <thead>
                                <tr>
                                    <th>Patient ID</th>
                                    <th>Name</th>
                                    <th>DOB</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Appointments</th>
                                    <th>Total Bills</th>
                                    <th>Total Billed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($report_data as $row): ?>
                                    <tr>
                                        <td><?php echo $row['patient_id']; ?></td>
                                        <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                        <td><?php echo $row['date_of_birth']; ?></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td><?php echo $row['phone']; ?></td>
                                        <td><?php echo $row['total_appointments']; ?></td>
                                        <td><?php echo $row['total_bills']; ?></td>
                                        <td>$<?php echo number_format($row['total_billed'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        <?php elseif($report_type === 'appointment'): ?>
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Patient Name</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Billed Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($report_data as $row): ?>
                                    <tr>
                                        <td><?php echo $row['appointment_id']; ?></td>
                                        <td><?php echo $row['patient_name']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($row['appointment_date'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($row['appointment_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['status'] === 'Completed' ? 'success' : 
                                                    ($row['status'] === 'Cancelled' ? 'danger' : 
                                                    ($row['status'] === 'No Show' ? 'warning' : 'primary')); 
                                            ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($row['billed_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        <?php else: ?>
                            <thead>
                                <tr>
                                    <th>Bill ID</th>
                                    <th>Patient Name</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Appointment Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($report_data as $row): ?>
                                    <tr>
                                        <td><?php echo $row['bill_id']; ?></td>
                                        <td><?php echo $row['patient_name']; ?></td>
                                        <td><?php echo $row['bill_date']; ?></td>
                                        <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['status'] === 'Paid' ? 'success' : 
                                                    ($row['status'] === 'Pending' ? 'warning' : 
                                                    ($row['status'] === 'Overdue' ? 'danger' : 'secondary')); 
                                            ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $row['appointment_date']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>