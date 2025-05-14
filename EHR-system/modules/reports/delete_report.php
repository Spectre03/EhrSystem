<?php
session_start();
include '../../db/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: view_reports.php");
    exit();
}

$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;

if (!$report_id) {
    $_SESSION['error'] = "Invalid report ID";
    header("Location: view_reports.php");
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if report exists
    $report = $conn->query("SELECT * FROM saved_reports WHERE report_id = $report_id")->fetch_assoc();
    
    if (!$report) {
        throw new Exception("Report not found");
    }

    // Delete report
    $stmt = $conn->prepare("DELETE FROM saved_reports WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete report");
    }

    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Report deleted successfully";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header("Location: view_reports.php");
exit();