<?php
session_start();
include '../../db/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? $data['id'] : 0;
        
        // Get bill info for logging
        $stmt = $conn->prepare("SELECT bill_number FROM bills WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $bill = $stmt->get_result()->fetch_assoc();
        
        if (!$bill) {
            throw new Exception("Bill not found");
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Delete bill items first (foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM bill_items WHERE bill_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Delete the bill
        $stmt = $conn->prepare("DELETE FROM bills WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Bill deleted successfully'
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