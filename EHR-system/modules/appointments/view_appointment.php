<?php include 'EHR system/header.php'; ?>
<?php
include 'db/db_connect.php';
// Update the fetch query:
$query = "SELECT a.*, 
          CONCAT(p.first_name, ' ', p.last_name) as patient_name,
          p.patient_id,
          CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
          d.specialization as doctor_specialization
          FROM appointments a
          LEFT JOIN patients p ON a.patient_id = p.id
          LEFT JOIN doctors d ON a.doctor_id = d.id
          WHERE a.id = ?";

$query = "SELECT appointments.id, patients.first_name AS patient_name, 
                 users.username AS doctor_name, 
                 appointments.appointment_date, appointments.status 
          FROM appointments
          JOIN patients ON appointments.patient_id = patients.id
          JOIN users ON appointments.doctor_id = users.id
          ORDER BY appointments.appointment_date DESC";
$result = $conn->query($query);
?>

<div class="container">
    <h2>Appointments</h2>
    <table>
        <thead>
            <tr>
                <th>Appointment ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        // In the display section, add:
<div class="mb-3">
    <label class="form-label fw-bold">Doctor:</label>
    <div><?php echo htmlspecialchars($row['doctor_name']); ?></div>
    <small class="text-muted">Specialization: <?php echo htmlspecialchars($row['doctor_specialization']); ?></small>
</div>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['patient_name']; ?></td>
                    <td><?php echo $row['doctor_name']; ?></td>
                    <td><?php echo date('d-m-Y H:i', strtotime($row['appointment_date'])); ?></td>
                    <td><?php echo $row['status']; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>
