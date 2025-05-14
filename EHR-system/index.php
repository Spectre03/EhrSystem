<?php
require_once 'config/get_statistics.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EHR System - Electronic Health Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/EHR-system/assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container-fluid py-5">
        <!-- Hero Section -->
        <div class="row mb-5 justify-content-center text-center">
            <div class="col-md-8">
                <h1 class="display-4 mb-3">Electronic Health Record System</h1>
                <p class="lead">Streamline your healthcare practice with our comprehensive EHR solution</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Quick Actions</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="modules/patient/add_patient.php" class="btn btn-primary w-100">
                                    <i class="fas fa-user-plus me-2"></i>New Patient
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="modules/appointments/add_appointment.php" class="btn btn-success w-100">
                                    <i class="fas fa-calendar-plus me-2"></i>Schedule Appointment
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="modules/patient/patient.php" class="btn btn-info w-100">
                                    <i class="fas fa-search me-2"></i>Find Patient
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="modules/billing/add_bill.php" class="btn btn-warning w-100">
                                    <i class="fas fa-file-invoice-dollar me-2"></i>Create Invoice
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- Main Modules Section -->
<div class="row g-4">
    <!-- Doctor Management -->
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-user-md fa-3x text-primary mb-3"></i>
                <h3 class="card-title h5">Doctor Management</h3>
                <p class="card-text">Manage doctors and schedules</p>
                <a href="modules/doctors/doctor.php" class="btn btn-outline-primary">Access Module</a>
            </div>
        </div>
    </div>
    
    <!-- Patient Management -->
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-success mb-3"></i>
                <h3 class="card-title h5">Patient Management</h3>
                <p class="card-text">Manage patient records</p>
                <a href="modules/patient/patient.php" class="btn btn-outline-success">Access Module</a>
            </div>
        </div>
    </div>
    
    <!-- Appointments -->
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-3x text-info mb-3"></i>
                <h3 class="card-title h5">Appointments</h3>
                <p class="card-text">Schedule appointments</p>
                <a href="modules/appointments/appointment.php" class="btn btn-outline-info">Access Module</a>
            </div>
        </div>
    </div>
    
    <!-- Billing -->
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-file-invoice-dollar fa-3x text-danger mb-3"></i>
                <h3 class="card-title h5">Billing</h3>
                <p class="card-text">Manage billing and invoices</p>
                <a href="modules/billing/billing.php" class="btn btn-outline-danger">Access Module</a>
            </div>
        </div>
    </div>

    <!-- Medical Records -->
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-file-medical fa-3x text-warning mb-3"></i>
                <h3 class="card-title h5">Medical Records</h3>
                <p class="card-text">Manage medical records</p>
                <a href="modules/medical_records/medical_records.php" class="btn btn-outline-warning">Access Module</a>
            </div>
        </div>
    </div>
</div>
    <!-- Statistics Section -->
<div class="row mt-5">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="fas fa-chart-line me-2"></i>
                    System Overview
                </h5>
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <div class="p-4 bg-primary bg-opacity-10 rounded">
                            <i class="fas fa-users fa-2x text-primary mb-3"></i>
                            <h3 class="text-primary"><?php echo number_format($stats['patients']); ?></h3>
                            <p class="text-muted mb-0">Registered Patients</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="p-4 bg-success bg-opacity-10 rounded">
                            <i class="fas fa-calendar-check fa-2x text-success mb-3"></i>
                            <h3 class="text-success"><?php echo number_format($stats['appointments']); ?></h3>
                            <p class="text-muted mb-0">Today's Appointments</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="p-4 bg-info bg-opacity-10 rounded">
                            <i class="fas fa-user-md fa-2x text-info mb-3"></i>
                            <h3 class="text-info"><?php echo number_format($stats['doctors']); ?></h3>
                            <p class="text-muted mb-0">Active Doctors</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="p-4 bg-warning bg-opacity-10 rounded">
                            <i class="fas fa-calendar-alt fa-2x text-warning mb-3"></i>
                            <h3 class="text-warning"><?php echo number_format($stats['total_appointments']); ?></h3>
                            <p class="text-muted mb-0">Total Appointments</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>