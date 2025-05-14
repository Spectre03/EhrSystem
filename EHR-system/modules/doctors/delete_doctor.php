<?php
session_start();
include '../../db/db_connect.php';

// Check if it's a POST request with JSON data
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['id'])) {
    $doctor_id = (int)$data['id'];
    
    // First check if the doctor exists
    $check_query = "SELECT id FROM doctors WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $doctor_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Doctor not found!'
        ]);
        exit();
    }
    
    // Delete the doctor
    $delete_query = "DELETE FROM doctors WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $doctor_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Doctor deleted successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting doctor: ' . $conn->error
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request!'
    ]);
}
?>