<?php
session_start();
include '../../db/db_connect.php';

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Get the first day of the month
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDay);
$dateComponents = getdate($firstDay);
$monthName = $dateComponents['month'];
$dayOfWeek = $dateComponents['wday'];

// Get appointments for the current month
$startDate = date('Y-m-01', $firstDay);
$endDate = date('Y-m-t', $firstDay);

$query = "SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
          FROM appointments a
          LEFT JOIN patients p ON a.patient_id = p.id
          WHERE appointment_date BETWEEN ? AND ?
          ORDER BY appointment_date, appointment_time";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

// Create appointments array indexed by date
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['appointment_date'];
    if (!isset($appointments[$date])) {
        $appointments[$date] = [];
    }
    $appointments[$date][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar View - EHR System</title>
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
                <li class="breadcrumb-item">
                    <a href="appointment.php" class="text-decoration-none">
                        <i class="fas fa-calendar"></i> Appointments
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-calendar-alt"></i> Calendar View
                </li>
            </ol>
        </nav>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-alt me-2"></i><?php echo $monthName . " " . $year; ?>
                </h5>
                <div class="btn-group">
                    <?php
                    $prevMonth = $month - 1;
                    $prevYear = $year;
                    if ($prevMonth == 0) {
                        $prevMonth = 12;
                        $prevYear--;
                    }
                    
                    $nextMonth = $month + 1;
                    $nextYear = $year;
                    if ($nextMonth == 13) {
                        $nextMonth = 1;
                        $nextYear++;
                    }
                    ?>
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" 
                       class="btn btn-light">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" 
                       class="btn btn-light">Today</a>
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" 
                       class="btn btn-light">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered calendar-table">
                        <thead>
                            <tr>
                                <th>Sunday</th>
                                <th>Monday</th>
                                <th>Tuesday</th>
                                <th>Wednesday</th>
                                <th>Thursday</th>
                                <th>Friday</th>
                                <th>Saturday</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                // Add blank cells for days before start of month
                                for ($i = 0; $i < $dayOfWeek; $i++) {
                                    echo "<td class='calendar-day empty'></td>";
                                }

                                // Add cells for days of month
                                $currentDay = 1;
                                $column = $dayOfWeek;

                                while ($currentDay <= $numberDays) {
                                    if ($column == 7) {
                                        $column = 0;
                                        echo "</tr><tr>";
                                    }

                                    $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                                    $isToday = ($currentDate == date('Y-m-d'));
                                    $hasAppointments = isset($appointments[$currentDate]);

                                    echo "<td class='calendar-day" . 
                                         ($isToday ? ' today' : '') . 
                                         ($hasAppointments ? ' has-appointments' : '') . "'>";
                                    
                                    // Day number
                                    echo "<div class='day-number'>" . $currentDay . "</div>";
                                    
                                    // Appointments for this day
                                    if ($hasAppointments) {
                                        echo "<div class='appointment-list'>";
                                        foreach ($appointments[$currentDate] as $apt) {
                                            $time = date('h:i A', strtotime($apt['appointment_time']));
                                            echo "<div class='appointment-item' data-bs-toggle='tooltip' 
                                                      title='{$apt['patient_name']} - {$apt['purpose']}'>";
                                            echo "<span class='time'>{$time}</span> ";
                                            echo "<span class='patient'>{$apt['patient_name']}</span>";
                                            echo "</div>";
                                        }
                                        echo "</div>";
                                    }
                                    
                                    echo "</td>";
                                    
                                    $currentDay++;
                                    $column++;
                                }

                                // Add blank cells for days after end of month
                                while ($column < 7) {
                                    echo "<td class='calendar-day empty'></td>";
                                    $column++;
                                }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <a href="add_appointment.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>New Appointment
                            </a>
                            <a href="appointment.php" class="btn btn-info">
                                <i class="fas fa-list me-2"></i>List View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Add click event to appointment items
        document.querySelectorAll('.appointment-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                // You can add functionality to view appointment details here
            });
        });
    </script>
</body>
</html>