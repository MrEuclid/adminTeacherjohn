<?php 
require_once "authCheckPIO.php";
restrictToAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Comparison</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .chart-box { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .filter-bar { background: #e9ecef; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        select[multiple] { background-image: none; overflow-y: auto; height: 120px; }
    </style>
</head>
<body class="bg-light p-4">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-bar-chart-fill"></i> Annual School Comparison</h2>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        <button class="btn btn-secondary btn-sm" onclick="history.back()">Back</button>
    </div>
</div>

<div class="row filter-bar mx-0 align-items-start g-3">
    <div class="col-md-3">
        <label class="fw-bold small text-muted mb-1">Filter Schools (Hold Ctrl)</label>
        <select id="schoolFilter" class="form-select border-primary fw-bold" multiple></select>
        <div class="d-flex gap-2 mt-1">
            <button class="btn btn-outline-secondary btn-sm py-0" style="font-size: 0.7rem;" id="btnAllSchools">All</button>
            <button class="btn btn-outline-secondary btn-sm py-0" style="font-size: 0.7rem;" id="btnClearSchools">Clear</button>
        </div>
    </div>

    <div class="col-md-3">
        <label class="fw-bold small text-muted">Time Interval</label>
        <div class="input-group">
            <span class="input-group-text bg-white small">From</span>
            <select id="minYear" class="form-select"></select>
            <span class="input-group-text bg-white small">To</span>
            <select id="maxYear" class="form-select"></select>
        </div>
    </div>

    <div class="col-md-3">
        <label class="fw-bold small text-muted d-block mb-1">Display Mode</label>
        <div class="form-check form-switch pt-1">
            <input class="form-check-input" type="checkbox" id="toggleGender" style="transform: scale(1.3);">
            <label class="form-check-label fw-bold ms-2" for="toggleGender">Show Gender Split</label>
        </div>
    </div>
    
    <div class="col-md-3 text-end text-muted small pt-2">
        <i class="bi bi-info-circle"></i> 
        <strong>Totals:</strong> Unique school colors (no blue/red).<br>
        <strong>Gender:</strong> Blue = Boys, Red = Girls.
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="chart-box">
            <h5 class="text-primary mb-3">Total Enrollment by School</h5>
            <div id="mainChart" style="height: 600px;"></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="chart-box">
            <h5>Detailed Data</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered text-center" id="dataTable">
                    <thead class="table-dark"></thead>
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
// Palette avoiding standard Blue (#3498db) and Red (#e74c3c)
const schoolColors = ['#27ae60', '#f39c12', '#8e44ad', '#2c3e50', '#16a085', '#d35400'];

$(document).ready(function() {
    $.getJSON("enrollmentStats.php", function(data) {
        if (!data || data.length === 0) { alert("No data."); return; }
        globalData = data;
        
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

        let allSchools = [...new Set(data.map(d => d.School))].filter(s=>s).sort();
        allSchools.forEach(s => $('#schoolFilter').append(new Option(s, s)));
        $('#schoolFilter option').prop('selected', true);

        updateDashboard();
    });

    $('#minYear, #maxYear, #schoolFilter, #toggleGender').on('change', updateDashboard);
    $('#btnAllSchools').click(() => { $('#schoolFilter option').prop('selected', true); updateDashboard(); });
    $('#btnClearSchools').click(() => { $('#schoolFilter option').prop('selected', false); updateDashboard(); });
});

function updateDashboard() {
    let minYear = parseInt($('#minYear').val());
    let maxYear = parseInt($('#maxYear').val());
    let selectedSchools = $('#schoolFilter').val() || [];
    let showGender = $('#toggleGender').is(':checked');

    if(minYear > maxYear) { let t = minYear; minYear = maxYear; maxYear = t; }

    let xYears = [];
    let xSchools = [];
    let yBoys = [];
    let yGirls = [];
    let yTotals = [];

    for(let y = minYear; y <= maxYear; y++) {
        let yStr = String(y);
        selectedSchools.sort().forEach(sch => {
            let records = globalData.filter(d => String(d.Year) === yStr && d.School === sch);
            let countM = records.filter(r => r.Gender === 'M').reduce((s, r) => s + r.Count, 0);
            let countF = records.filter(r => r.Gender === 'F').reduce((s, r) => s + r.Count, 0);
            let total = countM + countF;

            xYears.push(yStr);
            xSchools.push(sch);
            yBoys.push(countM);
            yGirls.push(countF);
            yTotals.push(total);
        });
    }

    let traces = [];

    if (!showGender) {
        // Map colors to schools
        let uniqueSchools = [...new Set(xSchools)];
        let schoolColorMap = {};
        uniqueSchools.forEach((s, i) => schoolColorMap[s] = schoolColors[i % schoolColors.length]);

        traces.push({
            x: [xYears, xSchools],
            y: yTotals,
            type: 'bar',
            name: 'Total Students',
            marker: { color: xSchools.map(s => schoolColorMap[s]) },
            text: yTotals,
            textposition: 'auto'
        });
    } else {
        traces.push({
            x: [xYears, xSchools],
            y: yBoys,
            type: 'bar',
            name: 'Boys',
            marker: { color: '#3498db' },
            text: yBoys,
            textposition: 'auto'
        });
        traces.push({
            x: [xYears, xSchools],
            y: yGirls,
            type: 'bar',
            name: 'Girls',
            marker: { color: '#e74c3c' },
            text: yGirls,
            textposition: 'auto'
        });
    }

    let layout = {
        barmode: showGender ? 'stack' : 'group',
        hovermode: 'closest',
        xaxis: { title: 'Academic Year', tickangle: -45 },
        yaxis: { title: 'Number of Students' },
        margin: {l:50, r:20, t:20, b:100},
        legend: { orientation: 'h', y: 1.1 }
    };

    Plotly.react('mainChart', traces, layout, {responsive: true});

    let $thead = $('#dataTable thead').empty();
    let headRow = `<tr><th>Year</th><th>School</th>`;
    if(showGender) headRow += `<th class="text-primary">Boys</th><th class="text-danger">Girls</th>`;
    headRow += `<th>Total</th></tr>`;
    $thead.append(headRow);

    let $tbody = $('#dataTable tbody').empty();
    for(let i = xYears.length - 1; i >= 0; i--) {
        if (yTotals[i] > 0) {
            let row = `<tr><td class="fw-bold">${xYears[i]}</td><td>${xSchools[i]}</td>`;
            if(showGender) row += `<td class="text-primary">${yBoys[i]}</td><td class="text-danger">${yGirls[i]}</td>`;
            row += `<td class="fw-bold">${yTotals[i]}</td></tr>`;
            $tbody.append(row);
        }
    }
}
</script>
</body>
</html>