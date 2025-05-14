<?php
session_start();
include '../../db/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? $data['id'] : 0;
        
        // Get appointment info for logging
        $stmt = $conn->prepare("SELECT appointment_date, appointment_time, patient_id FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $appointment = $stmt->get_result()->fetch_assoc();
        
        if (!$appointment) {
            throw new Exception("Appointment not found");
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Delete the appointment
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Appointment deleted successfully'
            ]);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
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