<?php
// FILE: analyst/enrollment.php
// PURPOSE: Director's Operational Overview (Fast & Clean)

require_once "../authCheckPIO.php"; 
restrictToAdmin();
include "../connectDatabase.php";
include "../yearMonth.php"; 

if (!isset($schoolYear)) { $schoolYear = date("Y"); }

// --- 1. ENROLLMENT COUNT (Fast Query) ---
$enrollmentCount = 0;
// We count all students active in the current year
$qEnroll = "SELECT COUNT(*) as total FROM New_ID_Year_Grade WHERE Year = '$schoolYear'";
$rEnroll = mysqli_query($dbServer, $qEnroll);
if ($row = mysqli_fetch_assoc($rEnroll)) {
    $enrollmentCount = $row['total'];
}

// --- 2. ATTENDANCE (Placeholder for your Logic) ---
// Since I don't have your specific attendance tables, 
// I have left this as static data or placeholders.
// You can paste your specific attendance PHP query here.
$attendancePct = "--"; 
$absentCount = "--"; 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director's Overview</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        .card { border: none; transition: transform 0.2s; }
        .hover-shadow:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
        .border-left-info { border-left: 5px solid #0dcaf0; }
        .border-left-success { border-left: 5px solid #198754; }
    </style>
</head>
<body class="bg-light">

    <?php include "header.php"; ?>

    <div class="container py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-secondary fw-bold">School Operations</h2>
            <span class="badge bg-white text-dark border p-2 shadow-sm">
                Academic Year: <?php echo $schoolYear; ?>
            </span>
        </div>

        <div class="row mb-5">
            <div class="col-12"><h6 class="text-muted border-bottom pb-2 mb-3">Daily Monitoring</h6></div>

            <div class="col-md-6">
                <div class="card shadow-sm border-left-info h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-info text-uppercase small fw-bold">Total Enrollment</h6>
                                <h2 class="mb-0 fw-bold display-6"><?php echo $enrollmentCount; ?></h2>
                                <small class="text-muted">Active Students</small>
                            </div>
                            <div class="text-info opacity-25">
                                <i class="bi bi-people-fill" style="font-size: 3rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-left-success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-success text-uppercase small fw-bold">Attendance</h6>
                                <h2 class="mb-0 fw-bold display-6"><?php echo $attendancePct; ?>%</h2>
                                <small class="text-danger fw-bold"><?php echo $absentCount; ?> Absent Today</small>
                            </div>
                            <div class="text-success opacity-25">
                                <i class="bi bi-calendar-check" style="font-size: 3rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12"><h6 class="text-muted border-bottom pb-2 mb-3">Academic Achievement</h6></div>

            <div class="col-md-6">
                <div class="card shadow-sm hover-shadow h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                            <i class="bi bi-backpack text-primary h2 mb-0"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Primary School</h5>
                            <p class="text-muted small mb-3">View Raw Marks & Class Averages (10-point scale)</p>
                            <a href="malydaPS.php" class="btn btn-outline-primary btn-sm stretched-link">
                                Open Dashboard <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm hover-shadow h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                            <i class="bi bi-mortarboard text-warning h2 mb-0"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">High School</h5>
                            <p class="text-muted small mb-3">View Standardized Scores (Target Mean 65, SD 10)</p>
                            <a href="malydaHS.php" class="btn btn-outline-warning text-dark btn-sm stretched-link">
                                Open Dashboard <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>