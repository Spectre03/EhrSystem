<?php
session_start();
include '../../db/db_connect.php';

// Fetch all patients for dropdown
$patients_query = "SELECT id, patient_id, first_name, last_name FROM patients ORDER BY first_name, last_name";
$patients_result = $conn->query($patients_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Generate bill number (format: BILL-YYYYMMDD-XXXX)
        $bill_number = 'BILL-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
        
        // Insert bill header
        $stmt = $conn->prepare("INSERT INTO bills (bill_number, patient_id, bill_date, due_date, subtotal, 
                               tax_amount, total_amount, status, notes) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $patient_id = $_POST['patient_id'];
        $bill_date = $_POST['bill_date'];
        $due_date = $_POST['due_date'];
        $subtotal = $_POST['subtotal'];
        $tax_amount = $_POST['tax_amount'];
        $total_amount = $_POST['total_amount'];
        $status = 'Pending';
        $notes = $_POST['notes'];
        
        $stmt->bind_param("sssddddss", 
            $bill_number,
            $patient_id,
            $bill_date,
            $due_date,
            $subtotal,
            $tax_amount,
            $total_amount,
            $status,
            $notes
        );
        
        if ($stmt->execute()) {
            $bill_id = $conn->insert_id;
            
            // Insert bill items
            $stmt = $conn->prepare("INSERT INTO bill_items (bill_id, description, quantity, unit_price, amount) 
                                  VALUES (?, ?, ?, ?, ?)");
            
            foreach ($_POST['items'] as $item) {
                $stmt->bind_param("isids",
                    $bill_id,
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['amount']
                );
                $stmt->execute();
            }
            
            $conn->commit();
            header('Location: billing.php?success=1');
            exit;
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error creating bill: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Bill - EHR System</title>
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
                <li class="breadcrumb-item">
                    <a href="billing.php" class="text-decoration-none">
                        <i class="fas fa-file-invoice-dollar"></i> Billing
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-plus"></i> Add New Bill
                </li>
            </ol>
        </nav>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus me-2"></i>Create New Bill
                </h5>
            </div>
            <div class="card-body">
                <form id="billForm" action="" method="POST" class="bill-form">
                    <div class="row mb-4">
                        <!-- Patient Selection -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="patient_id" class="form-label">Patient</label>
                                <select class="form-select" id="patient_id" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php while ($patient = $patients_result->fetch_assoc()): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . 
                                                  ' (ID: ' . $patient['patient_id'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Bill Date -->
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="bill_date" class="form-label">Bill Date</label>
                                <input type="date" class="form-control" id="bill_date" name="bill_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <!-- Due Date -->
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Bill Items -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Bill Items</h6>
                        </div>
                        <div class="card-body">
                            <table class="table bill-items-table" id="billItems">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Amount</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="bill-item">
                                        <td>
                                            <input type="text" class="form-control" 
                                                   name="items[0][description]" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control quantity" 
                                                   name="items[0][quantity]" value="1" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control unit-price" 
                                                   name="items[0][unit_price]" value="0.00" step="0.01" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control amount" 
                                                   name="items[0][amount]" value="0.00" step="0.01" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-item">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-success" id="addItem">
                                <i class="fas fa-plus me-2"></i>Add Item
                            </button>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="subtotal" class="form-label">Subtotal</label>
                                        <input type="number" class="form-control" id="subtotal" 
                                               name="subtotal" value="0.00" step="0.01" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tax_amount" class="form-label">Tax (10%)</label>
                                        <input type="number" class="form-control" id="tax_amount" 
                                               name="tax_amount" value="0.00" step="0.01" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="total_amount" class="form-label">Total Amount</label>
                                        <input type="number" class="form-control total-amount" id="total_amount" 
                                               name="total_amount" value="0.00" step="0.01" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="billing.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Bill
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const billItems = document.getElementById('billItems');
            const addItemBtn = document.getElementById('addItem');
            
            // Add new item row
            addItemBtn.addEventListener('click', function() {
                const newRow = document.querySelector('.bill-item').cloneNode(true);
                const inputs = newRow.querySelectorAll('input');
                const index = document.querySelectorAll('.bill-item').length;
                
                inputs.forEach(input => {
                    input.value = input.type === 'number' ? (input.classList.contains('amount') ? '0.00' : '1') : '';
                    input.name = input.name.replace('[0]', `[${index}]`);
                });
                
                billItems.querySelector('tbody').appendChild(newRow);
                updateTotals();
            });
            
            // Remove item row
            billItems.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-item') || 
                    e.target.parentElement.classList.contains('remove-item')) {
                    const items = document.querySelectorAll('.bill-item');
                    if (items.length > 1) {
                        const row = e.target.closest('.bill-item');
                        row.remove();
                        updateTotals();
                    }
                }
            });
            
            // Calculate line amount
            billItems.addEventListener('input', function(e) {
                if (e.target.classList.contains('quantity') || 
                    e.target.classList.contains('unit-price')) {
                    const row = e.target.closest('.bill-item');
                    const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                    const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
                    row.querySelector('.amount').value = (quantity * unitPrice).toFixed(2);
                    updateTotals();
                }
            });
            
            // Update totals
            function updateTotals() {
                let subtotal = 0;
                document.querySelectorAll('.amount').forEach(input => {
                    subtotal += parseFloat(input.value) || 0;
                });
                
                const taxRate = 0.10; // 10% tax
                const taxAmount = subtotal * taxRate;
                const total = subtotal + taxAmount;
                
                document.getElementById('subtotal').value = subtotal.toFixed(2);
                document.getElementById('tax_amount').value = taxAmount.toFixed(2);
                document.getElementById('total_amount').value = total.toFixed(2);
            }
            
            // Form validation
            document.getElementById('billForm').addEventListener('submit', function(e) {
                const items = document.querySelectorAll('.bill-item');
                let valid = true;
                
                items.forEach(item => {
                    const amount = parseFloat(item.querySelector('.amount').value);
                    if (amount <= 0) {
                        valid = false;
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please ensure all items have valid quantities and prices');
                }
            });
        });
    </script>
</body>
</html>