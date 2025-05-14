<?php
session_start();
include '../../db/db_connect.php';

// Get report filters
$specialization = isset($_GET['specialization']) ? $_GET['specialization'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '';

// Base query
$query = "SELECT d.*, 
          COUNT(DISTINCT a.id) as total_appointments,
          COUNT(DISTINCT p.id) as total_patients
          FROM doctors d
          LEFT JOIN appointments a ON d.id = a.doctor_id
          LEFT JOIN patients p ON a.patient_id = p.id";

// Add filters
$where_conditions = [];
if ($specialization) {
    $where_conditions[] = "d.specialization = '" . mysqli_real_escape_string($conn, $specialization) . "'";
}
if ($status) {
    $where_conditions[] = "d.status = '" . mysqli_real_escape_string($conn, $status) . "'";
}
if ($date_range) {
    $dates = explode(' - ', $date_range);
    if (count($dates) == 2) {
        $where_conditions[] = "d.created_at BETWEEN '" . mysqli_real_escape_string($conn, $dates[0]) . "' AND '" . mysqli_real_escape_string($conn, $dates[1]) . "'";
    }
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
}

$query .= " GROUP BY d.id ORDER BY d.first_name, d.last_name";
$result = $conn->query($query);

// Get unique specializations for filter
$spec_query = "SELECT DISTINCT specialization FROM doctors ORDER BY specialization";
$specializations = $conn->query($spec_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors Report - EHR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="../../assets/styles.css">
    <link rel="stylesheet" href="../reports/reports.css">
    <style>
        .report-stat-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .report-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .stat-icon-lg {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .filter-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .report-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .report-actions .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .report-actions .btn:hover {
            transform: translateY(-2px);
        }

        .daterangepicker {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
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
                    <a href="../doctors/doctor.php" class="text-decoration-none">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    <i class="fas fa-chart-bar"></i> Reports
                </li>
            </ol>
        </nav>

        <!-- Report Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card report-stat-card bg-primary bg-gradient text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-user-md stat-icon-lg"></i>
                        <h3 class="display-6 fw-bold mb-2"><?php echo $result->num_rows; ?></h3>
                        <h6 class="text-white-50">Total Doctors</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card report-stat-card bg-success bg-gradient text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle stat-icon-lg"></i>
                        <h3 class="display-6 fw-bold mb-2">
                            <?php 
                            $active_count = 0;
                            $result->data_seek(0);
                            while($row = $result->fetch_assoc()) {
                                if($row['status'] == 'Active') $active_count++;
                            }
                            echo $active_count;
                            ?>
                        </h3>
                        <h6 class="text-white-50">Active Doctors</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card report-stat-card bg-info bg-gradient text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-stethoscope stat-icon-lg"></i>
                        <h3 class="display-6 fw-bold mb-2">
                            <?php 
                            $spec_count_query = "SELECT COUNT(DISTINCT specialization) as count FROM doctors";
                            $spec_count = $conn->query($spec_count_query)->fetch_assoc()['count'];
                            echo $spec_count;
                            ?>
                        </h3>
                        <h6 class="text-white-50">Specializations</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card report-stat-card bg-warning bg-gradient text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-users stat-icon-lg"></i>
                        <h3 class="display-6 fw-bold mb-2">
                            <?php 
                            $result->data_seek(0);
                            $total_patients = 0;
                            while($row = $result->fetch_assoc()) {
                                $total_patients += $row['total_patients'];
                            }
                            echo $result->num_rows > 0 ? round($total_patients / $result->num_rows, 1) : 0;
                            ?>
                        </h3>
                        <h6 class="text-white-50">Avg. Patients/Doctor</h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="card filter-card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="fas fa-filter me-2 text-primary"></i>Report Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted">Specialization</label>
                        <select name="specialization" class="form-select">
                            <option value="">All Specializations</option>
                            <?php while($spec = $specializations->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($spec['specialization']); ?>"
                                        <?php echo $specialization == $spec['specialization'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec['specialization']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" <?php echo $status == 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $status == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted">Date Range</label>
                        <input type="text" name="date_range" class="form-control" id="dateRange" 
                               value="<?php echo htmlspecialchars($date_range); ?>"
                               placeholder="Select date range">
                    </div>
                    <div class="col-12 report-actions">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-2"></i>Apply Filters
                        </button>
                        <a href="doctors_report.php" class="btn btn-secondary me-2">
                            <i class="fas fa-redo me-2"></i>Reset
                        </a>
                        <button type="button" class="btn btn-success me-2" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-2"></i>Export to Excel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf me-2"></i>Export to PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Detailed Report -->
        <div class="card filter-card">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-table me-2 text-primary"></i>Detailed Doctor Report
                    </h5>
                    <span class="badge bg-primary"><?php echo $result->num_rows; ?> Records</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover report-table" id="doctorsReport">
                        <thead>
                            <tr>
                                <th class="border-0">Doctor Name</th>
                                <th class="border-0">Specialization</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Appointments</th>
                                <th class="border-0">Patients</th>
                                <th class="border-0">Contact</th>
                                <th class="border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $result->data_seek(0);
                            while($row = $result->fetch_assoc()): 
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3 bg-primary rounded-circle text-center text-white">
                                            <?php 
                                                $initials = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
                                                echo $initials;
                                            ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold">Dr. <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php echo htmlspecialchars($row['specialization']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $row['status'] == 'Active' ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="fas fa-calendar-check text-success"></i>
                                        </div>
                                        <div><?php echo $row['total_appointments']; ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="fas fa-users text-info"></i>
                                        </div>
                                        <div><?php echo $row['total_patients']; ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <div><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($row['email']); ?></div>
                                            <small class="text-muted"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($row['phone']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../doctors/view_doctor.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../doctors/edit_doctor.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.20/jspdf.plugin.autotable.min.js"></script>

    <script>
        // Initialize date range picker with better styling
        $(function() {
            $('#dateRange').daterangepicker({
                autoUpdateInput: false,
                opens: 'left',
                drops: 'down',
                maxDate: new Date(),
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                },
                ranges: {
                   'Today': [moment(), moment()],
                   'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                   'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                   'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                   'This Month': [moment().startOf('month'), moment().endOf('month')],
                   'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            });

            $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            });

            $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        });

        // Export to Excel with styling
        function exportToExcel() {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(document.getElementById('doctorsReport'));
            
            // Add some styling
            const range = XLSX.utils.decode_range(ws['!ref']);
            for(let C = range.s.c; C <= range.e.c; ++C) {
                const address = XLSX.utils.encode_col(C) + "1";
                if(!ws[address]) continue;
                ws[address].s = {
                    fill: { fgColor: { rgb: "EFEFEF" } },
                    font: { bold: true }
                };
            }
            
            XLSX.utils.book_append_sheet(wb, ws, "Doctors Report");
            XLSX.writeFile(wb, `doctors_report_${moment().format('YYYY-MM-DD')}.xlsx`);
        }

        // Export to PDF with styling
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'pt', 'a4');
            
            doc.setFontSize(18);
            doc.text('Doctors Report', 40, 40);
            
            doc.setFontSize(10);
            doc.text(`Generated on: ${moment().format('YYYY-MM-DD HH:mm:ss')}`, 40, 60);
            
            doc.autoTable({
                html: '#doctorsReport',
                startY: 70,
                theme: 'grid',
                headStyles: {
                    fillColor: [66, 139, 202],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [245, 245, 245]
                },
                margin: { top: 70, right: 40, bottom: 40, left: 40 },
                styles: { 
                    fontSize: 8,
                    cellPadding: 5
                }
            });

            doc.save(`doctors_report_${moment().format('YYYY-MM-DD')}.pdf`);
        }
    </script>
</body>
</html>