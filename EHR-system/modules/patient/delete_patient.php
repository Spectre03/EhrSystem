<?php
session_start();
include '../../db/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        
        // First, get patient info for logging
        $stmt = $conn->prepare("SELECT patient_id, first_name, last_name FROM patients WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        
        if (!$patient) {
            throw new Exception("Patient not found");
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Delete related records first (if they exist)
        $tables = ['appointments', 'medical_records', 'prescriptions'];
        foreach ($tables as $table) {
            $stmt = $conn->prepare("DELETE FROM $table WHERE patient_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        
        // Delete the patient
        $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Commit transaction
            $conn->commit();
            
            // Log the deletion
            $log_msg = "Patient deleted: ID {$patient['patient_id']} - {$patient['first_name']} {$patient['last_name']}";
            error_log($log_msg);
            
            echo json_encode([
                'success' => true,
                'message' => 'Patient deleted successfully'
            ]);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->connect_errno != 0) {
            $conn->rollback();
        }
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>