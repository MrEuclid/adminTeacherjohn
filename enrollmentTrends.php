<?php 
require_once "authCheckPIO.php";
restrictToAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade Level Trends</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .chart-box { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .filter-bar { background: #e9ecef; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        select[multiple] { background-image: none; overflow-y: auto; height: 160px; border: 1px solid #ced4da; }
        .form-label-custom { font-weight: 700; font-size: 0.8rem; color: #495057; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body class="bg-light p-4">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up-arrow"></i> Grade Level Growth Trends</h2>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        <button class="btn btn-secondary btn-sm" onclick="history.back()">Back</button>
    </div>
</div>

<div class="row filter-bar mx-0 g-4">
    <div class="col-md-3 border-end border-2 border-white">
        <div class="mb-3">
            <label class="form-label-custom mb-1">1. School Campus</label>
            <select id="schoolSelect" class="form-select">
                <option value="All">All Schools</option>
            </select>
        </div>
        
        <div>
            <label class="form-label-custom mb-1">2. Academic Period</label>
            <div class="input-group">
                <span class="input-group-text bg-white small">From</span>
                <select id="minYear" class="form-select"></select>
                <span class="input-group-text bg-white small">To</span>
                <select id="maxYear" class="form-select"></select>
            </div>
        </div>
    </div>

    <div class="col-md-4 border-end border-2 border-white">
        <label class="form-label-custom mb-1">3. Grade Levels</label>
        <select id="gradeSelect" class="form-select fw-bold" multiple></select>
        <div class="d-flex justify-content-between mt-2">
            <small class="text-muted">Hold Ctrl for multiple</small>
            <div>
                <button class="btn btn-link btn-sm p-0 text-decoration-none me-3" id="btnAllGrades">All</button>
                <button class="btn btn-link btn-sm p-0 text-decoration-none text-danger" id="btnClearGrades">Clear</button>
            </div>
        </div>
    </div>

    <div class="col-md-5 d-flex flex-column justify-content-center">
        <div class="alert alert-light border-0 shadow-sm mb-0">
            <h6 class="alert-heading fw-bold"><i class="bi bi-info-circle-fill text-primary"></i> Data Alignment Fix</h6>
            <p class="small mb-0 text-muted">The chart now forces a <strong>categorical axis</strong>. This ensures that bars for older years (pre-2022) are displayed correctly and are not squashed or misaligned with the trend lines.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="chart-box">
            <h5 class="text-primary mb-3">Longitudinal Growth: <span id="chartTitleGrade" class="text-dark"></span></h5>
            <div id="trendChart" style="height: 500px;"></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="chart-box">
            <h5>Data Summary</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover text-center align-middle" id="dataTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Year</th>
                            <th class="text-danger">Girls</th>
                            <th class="text-primary">Boys</th>
                            <th>Total Students</th>
                            <th>% Female</th>
                            <th>Growth</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.plot.ly/plotly-2.26.0.min.js"></script>

<script>
let globalData = [];

$(document).ready(function() {
    $.getJSON("enrollmentStats.php", function(data) {
        globalData = data;
        
        let schools = [...new Set(data.map(d => d.School))].filter(s => s).sort();
        schools.forEach(s => $('#schoolSelect').append(new Option(s, s)));

        let allYears = [...new Set(data.map(d => d.Year))].sort((a,b)=>a-b);
        let minDataYear = allYears[0];
        let maxDataYear = new Date().getFullYear();
        if(allYears[allYears.length-1] > maxDataYear) maxDataYear = allYears[allYears.length-1];

        for(let y = minDataYear; y <= maxDataYear; y++) {
            $('#minYear').append(new Option(y, y));
            $('#maxYear').append(new Option(y, y));
        }
        $('#minYear').val(minDataYear); 
        $('#maxYear').val(maxDataYear);

        syncGradeFilter();
    });

    $('#schoolSelect, #minYear, #maxYear').on('change', syncGradeFilter);
    $('#gradeSelect').on('change', updateDashboard);
    $('#btnAllGrades').click(() => { $('#gradeSelect option').prop('selected', true); updateDashboard(); });
    $('#btnClearGrades').click(() => { $('#gradeSelect option').prop('selected', false); updateDashboard(); });
});

function syncGradeFilter() {
    let selSchool = $('#schoolSelect').val();
    let minYear = parseInt($('#minYear').val());
    let maxYear = parseInt($('#maxYear').val());
    
    let availableGrades = [...new Set(globalData.filter(d => {
        let matchSchool = selSchool === "All" || d.School === selSchool;
        let matchYear = d.Year >= minYear && d.Year <= maxYear;
        return matchSchool && matchYear;
    }).map(d => d.Grade))].sort((a, b) => {
        if(a === 'K') return -1; if(b === 'K') return 1;
        let nA = parseInt(a.replace(/\D/g,'')) || 0;
        let nB = parseInt(b.replace(/\D/g,'')) || 0;
        return nA !== nB ? nA - nB : a.localeCompare(b);
    });

    let currentSelections = $('#gradeSelect').val() || [];
    $('#gradeSelect').empty();
    availableGrades.forEach(g => {
        let option = new Option(g, g);
        if (currentSelections.includes(g)) option.selected = true;
        $('#gradeSelect').append(option);
    });

    if ($('#gradeSelect').val().length === 0 && availableGrades.length > 0) {
        let defaultG = availableGrades.includes('G12') ? 'G12' : availableGrades[0];
        $('#gradeSelect').val([defaultG]);
    }
    updateDashboard();
}

function updateDashboard() {
    let selGrades = $('#gradeSelect').val() || [];
    let selSchool = $('#schoolSelect').val();
    let minYear = parseInt($('#minYear').val());
    let maxYear = parseInt($('#maxYear').val());

    if(selGrades.length === 0) {
        $('#chartTitleGrade').text("No Selection");
        Plotly.purge('trendChart');
        return;
    }

    $('#chartTitleGrade').text(selGrades.length <= 4 ? selGrades.join(", ") : selGrades.length + " Grades Selected");

    // 1. Initialize Year Data (Zero-filled)
    let statsByYear = {};
    let years = [];
    for(let y = minYear; y <= maxYear; y++) {
        let yStr = String(y);
        years.push(yStr);
        statsByYear[yStr] = { F:0, M:0, Total:0 };
    }

    // 2. Aggregate Data
    globalData.forEach(d => {
        let yStr = String(d.Year);
        if(selGrades.includes(d.Grade) && (selSchool === "All" || d.School === selSchool)) {
            if(statsByYear[yStr]) {
                if(d.Gender === 'F') statsByYear[yStr].F += d.Count;
                else if(d.Gender === 'M') statsByYear[yStr].M += d.Count;
                statsByYear[yStr].Total += d.Count;
            }
        }
    });

    let girls = years.map(y => statsByYear[y].F);
    let boys = years.map(y => statsByYear[y].M);
    let totals = years.map(y => statsByYear[y].Total);

    // 3. COVID Highlight Shapes (Must use string coordinates for category axis)
    let shapes = [];
    if(years.includes("2020") || years.includes("2021")) {
        // Find indices to center the box
        let startIdx = years.indexOf("2020");
        let endIdx = years.indexOf("2021");
        
        // If 2020 or 2021 are in the range, draw the box
        shapes.push({
            type: 'rect', xref: 'x', yref: 'paper',
            x0: (startIdx !== -1 ? startIdx - 0.5 : -0.5), 
            x1: (endIdx !== -1 ? endIdx + 0.5 : years.length - 0.5),
            y0: 0, y1: 1,
            fillcolor: '#f39c12', opacity: 0.1, line: {width: 0}
        });
    }

    // 4. Render Chart
    Plotly.react('trendChart', [
        { 
            x: years, y: totals, name: 'Total Enrollment', 
            type: 'bar', opacity: 0.2, marker: {color: '#6c757d'} 
        },
        { 
            x: years, y: girls, name: 'Girls', 
            type: 'scatter', mode: 'lines+markers', 
            line: {color: '#e74c3c', width: 4, shape: 'spline'} 
        },
        { 
            x: years, y: boys, name: 'Boys', 
            type: 'scatter', mode: 'lines+markers', 
            line: {color: '#3498db', width: 4, shape: 'spline'} 
        }
    ], {
        barmode: 'overlay', // Crucial: Keeps Bar centered behind the Lines
        hovermode: 'x unified',
        xaxis: { type: 'category', title: 'Academic Year' }, 
        yaxis: { title: 'Number of Students', rangemode: 'tozero' },
        margin: {l:50, r:20, t:10, b:50},
        legend: { orientation: 'h', y: 1.1, x: 0.5, xanchor: 'center' },
        shapes: shapes
    }, {responsive: true});

    // 5. Update Table
    let $tbody = $('#dataTable tbody').empty();
    [...years].reverse().forEach(y => {
        let f = statsByYear[y].F, m = statsByYear[y].M, t = statsByYear[y].Total;
        let pct = t > 0 ? Math.round((f/t)*100) + '%' : '-';
        let growthStr = '-';
        let prevY = String(parseInt(y) - 1);
        if(statsByYear[prevY] && statsByYear[prevY].Total > 0) {
            let diff = t - statsByYear[prevY].Total;
            growthStr = (diff >= 0 ? '<span class="text-success">↑ ' : '<span class="text-danger">↓ ') + Math.abs(diff) + '</span>';
        }
        $tbody.append(`<tr><td class="fw-bold">${y}</td><td>${f}</td><td>${m}</td><td class="fw-bold">${t}</td><td>${pct}</td><td>${growthStr}</td></tr>`);
    });
}
</script>
</body>
</html>