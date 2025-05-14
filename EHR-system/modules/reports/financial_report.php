<?php
session_start();
include '../../db/db_connect.php';

// Get date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get overall financial statistics
$financial_stats = $conn->query("
    SELECT 
        COUNT(*) as total_bills,
        SUM(total_amount) as total_billed,
        SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) as total_collected,
        SUM(CASE WHEN status = 'Pending' THEN total_amount ELSE 0 END) as total_pending,
        SUM(CASE WHEN status = 'Overdue' THEN total_amount ELSE 0 END) as total_overdue
    FROM bills
    WHERE bill_date BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc();

// Get daily revenue
$daily_revenue = $conn->query("
    SELECT 
        DATE(bill_date) as date,
        SUM(total_amount) as total_amount,
        SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) as collected_amount
    FROM bills
    WHERE bill_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(bill_date)
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);

// Get payment method distribution
$payment_methods = $conn->query("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM payments
    WHERE payment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY payment_method
")->fetch_all(MYSQLI_ASSOC);

// Get top services by revenue
$top_services = $conn->query("
    SELECT 
        service_name,
        COUNT(*) as count,
        SUM(amount) as total_revenue
    FROM bill_items
    JOIN bills ON bill_items.bill_id = bills.bill_id
    WHERE bills.bill_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY service_name
    ORDER BY total_revenue DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Calculate collection rate
$collection_rate = $financial_stats['total_billed'] > 0 
    ? ($financial_stats['total_collected'] / $financial_stats['total_billed']) * 100 
    : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - EHR System</title>
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
                    Financial Reports
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
                                <h6 class="text-muted mb-2">Total Revenue</h6>
                                <h2 class="mb-0">$<?php echo number_format($financial_stats['total_billed'], 2); ?></h2>
                            </div>
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-dollar-sign"></i>
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
                                <h6 class="text-muted mb-2">Collection Rate</h6>
                                <h2 class="mb-0"><?php echo round($collection_rate, 1); ?>%</h2>
                            </div>
                            <div class="stat-icon bg-success">
                                <i class="fas fa-chart-pie"></i>
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
                                <h6 class="text-muted mb-2">Pending</h6>
                                <h2 class="mb-0">$<?php echo number_format($financial_stats['total_pending'], 2); ?></h2>
                            </div>
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-clock"></i>
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
                                <h6 class="text-muted mb-2">Overdue</h6>
                                <h2 class="mb-0">$<?php echo number_format($financial_stats['total_overdue'], 2); ?></h2>
                            </div>
                            <div class="stat-icon bg-danger">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Daily Revenue Trend -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daily Revenue Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Payment Methods</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="paymentMethodsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Services Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Top Services by Revenue</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Count</th>
                                <th>Total Revenue</th>
                                <th>Average Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_services as $service): ?>
                                <tr>
                                    <td><?php echo $service['service_name']; ?></td>
                                    <td><?php echo $service['count']; ?></td>
                                    <td>$<?php echo number_format($service['total_revenue'], 2); ?></td>
                                    <td>$<?php echo number_format($service['total_revenue'] / $service['count'], 2); ?></td>
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
        // Revenue Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_revenue, 'date')); ?>,
                datasets: [{
                    label: 'Total Billed',
                    data: <?php echo json_encode(array_column($daily_revenue, 'total_amount')); ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    fill: true
                }, {
                    label: 'Collected',
                    data: <?php echo json_encode(array_column($daily_revenue, 'collected_amount')); ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Payment Methods Chart
        new Chart(document.getElementById('paymentMethodsChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($payment_methods, 'payment_method')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($payment_methods, 'total_amount')); ?>,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(153, 102, 255, 0.5)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(153, 102, 255, 1)'
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