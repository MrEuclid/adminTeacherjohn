<?php 
require_once "../authCheckPIO.php";
restrictToAdmin();
include "../connectDatabase.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    
    <style>
        .dashboard-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; background: white; }
        .filter-row { background: #e9ecef; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        select[multiple] { height: 120px; overflow-y: auto; }
        .stat-value { font-size: 2rem; font-weight: bold; }
        #loadingOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); z-index: 9999; display: flex; justify-content: center; align-items: center; flex-direction: column; }
        .spinner { width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-light container-fluid py-4">

    <div id="loadingOverlay">
        <div class="spinner"></div>
        <h4>Loading Student Data...</h4>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>School Performance Dashboard</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="window.print()">Print Report</button>
            <button class="btn btn-warning shadow-sm fw-bold" id="back" onclick="history.back()">GO BACK</button>
        </div>
    </div>

    <div class="row filter-row g-3">
        <div class="col-12 mb-3">
            <div class="card p-3 border-primary bg-light">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="text-primary fw-bold mb-0">Analysis Mode</h5>
                        <small class="text-muted">Choose how marks are calculated</small>
                    </div>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="scoreMode" id="modeRaw" value="Raw" checked>
                        <label class="btn btn-outline-primary fw-bold" for="modeRaw">📊 Raw Scores (Actual)</label>

                        <input type="radio" class="btn-check" name="scoreMode" id="modeStd" value="Standardized">
                        <label class="btn btn-outline-success fw-bold" for="modeStd">⚖️ Standardized (Fair Comparison)</label>
                    </div>
                </div>
                <div id="stdInfo" class="text-muted small mt-2" style="display:none;">
                    <em>* Standardizes all tests to <strong>Mean=65, SD=15</strong>. Corrects for "Hard" vs "Easy" tests to allow valid Subject-to-Subject comparison.</em>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-bold">Select Grades:</label>
            <select id="gradeFilter" class="form-select" multiple></select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Select Subjects:</label>
            <select id="subjectFilter" class="form-select" multiple></select>
        </div>
        <div class="col-md-3">
             <label class="form-label fw-bold">Select Test IDs:</label>
             <select id="testFilter" class="form-select" multiple></select>
        </div>
        <div class="col-md-3 d-flex flex-column justify-content-end">
            <label class="form-label fw-bold">Compare By:</label>
            <div class="btn-group w-100 mb-2" role="group">
                <input type="radio" class="btn-check" name="groupMode" id="groupByClass" value="Grade" checked>
                <label class="btn btn-outline-primary" for="groupByClass">Class</label>
                
                <input type="radio" class="btn-check" name="groupMode" id="groupBySubject" value="Subject">
                <label class="btn btn-outline-primary" for="groupBySubject">Subject</label>

                <input type="radio" class="btn-check" name="groupMode" id="groupByTest" value="TestID">
                <label class="btn btn-outline-primary" for="groupByTest">Test ID</label>
            </div>
            
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="splitGender">
                <label class="form-check-label fw-bold text-primary" for="splitGender">Split Chart by Gender</label>
            </div>

            <button id="resetFilters" class="btn btn-secondary w-100">Reset All</button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3"><div class="dashboard-card text-center"><h6 class="text-muted">Total Students</h6><div id="totalCount" class="stat-value text-dark">-</div></div></div>
        <div class="col-md-3"><div class="dashboard-card text-center"><h6 class="text-muted">Avg Percentage</h6><div id="avgScore" class="stat-value text-primary">-</div></div></div>
        <div class="col-md-3"><div class="dashboard-card text-center"><h6 class="text-muted">Pass Rate</h6><div id="passRate" class="stat-value text-success">-</div></div></div>
        <div class="col-md-3"><div class="dashboard-card text-center"><h6 class="text-muted">Fail Rate</h6><div id="failRate" class="stat-value text-danger">-</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="dashboard-card">
                <h5 class="card-title">Performance Comparison</h5>
                <div id="boxPlotContainer" style="height: 450px;"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <h5 class="card-title">Grade Distribution</h5>
                <div id="distributionContainer" style="height: 450px;"></div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="dashboard-card">
                <h4 class="mb-3">Generated Report List</h4>
                <div class="alert alert-success small py-2">
                    <i class="bi bi-check-circle-fill"></i> 
                    <strong>Dynamic Ranking Active:</strong> The "Place" column is based on current filter and mode (Raw vs Standardized).
                </div>
                <table id="marksTable" class="display table table-striped table-hover" style="width:100%"></table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.plot.ly/plotly-2.26.0.min.js"></script>

    <script>
    $(document).ready(function() {
        let globalData = [];
        let filteredData = [];
        let table = null;

        // --- STATISTICAL SETTINGS ---
        const TARGET_MEAN = 65; // We force every test to have this average
        const TARGET_SD = 15;   // We force this spread

        function naturalSort(a, b) {
            return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
        }

        function calculateGrade(score) {
            if (isNaN(score)) return "NA";
            if (score >= 90) return "A";
            if (score >= 80) return "B";
            if (score >= 70) return "C";
            if (score >= 60) return "D";
            if (score >= 50) return "E";
            return "F";
        }

        // --- STATS ENGINE ---
        function calculateStandardStats(dataSet) {
            let cohorts = {};
            
            dataSet.forEach(d => {
                let key = d.Grade + "|" + d.Subject + "|" + d.TestID;
                if(!cohorts[key]) cohorts[key] = { scores: [] };
                if(!isNaN(d.RawPercentage)) cohorts[key].scores.push(d.RawPercentage);
            });

            for (let key in cohorts) {
                let scores = cohorts[key].scores;
                if (scores.length > 1) {
                    let sum = scores.reduce((a, b) => a + b, 0);
                    let mean = sum / scores.length;
                    let variance = scores.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / scores.length;
                    let sd = Math.sqrt(variance);
                    cohorts[key].stats = { mean: mean, sd: sd };
                } else {
                    cohorts[key].stats = { mean: scores[0] || 0, sd: 0 };
                }
            }

            dataSet.forEach(d => {
                let key = d.Grade + "|" + d.Subject + "|" + d.TestID;
                let stats = cohorts[key].stats;
                
                if (stats.sd > 0) {
                    let zScore = (d.RawPercentage - stats.mean) / stats.sd;
                    let stdScore = TARGET_MEAN + (zScore * TARGET_SD);
                    stdScore = Math.max(0, Math.min(100, stdScore));
                    d.StdPercentage = parseFloat(stdScore.toFixed(1));
                } else {
                    d.StdPercentage = TARGET_MEAN; 
                }
            });
        }

        $.ajax({
            url: "hsStatsAll.php", 
            dataType: "json",
            success: function(data) {
                if (!data || data.length === 0) {
                     alert("No data received.");
                     $("#loadingOverlay").fadeOut();
                     return;
                }

                // --- 1. FORCE REMOVE UNWANTED SUBJECTS ---
                const EXCLUDED_SUBJECTS = ['LIFE', 'HOME', 'PE', 'TECH'];
                data = data.filter(row => {
                    return !EXCLUDED_SUBJECTS.includes(row.Subject.toUpperCase());
                });
                // ------------------------------------------
                
                data.forEach(row => {
                    row.RawPercentage = parseFloat(row.Percentage); 
                    row.StdPercentage = 0; 
                });

                globalData = data;
                
                calculateStandardStats(globalData);
                
                filteredData = globalData;
                
                populateFilters(data);
                calculateDynamicRanks(filteredData);
                initDataTable(filteredData);
                updateDashboard();
                
                $("#loadingOverlay").fadeOut(500);
            },
            error: function(xhr, status, error) { console.error(error); $("#loadingOverlay").hide(); }
        });

        function calculateDynamicRanks(dataSet) {
            let useStd = $('#modeStd').is(':checked');
            
            dataSet.sort((a, b) => {
                let valA = useStd ? a.StdPercentage : a.RawPercentage;
                let valB = useStd ? b.StdPercentage : b.RawPercentage;
                if (valB !== valA) return valB - valA;
                return a.Name.localeCompare(b.Name);
            });

            let rank = 0;
            let lastPct = -1;
            dataSet.forEach((row, index) => {
                let val = useStd ? row.StdPercentage : row.RawPercentage;
                if (val !== lastPct) rank = index + 1;
                row.DynamicRank = rank;
                lastPct = val;
            });
        }

        function populateFilters(data) {
            let grades = [...new Set(data.map(d => d.Grade))].sort(naturalSort);
            let subjects = [...new Set(data.map(d => d.Subject))].sort();
            let tests = [...new Set(data.map(d => d.TestID))].sort(naturalSort); 
            grades.forEach(g => $('#gradeFilter').append(`<option value="${g}">${g}</option>`));
            subjects.forEach(s => $('#subjectFilter').append(`<option value="${s}">${s}</option>`));
            tests.forEach(t => $('#testFilter').append(`<option value="${t}">${t}</option>`));
        }

        function applyFilters() {
            let selectedGrades = $('#gradeFilter').val();
            let selectedSubjects = $('#subjectFilter').val();
            let selectedTests = $('#testFilter').val();

            filteredData = globalData.filter(row => {
                let gradeMatch = !selectedGrades.length || selectedGrades.includes(row.Grade);
                let subjectMatch = !selectedSubjects.length || selectedSubjects.includes(row.Subject);
                let testMatch = !selectedTests.length || selectedTests.includes(row.TestID);
                return gradeMatch && subjectMatch && testMatch;
            });

            calculateDynamicRanks(filteredData);
            updateDashboard();
        }

        function updateDashboard() {
            let useStd = $('#modeStd').is(':checked');
            
            if(useStd) $('#stdInfo').show(); else $('#stdInfo').hide();

            let pcts = filteredData.map(d => useStd ? d.StdPercentage : d.RawPercentage).filter(p => !isNaN(p));
            
            let avg = pcts.length ? (pcts.reduce((a, b) => a + b, 0) / pcts.length).toFixed(1) + "%" : "0%";
            let passCount = pcts.filter(s => s >= 50).length;
            let failCount = pcts.filter(s => s < 50).length;
            
            $('#totalCount').text(filteredData.length);
            $('#avgScore').text(avg);
            $('#passRate').text(pcts.length ? Math.round((passCount/pcts.length)*100) + "%" : "0%");
            $('#failRate').text(pcts.length ? Math.round((failCount/pcts.length)*100) + "%" : "0%");

            // Charts
            let groupMode = $('input[name="groupMode"]:checked').val();
            let splitGender = $('#splitGender').is(':checked');
            let traces = [];

            // --- PRE-CALCULATE SORT ORDER ---
            let uniqueCategories = [...new Set(filteredData.map(d => d[groupMode]))];
            uniqueCategories.sort(naturalSort);
            // ---------------------------------

            if (splitGender) {
                let boys = { x: [], y: [] }, girls = { x: [], y: [] };
                filteredData.forEach(d => {
                    let val = useStd ? d.StdPercentage : d.RawPercentage;
                    let key = d[groupMode];
                    if (d.Gender && (d.Gender.toUpperCase() === 'M' || d.Gender.toUpperCase() === 'MALE')) {
                        boys.x.push(key); boys.y.push(val);
                    } else {
                        girls.x.push(key); girls.y.push(val);
                    }
                });
                traces.push({ x: boys.x, y: boys.y, type: 'box', name: 'Boys', marker: {color:'#3498db'} });
                traces.push({ x: girls.x, y: girls.y, type: 'box', name: 'Girls', marker: {color:'#e91e63'} });
            } else {
                let groups = {};
                filteredData.forEach(d => {
                    let val = useStd ? d.StdPercentage : d.RawPercentage;
                    let key = d[groupMode]; 
                    if(!groups[key]) groups[key] = [];
                    groups[key].push(val);
                });
                
                uniqueCategories.forEach(k => {
                    if (groups[k]) {
                        traces.push({ y: groups[k], type: 'box', name: k, boxpoints: 'outliers', jitter: 0.3 });
                    }
                });
            }
            
            Plotly.react('boxPlotContainer', traces, { 
                title: `${useStd ? "Standardized" : "Raw"} Comparison by ${groupMode}`, 
                yaxis: { title: 'Percentage Score', range: [0, 100] }, 
                xaxis: { 
                    type: 'category',
                    categoryorder: 'array',
                    categoryarray: uniqueCategories // FORCE ALPHANUMERIC ORDER
                },
                boxmode: 'group'
            });

            // Distribution Chart
            let gradeCounts = { 'A': 0, 'B': 0, 'C': 0, 'D': 0, 'E': 0, 'F': 0 };
            filteredData.forEach(d => {
                let val = useStd ? d.StdPercentage : d.RawPercentage;
                let grade = calculateGrade(val);
                if(gradeCounts[grade] !== undefined) gradeCounts[grade]++;
            });
            let barTrace = {
                x: Object.keys(gradeCounts), y: Object.values(gradeCounts), type: 'bar',
                marker: { color: ['#2ecc71', '#3498db', '#9b59b6', '#f1c40f', '#e67e22', '#e74c3c'] },
                text: Object.values(gradeCounts).map(String), textposition: 'auto'
            };
            Plotly.react('distributionContainer', [barTrace], { title: 'Letter Grade Totals', yaxis: { title: 'Count' } });

            if(table) { table.clear().rows.add(filteredData).draw(); }
        }

        function initDataTable(dataSet) {
            table = $('#marksTable').DataTable({
                data: dataSet,
                columns: [
                    { data: 'StudentID', title: 'ID' },
                    { data: 'Name', title: 'Name' },
                    { data: 'Gender', title: 'Gen' },
                    { data: 'Grade', title: 'Class' },
                    { data: 'TestID', title: 'Test' },
                    { data: 'YearsEnrolled', title: 'Yrs' },
                    { data: 'Subject', title: 'Subject' },
                    { 
                        data: null, title: 'Score',
                        render: function(data, type, row) {
                            let useStd = $('#modeStd').is(':checked');
                            let val = useStd ? row.StdPercentage : row.RawPercentage;
                            return type === 'display' ? val + '%' : val;
                        }
                    },
                    { data: 'DynamicRank', title: 'Place', type: 'num' },
                    { 
                        data: null, title: 'Grade',
                        render: function(data, type, row) {
                            let useStd = $('#modeStd').is(':checked');
                            let val = useStd ? row.StdPercentage : row.RawPercentage;
                            return calculateGrade(val);
                        }
                    }
                ],
                dom: 'Bfrtip',
                pageLength: 50,
                order: [[8, 'asc']], 
                buttons: [ 'excelHtml5', 'pdfHtml5', 'copy' ]
            });
        }
        
        $('select').on('change', applyFilters);
        $('input[name="groupMode"]').on('change', updateDashboard);
        $('#splitGender').on('change', updateDashboard);
        $('input[name="scoreMode"]').on('change', function() {
            calculateDynamicRanks(filteredData);
            updateDashboard();
        });
        $('#resetFilters').on('click', function() { $('select option').prop('selected', false); $('#splitGender').prop('checked', false); applyFilters(); });
    });
    </script>
</body>
</html>