<?php
// Include your database connection
include "../connectDatabase.php";
date_default_timezone_set('Asia/Phnom_Penh');
// Set the current date (used by the JavaScript)
$date = date('Y-m-d');

// --- NEW: Fetch Dynamic Grades ---
// Set the current year based on your system's needs (e.g., date('Y') or a specific academic year string)
$currentYear = date('Y'); 

// Fetch all unique grades for the current school year, sorted alphabetically
$gradeQuery = "SELECT DISTINCT Grade FROM New_ID_Year_Grade WHERE Year = '$currentYear' ORDER BY Grade ASC";
$gradeResult = mysqli_query($dbServer, $gradeQuery);
$activeGrades = [];

if ($gradeResult) {
    while ($row = mysqli_fetch_assoc($gradeResult)) {
        $activeGrades[] = $row['Grade'];
    }
}
// ---------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* Custom styles for the attendance buttons */
        .student-btn {
            height: 100px;
            margin-bottom: 10px;
            border: 2px solid transparent;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.2s ease-in-out;
        }
        
        .student-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.2);
        }

        /* Status Colors */
        .state-Y { background-color: #28a745; color: white; border-color: #1e7e34; } /* Present - Green */
        .state-N { background-color: #dc3545; color: white; border-color: #bd2130; } /* Absent - Red */
        .state-P { background-color: #ffc107; color: black; border-color: #d39e00; } /* Permission - Yellow */
        .state-L { background-color: #17a2b8; color: white; border-color: #117a8b; } /* Late - Blue */
        
        .status-badge {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 5px;
        }
    </style>
</head>
<body class="bg-light">
 <button class="btn btn-warning shadow-sm px-6 font-bold" id="back" onclick="history.back()">
            GO BACK
        </button>
<div class="container mt-4">
    <h2 class="mb-4">Daily Attendance - <?php echo date('F j, Y'); ?></h2>

    <div class="row mb-3">
        <div class="col-md-4 col-8">
            <select id="selectGrade" class="form-select">
                <option value="" disabled selected>Select Grade/Class...</option>
                <?php 
                // Loop through the dynamically fetched grades and output the options
                foreach ($activeGrades as $grade) {
                    // htmlspecialchars is used for safety in case there are special characters in the grade names
                    $safeGrade = htmlspecialchars($grade);
                    echo "<option value=\"{$safeGrade}\">{$safeGrade}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-2 col-4">
            <button id="load" class="btn btn-primary w-100">Load</button>
            
        </div>
    </div>

    <div id="message" class="alert d-none" role="alert"></div>

    <div class="row">
        <div class="col-12">
            <div id="btnContainer" class="d-grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));">
                </div>
        </div>
    </div>

    <div class="row mt-4 mb-5">
        <div class="col-12 text-center">
            <button id="save" class="btn btn-success btn-lg px-5" disabled>Save Attendance</button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let studentData = [];
    const date = '<?php echo $date; ?>';

    // The State Machine starts at Y (Present), tapping it goes to N (Absent) next.
    // Flow: Present -> Absent -> Permission -> Late -> Present
    const nextState = { 'Y': 'N', 'N': 'P', 'P': 'L', 'L': 'Y' };
    const stateLabels = { 'N': 'Absent', 'Y': 'Present', 'P': 'Permission', 'L': 'Late' };

    $('#load').click(function() {
        const grade = $('#selectGrade').val();
        if (!grade) {
            alert("Please select a grade first.");
            return;
        }

        $('#btnContainer').empty();
        $('#save').prop('disabled', false).text('Save Attendance');
        $('#message').addClass('d-none');

        // Fetch students for the selected grade
        $.ajax({
            url: 'loadButtons.php',
            type: 'POST',
            dataType: 'json', 
            data: { studentGrade: grade }
        }).done(function(data) {
            studentData = data;
            
            // Check if there are students to process
            if (studentData.length === 0) {
                $('#message').removeClass('d-none alert-success').addClass('alert-danger').html('No students found for this grade.');
                $('#save').prop('disabled', true);
                return;
            }
            
            const studentIDs = studentData.map(s => s.studentID);
            const isPM = new Date().getHours() >= 12; // Check if current time is 12:00 PM or later

            // Fetch today's existing attendance to check for AM status and 'P' statuses
            $.ajax({
                url: 'getTodayAttendance.php',
                type: 'POST',
                dataType: 'json',
                data: { studentIDs: studentIDs, date: date }
            }).done(function(todayRecords) {
                
                $.each(studentData, function(index, student) {
                    let initialStatus = 'Y'; // Default to Present
                    
                    const record = todayRecords[student.studentID];
                    if (record) {
                        // If 'P' is already marked today, default to 'P'
                        if (record.hasPermission) {
                            initialStatus = 'P';
                        } 
                        // If it's the afternoon, default to their AM status
                        else if (isPM && record.amStatus) {
                            initialStatus = record.amStatus; 
                        }
                    }

                    student.status = initialStatus; 
                    
                    const btn = $('<button>')
                        .addClass('student-btn w-100 d-flex flex-column align-items-center justify-content-center')
                        .addClass('state-' + initialStatus)
                        .attr('data-index', index)
                        .attr('data-status', initialStatus)
                        .html(`<span>${student.studentID}</span>
                               <span class="text-truncate w-100 px-1">${student.khmerName}</span>
                               <span class="status-badge state-label">${stateLabels[initialStatus]}</span>`);
                    
                    $('#btnContainer').append(btn);
                });

                // Attach click handlers AFTER buttons are generated
                $('#btnContainer').off('click').on('click', '.student-btn', function() {
                    const btn = $(this);
                    const index = btn.attr('data-index');
                    const currentState = btn.attr('data-status');
                    
                    const newState = nextState[currentState];
                    
                    btn.removeClass('state-' + currentState).addClass('state-' + newState);
                    btn.attr('data-status', newState);
                    btn.find('.state-label').text(stateLabels[newState]);
                    
                    studentData[index].status = newState;
                });

            }).fail(function() {
                alert("Could not check today's previous attendance records.");
            });

        }).fail(function(jqXHR, textStatus, errorThrown) {
            alert("Error loading students. Check the console for details.");
            console.log("AJAX Error:", textStatus, errorThrown);
        });
    });

    // Save Data
    $('#save').click(function() {
        const now = new Date();
        const exactTime = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        
        $.each(studentData, function(i, s) {
            s.shortTime = exactTime;
            s.shortDate = date;
        });

        $('#save').text('Saving...').prop('disabled', true);

        $.ajax({
            url: 'markRoll.php',
            type: 'POST',
            data: { data: studentData }
        }).done(function(response) {
            $('#message').removeClass('d-none alert-danger').addClass('alert-success').html('Attendance saved successfully at ' + exactTime + '!');
            $('#btnContainer').empty();
            studentData = []; 
            $('#save').text('Saved!'); 
        }).fail(function() {
            $('#message').removeClass('d-none alert-success').addClass('alert-danger').html('Error saving attendance.');
            $('#save').text('Save Attendance').prop('disabled', false);
        });
    });
});
</script>

</body>
</html>