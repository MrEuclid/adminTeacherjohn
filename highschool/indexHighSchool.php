<?php 
require_once "../authCheckPIO.php";
restrictToAdmin();
include "../connectDatabase.php"; 
include "../yearMonth.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure $y is defined (handling the academic year shift for Nov/Dec)
$y = $year;
if ($month > 9) {
    $y = $year - 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>High School Marks Dashboard</title>
    <link href='https://fonts.googleapis.com/css?family=Khmer' rel='stylesheet' type='text/css'>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Modernized Bootstrap & DataTables CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    
    <style type="text/css">
        body { background-color: #f8f9fa; font-family: Arial, 'Khmer', sans-serif; }
        .dashboard-header { text-align:center; padding: 20px 0; color: #2c3e50; font-weight:bold; }
        .control-panel { background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .info-panel { background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px; display: none; text-align: center;}
        .info-panel h4 { margin: 0; color: #0056b3; font-weight: bold; }
        .form-control { display: inline-block; width: auto; margin: 0 10px; }
        .radio-inline { margin-right: 15px; font-size: 16px; }
        #errorMessage { display: none; background-color: #ffeeba; color: #856404; padding: 10px; border-radius: 4px; text-align: center; margin-bottom: 15px; }
        .pink { background-color: #ffb3b3; text-align: center; }
        .highlight { background-color: #e6f2ff; }
        input[type="text"].pink { width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 4px; }
    </style>   
</head>

<body>

<div class="container">
    <div class="row">
        <div class="col-sm-12">
            <?php include "menu.html"; ?>
            <h1 class="dashboard-header">PIO High School Markbook Dashboard</h1>
            <p class="text-center text-muted"><strong>School Year: <?php echo ($year-1) . " / " . $year; ?></strong> | Today is <?php echo date('d-M-Y'); ?></p>
        </div>
    </div>

    <!-- MAIN CONTROL PANEL -->
    <div class="row">
        <div class="col-sm-12">
            <div class="control-panel">
                <div id="errorMessage"></div>
                
                <h3 class="text-center" style="color:#27ae60; margin-bottom: 20px;">Select Test Parameters</h3>
                
                <!-- Test Type Selection -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-sm-12 text-center">
                        <label class="radio-inline">
                            <input type="radio" id="monthly" name="testType" value="month" checked> <strong>Monthly Test</strong>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="SEM1" name="testType" value="S1"> <strong>Semester 1</strong>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="SEM2" name="testType" value="S2"> <strong>Semester 2</strong>
                        </label>
                    </div>
                </div>

                <hr>

                <!-- Month, Subject, Class Selection -->
                <div class="row text-center">
                    <form id="markSelectionForm" onsubmit="return false;">
                        
                        <?php
                            // Generate Month Array
                            $monthArray = [];
                            $oldYear = $year - 1;
                            $query = "SELECT distinct testID FROM hsMarks WHERE testID > concat('".$oldYear."','-09') ORDER BY id DESC";
                            $result = mysqli_query($dbServer,$query);
                            while ($data = mysqli_fetch_row($result)) {
                                $monthArray[] = $data[0];
                            }
                            $now = date('Y-m');
                            array_push($monthArray, $now);
                            $monthArray = array_unique($monthArray);
                            
                            // Get Subjects
                            $querySubjects = "SELECT code, english, khmer FROM hsSubjects ORDER BY code";
                            $resultSubjects = mysqli_query($dbServer, $querySubjects);

                            // Get Classes
                            $queryClasses  = "SELECT DISTINCT Grade FROM New_ID_Year_Grade WHERE School = 'PIOHS' AND Year = '$year' ORDER BY CAST(substr(Grade,2,2) AS UNSIGNED)";
                            $resultClasses = mysqli_query($dbServer, $queryClasses);
                        ?>

                        <div class="form-group" style="display:inline-block;">
                            <label for="yearMonth">Month: </label>
                            <select id="yearMonth" name="yearMonth" class="form-control">
                                <option value="">Select Month</option>
                                <?php foreach($monthArray as $m): ?>
                                    <option value="<?php echo $m; ?>"><?php echo $m; ?></option>  
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="display:inline-block;">
                            <label for="subject">Subject: </label>
                            <select id="subject" name="subject" class="form-control">
                                <option value="">Select Subject</option>
                                <?php while ($data = mysqli_fetch_assoc($resultSubjects)): ?>
                                    <option value="<?php echo $data['code']; ?>">
                                        <?php echo $data['english'] . " " . $data['khmer'] . " (" . $data['code'] . ")"; ?>
                                    </option>  
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group" style="display:inline-block;">
                            <label for="grade">Class: </label>
                            <select id="grade" name="grade" class="form-control">
                                <option value="">Select Class</option>
                                <?php while ($data = mysqli_fetch_assoc($resultClasses)): ?>
                                    <option value="<?php echo $data['Grade']; ?>"><?php echo $data['Grade']; ?></option>  
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group" style="display:inline-block; vertical-align: bottom;">
                            <button id="fetchDataBtn" class="btn btn-primary" style="margin-left: 15px;">Load Markbook</button>
                        </div>
                    </form>
                </div>

                <!-- Info Panel for Subject/Level/Maxima -->
                <div id="subjectInfoPanel" class="info-panel">
                    <h4>
                        <span id="displaySubject"></span> | 
                        Class: <span id="displayGrade"></span> | 
                        Test: <span id="displayTestID"></span> | 
                        Level: <span id="displayLevel"></span> | 
                        Max Mark: <span id="displayMaxima" class="text-danger"></span>
                    </h4>
                </div>

            </div> <!-- /control-panel -->
        </div>
    </div>

    <!-- WORKSPACE AREA (Where Add/Edit form loads) -->
    <div id="workspaceArea"></div>

</div> <!-- /container -->

<!-- Scripts -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    
    // Handle Radio Button Logic to override the month dropdown for Semesters
    $('input[type=radio][name=testType]').change(function() {
        if (this.value == 'month') {
            $('#yearMonth').prop('disabled', false);
        } else {
            // If Semester is selected, disable the month dropdown 
            // The backend router will build the S1/S2 test ID automatically
            $('#yearMonth').val('');
            $('#yearMonth').prop('disabled', true);
        }
    });

    // Main Fetch Button Click
    $('#fetchDataBtn').on('click', function() {
        var testType = $("input[name='testType']:checked").val();
        var subject = $('#subject').val();
        var grade = $('#grade').val();
        var yearMonth = $('#yearMonth').val();

        // Validation
        if (!subject) { alert("Please select a subject."); return; }
        if (!grade) { alert("Please select a class."); return; }
        if (testType === 'month' && !yearMonth) { alert("Please select a month for the monthly test."); return; }

        $('#errorMessage').hide();
        $('#workspaceArea').html('<h3 class="text-center text-muted">Loading data...</h3>');

        // Call our new backend router script
        $.ajax({
            dataType: 'json',
            type: 'POST',
            url: 'routeMarksRequest.php',
            data: { subject: subject, grade: grade, yearMonth: yearMonth, testType: testType },
            success: function(response) {
                if (response.status === 'error') {
                    $('#errorMessage').text(response.message).show();
                    $('#workspaceArea').empty();
                    $('#subjectInfoPanel').hide();
                } else {
                    // Update the Info Panel
                    $('#displaySubject').text(response.subjectName);
                    $('#displayGrade').text(grade);
                    $('#displayTestID').text(response.testID);
                    $('#displayLevel').text(response.level);
                    $('#displayMaxima').text(response.maxima);
                    $('#subjectInfoPanel').fadeIn();

                    // Load the returned HTML form into the workspace
                    $('#workspaceArea').html(response.html);
                }
            },
            error: function() {
                alert('A network error occurred while fetching the data.');
                $('#workspaceArea').empty();
            }
        });
    });
});

// Validation function attached to the dynamically loaded input fields
// Validation function attached to the dynamically loaded input fields
// Validation function attached to the dynamically loaded input fields
function overLimit(data, id, max) {
    var n = parseFloat(data);
    var min = -0.01;
    var error = false;
    var errorMsg = "";
    
    var $input = $('#' + id);

    // 1. Reset styles and remove the error-tracking class
    $input.css({"background-color": "white", "color": "black"}).removeClass('invalid-mark');

    // 2. Run checks (Only if the cell is NOT blank. Blank means absent.)
    if (data.trim() !== '') {
        if (isNaN(data)) {
            error = true;
            errorMsg = "Please enter a valid number.";
        } else if (n > parseFloat(max)) {
            error = true;
            errorMsg = "A mark entered (" + n + ") is greater than the maximum allowed (" + max + ").";
        } else if (n < min) {
            error = true;
            errorMsg = "A mark cannot be negative.";
        }
    }

    // 3. Apply UI feedback
    if (error) {
        // Turn the box red and ADD the tracking class
        $input.css({"background-color": "red", "color": "yellow"}).addClass('invalid-mark');
        
        // Make the error banner sticky so it follows you down the page
        $('#errorMessage').css({
            "position": "sticky", 
            "top": "20px", 
            "z-index": "9999",
            "box-shadow": "0px 4px 10px rgba(0,0,0,0.2)"
        }).text(errorMsg).slideDown();
        
    } else {
        // Turn the box green if it has a valid number (leave white if blank)
        if (data.trim() !== '') {
            $input.css({"background-color": "green", "color": "white", "text-align": "center"});
        }
    }

    // 4. Check if we need to lock or unlock the Submit button
    toggleSubmitButton();
}

// Helper function to lock/unlock the form
function toggleSubmitButton() {
    // Find the submit button inside our dynamic form
    var $submitBtn = $('#marksForm button[type="submit"]');
    
    // Count how many input boxes currently have the 'invalid-mark' class
    var errorCount = $('.invalid-mark').length;

    if (errorCount > 0) {
        // If there is even one error, lock the button and change its appearance
        $submitBtn.prop('disabled', true).css("opacity", "0.5").text("Fix red marks to save");
    } else {
        // If all errors are fixed, unlock the button and hide the error banner
        $submitBtn.prop('disabled', false).css("opacity", "1");
        
        // Restore the original text based on whether we are adding or editing
        var isEditMode = $submitBtn.hasClass('btn-warning');
        $submitBtn.text(isEditMode ? 'Update Marks' : 'Save New Marks');
        
        $('#errorMessage').slideUp();
    }
}
</script>
</body>
</html>