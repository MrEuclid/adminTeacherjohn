<?php 
require_once "../authCheckPIO.php";
restrictToAdmin();
include "../connectDatabase.php"; 
// include_once "../print_query_data_plain.php"; // Uncomment if you want to use the DataTable function we built earlier

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Extract POST variables from the router form
$maxima = $_POST['maxima'];
$test = trim($_POST["test"]);
$subject = trim($_POST["subject"]);
$grade = trim($_POST["grade"]);

$studentID = $_POST["student"]; // Array of IDs
$oldMark = $_POST["oldMarks"];  // Array of original marks
$newMark = $_POST["mark"];      // Array of edited marks

// Find the Year for this class based on the first student
$sid = $studentID[0];
$queryYear = "SELECT Year FROM New_ID_Year_Grade WHERE Student_ID = '$sid' ORDER BY Year DESC LIMIT 1";
$resultYear = mysqli_query($dbServer, $queryYear);
$dataYear = mysqli_fetch_row($resultYear);
$year = $dataYear[0];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Marks Updated</title>
    <link href='https://fonts.googleapis.com/css?family=Khmer' rel='stylesheet' type='text/css'>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: Arial, 'Khmer', sans-serif; }
        .control-panel { background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-sm-12 text-left">
            <?php include "menu.html"; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="control-panel">
                <h2 class="text-center text-success">Marks Successfully Updated</h2>
                <h4 class="text-center">Test: <?php echo $test; ?> | Subject: <?php echo $subject; ?> | Class: <?php echo $grade; ?></h4>
                <hr>

                <?php 
                $l = count($newMark);
                $updatedCount = 0;
                $marksPercent = [];

                // Process the updates
                for ($i = 0; $i < $l; $i++) {
                    $student = $studentID[$i];
                    $nM = $newMark[$i];
                    
                    // Allow blank strings to be processed if they deleted a mark (optional, depending on your DB rules)
                    // We only run the query if the mark actually changed
                    if ($nM != $oldMark[$i]) {
                        
                        // Prevent division by zero error if maxima is 0
                        if ($maxima > 0 && is_numeric($nM)) {
                            $marksPercent[$i] = ($nM * 100) / $maxima;
                        } else {
                            $marksPercent[$i] = 0;
                        }

                        // Use prepared statements or escaping here if possible, but keeping your original SQL structure
                        $queryUpdate = "UPDATE `hsMarks` SET mark= '$nM' 
                                        WHERE studentID = '$student' AND subjectID = '$subject' AND testID = '$test'";
                        mysqli_query($dbServer, $queryUpdate);
                        $updatedCount++;
                    } else {
                        // Keep track of percentages even if the mark didn't change this round
                        if ($maxima > 0 && is_numeric($nM)) {
                            $marksPercent[$i] = ($nM * 100) / $maxima;
                        } else {
                            $marksPercent[$i] = 0;
                        }
                    }
                } 
                ?>

                <div class="alert alert-info text-center">
                    Successfully modified <strong><?php echo $updatedCount; ?></strong> records out of <?php echo $l; ?> students.
                </div>

                <!-- RESULTS SECTION -->
                <div class="row mt-4">
                    <div class="col-sm-8 col-sm-offset-2">
                        <?php
                        $cnt = 0;
                        $sum = 0;
                        $dns = 0; // Did not sit

                        $a = 0; $b = 0; $c = 0; $d = 0; $e = 0; $f = 0;

                        // Process array marks for statistics
                        for ($i = 0; $i < $l; $i++) {
                            // Check if the mark is actually numeric and not empty string
                            if (is_numeric($newMark[$i]) && $newMark[$i] !== '') {
                                $cnt++; 
                                $sum += $marksPercent[$i];
                                
                                if ($marksPercent[$i] < 50) { $f++; }
                                elseif ($marksPercent[$i] >= 50 && $marksPercent[$i] < 60) { $e++; }
                                elseif ($marksPercent[$i] >= 60 && $marksPercent[$i] < 70) { $d++; }
                                elseif ($marksPercent[$i] >= 70 && $marksPercent[$i] < 80) { $c++; }
                                elseif ($marksPercent[$i] >= 80 && $marksPercent[$i] < 90) { $b++; }
                                elseif ($marksPercent[$i] >= 90) { $a++; }
                            } else {
                                $dns++;
                            }
                        }

                        if ($cnt > 0) {
                            $passed = $a + $b + $c + $d + $e;
                            $passRate = round(100 * $passed / $cnt, 0);
                            $average = round($sum / $cnt, 0);
                        ?>
                            
                        <div class="panel panel-default">
                            <div class="panel-heading"><h3 class="panel-title text-center">Class Statistics</h3></div>
                            <div class="panel-body text-center">
                                <p><strong>Total Valid Results:</strong> <?php echo $cnt; ?></p>
                                <p><strong>Passed:</strong> <?php echo $passed; ?> (<?php echo $passRate; ?>%)</p>
                                <p><strong>Did Not Sit:</strong> <?php echo $dns; ?></p>
                                <hr>
                                <h4>Grade Breakdown</h4>
                                <ul class="list-unstyled">
                                    <li><strong>A:</strong> <?php echo $a; ?> (<?php echo round(100 * $a / $cnt, 0); ?>%)</li>
                                    <li><strong>B:</strong> <?php echo $b; ?> (<?php echo round(100 * $b / $cnt, 0); ?>%)</li>
                                    <li><strong>C:</strong> <?php echo $c; ?> (<?php echo round(100 * $c / $cnt, 0); ?>%)</li>
                                    <li><strong>D:</strong> <?php echo $d; ?> (<?php echo round(100 * $d / $cnt, 0); ?>%)</li>
                                    <li><strong>E:</strong> <?php echo $e; ?> (<?php echo round(100 * $e / $cnt, 0); ?>%)</li>
                                    <li class="text-danger"><strong>F:</strong> <?php echo $f; ?> (<?php echo round(100 * $f / $cnt, 0); ?>%)</li>
                                </ul>
                                <hr>
                                <h4><strong>Class Average:</strong> <?php echo $average; ?>%</h4>
                            </div>
                        </div>

                        <?php 
                        } else {
                            echo "<div class='alert alert-warning text-center'>No valid marks were entered to calculate statistics.</div>";
                        }
                        ?>

                        <div class="text-center" style="margin-top: 20px;">
                            <a href="indexHighSchool.php" class="btn btn-primary">Return to Dashboard</a>
                        </div>
                    </div>
                </div> <!-- /row -->

            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>