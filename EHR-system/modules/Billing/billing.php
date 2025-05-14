<?php
session_start();
include '../../db/db_connect.php';

// Fetch all bills with patient information
$query = "SELECT b.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name, p.patient_id as patient_number 
          FROM bills b 
          LEFT JOIN patients p ON b.patient_id = p.id 
          ORDER BY b.bill_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Management - EHR System</title>
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
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-file-invoice-dollar"></i> Billing
                </li>
            </ol>
        </nav>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Quick Actions</h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="add_bill.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>New Bill
                            </a>
                            <a href="generate_bill_for_patient.php" class="btn btn-success">
                                <i class="fas fa-user-plus me-2"></i>Generate Bill for Patient
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bills List -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Bills List
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Bill #</th>
                                <th>Patient</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($bill = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($bill['patient_name']); ?>
                                            <small class="text-muted d-block">
                                                ID: <?php echo htmlspecialchars($bill['patient_number']); ?>
                                            </small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($bill['bill_date'])); ?></td>
                                        <td>$<?php echo number_format($bill['total_amount'], 2); ?></td>
                                        <td>
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
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($bill['due_date'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view_bill.php?id=<?php echo $bill['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger delete-bill" 
                                                        data-id="<?php echo $bill['id']; ?>"
                                                        data-bill-number="<?php echo htmlspecialchars($bill['bill_number']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No bills found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete bill #<span id="billNumber"></span>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete bill functionality
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            let billToDelete = null;

            // Handle delete button clicks
            document.querySelectorAll('.delete-bill').forEach(button => {
                button.addEventListener('click', function() {
                    billToDelete = this.dataset.id;
                    document.getElementById('billNumber').textContent = this.dataset.billNumber;
                    deleteModal.show();
                });
            });

            // Handle delete confirmation
            document.getElementById('confirmDelete').addEventListener('click', function() {
                if (billToDelete) {
                    fetch('delete_bill.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: billToDelete
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Error deleting bill: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting bill');
                    });
                }
            });
        });
    </script>
</body>
</html>