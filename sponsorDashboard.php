<?php 
// sponsorDashboard.php - Final Complete Version
session_start();
// MUST MATCH the key in sponsorData.php
$donorSecretKey = "PIO_Impact_Report_2026_Secure"; 

$isLoggedIn = isset($_SESSION['user_id']); 
$urlKey = isset($_GET['key']) ? $_GET['key'] : '';

// AUTHENTICATION CHECK
if (!$isLoggedIn && $urlKey !== $donorSecretKey) {
    require_once ".authCheckPIO.php"; 
    exit();
}

include "connectDatabase.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsor Impact Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.plot.ly/plotly-2.26.0.min.js"></script>
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header-strip { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); color: white; padding: 20px 0; margin-bottom: 25px; }
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
            border-top: 5px solid #3498db; border-radius: 50%;
            animation: spin 1s linear infinite; margin-bottom: 15px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        #errorLog { color: red; margin-top: 20px; text-align: center; max-width: 80%; }
        @media print { .no-print { display: none !important; } .kpi-card, .chart-container { box-shadow: none; border: 1px solid #ddd; } }
    </style>
</head>
<body>

<div id="loadingOverlay">
    <div class="spinner"></div>
    <h5 class="text-primary fw-bold">Loading Data...</h5>
    <div id="errorLog"></div>
</div>

<div class="header-strip">
    <div class="container d-flex justify-content-between align-items-center">
        <div><h2 class="m-0 fw-light"><i class="fa-solid fa-chart-line me-2"></i>PIO Impact Report</h2></div>
    <div class="d-flex gap-2 no-print align-items-center">

<a href = "https://docs.google.com/document/d/1j0r4cZDhTPTmTh_3nRQx1f0OwZZVlwha3dbMe8fKbbA/edit?usp=sharing" target = "_blank">
  <button class = "btn btn-primary btn-sm "  id = "activities">PIO activities</button></a>

  <a href = "https://www.facebook.com/pio.cambodia">
     <button class = "btn btn-primary btn-sm" id = "facebook">Facebook</button></a>
      
        </div>
        <div class="d-flex gap-2 no-print align-items-center">
            <select id="schoolFilter" class="form-select form-select-sm" style="width: auto;">
                <option value="All">All Schools</option>
            </select>
            <select id="startYearSelect" class="form-select form-select-sm" style="width: auto;"></select>
            <button class="btn btn-light btn-sm fw-bold ms-2" onclick="window.print()"><i class="fa-solid fa-print"></i></button>
        </div>
    </div>
</div>

<div class="container">
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="kpi-card"><div class="kpi-value" id="kpi-total">-</div><div class="kpi-label">Current Enrolment</div></div></div>
        <div class="col-md-4"><div class="kpi-card"><div class="kpi-value text-danger" id="kpi-gender">-</div><div class="kpi-label">Female %</div></div></div>
        <div class="col-md-4"><div class="kpi-card"><div class="kpi-value text-success" id="kpi-attend">-</div><div class="kpi-label">Avg Attendance</div></div></div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="chart-container">
                <div class="chart-header"><h5 class="chart-title">Enrollments</h5></div>
                <div id="enrollmentChart" style="height: 400px;"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-container">
                <div class="chart-header"><h5 class="chart-title">Current Gender Split</h5></div>
                <div id="genderDonut" style="height: 400px;"></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="chart-container">
                <div class="chart-header">
                    <h5 class="chart-title">Age Profile</h5>
                    <select id="ageYearSelect" class="form-select form-select-sm border-primary" style="width: auto; background-color: #f0f8ff;"></select>
                </div>
                <div id="ageChart" style="height: 350px;"></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <div class="chart-header"><h5 class="chart-title">Retention Trend (Survival Rate)</h5></div>
                <div id="retentionChart" style="height: 300px;"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <div class="chart-header"><h5 class="chart-title">Attendance History (By Gender)</h5></div>
                <div id="attendanceChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
let globalData = null;
const currentYear = new Date().getFullYear();
let availableSchools = []; 
const accessKey = "<?php echo htmlspecialchars($urlKey); ?>";

$(document).ready(function() {
    let fetchUrl = 'sponsorData.php';
    if(accessKey) fetchUrl += '?key=' + encodeURIComponent(accessKey);

    $.getJSON(fetchUrl, function(data) {
        try {
            if(!data || !data.enrollment) throw new Error("Invalid Data received");
            if(data.error) throw new Error(data.error); 
            
            globalData = data;
            
            // Dynamic Schools
            availableSchools = data.schools_found || ['SMC', 'PIOHS'];
            let $schFilter = $('#schoolFilter');
            $schFilter.empty().append('<option value="All">All Schools</option>');
            availableSchools.forEach(s => $schFilter.append(`<option value="${s}">${s}</option>`));

            initFilters(data);
            updateDashboard();
            $('#loadingOverlay').fadeOut(500);
            
        } catch (e) {
            console.error(e);
            $('#errorLog').html("<b>Error:</b> " + e.message);
            $('.spinner').hide();
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        $('#errorLog').html("<b>Connection Error:</b> " + textStatus + " " + errorThrown);
        $('.spinner').hide();
    });

    $('#schoolFilter, #startYearSelect, #ageYearSelect').on('change', updateDashboard);
});

function initFilters(data) {
    let minDataYear = data.min_year || 2015;
    let maxYear = data.max_year || currentYear;
    
    let $startSel = $('#startYearSelect');
    $startSel.empty();
    for (let y = maxYear; y >= minDataYear; y--) { $startSel.append(`<option value="${y}">Start: ${y}</option>`); }
    $startSel.val(Math.max(minDataYear, currentYear - 5));

    let $ageSel = $('#ageYearSelect');
    $ageSel.empty();
    let sortedYears = Object.keys(data.enrollment || {}).map(Number).sort((a,b)=>b-a);
    if(sortedYears.length === 0) sortedYears = [currentYear];
    sortedYears.forEach(y => $ageSel.append(`<option value="${y}">Year: ${y}</option>`));
    $ageSel.val(sortedYears[0]);
}

function updateDashboard() {
    if (!globalData) return;
    try {
        let filterSchool = $('#schoolFilter').val();
        let startYear = parseInt($('#startYearSelect').val()) || (currentYear - 5);
        let ageYear = parseInt($('#ageYearSelect').val()) || currentYear;
        const isSchoolVisible = (sch) => (filterSchool === 'All' || filterSchool === sch);

        // 1. ENROLLMENT
        let years = Object.keys(globalData.enrollment).map(Number).filter(y => y >= startYear).sort((a,b)=>a-b);
        let yearLabels = years.map(String);
        let traces = [];
        let schoolColors = {'SMC': '#3498db', 'PIOHS': '#e67e22', 'BK': '#9b59b6', 'HS': '#2ecc71', 'BSPH2': '#e74c3c'};
        let genders = ['Girls', 'Boys', 'Unknown'];

        availableSchools.forEach(sch => {
            if (!isSchoolVisible(sch)) return;
            genders.forEach(gen => {
                let yVals = years.map(y => (globalData.enrollment[y] && globalData.enrollment[y][sch]) ? (globalData.enrollment[y][sch][gen] || 0) : 0);
                if (yVals.some(v => v > 0)) {
                    let baseC = schoolColors[sch] || '#95a5a6';
                    let color = (gen === 'Girls') ? lighten(baseC) : baseC;
                    if (gen === 'Unknown') color = '#bdc3c7';
                    traces.push({ x: yearLabels, y: yVals, name: `${sch} ${gen}`, type: 'bar', marker: { color: color } });
                }
            });
        });
        Plotly.react('enrollmentChart', traces, {barmode: 'stack', xaxis: { type: 'category' }, legend: { orientation: 'h', y: -0.1 }, margin: {l:40, r:10, t:10, b:40}}, {responsive: true});

        // 2. AGE
        let ageKeys = ['4-5', '6-7', '8-9', '10-11', '12-13', '14-15', '16-17', '18+'];
        let ageVals = new Array(ageKeys.length).fill(0);
        availableSchools.forEach(sch => {
            if (!isSchoolVisible(sch)) return;
            if (globalData.age_dist && globalData.age_dist[ageYear] && globalData.age_dist[ageYear][sch]) {
                let dist = globalData.age_dist[ageYear][sch];
                ageKeys.forEach((k, i) => ageVals[i] += (dist[k] || 0));
            }
        });
        Plotly.react('ageChart', [{
            x: ageKeys, y: ageVals, type: 'bar', marker: {color: ageVals, colorscale: 'Viridis', showscale: false}
        }], {xaxis: {title: 'Age Group', type: 'category'}, yaxis: {title: 'Count'}, margin: {l:40, r:10, t:10, b:40}}, {responsive: true});

        // 3. ATTENDANCE (History)
        let attYears = Object.keys(globalData.attendance || {}).map(Number).filter(y => y >= startYear && y >= 2022).sort((a,b)=>a-b);
        let attTraces = [];
        if (attYears.length > 0) {
            availableSchools.forEach(sch => {
                if (!isSchoolVisible(sch)) return;
                ['Girls', 'Boys'].forEach(gen => {
                    let yVals = attYears.map(y => (globalData.attendance[y] && globalData.attendance[y][sch]) ? (globalData.attendance[y][sch][gen] || null) : null);
                    if (yVals.some(v => v !== null)) {
                        let baseC = schoolColors[sch] || '#95a5a6';
                        let color = (gen === 'Girls') ? lighten(baseC) : baseC;
                        attTraces.push({ x: attYears.map(String), y: yVals, type: 'scatter', mode: 'lines+markers', name: `${sch} ${gen}`, line: { color: color } });
                    }
                });
            });
        }
        Plotly.react('attendanceChart', attTraces, {xaxis:{type:'category'}, yaxis:{range:[80,100]}, margin:{l:40, r:10, t:10, b:30}}, {responsive: true});

        // 4. RETENTION
        let retYears = Object.keys(globalData.retention || {}).map(Number).filter(y => y >= startYear).sort((a,b)=>a-b);
        let retY = retYears.map(y => {
            let nums = [];
            availableSchools.forEach(sch => {
                if(isSchoolVisible(sch) && globalData.retention[y] && globalData.retention[y][sch] !== undefined) {
                    nums.push(globalData.retention[y][sch]);
                }
            });
            return nums.length ? nums.reduce((a,b)=>a+b)/nums.length : null;
        });
        Plotly.react('retentionChart', [{x: retYears.map(String), y: retY, type: 'scatter', mode: 'lines+markers', line: {color: '#2ecc71'}}], {xaxis:{type:'category'}, margin:{l:40, r:10, t:10, b:30}}, {responsive: true});

        // ------------------------------------------
        // 5. KPIs (Dynamic Calculations)
        // ------------------------------------------
        let maxYear = globalData.max_year || currentYear;
        
        // A. Enrollment & Gender
        let kpiTot=0, kpiG=0, kpiKn=0;
        if (globalData.enrollment[maxYear]) {
            availableSchools.forEach(sch => {
                if (isSchoolVisible(sch) && globalData.enrollment[maxYear][sch]) {
                    let d = globalData.enrollment[maxYear][sch];
                    kpiTot += (d.Girls||0)+(d.Boys||0)+(d.Unknown||0);
                    kpiG += (d.Girls||0); kpiKn += (d.Girls||0)+(d.Boys||0);
                }
            });
        }
        $('#kpi-total').text(kpiTot);
        $('#kpi-gender').text((kpiKn ? Math.round(kpiG/kpiKn*100) : 0) + '%');

        // B. Attendance (Weighted Average of Active Filter)
        let latestAttYear = attYears.length > 0 ? attYears[attYears.length - 1] : maxYear; 
        let attSum = 0; 
        let attCount = 0;
        if (globalData.attendance && globalData.attendance[latestAttYear]) {
             availableSchools.forEach(sch => {
                if (isSchoolVisible(sch) && globalData.attendance[latestAttYear][sch]) {
                    let d = globalData.attendance[latestAttYear][sch];
                    if (d.Girls !== undefined) { attSum += d.Girls; attCount++; }
                    if (d.Boys !== undefined) { attSum += d.Boys; attCount++; }
                }
            });
        }
        let dynamicAvgAtt = attCount > 0 ? Math.round((attSum / attCount) * 10) / 10 : 0;
        
        $('.kpi-label:contains("Avg Attendance")').text(`Avg Attendance (${latestAttYear})`);
        $('#kpi-attend').text(dynamicAvgAtt + '%');
        
        Plotly.react('genderDonut', [{values: [kpiG, kpiKn-kpiG], labels:['Girls','Boys'], type:'pie', hole:.6, marker:{colors:['#e91e63','#2980b9']}, textinfo:'label+percent'}], {margin: {l:20, r:20, t:20, b:20}}, {responsive: true});
        
    } catch(err) { console.error(err); }
}

function lighten(color) {
    if(color === '#3498db') return '#85c1e9'; 
    if(color === '#e67e22') return '#f5cba7';
    if(color === '#9b59b6') return '#d2b4de';
    if(color === '#2ecc71') return '#82e0aa';
    if(color === '#e74c3c') return '#f1948a';
    return '#d7dbdd';
}
</script>
</body>
</html>