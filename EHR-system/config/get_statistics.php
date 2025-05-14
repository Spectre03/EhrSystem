<?php
include 'db/db_connect.php';
function getSystemStats() {
    global $conn;
    $stats = [
        'patients' => 0,
        'appointments' => 0,
        'doctors' => 0,
        'total_appointments' => 0
    ];
    
    try {
        // Get patient count
        $patientQuery = "SELECT COUNT(*) as count FROM patients";
        $result = mysqli_query($conn, $patientQuery);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['patients'] = $row['count'];
        }

        // Get doctor count
        $doctorQuery = "SELECT COUNT(*) as count FROM doctors";
        $result = mysqli_query($conn, $doctorQuery);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['doctors'] = $row['count'];
        }

        // Get today's appointments
        $todayQuery = "SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()";
        $result = mysqli_query($conn, $todayQuery);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['appointments'] = $row['count'];
        }

        // Get total appointments
        $totalQuery = "SELECT COUNT(*) as count FROM appointments";
        $result = mysqli_query($conn, $totalQuery);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['total_appointments'] = $row['count'];
        }

    } catch(Exception $e) {
        // Log error if needed
        error_log("Error in getSystemStats: " . $e->getMessage());
    }
    
    return $stats;
}

// Initialize the stats
$stats = getSystemStats();
?>