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
    <title>Retention & Inclusion Dashboard</title>
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
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; flex-wrap: wrap; gap: 10px; }
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
    <h5 class="text-primary fw-bold">Analyzing Student Data...</h5>
    <div id="errorLog" class="text-danger mt-3"></div>
</div>
 
<div class="header-strip">
    <div class="container d-flex justify-content-between align-items-center">

      <button class="btn btn-warning shadow-sm px-6 font-bold" id="back" onclick="history.back()">
            GO BACK
        </button>
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
                <div class="kpi-label">Overage Students (Age > Grade+6)</div>
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
                <div id="overagePercentChart" style="height: 350px;"></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-container">
                <div class="chart-header"><h5 class="chart-title">Age Distribution by Grade</h5></div>
                <div id="overageStackChart" style="height: 350px;"></div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12">
            <div class="chart-container border-top border-4 border-info">
                <div class="chart-header">
                    <h5 class="chart-title"><i class="fa-solid fa-chart-line text-info me-2"></i>Longitudinal Cohort Retention</h5>
                    
                    <div class="d-flex align-items-center no-print flex-wrap gap-3">
                        <div class="btn-group btn-group-sm bg-light p-1 rounded border" role="group">
                            <input type="radio" class="btn-check" name="genderSplit" id="btnTotal" value="total" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary border-0" for="btnTotal">Total Cohort</label>

                            <input type="radio" class="btn-check" name="genderSplit" id="btnGender" value="split" autocomplete="off">
                            <label class="btn btn-outline-secondary border-0" for="btnGender"><i class="fa-solid fa-venus-mars me-1"></i>Split by Gender</label>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <label class="small fw-bold text-muted mb-0">Entry Year:</label>
                            <select id="cohortYearSelect" class="form-select form-select-sm" style="width: 120px;"></select>
                            
                            <label class="small fw-bold text-muted mb-0 ms-2">Entry Grade:</label>
                            <select id="cohortGradeSelect" class="form-select form-select-sm" style="width: 120px;"></select>
                        </div>
                    </div>
                </div>
                <div id="cohortChart" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
let globalData = null;

$(document).ready(function() {
    let fetchUrl = 'retentionData.php';
    let accessKey = "<?php echo htmlspecialchars($urlKey); ?>";
    if(accessKey) fetchUrl += '?key=' + encodeURIComponent(accessKey);

    $.getJSON(fetchUrl, function(data) {
        if(data.error) {
            $('#errorLog').text(data.error);
            return;
        }
        
        globalData = data;
        
        // Populate Dropdowns
        let years = Object.keys(data.yearlyData).sort((a,b) => b - a);
        years.forEach(y => $('#yearSelect').append(`<option value="${y}">Academic Year: ${y}</option>`));
        
        let cohortYears = Object.keys(data.cohorts).sort((a,b) => b - a);
        cohortYears.forEach(y => $('#cohortYearSelect').append(`<option value="${y}">Started ${y}</option>`));

        if(years.length > 0) {
            $('#yearSelect').val(years[0]);
            updateOverageDashboard();
        }

        if(cohortYears.length > 0) {
            $('#cohortYearSelect').val(cohortYears[0]);
            updateCohortGrades(); 
        }
        
        $('#loadingOverlay').fadeOut(500);
        
    }).fail(function() {
        $('#errorLog').text("Failed to load retention data.");
        $('.spinner').hide();
    });

    $('#yearSelect').on('change', updateOverageDashboard);
    $('#cohortYearSelect').on('change', updateCohortGrades);
    $('#cohortGradeSelect').on('change', updateCohortDashboard);
    $('input[name="genderSplit"]').on('change', updateCohortDashboard); // Re-draw when toggled
});

function updateCohortGrades() {
    if (!globalData) return;
    let baseYear = $('#cohortYearSelect').val();
    let gradesData = globalData.cohorts[baseYear];
    
    let $gradeSel = $('#cohortGradeSelect');
    $gradeSel.empty();

    if (gradesData) {
        let availableGrades = Object.keys(gradesData).sort((a, b) => parseInt(a) - parseInt(b));
        availableGrades.forEach(g => {
            $gradeSel.append(`<option value="${g}">Grade ${g}</option>`);
        });
        
        if(availableGrades.includes("1")) {
            $gradeSel.val("1");
        } else if (availableGrades.length > 0) {
            $gradeSel.val(availableGrades[0]);
        }
    }
    updateCohortDashboard();
}

function updateOverageDashboard() {
    // [Overage Chart logic remains the same]
    if (!globalData) return;
    let selectedYear = $('#yearSelect').val();
    let yearData = globalData.yearlyData[selectedYear];
    if(!yearData) return;

    let totalStudents = 0, totalOverage = 0;
    let grades = Object.keys(yearData);
    grades.sort((a, b) => parseInt(a.replace(/\D/g, '')) - parseInt(b.replace(/\D/g, '')));

    let overagePercents = [], typicalCounts = [], overageCounts = [];

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
    let overallPercent = totalStudents > 0 ? ((totalOverage / totalStudents) * 100).toFixed(1) : 0;
    $('#kpi-overage-percent').text(overallPercent + '%');

    Plotly.react('overagePercentChart', [{
        x: grades, y: overagePercents, type: 'bar', marker: { color: '#8e44ad' },
        text: overagePercents.map(val => val.toFixed(1) + '%'), textposition: 'auto',
    }], { yaxis: { title: '% Overage', range: [0, 100] }, margin: { l: 50, r: 20, t: 20, b: 40 } }, { responsive: true });

    Plotly.react('overageStackChart', [
        { x: grades, y: typicalCounts, name: 'Typical Age', type: 'bar', marker: { color: '#3498db' } },
        { x: grades, y: overageCounts, name: 'Overage', type: 'bar', marker: { color: '#f39c12' } }
    ], { barmode: 'stack', yaxis: { title: 'Students' }, legend: { orientation: 'h', y: -0.2 }, margin: { l: 50, r: 20, t: 20, b: 40 } }, { responsive: true });
}

function updateCohortDashboard() {
    if (!globalData) return;
    let baseYear = $('#cohortYearSelect').val();
    let baseGrade = $('#cohortGradeSelect').val();
    let viewMode = $('input[name="genderSplit"]:checked').val(); // 'total' or 'split'
    
    if(!globalData.cohorts[baseYear] || !globalData.cohorts[baseYear][baseGrade]) {
        Plotly.purge('cohortChart');
        return;
    }

    let cohortData = globalData.cohorts[baseYear][baseGrade];
    let progressionYears = Object.keys(cohortData).sort();
    
    let traces = [];

    // Helper function to build a trace
    function buildTrace(dataKey, name, color, isFill) {
        let counts = [];
        let pcts = [];
        let hoverTexts = [];

        progressionYears.forEach(year => {
            let stats = cohortData[year][dataKey];
            counts.push(stats.count);
            pcts.push(stats.pct);
            
            let yearsElapsed = year - baseYear;
            let currentExpectedGrade = parseInt(baseGrade) + yearsElapsed;
            let label = yearsElapsed === 0 ? `Started G${baseGrade}` : `+${yearsElapsed} Yr (Expected G${currentExpectedGrade})`;
            
            hoverTexts.push(`<b>${year}</b> (${label})<br>${name}: ${stats.count} students<br>${stats.pct}% retained`);
        });

        return {
            x: progressionYears,
            y: counts,
            type: 'scatter',
            mode: 'lines+markers+text',
            name: name,
            line: { color: color, width: 4, shape: 'spline' },
            marker: { size: 10, color: color, line: { color: 'white', width: 2 } },
            text: pcts.map(p => p + '%'),
            textposition: 'top center',
            textfont: { weight: 'bold', size: 12, color: color },
            hoverinfo: 'text',
            hovertext: hoverTexts,
            fill: isFill ? 'tozeroy' : 'none',
            fillcolor: isFill ? 'rgba(13, 202, 240, 0.1)' : 'transparent'
        };
    }

    if (viewMode === 'total') {
        // Draw the single total line
        traces.push(buildTrace('total', 'Total Students', '#0dcaf0', true));
    } else {
        // Draw two lines (matching the sponsorDashboard pink/blue theme)
        traces.push(buildTrace('F', 'Girls', '#e91e63', false));
        traces.push(buildTrace('M', 'Boys', '#2980b9', false));
    }

    Plotly.react('cohortChart', traces, {
        xaxis: { title: 'Academic Year', tickmode: 'array', tickvals: progressionYears },
        yaxis: { title: 'Number of Students Enrolled', rangemode: 'tozero' },
        margin: { l: 60, r: 40, t: 20, b: 50 },
        hovermode: 'closest',
        legend: { orientation: 'h', y: 1.1, x: 0.5, xanchor: 'center' }
    }, { responsive: true });
}
</script>
</body>
</html>