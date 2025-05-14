<?php
session_start();
include '../../db/db_connect.php';

// Initialize variables
$total_records = 0;
$recent_records = 0;
$active_patients = 0;
$records = null;

try {
    // Fetch statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM medical_records");
    $stmt->execute();
    $total_records = $stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT COUNT(*) as recent FROM medical_records WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $recent_records = $stmt->get_result()->fetch_assoc()['recent'];

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT patient_id) as active FROM medical_records");
    $stmt->execute();
    $active_patients = $stmt->get_result()->fetch_assoc()['active'];

    // Only fetch records if there are any
    if ($total_records > 0) {
        $stmt = $conn->prepare("
            SELECT 
                mr.*,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                DATE_FORMAT(mr.created_at, '%M %d, %Y') as formatted_date
            FROM medical_records mr
            JOIN patients p ON mr.patient_id = p.id
            JOIN doctors d ON mr.doctor_id = d.id
            ORDER BY mr.created_at DESC
        ");
        $stmt->execute();
        $records = $stmt->get_result();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
        }
        
        body {
            background-color: #f5f6fa;
        }
        
        .stat-card {
            transition: transform 0.3s ease;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .main-content {
            padding: 2rem;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .action-buttons .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include '../../header.php'; ?>
    
    <div class="container-fluid main-content">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-4">Medical Records Management</h2>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card card bg-primary text-white mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Total Records</h6>
                                        <h2 class="mb-0"><?php echo $total_records; ?></h2>
                                    </div>
                                    <i class="fas fa-file-medical fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="stat-card card bg-success text-white mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Recent Records</h6>
                                        <h2 class="mb-0"><?php echo $recent_records; ?></h2>
                                    </div>
                                    <i class="fas fa-clock fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="stat-card card bg-info text-white mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Active Patients</h6>
                                        <h2 class="mb-0"><?php echo $active_patients; ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons mb-4">
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                        <i class="fas fa-plus-circle me-2"></i>Add New Record
                    </button>
                    <button class="btn btn-outline-secondary" onclick="exportRecords()">
                        <i class="fas fa-download me-2"></i>Export Records
                    </button>
                </div>
                
                 <!-- Records Table -->
                 <div class="table-container">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <table class="table table-hover" id="recordsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient Name</th>
                                    <th>Doctor</th>
                                    <th>Diagnosis</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($records && $records->num_rows > 0): ?>
                                    <?php while ($record = $records->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $record['formatted_date']; ?></td>
                                            <td><?php echo $record['patient_name']; ?></td>
                                            <td><?php echo $record['doctor_name']; ?></td>
                                            <td><?php echo substr($record['diagnosis'], 0, 50) . '...'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $record['status'] === 'completed' ? 'success' : 
                                                        ($record['status'] === 'pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info me-1" onclick="viewRecord(<?php echo $record['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-primary me-1" onclick="editRecord(<?php echo $record['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteRecord(<?php echo $record['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-folder-open fa-3x mb-3"></i>
                                                <p>No medical records found. Click "Add New Record" to create one.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

    <!-- Add Record Modal -->
    <div class="modal fade" id="addRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Medical Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="recordForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Patient</label>
                                <select class="form-select" name="patient_id" required>
                                    <!-- Will be populated via AJAX -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Doctor</label>
                                <select class="form-select" name="doctor_id" required>
                                    <!-- Will be populated via AJAX -->
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diagnosis</label>
                            <textarea class="form-control" name="diagnosis" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Treatment</label>
                            <textarea class="form-control" name="treatment" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Follow-up Date</label>
                                <input type="date" class="form-control" name="follow_up_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveRecord()">Save Record</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#recordsTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 10,
                "language": {
                    "search": "Search records:",
                    "lengthMenu": "Show _MENU_ records per page",
                }
            });
        });

        function viewRecord(id) {
            // Implement view functionality
            window.location.href = `view_record.php?id=${id}`;
        }

        function editRecord(id) {
            // Implement edit functionality
            window.location.href = `edit_record.php?id=${id}`;
        }

        function deleteRecord(id) {
            if (confirm('Are you sure you want to delete this record?')) {
                fetch(`delete_record.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting record');
                    }
                });
            }
        }

        function exportRecords() {
            window.location.href = 'export_records.php';
        }

        function saveRecord() {
            const form = document.getElementById('recordForm');
            const formData = new FormData(form);

            fetch('save_record.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error saving record');
                }
            });
        }
    </script>
</body>
</html>