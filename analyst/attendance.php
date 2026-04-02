<?php 
require_once "../authCheckPIO.php";
restrictToAdmin();
include "header.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analyst - Attendance Data</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <script src="https://cdn.plot.ly/plotly-2.26.0.min.js"></script>
    
    <style>
        .chart-box { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .loading-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.9); z-index:9999; display:flex; align-items:center; justify-content:center; flex-direction:column; }
        /* Layout Tweaks */
        .grade-sidebar { height: calc(100vh - 140px); overflow-y: auto; }
        select[multiple] { height: 100%; min-height: 300px; }
    </style>
</head>
<body class="bg-light">

<div id="loader" class="loading-overlay">
    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
    <h5 class="mt-3">Loading Attendance Data...</h5>
    <small class="text-muted">Fetching all years (This may take ~20 seconds)</small>
</div>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-md-2">
            <div class="chart-box sticky-top" style="top: 90px; height: 85vh;">
                <label class="fw-bold small text-muted">GRADE SELECTION</label>
                <div class="d-grid gap-2 mb-2">
                    <button class="btn btn-primary btn-sm" id="btnAllGrades">Select All</button>
                    <button class="btn btn-outline-secondary btn-sm" id="btnClearGrades">Clear</button>
                </div>
                <select id="gradeSelect" class="form-select" multiple style="height: 100%;"></select>
            </div>
        </div>

        <div class="col-md-10">
            <div class="chart-box">
                <h5 class="text-primary mb-3">Attendance Trends</h5>
                <div id="attendanceChart" style="height: 400px;"></div>
            </div>

            <div class="chart-box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="m-0"><i class="bi bi-table"></i> Data Detail</h5>
                    <span class="badge bg-secondary" id="rowCounter">0 Rows</span>
                </div>
                
                <table id="attendTable" class="table table-sm table-striped table-hover" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>Month</th>
                            <th>School</th>
                            <th>Grade</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Rate %</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
const anaSchool = "<?php echo $_SESSION['ana_school']; ?>";
const anaYearFrom = parseInt("<?php echo $_SESSION['ana_year_from']; ?>");
const anaYearTo = parseInt("<?php echo $_SESSION['ana_year_to']; ?>");

let globalData = [];
let dataTable = null;

$(document).ready(function() {
    // 1. Fetch Data
    $.getJSON("../attendanceStats.php", function(data) {
        // Initial filter by School & Year to keep memory clean
        globalData = data.filter(d => {
            let y = parseInt(d.YearMonth.substring(0,4));
            return (anaSchool === "All" || d.School === anaSchool) && 
                   (y >= anaYearFrom && y <= anaYearTo);
        });

        initDashboard();
        $('#loader').fadeOut();
    }).fail(function(jqXHR) {
        $('#loader').html('<h4 class="text-danger">Error Loading Data</h4><p>'+jqXHR.statusText+'</p>');
    });

    // 2. Event Listeners
    $('#btnAllGrades').click(() => { $('#gradeSelect option').prop('selected', true); refreshView(); });
    $('#btnClearGrades').click(() => { $('#gradeSelect option').prop('selected', false); refreshView(); });
    $('#gradeSelect').on('change', refreshView);
});

function initDashboard() {
    // Populate Grades
    let grades = [...new Set(globalData.map(d => d.Grade))].sort(naturalSort);
    let $sel = $('#gradeSelect').empty();
    grades.forEach(g => $sel.append(new Option(g, g)));
    $sel.find('option').prop('selected', true); // Default: All Selected
    
    refreshView();
}

function refreshView() {
    let selectedGrades = $('#gradeSelect').val() || [];
    
    // Filter Data
    let displayData = globalData.filter(d => selectedGrades.includes(d.Grade));
    $('#rowCounter').text(displayData.length + " Rows");

    // Update Table
    updateTable(displayData);
    
    // Update Chart
    updateChart(displayData, selectedGrades);
}

function updateTable(data) {
    if (dataTable) {
        dataTable.clear().rows.add(data).draw();
    } else {
        dataTable = $('#attendTable').DataTable({
            data: data,
            columns: [
                { data: 'YearMonth' },
                { data: 'School' },
                { data: 'Grade' },
                { data: 'TotalPresent' }, // Now this exists in JSON!
                { data: 'TotalAbsent' },  // This too!
                { 
                    data: 'Rate',
                    render: function(val) {
                        let color = val >= 95 ? '#198754' : (val >= 90 ? '#ffc107' : '#dc3545');
                        return `<span style="color:${color}; font-weight:bold;">${val}%</span>`;
                    }
                }
            ],
            dom: 'Bfrtip',
            buttons: [ 'excelHtml5', 'csvHtml5' ],
            pageLength: 15,
            order: [[0, 'desc'], [2, 'asc']]
        });
    }
}

function updateChart(data, grades) {
    // Sort grades for consistent legend
    grades.sort(naturalSort);
    
    let traces = [];
    grades.forEach(g => {
        // Get data for this grade, sorted by date
        let gData = data.filter(d => d.Grade === g).sort((a,b) => a.YearMonth.localeCompare(b.YearMonth));
        
        if(gData.length > 0) {
            traces.push({
                x: gData.map(d => d.YearMonth),
                y: gData.map(d => d.Rate),
                name: g,
                mode: 'lines+markers',
                line: { shape: 'spline' } // Makes lines smooth
            });
        }
    });

    Plotly.react('attendanceChart', traces, {
        margin: {t:30, l:50, r:20, b:50},
        xaxis: {type: 'category', title: 'Month'},
        yaxis: {range: [0, 105], title: 'Attendance %'},
        hovermode: 'x unified'
    }, {responsive: true});
}

function naturalSort(a, b) {
    if(a === 'K') return -1; if(b === 'K') return 1;
    let nA = parseInt(a.replace(/\D/g,'')) || 0;
    let nB = parseInt(b.replace(/\D/g,'')) || 0;
    return nA !== nB ? nA - nB : a.localeCompare(b);
}
</script>
</body>
</html>