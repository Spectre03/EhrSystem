
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar {
            padding: 0.8rem 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
        }
        .nav-link {
            padding: 0.5rem 1rem !important;
            color: rgba(255,255,255,0.9) !important;
        }
        .nav-link:hover {
            color: #ffffff !important;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }
        .nav-link i {
            margin-right: 0.5rem;
            width: 1.2rem;
            text-align: center;
        }
        .dropdown-item {
            padding: 0.7rem 1rem;
        }
        .dropdown-item i {
            margin-right: 0.5rem;
            width: 1.2rem;
            text-align: center;
        }
        .alert {
            margin: 1rem;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand" href="/EHR-system/index.php">
                <i class="fas fa-hospital"></i> EHR System
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Main Navigation -->
<ul class="navbar-nav me-auto mb-2 mb-lg-0">
    <li class="nav-item">
        <a class="nav-link" href="/EHR-system/index.php">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="/EHR-system/modules/patient/patient.php">
            <i class="fas fa-user"></i>
            <span>Patients</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="/EHR-system/modules/doctors/doctor.php">
            <i class="fas fa-user-md"></i>
            <span>Doctors</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="/EHR-system/modules/appointments/appointment.php">
            <i class="fas fa-calendar-check"></i>
            <span>Appointments</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="/EHR-system/modules/Billing/billing.php">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>Billing</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="/EHR-system/modules/medical_records/medical_records.php">
            <i class="fas fa-file-medical"></i>
            <span>Medical Records</span>
        </a>
    </li>
</ul>
                <!-- Admin/User Menu -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i>
                            <span>Admin</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="/EHR-system/modules/admin/profile.php">
                                    <i class="fas fa-user-edit"></i>
                                    <span>Profile</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/EHR-system/modules/admin/settings.php">
                                    <i class="fas fa-cog"></i>
                                    <span>Settings</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="/EHR-system/logout.php">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Bootstrap and other scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Highlight active navigation item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                if (currentPath.includes(link.getAttribute('href'))) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>