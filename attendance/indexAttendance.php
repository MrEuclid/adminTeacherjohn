<?php 
// require_once "../authCheckPIO.php";
// restrictToAdmin();
include "../connectDatabase.php" ; 
$date = date('Y-m-d') ; // Changed to standard DB format
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mark Roll</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Khmer' rel='stylesheet' type='text/css'>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <style>
        body { background-color: #f8f9fa; font-family: 'Khmer', sans-serif; }
        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .student-btn {
            height: 100px;
            font-size: 1.1em;
            font-weight: bold;
            border-radius: 12px;
            transition: transform 0.1s, box-shadow 0.1s;
            border: 2px solid transparent;
        }
        .student-btn:active { transform: scale(0.95); }
        
        /* State Colors */
        .state-N { background-color: #f8d7da; color: #842029; border-color: #f5c2c7; } /* Red/Absent */
        .state-Y { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; } /* Green/Present */
        .state-P { background-color: #cfe2ff; color: #084298; border-color: #b6d4fe; } /* Blue/Permission */
        .state-L { background-color: #fff3cd; color: #664d03; border-color: #ffecb5; } /* Yellow/Late */
        
        .status-badge {
            display: block;
            font-size: 0.8em;
            opacity: 0.8;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row mb-4 align-items-end">
        <div class="col-md-3">
            <h2 class="text-primary mb-0">Mark Roll</h2>
            <small class="text-muted"><?php echo date('D, d M Y'); ?></small>
        </div>
        
        <div class="col-md-2">
            <label class="form-label fw-bold">Select Class</label>
            <select class="form-select" id="selectGrade">
                <option value="G7A">G7A</option>
                <option value="G8A">G8A</option>
                <option value="G9A">G9A</option>
            </select>
        </div>
        
        <div class="col-md-2">
            <label class="form-label fw-bold">Time</label>
            <select class="form-select" id="time">
                <option value="08:00">AM</option>
                <option value="13:00">PM</option>
            </select>
        </div>

        <div class="col-md-5 text-end">
            <button class="btn btn-outline-primary me-2" id="load">Load Class</button>
            <button class="btn btn-success px-4" id="save" disabled>Save Attendance</button>
        </div>
    </div>

    <div id="message" class="alert d-none"></div>

    <div class="attendance-grid" id="btnContainer"></div>
</div>

<script>
$(document).ready(function() {
    let studentData = [];
    const date = '<?php echo $date; ?>';

    // The State Machine: N -> Y -> P -> L -> N
    const nextState = { 'N': 'Y', 'Y': 'P', 'P': 'L', 'L': 'N' };
    const stateLabels = { 'N': 'Absent', 'Y': 'Present', 'P': 'Permission', 'L': 'Late' };

    $('#load').click(function() {
        const grade = $('#selectGrade').val();
        $('#btnContainer').empty();
        $('#save').prop('disabled', false);
        $('#message').addClass('d-none');

        $.ajax({
            url: 'loadButtons.php',
            type: 'POST',
            dataType: 'json', // Expect clean JSON back
            data: { studentGrade: grade }
        }).done(function(data) {
            studentData = data;
            
            $.each(data, function(index, student) {
                // Default everyone to 'N' (Absent) initially
                student.status = 'N'; 
                
                const btn = $('<button>')
                    .addClass('student-btn state-N w-100 d-flex flex-column align-items-center justify-content-center')
                    .attr('data-index', index)
                    .attr('data-status', 'N')
                    .html(`<span>${student.studentID}</span>
                           <span class="text-truncate w-100 px-1">${student.khmerName}</span>
                           <span class="status-badge state-label">Absent</span>`);
                
                $('#btnContainer').append(btn);
            });
        }).fail(function() {
            alert("Error loading students.");
        });
    });

    // Handle Button Clicks
    $('#btnContainer').on('click', '.student-btn', function() {
        const btn = $(this);
        const index = btn.attr('data-index');
        const currentState = btn.attr('data-status');
        
        // Calculate new state
        const newState = nextState[currentState];
        
        // Update the button visuals
        btn.removeClass('state-' + currentState).addClass('state-' + newState);
        btn.attr('data-status', newState);
        btn.find('.state-label').text(stateLabels[newState]);
        
        // Update the data array
        studentData[index].status = newState;
    });

    // Save Data
    $('#save').click(function() {
        const time = $('#time').val();
        
        // Append time and date to the payload
        $.each(studentData, function(i, s) {
            s.shortTime = time;
            s.shortDate = date;
        });

        $('#save').text('Saving...').prop('disabled', true);

        $.ajax({
            url: 'markRoll.php',
            type: 'POST',
            data: { data: studentData }
        }).done(function(response) {
            $('#message').removeClass('d-none alert-danger').addClass('alert-success').html('Attendance saved successfully!');
            $('#save').text('Save Attendance').prop('disabled', false);
        }).fail(function() {
            $('#message').removeClass('d-none alert-success').addClass('alert-danger').html('Error saving attendance.');
            $('#save').text('Save Attendance').prop('disabled', false);
        });
    });
});
</script>
</body>
</html>