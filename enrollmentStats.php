<?php
// enrollmentStats.php
require_once "authCheckPIO.php";
restrictToAdmin();
include "connectDatabase.php"; 
// header('Content-Type: application/json');

// Performance settings
ini_set('memory_limit', '256M');

// 1. We need to join History with Student Bio to get Gender
// We Group By Year, Grade, and Gender to get the counts
$query = "
    SELECT 
        h.Year, 
        h.Grade, 
        h.School,
        s.Gender,
        COUNT(h.Student_ID) as StudentCount
    FROM New_ID_Year_Grade h
    LEFT JOIN New_Students s ON h.Student_ID = s.ID
    WHERE h.Year >= 2015
    GROUP BY h.Year, h.Grade, h.School, s.Gender
    ORDER BY h.Year ASC, h.Grade ASC
";

$result = mysqli_query($dbServer, $query);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Normalize Gender

  
    $gRaw = strtoupper((string)($row['Gender']));
    $gender = 'Unknown';
    if (in_array($gRaw, ['F', 'GIRL', 'GIRLS', 'FEMALE'])) $gender = 'F';
    elseif (in_array($gRaw, ['M', 'BOY', 'BOYS', 'MALE'])) $gender = 'M';
    
    // Normalize Grade (Remove spaces, uppercase)
    $grade = strtoupper(str_replace(' ', '', $row['Grade']));
    
    // Store
    $data[] = [
        'Year' => intval($row['Year']),
        'Grade' => $grade,
        'School' => $row['School'],
        'Gender' => $gender,
        'Count' => intval($row['StudentCount'])
    ];
}

echo json_encode($data);
?>