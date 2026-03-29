<?php
require_once "../authCheckPIO.php";
restrictToAdmin();
include "../connectDatabase.php"; 

// Get POST variables sent from the AJAX request
$subject = trim($_POST["subject"]);
$grade = trim($_POST["grade"]);
$testType = trim($_POST["testType"]);
$yearMonth = trim($_POST["yearMonth"]); // Might be empty if Semester

// 1. Calculate the base Year
// If a month is selected, extract year from it. Otherwise, use current year logic.
if (!empty($yearMonth)) {
    $year = substr($yearMonth, 0, 4);
} else {
    $year = date("Y");
    $month = date("m");
    if ($month > 9) { $year = $year + 1; }
}

// 2. Determine the precise testID
if ($testType == 'month') {
    $targetTestID = $yearMonth;
} else {
    // It's a semester test (S1 or S2)
    $targetTestID = $year . "-" . $testType; 
}

// 3. Check if marks already exist for this class, subject, and test
$queryCheck = "SELECT count(*) as count FROM hsMarks 
               WHERE subjectID = '$subject' 
               AND testID = '$targetTestID'
               AND studentID IN (SELECT Student_ID FROM New_ID_Year_Grade WHERE Year = '$year' AND Grade = '$grade')";
$resultCheck = mysqli_query($dbServer, $queryCheck);
$dataCheck = mysqli_fetch_assoc($resultCheck);
$marksExist = ($dataCheck['count'] > 0);

// 4. Calculate Level 
$letters = array("A", "B", "C", "D","E","F");
$digits = array(0,1,2);
$l = strlen($grade);
$lastChar = substr($grade, $l-1, 1);
$level = "";

if ($l == 2) { $level = $lastChar; }
if ($l == 3 && in_array($lastChar, $digits)) { $level = substr($grade, 1, 2); }
if ($l == 3 && in_array($lastChar, $letters)) { $level = substr($grade, 1, 1); }
if ($l == 4 && in_array($lastChar, $letters)) { $level = substr($grade, 1, 2); }

if ($grade == 'G12A') { $level = '12SOC'; }
if ($grade == 'G12B') { $level = '12SCI'; }
if ($grade == 'G11A') { $level = '11SOC'; }
if ($grade == 'G11B') { $level = '11SCI'; }

// 5. Get Subject Name & Maxima
$querySub = "SELECT english from hsSubjects WHERE code = '$subject'";
$resultSub = mysqli_query($dbServer, $querySub);
$subData = mysqli_fetch_row($resultSub);
$englishName = $subData[0];

$queryMax = "SELECT max from hsMaxima WHERE subjectCode = '$subject' AND level = '$level'";
$resultMax = mysqli_query($dbServer, $queryMax);
if (mysqli_num_rows($resultMax) > 0) {
    $maxData = mysqli_fetch_row($resultMax);
    $maxima = $maxData[0];
} else {
    // Failsafe if maxima isn't set in DB
    echo json_encode(['status' => 'error', 'message' => "Error: No maxima set for $subject at level $level."]);
    exit();
}

// 6. Generate the HTML Form
$htmlResponse = "";

if ($marksExist) {
    // --- MODE: EDIT MARKS ---
    $htmlResponse .= "<div class='alert alert-info text-center'><strong>Note:</strong> Marks already exist for this test. You are now in <strong>Edit Mode</strong>.</div>";
    
    // Fetch existing marks
    $queryStudents = "SELECT New_Students.ID AS ID, 
                      concat(khmer_family_name,' ',khmer_first_name) AS Khmer,
                      concat(Family_name, ' ',First_name) AS English, hsMarks.mark as currentMark
                      FROM New_Students
                      JOIN New_ID_Year_Grade ON New_Students.ID = New_ID_Year_Grade.Student_ID
                      JOIN hsMarks ON New_Students.ID = hsMarks.studentID
                      WHERE School = 'PIOHS' AND Year = '$year' AND Grade = '$grade' 
                      AND hsMarks.subjectID = '$subject' AND hsMarks.testID = '$targetTestID'
                      ORDER BY New_Students.ID";
                      
    $actionUrl = "writeUpdatedMarks.php"; 
    $buttonText = "Update Marks";
    $buttonClass = "btn-warning";
    $isEditMode = true;

} else {
    // --- MODE: ADD NEW MARKS ---
    $htmlResponse .= "<div class='alert alert-success text-center'>Ready to add new marks.</div>";
    
    // Fetch empty students list
    $queryStudents = "SELECT New_Students.ID AS ID, 
                      concat(khmer_family_name,' ',khmer_first_name) AS Khmer,
                      concat(Family_name, ' ',First_name) AS English, '' as currentMark
                      FROM New_Students
                      JOIN New_ID_Year_Grade ON New_Students.ID = New_ID_Year_Grade.Student_ID
                      WHERE School = 'PIOHS' AND Year = '$year' AND Grade = '$grade'
                      ORDER BY New_Students.ID";
                      
    $actionUrl = "addHSMarksToDB.php";
    $buttonText = "Save New Marks";
    $buttonClass = "btn-success";
    $isEditMode = false;
}

$resultStudents = mysqli_query($dbServer, $queryStudents);

// Build the form HTML
$htmlResponse .= "<form action='$actionUrl' method='POST' id='marksForm'>";
$htmlResponse .= "<input type='hidden' name='subject' value='$subject'>";
$htmlResponse .= "<input type='hidden' name='test' value='$targetTestID'>";
$htmlResponse .= "<input type='hidden' name='grade' value='$grade'>";
$htmlResponse .= "<input type='hidden' name='maxima' value='$maxima'>";

// Table Headers
$htmlResponse .= "<div class='row' style='font-weight:bold; border-bottom:2px solid #ccc; margin-bottom:10px; padding-bottom:5px;'>";
$htmlResponse .= "<div class='col-sm-2 text-right'>ID</div>";
$htmlResponse .= "<div class='col-sm-3'>Khmer Name</div>";
$htmlResponse .= "<div class='col-sm-3 text-right'>English Name</div>";

if ($isEditMode) {
    $htmlResponse .= "<div class='col-sm-2 text-center text-muted'>Old Mark</div>";
    $htmlResponse .= "<div class='col-sm-2 text-center'>New Mark</div>";
} else {
    $htmlResponse .= "<div class='col-sm-4 text-center'>Mark</div>";
}
$htmlResponse .= "</div>";

// Student Rows
$idCounter = 0;
while ($data = mysqli_fetch_assoc($resultStudents)) {
    $m_id = "m-" . $idCounter;
    $val = $data['currentMark'];
    
    $htmlResponse .= "<div class='row' style='margin-bottom:5px; border-bottom:1px solid #eee; padding-bottom:5px;'>";
    $htmlResponse .= "<div class='col-sm-2 text-right pink' style='padding-top:6px;'>{$data['ID']}";
    $htmlResponse .= "<input type='hidden' name='student[]' value='{$data['ID']}'>";
    $htmlResponse .= "</div>";
    $htmlResponse .= "<div class='col-sm-3 highlight' style='padding-top:6px;'>{$data['Khmer']}</div>";
    $htmlResponse .= "<div class='col-sm-3 highlight text-right' style='padding-top:6px;'>{$data['English']}</div>";
    
    // Conditionally show the old mark in a read-only box
    if ($isEditMode) {
        $htmlResponse .= "<div class='col-sm-2 text-center'>";
        // tabindex='-1' prevents the Tab key from stopping on this read-only input
        $htmlResponse .= "<input type='text' class='form-control text-center text-muted' value='$val' readonly tabindex='-1' style='width:60px; display:inline-block; background-color:#e9ecef;'>";
        $htmlResponse .= "<input type='hidden' name='oldMarks[]' value='$val'>"; 
        $htmlResponse .= "</div>";
        $htmlResponse .= "<div class='col-sm-2 text-center'>";
    } else {
        $htmlResponse .= "<div class='col-sm-4 text-center'>";
    }
    
    // The editable mark box
    $htmlResponse .= "<input class='pink form-control' id='$m_id' type='text' value='$val' onblur='overLimit(this.value, this.id, $maxima)' name='mark[]' style='width:80px; display:inline-block;'>";
    $htmlResponse .= "</div>";
    $htmlResponse .= "</div>";
    
    $idCounter++;
}

$htmlResponse .= "<div class='row' style='margin-top: 20px;'><div class='col-sm-12 text-center'>";
$htmlResponse .= "<button type='submit' class='btn $buttonClass btn-lg' onclick='$(this).hide();'>$buttonText</button>";
$htmlResponse .= "</div></div></form>";

// 7. Return the JSON payload
echo json_encode([
    'status' => 'success',
    'subjectName' => $englishName,
    'testID' => $targetTestID,
    'level' => $level,
    'maxima' => $maxima,
    'html' => $htmlResponse
]);
?>