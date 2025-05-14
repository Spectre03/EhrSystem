<?php
session_start();
include '../../db/db_connect.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="medical_records_export.csv"');

// Create file pointer connected to PHP output
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel display
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, ['Date', 'Patient', 'Doctor', 'Diagnosis', 'Treatment', 'Follow-up Date', 'Status', 'Notes']);

// Fetch records
$stmt = $conn->prepare("
    SELECT 
        mr.created_at,
        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
        CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
        mr.diagnosis,
        mr.treatment,
        mr.follow_up_date,
        mr.status,
        mr.notes
    FROM medical_records mr
    JOIN patients p ON mr.patient_id = p.id
    JOIN doctors d ON mr.doctor_id = d.id
    ORDER BY mr.created_at DESC
");

$stmt->execute();
$result = $stmt->get_result();

// Output each row of the data
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}