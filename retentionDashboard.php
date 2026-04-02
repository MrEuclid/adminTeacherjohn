<?php 
session_start();
$donorSecretKey = "PIO_Impact_Report_2026_Secure"; 

$isLoggedIn = isset($_SESSION['user_id']); 
$urlKey = isset($_GET['key']) ? $_GET['key'] : '';

if (!$isLoggedIn && $urlKey !== $donorSecretKey) {
    require_once ".authCheckPIO.php"; 
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retention & Overage Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.plot.ly/plotly-2.26.0.min.js"></script>
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header-strip { background: linear-gradient(135deg, #8e44ad 0%, #2980b9 100%); color: white; padding: 20px 0; margin-bottom: 25px; }
        .kpi-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: 100%; text-align: center; }
        .kpi-value { font-size: 2.2rem; font-weight: bold; color: #2c3e50; }
        .kpi-label { font-size: 0.85rem; color: #7f8c8d; text-transform: uppercase; letter-spacing: 1px; }
        .chart-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .chart-title { font-weight: 600; font-size: 1.1rem; color: #34495e; margin: 0; }
        
        #loadingOverlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.95); z-index: 9999;
            display: flex; justify-content: center; align-items: center; flex-direction: column;
        }
        .spinner {
            width: 50px; height: 50px; border: 5px solid #f3f3f3;
            border-top: 5px solid #8e44ad; border-radius: 50%;
            animation: spin 1s linear infinite; margin-bottom: 15px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @media print { .no-print { display: none !important; } .kpi-card, .chart-container { box-shadow: none; border: 1px solid #ddd; } }
    </style>
</head>
<body>

<div id="loadingOverlay">
    <div class="spinner"></div>
    <h5 class="text-primary fw-bold">Calculating Age Profiles...</h5>
    <div id="errorLog" class="text-danger mt-3"></div>
</div>

<div class="header-strip">
    <div class="container d-flex justify-content-between align-items-center">
        <div><h2 class="m-0 fw-light"><i class="fa-solid fa-users-rays me-2"></i>Retention & Inclusion Report</h2></div>
        <div class="d-flex gap-2 no-print align-items-center">
            <select id="yearSelect" class="form-select form-select-sm" style="width: auto;"></select>
            <button class="btn btn-light btn-sm fw-bold ms-2" onclick="window.print()"><i class="fa-solid fa-print"></i></button>
        </div>
    </div>
</div>

<div class="container">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-value" id="kpi-total">-</div>
                <div class="kpi-label">Total Analyzed Enrolment</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-value text-warning" id="kpi-overage-count">-</div>
                <div class="kpi-label">Overage Students (Age > Grade+5)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-value text-success" id="kpi-overage-percent">-</div>
                <div class="kpi-label">System Inclusion Rate (% Overage)</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="chart-container">
                <div class="chart-header"><h5 class="chart-title">Percentage of Overage Students by Grade</h5></div>
                <div id="overagePercentChart" style="height: 400px;"></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-container">
                <div class="chart-header"><h5 class="chart-title">Age Distribution by Grade</h5></div>
                <div id="overageStackChart" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
let globalData = null;

$(document).ready(function() {
    // We will call a new backend file dedicated to these complex calculations
    let fetchUrl = 'retentionData.php';
    let accessKey = "<?php echo htmlspecialchars($urlKey); ?>";
    if(accessKey) fetchUrl += '?key=' + encodeURIComponent(accessKey);

    $.getJSON(fetchUrl, function(data) {
        if(data.error) {
            $('#errorLog').text(data.error);
            return;
        }
        
        globalData = data;
        
        // Populate Year Dropdown based on available data
        let years = Object.keys(data.yearlyData).sort((a,b) => b - a);
        let $yearSel = $('#yearSelect');
        years.forEach(y => $yearSel.append(`<option value="${y}">Academic Year: ${y}</option>`));
        
        if(years.length > 0) {
            $yearSel.val(years[0]);
            updateDashboard();
        }
        
        $('#loadingOverlay').fadeOut(500);
        
    }).fail(function() {
        $('#errorLog').text("Failed to load retention data.");
        $('.spinner').hide();
    });

    $('#yearSelect').on('change', updateDashboard);
});

function updateDashboard() {
    if (!globalData) return;
    
    let selectedYear = $('#yearSelect').val();
    let yearData = globalData.yearlyData[selectedYear];
    
    if(!yearData) return;

    // --- 1. Calculate KPIs ---
    let totalStudents = 0;
    let totalOverage = 0;

    let grades = Object.keys(yearData);
    // Sort grades logically (e.g., G1, G2, G3... instead of alphabetically G1, G10, G2)
    grades.sort((a, b) => parseInt(a.replace(/\D/g, '')) - parseInt(b.replace(/\D/g, '')));

    let overagePercents = [];
    let typicalCounts = [];
    let overageCounts = [];

    grades.forEach(grade => {
        let stats = yearData[grade];
        totalStudents += (stats.typical + stats.overage);
        totalOverage += stats.overage;
        
        let gradeTotal = stats.typical + stats.overage;
        let percent = gradeTotal > 0 ? (stats.overage / gradeTotal) * 100 : 0;
        
        overagePercents.push(percent);
        typicalCounts.push(stats.typical);
        overageCounts.push(stats.overage);
    });

    $('#kpi-total').text(totalStudents);
    $('#kpi-overage-count').text(totalOverage);
    let overallPercent = totalStudents > 0 ? Math.round((totalOverage / totalStudents) * 10) / 10 : 0;
    $('#kpi-overage-percent').text(overallPercent + '%');

    // --- 2. Chart: Percentage of Overage Students ---
    let percentTrace = {
        x: grades,
        y: overagePercents,
        type: 'bar',
        marker: { color: '#8e44ad' },
        text: overagePercents.map(val => val.toFixed(1) + '%'),
        textposition: 'auto',
    };
    Plotly.react('overagePercentChart', [percentTrace], {
        yaxis: { title: '% Overage', range: [0, 100] },
        margin: { l: 50, r: 20, t: 20, b: 40 }
    }, { responsive: true });

    // --- 3. Chart: Stacked Absolutes ---
    let traceTypical = {
        x: grades,
        y: typicalCounts,
        name: 'Typical Age',
        type: 'bar',
        marker: { color: '#3498db' }
    };
    let traceOverage = {
        x: grades,
        y: overageCounts,
        name: 'Overage',
        type: 'bar',
        marker: { color: '#f39c12' }
    };
    Plotly.react('overageStackChart', [traceTypical, traceOverage], {
        barmode: 'stack',
        yaxis: { title: 'Number of Students' },
        legend: { orientation: 'h', y: -0.2 },
        margin: { l: 50, r: 20, t: 20, b: 40 }
    }, { responsive: true });
}
</script>
</body>
</html>