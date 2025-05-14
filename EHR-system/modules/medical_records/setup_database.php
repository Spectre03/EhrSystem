<?php
include '../../db/db_connect.php';

try {
    // Create or modify medical_records table
    $sql = "CREATE TABLE IF NOT EXISTS medical_records (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        diagnosis TEXT NOT NULL,
        treatment TEXT NOT NULL,
        follow_up_date DATE,
        status VARCHAR(20) DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    )";

    if ($conn->query($sql)) {
        echo "Medical records table created/updated successfully!<br>";
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>