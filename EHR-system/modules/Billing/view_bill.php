<?php
session_start();
include '../../db/db_connect.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Fetch bill details with patient information
$query = "SELECT b.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name, 
          p.patient_id as patient_number, p.address, p.phone, p.email 
          FROM bills b 
          LEFT JOIN patients p ON b.patient_id = p.id 
          WHERE b.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();

if (!$bill) {
    header('Location: billing.php?error=1');
    exit;
}

// Fetch bill items
$items_query = "SELECT * FROM bill_items WHERE bill_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$items_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bill - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="billing.css">
</head>
<body>
    <?php include '../../header.php'; ?>

    <div class="container-fluid py-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4 no-print">
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
                    <i class="fas fa-eye"></i> View Bill
                </li>
            </ol>
        </nav>

        <!-- Quick Actions -->
        <div class="row mb-4 no-print">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="fas fa-print me-2"></i>Print Bill
                            </button>
                            <a href="billing.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Bills
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bill Details -->
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Bill Header -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2 class="mb-4">
                            <i class="fas fa-hospital me-2"></i>EHR System
                        </h2>
                        <p class="mb-1">123 Healthcare Street</p>
                        <p class="mb-1">Medical City, MC 12345</p>
                        <p class="mb-1">Phone: (555) 123-4567</p>
                        <p>Email: billing@ehrsystem.com</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h1 class="mb-4">INVOICE</h1>
                        <p class="mb-1"><strong>Bill #:</strong> <?php echo htmlspecialchars($bill['bill_number']); ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo date('F d, Y', strtotime($bill['bill_date'])); ?></p>
                        <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($bill['due_date'])); ?></p>
                    </div>
                </div>

                <!-- Bill To -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="mb-3">Bill To:</h5>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($bill['patient_name']); ?></strong></p>
                        <p class="mb-1">Patient ID: <?php echo htmlspecialchars($bill['patient_number']); ?></p>
                        <p class="mb-1"><?php echo htmlspecialchars($bill['address']); ?></p>
                        <p class="mb-1">Phone: <?php echo htmlspecialchars($bill['phone']); ?></p>
                        <p>Email: <?php echo htmlspecialchars($bill['email']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <div class="payment-status <?php echo strtolower($bill['status']); ?>">
                            <h5 class="mb-2">Payment Status</h5>
                            <span class="badge <?php 
                                echo match($bill['status']) {
                                    'Paid' => 'bg-success',
                                    'Pending' => 'bg-warning',
                                    'Overdue' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            ?>">
                                <?php echo htmlspecialchars($bill['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Bill Items -->
                <div class="table-responsive mb-4">
                    <table class="table bill-items-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $items_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($item['amount'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end">$<?php echo number_format($bill['subtotal'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tax (10%):</strong></td>
                                <td class="text-end">$<?php echo number_format($bill['tax_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Notes -->
                <?php if ($bill['notes']): ?>
                    <div class="mb-4">
                        <h5>Notes:</h5>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($bill['notes'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Payment Instructions -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Payment Instructions</h5>
                        <p class="card-text mb-0">
                            Please make payment by the due date. For questions about this bill, 
                            contact our billing department at (555) 123-4567 or billing@ehrsystem.com
                        </p>
                    </div>
                </div>

                <!-- Thank You Note -->
                <div class="text-center">
                    <h5>Thank You for Your Business!</h5>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>