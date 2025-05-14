<?php
session_start();
include '../../db/db_connect.php';

// Check if record ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Record ID not provided";
    header("Location: medical_records.php");
    exit();
}

$record_id = $_GET['id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // First get the patient_id for redirection
    $stmt = $conn->prepare("SELECT patient_id FROM medical_records WHERE id = ?");
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();

    if (!$record) {
        throw new Exception("Medical record not found");
    }

    $patient_id = $record['patient_id'];

    // Delete the medical record
    $stmt = $conn->prepare("DELETE FROM medical_records WHERE id = ?");
    $stmt->bind_param("i", $record_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = "Medical record deleted successfully";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

// Redirect back to the medical records page
header("Location: medical_records.php" . ($patient_id ? "?patient_id=" . $patient_id : ""));
exit();