<?php
// === SECURITY & SETUP ===
session_start();
$donorSecretKey = "PIO_Impact_Report_2026_Secure"; 

$isLoggedIn = isset($_SESSION['user_id']); 
$urlKey = isset($_GET['key']) ? $_GET['key'] : '';

if (!$isLoggedIn && $urlKey !== $donorSecretKey) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

include "../connectDatabase.php"; // Adjust path if needed
header('Content-Type: application/json');

// ==============================================================================
// 🛑 DATABASE CONFIGURATION 🛑
// Update these variables to match your exact database table and column names!
// ==============================================================================
$gradeTable    = "New_ID_Year_Grade"; // Your table linking students to years/grades
$studentTable  = "New_Students";          // Your main table holding student details
$joinCol1      = "Student_ID";         // ID column in the grade table
$joinCol2      = "id";         // ID column in the student table
$dobColumn     = "Date_birth";               // Column name for Date of Birth (e.g., 'DOB' or 'DateOfBirth')
$yearColumn    = "Year";              // Column for the academic year in the grade table
$gradeColumn   = "Grade";             // Column for the class/grade in the grade table
// ==============================================================================

// Create the final data structure
$response = [
    'yearlyData' => []
];

// Query to get every student's enrollment year, grade, and date of birth
$query = "
    SELECT 
        g.$yearColumn AS enrollYear, 
        g.$gradeColumn AS gradeLabel, 
        s.$dobColumn AS dateOfBirth
    FROM $gradeTable g
    JOIN $studentTable s ON g.$joinCol1 = s.$joinCol2
    WHERE s.$dobColumn IS NOT NULL AND s.$dobColumn != '0000-00-00'
";

$result = mysqli_query($dbServer, $query);

if (!$result) {
    echo json_encode(['error' => 'Database Query Failed: ' . mysqli_error($dbServer)]);
    exit;
}

while ($row = mysqli_fetch_assoc($result)) {
    $yearStr   = $row['enrollYear']; // E.g., "2025" or "2025-2026"
    $gradeStr  = $row['gradeLabel']; // E.g., "G1", "Grade 2", "3"
    $dobStr    = $row['dateOfBirth'];
    
    // 1. Extract the actual starting year as an integer (Handles "2025" or "2025-2026")
    $enrollYearInt = (int)substr(trim($yearStr), 0, 4);
    if ($enrollYearInt < 2000) continue; // Skip invalid years
    
    // 2. Extract the numeric Grade Level from the string
    // This turns "Grade 4" -> 4, "G12" -> 12, "3" -> 3
    $gradeLevel = (int)preg_replace('/[^0-9]/', '', $gradeStr);
    
    // If no number is found (e.g., Kindergarten), you can either skip or assign a level (like 0)
    if ($gradeLevel === 0) continue; 

    // 3. Calculate the student's age AT THE TIME of that specific enrollment year
    $birthYear = (int)date('Y', strtotime($dobStr));
    $ageDuringYear = $enrollYearInt - $birthYear;
    
    // 4. Apply the Overage Logic: Age > (Grade Level + 5)
    $expectedAge = $gradeLevel + 5;
    $isOverage = ($ageDuringYear > $expectedAge);

    // 5. Build the nested JSON structure
    // Initialize the Year if it doesn't exist yet
    if (!isset($response['yearlyData'][$yearStr])) {
        $response['yearlyData'][$yearStr] = [];
    }
    
    // Initialize the Grade for that Year if it doesn't exist yet
    if (!isset($response['yearlyData'][$yearStr][$gradeStr])) {
        $response['yearlyData'][$yearStr][$gradeStr] = [
            'typical' => 0,
            'overage' => 0
        ];
    }
    
    // Increment the appropriate counter
    if ($isOverage) {
        $response['yearlyData'][$yearStr][$gradeStr]['overage']++;
    } else {
        $response['yearlyData'][$yearStr][$gradeStr]['typical']++;
    }
}

// Output the final JSON for the dashboard to render
echo json_encode($response);
?>