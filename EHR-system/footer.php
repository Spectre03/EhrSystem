<footer class="footer mt-auto py-3 bg-light">
    <div class="container">
        <div class="row">
            <!-- Quick Links -->
            <div class="col-md-4 mb-3">
                <h5 class="text-primary mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="/EHR-system/modules/patient/patient.php" class="text-decoration-none text-secondary">
                            <i class="fas fa-users me-2"></i>Patients
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/EHR-system/modules/appointments/appointment.php" class="text-decoration-none text-secondary">
                            <i class="fas fa-calendar-alt me-2"></i>Appointments
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/EHR-system/modules/medical_records/medical_records.php" class="text-decoration-none text-secondary">
                            <i class="fas fa-file-medical me-2"></i>Medical Records
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-md-4 mb-3">
                <h5 class="text-primary mb-3">Contact Info</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-phone me-2 text-secondary"></i>
                        <span class="text-secondary">+1 234 567 8900</span>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-envelope me-2 text-secondary"></i>
                        <span class="text-secondary">support@ehrsystem.com</span>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-map-marker-alt me-2 text-secondary"></i>
                        <span class="text-secondary">123 Healthcare St, Medical City</span>
                    </li>
                </ul>
            </div>

            <!-- Working Hours -->
            <div class="col-md-4 mb-3">
                <h5 class="text-primary mb-3">Working Hours</h5>
                <ul class="list-unstyled">
                    <li class="mb-2 text-secondary">
                        <i class="fas fa-clock me-2"></i>
                        Monday - Friday: 8:00 AM - 8:00 PM
                    </li>
                    <li class="mb-2 text-secondary">
                        <i class="fas fa-clock me-2"></i>
                        Saturday: 9:00 AM - 5:00 PM
                    </li>
                    <li class="mb-2 text-secondary">
                        <i class="fas fa-clock me-2"></i>
                        Sunday: Emergency Only
                    </li>
                </ul>
            </div>
        </div>

        <hr class="my-4">

        <!-- Bottom Footer -->
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <span class="text-secondary">
                    &copy; <?php echo date('Y'); ?> EHR System. All rights reserved.
                </span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <a href="#" class="text-secondary text-decoration-none me-3">Privacy Policy</a>
                <a href="#" class="text-secondary text-decoration-none me-3">Terms of Service</a>
                <a href="#" class="text-secondary text-decoration-none">Contact Us</a>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer {
        border-top: 1px solid rgba(0,0,0,0.1);
    }
    
    .footer h5 {
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .footer a:hover {
        color: var(--bs-primary) !important;
    }
    
    .footer i {
        width: 20px;
        text-align: center;
    }
    
    .footer ul li {
        transition: all 0.3s ease;
    }
    
    .footer ul li:hover {
        transform: translateX(5px);
    }
    
    @media (max-width: 768px) {
        .footer {
            text-align: center;
        }
        
        .footer ul li:hover {
            transform: none;
        }
    }
</style>