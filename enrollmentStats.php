<?php
// require_once "authCheckPIO.php";
// restrictToAdmin();
include "connectDatabase.php"; 

// Force the server to send this strictly as JSON data
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
    WHERE h.Year >= 2010
    GROUP BY h.Year, h.Grade, h.School, s.Gender
    ORDER BY h.Year ASC, h.Grade ASC
";

$result = mysqli_query($dbServer, $query);

// Safely handle query failures
if (!$result) {
    die(json_encode(["error" => "Database query failed."]));
}

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    // The ?? '' operator prevents the trim(null) error caused by the LEFT JOIN
    // If the LEFT JOIN returns NULL, it defaults to an empty string.
    $genderRaw = trim($row['Gender'] ?? '');
    
    // Since we know the DB is strictly F or M, we simplify the logic:
    $gender = ($genderRaw === 'F' || $genderRaw === 'M') ? $genderRaw : 'Unknown';
    
    // Normalize Grade (Remove spaces, uppercase) and protect against nulls
    $grade = strtoupper(str_replace(' ', '', $row['Grade'] ?? ''));
    
    // Store
    $data[] = [
        'Year' => intval($row['Year']),
        'Grade' => $grade,
        'School' => $row['School'],
        'Gender' => $gender,
        'Count' => intval($row['StudentCount'])
    ];
}

// Output the clean JSON
echo json_encode($data);

mysqli_close($dbServer);
exit();
?>