<?php
session_start();
include '../../db/db_connect.php';

// Fetch appointments with patient and doctor details
$query = "SELECT a.*, 
          CONCAT(p.first_name, ' ', p.last_name) as patient_name,
          p.patient_id,
          CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
          d.specialization as doctor_specialization
          FROM appointments a
          LEFT JOIN patients p ON a.patient_id = p.id
          LEFT JOIN doctors d ON a.doctor_id = d.id
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="appointment.css">
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
                    <i class="fas fa-calendar"></i> Appointments
                </li>
            </ol>
        </nav>
 <!-- Appointment Management Header -->
 <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-calendar-alt me-2"></i>Appointments Dashboard</h2>
                    <div class="btn-group">
                        <a href="add_appointment.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> New Appointment
                        </a>
                        <a href="../reports/appointments_report.php" class="btn btn-success">
                            <i class="fas fa-chart-bar me-1"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Appointment Management Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="report-action-card">
                    <div class="icon-wrapper bg-primary">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <h5>Schedule</h5>
                    <p>Create a new appointment</p>
                    <a href="add_appointment.php" class="btn btn-primary btn-sm">Schedule Now</a>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="report-action-card">
                    <div class="icon-wrapper bg-info">
                        <i class="fas fa-list"></i>
                    </div>
                    <h5>View All</h5>
                    <p>See all appointments</p>
                    <a href="appointment.php" class="btn btn-info btn-sm">View List</a>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="report-action-card">
                    <div class="icon-wrapper bg-warning">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <h5>Today's</h5>
                    <p>View today's appointments</p>
                    <a href="appointment.php?date=today" class="btn btn-warning btn-sm">View Today</a>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="report-action-card">
                    <div class="icon-wrapper bg-success">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5>Analytics</h5>
                    <p>View appointment reports</p>
                    <a href="../reports/appointments_report.php" class="btn btn-success btn-sm">View Reports</a>
                </div>
            </div>
        </div>
        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Schedule New Appointment -->
                            <div class="col-md-3">
                                <a href="add_appointment.php" class="card h-100 text-decoration-none hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-plus fa-2x mb-2 text-primary"></i>
                                        <h6 class="card-title mb-0">New Appointment</h6>
                                        <small class="text-muted">Schedule an appointment</small>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Calendar View -->
                            <div class="col-md-3">
                                <a href="calendar_view.php" class="card h-100 text-decoration-none hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-alt fa-2x mb-2 text-success"></i>
                                        <h6 class="card-title mb-0">Calendar View</h6>
                                        <small class="text-muted">Monthly calendar</small>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Today's Appointments -->
                            <div class="col-md-3">
                                <a href="?filter=today" class="card h-100 text-decoration-none hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-clock fa-2x mb-2 text-info"></i>
                                        <h6 class="card-title mb-0">Today's Schedule</h6>
                                        <small class="text-muted">View today's appointments</small>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Reports -->
                            <div class="col-md-3">
                                <a href="appointment_reports.php" class="card h-100 text-decoration-none hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-line fa-2x mb-2 text-warning"></i>
                                        <h6 class="card-title mb-0">Reports</h6>
                                        <small class="text-muted">Generate reports</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form class="row g-3" method="GET">
                            <div class="col-md-3">
                                <label class="form-label">Date Range</label>
                                <select class="form-select" name="date_range">
                                    <option value="all">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="tomorrow">Tomorrow</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="all">All Status</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="no_show">No Show</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" placeholder="Search patient name or ID...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments List -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Appointments List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
    <td>
        <div class="fw-bold"><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></div>
        <small class="text-muted"><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></small>
    </td>
    <td>
        <div><?php echo htmlspecialchars($row['patient_name']); ?></div>
        <small class="text-muted">ID: <?php echo htmlspecialchars($row['patient_id']); ?></small>
    </td>
    <td>
        <div><?php echo htmlspecialchars($row['doctor_name'] ?? 'Not Assigned'); ?></div>
        <small class="text-muted"><?php echo htmlspecialchars($row['doctor_specialization'] ?? ''); ?></small>
    </td>
    <td><?php echo htmlspecialchars($row['purpose']); ?></td>
    <td>
        <span class="badge bg-<?php 
            echo $row['status'] == 'Completed' ? 'success' : 
                ($row['status'] == 'Cancelled' ? 'danger' : 
                ($row['status'] == 'No Show' ? 'warning' : 'info')); 
        ?>">
            <?php echo htmlspecialchars($row['status']); ?>
        </span>
    </td>
    <td>
        <div class="btn-group">
            <a href="view_appointment.php?id=<?php echo $row['id']; ?>" 
               class="btn btn-sm btn-info">
                <i class="fas fa-eye"></i>
            </a>
            <a href="edit_appointment.php?id=<?php echo $row['id']; ?>" 
               class="btn btn-sm btn-primary">
                <i class="fas fa-edit"></i>
            </a>
            <button type="button" class="btn btn-sm btn-danger" 
                    onclick="confirmDelete(<?php echo $row['id']; ?>)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </td>
</tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x mb-3 text-muted"></i>
                                        <p class="mb-0 text-muted">No appointments found</p>
                                    </td>
                                </tr>
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
                    Are you sure you want to delete this appointment?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let appointmentToDelete = null;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        function deleteAppointment(id) {
            appointmentToDelete = id;
            deleteModal.show();
        }

        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (appointmentToDelete) {
                fetch('delete_appointment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: appointmentToDelete
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting appointment: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the appointment');
                });
            }
            deleteModal.hide();
        });
    </script>
</body>
</html>