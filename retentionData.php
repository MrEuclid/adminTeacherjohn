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

include "connectDatabase.php"; 
// header('Content-Type: application/json');

// ==============================================================================
// 🛑 DATABASE CONFIGURATION 🛑
// ==============================================================================
$gradeTable    = "New_ID_Year_Grade"; 
$studentTable  = "New_Students";          
$joinCol1      = "Student_ID";         
$joinCol2      = "id";         
$dobColumn     = "Date_birth";               
$yearColumn    = "Year";              
$gradeColumn   = "Grade";             
$genderColumn  = "Gender"; // NEW: Update this to your gender column name
// ==============================================================================

$response = [
    'yearlyData' => [],
    'cohorts' => [] 
];

// Added s.$genderColumn to the query
$query = "
    SELECT 
        g.$joinCol1 AS studentID,
        g.$yearColumn AS enrollYear, 
        g.$gradeColumn AS gradeLabel, 
        s.$dobColumn AS dateOfBirth,
        s.$genderColumn AS gender
    FROM $gradeTable g
    JOIN $studentTable s ON g.$joinCol1 = s.$joinCol2
    WHERE s.$dobColumn IS NOT NULL AND s.$dobColumn != '0000-00-00'
";

$result = mysqli_query($dbServer, $query);

if (!$result) {
    echo json_encode(['error' => 'Database Query Failed: ' . mysqli_error($dbServer)]);
    exit;
}

$enrollmentsByYear = []; 
$studentHistory = []; 
$studentGender = []; // NEW: Fast lookup for a student's gender

while ($row = mysqli_fetch_assoc($result)) {
    $yearStr   = $row['enrollYear']; 
    $gradeStr  = $row['gradeLabel']; 
    $dobStr    = $row['dateOfBirth'];
    $studentID = $row['studentID'];
    
    // Save gender (normalized to uppercase first letter, e.g., 'M' or 'F')
    $studentGender[$studentID] = strtoupper(substr(trim($row['gender']), 0, 1));
    
    $enrollYearInt = (int)substr(trim($yearStr), 0, 4);
    if ($enrollYearInt < 2000) continue; 
    
    $gradeLevel = (int)preg_replace('/[^0-9]/', '', $gradeStr);
    if ($gradeLevel === 0) continue; 

    // --- OVERAGE CALCULATION ---
    $birthYear = (int)date('Y', strtotime($dobStr));
    $ageDuringYear = $enrollYearInt - $birthYear;
    $expectedAge = $gradeLevel + 6;
    $isOverage = ($ageDuringYear > $expectedAge);

    if (!isset($response['yearlyData'][$enrollYearInt])) {
        $response['yearlyData'][$enrollYearInt] = [];
    }
    if (!isset($response['yearlyData'][$enrollYearInt][$gradeStr])) {
        $response['yearlyData'][$enrollYearInt][$gradeStr] = ['typical' => 0, 'overage' => 0];
    }
    
    if ($isOverage) {
        $response['yearlyData'][$enrollYearInt][$gradeStr]['overage']++;
    } else {
        $response['yearlyData'][$enrollYearInt][$gradeStr]['typical']++;
    }

    // --- BUILD STUDENT HISTORY ---
    $enrollmentsByYear[$enrollYearInt][] = $studentID;
    $studentHistory[$studentID][$enrollYearInt] = $gradeLevel;
}

// --- UNIVERSAL COHORT MATH ---
$cohortBase = []; 

foreach ($studentHistory as $studentID => $yearsAttended) {
    $entryYear = min(array_keys($yearsAttended));
    $entryGrade = $yearsAttended[$entryYear];
    $cohortBase[$entryYear][$entryGrade][] = $studentID;
}

ksort($enrollmentsByYear);

foreach ($cohortBase as $baseYear => $grades) {
    ksort($grades); 
    foreach ($grades as $baseGrade => $students) {
        $uniqueStudents = array_unique($students);
        $baseTotalCount = count($uniqueStudents);
        
        if ($baseTotalCount < 2) continue; 
        
        // Count starting Boys vs Girls
        $baseM = 0; $baseF = 0;
        foreach($uniqueStudents as $id) {
            if ($studentGender[$id] === 'M') $baseM++;
            if ($studentGender[$id] === 'F') $baseF++;
        }
        
        foreach ($enrollmentsByYear as $targetYear => $allStudentsInTargetYear) {
            if ($targetYear >= $baseYear) {
                $retainedStudents = array_intersect($uniqueStudents, $allStudentsInTargetYear);
                $retainedTotalCount = count($retainedStudents);
                
                // Count retained Boys vs Girls
                $retM = 0; $retF = 0;
                foreach($retainedStudents as $id) {
                    if ($studentGender[$id] === 'M') $retM++;
                    if ($studentGender[$id] === 'F') $retF++;
                }
                
                $response['cohorts'][$baseYear][$baseGrade][$targetYear] = [
                    'total' => [
                        'count' => $retainedTotalCount,
                        'pct' => round(($retainedTotalCount / $baseTotalCount) * 100, 1)
                    ],
                    'M' => [
                        'count' => $retM,
                        'pct' => $baseM > 0 ? round(($retM / $baseM) * 100, 1) : 0
                    ],
                    'F' => [
                        'count' => $retF,
                        'pct' => $baseF > 0 ? round(($retF / $baseF) * 100, 1) : 0
                    ]
                ];
            }
        }
    }
}

echo json_encode($response);
?>