<?php
// sponsorData.php - Final Production Version
// -------------------------------------------------------------
// SECURITY CONFIGURATION
session_start();
$donorSecretKey = "PIO_Impact_Report_2026_Secure"; // CHANGE THIS if needed

$isLoggedIn = isset($_SESSION['user_id']); 
$hasKey = (isset($_GET['key']) && $_GET['key'] === $donorSecretKey);

if (!$isLoggedIn && !$hasKey) {
    http_response_code(403);
    die(json_encode(["error" => "Access Denied."]));
}
// -------------------------------------------------------------

require_once "connectDatabase.php"; 
// header('Content-Type: application/json');

// Performance Settings
ini_set('memory_limit', '512M'); 
set_time_limit(300);             
mysqli_report(MYSQLI_REPORT_OFF);

// 1. SETUP
$currentYear = date("Y");
$latestDataYear = $currentYear; 

$yearCheck = mysqli_query($dbServer, "SELECT MAX(Year) as MaxYear FROM New_ID_Year_Grade");
if ($row = mysqli_fetch_assoc($yearCheck)) {
    $latestDataYear = intval($row['MaxYear']);
}

$response = [
    'min_year' => $latestDataYear, 
    'max_year' => $latestDataYear,
    'enrollment' => [],
    'schools_found' => [],
    'age_dist' => [], 
    'retention' => [],
    'attendance' => [],
    'kpi' => [],
    'debug' => []
];

// =========================================================
// 2. PRE-LOAD LOOKUP MAPS
// =========================================================
$genderMap = []; 
$qBio = "SELECT ID, Gender, Date_birth, Gone FROM New_Students";
$rBio = mysqli_query($dbServer, $qBio);
while($row = mysqli_fetch_assoc($rBio)) {
    $gRaw = strtoupper(trim($row['Gender']));
    $g = 'Unknown';
    if (strpos($gRaw, 'F') === 0 || strpos($gRaw, 'G') === 0) $g = 'Girls';
    elseif (strpos($gRaw, 'M') === 0 || strpos($gRaw, 'B') === 0) $g = 'Boys';
    
    $goneRaw = isset($row['Gone']) ? strtoupper(trim($row['Gone'])) : 'N';
    
    $genderMap[$row['ID']] = [
        'g' => $g,
        'dob' => $row['Date_birth'],
        'gone' => $goneRaw
    ];
}

$schoolMap = [];
$qSch = "SELECT Year, Student_ID, School FROM New_ID_Year_Grade WHERE Year >= 2010";
$rSch = mysqli_query($dbServer, $qSch);
while($row = mysqli_fetch_assoc($rSch)) {
    $y = intval($row['Year']);
    $sid = $row['Student_ID'];
    $schoolMap[$y][$sid] = strtoupper(trim($row['School']));
}

// =========================================================
// 3. ENROLLMENT & AGE
// =========================================================
$ageTemplate = ['4-5'=>0, '6-7'=>0, '8-9'=>0, '10-11'=>0, '12-13'=>0, '14-15'=>0, '16-17'=>0, '18+'=>0];
mysqli_data_seek($rSch, 0); 

while($row = mysqli_fetch_assoc($rSch)) {
    $y = intval($row['Year']);
    if ($y < $response['min_year']) $response['min_year'] = $y;

    $school = strtoupper(trim($row['School']));
    if (empty($school)) continue;
    if (!in_array($school, $response['schools_found'])) $response['schools_found'][] = $school;

    $sid = $row['Student_ID'];
    $studentData = $genderMap[$sid] ?? [];
    $gender = $studentData['g'] ?? 'Unknown';
    $isGone = ($studentData['gone'] ?? 'N') === 'Y';

    // Exclude 'Gone' students from current year count
    if ($y == $latestDataYear && $isGone) {
        continue;
    }

    // Enrollment
    if (!isset($response['enrollment'][$y][$school][$gender])) $response['enrollment'][$y][$school][$gender] = 0;
    $response['enrollment'][$y][$school][$gender]++;

    // Age
    $dob = $studentData['dob'] ?? null;
    if ($dob && $dob != '0000-00-00') {
        $birthYear = intval(substr($dob, 0, 4)); 
        if ($birthYear > 1990 && $birthYear <= $y) {
            $age = $y - $birthYear; 
            $bucket = '18+';
            if ($age <= 5) $bucket = '4-5';
            elseif ($age <= 7) $bucket = '6-7';
            elseif ($age <= 9) $bucket = '8-9';
            elseif ($age <= 11) $bucket = '10-11';
            elseif ($age <= 13) $bucket = '12-13';
            elseif ($age <= 15) $bucket = '14-15';
            elseif ($age <= 17) $bucket = '16-17';

            if (!isset($response['age_dist'][$y][$school])) $response['age_dist'][$y][$school] = $ageTemplate;
            $response['age_dist'][$y][$school][$bucket]++;
        }
    }
}

// =========================================================
// 4. RETENTION (Fail-Safe Year-Over-Year Calculation)
// =========================================================
// We use a single query with a LEFT JOIN to calculate retention for ALL years at once.
// It matches students in Year X with Year X+1. 
// It safely ignores Grade 12 students and avoids calculating retention for the current year.

$queryRetention = "
    SELECT 
        t1.Year AS Start_Year,
        t1.School,
        COUNT(DISTINCT t1.Student_ID) AS Total_Started,
        COUNT(DISTINCT t2.Student_ID) AS Total_Returned
    FROM New_ID_Year_Grade t1
    LEFT JOIN New_ID_Year_Grade t2 
        ON t1.Student_ID = t2.Student_ID 
        AND t2.Year = (t1.Year + 1)
    WHERE t1.Grade NOT LIKE 'G12%' 
      AND t1.Grade != '12'
      AND t1.Year < $latestDataYear 
    GROUP BY t1.Year, t1.School
";

$resultRetention = mysqli_query($dbServer, $queryRetention);

if ($resultRetention) {
    while ($row = mysqli_fetch_assoc($resultRetention)) {
        $retentionYear = intval($row['Start_Year']);
        $school = strtoupper(trim($row['School']));
        $started = intval($row['Total_Started']);
        $returned = intval($row['Total_Returned']);
        
        // Calculate percentage safely
        $retentionRate = ($started > 0) ? round(($returned / $started) * 100, 1) : 0;
        
        // Store in JSON response array
        $response['retention'][$retentionYear][$school] = $retentionRate;
    }
}

// Sort the years chronologically just in case the SQL returns them out of order
ksort($response['retention']);


// =========================================================
// 5. ATTENDANCE
// =========================================================
$qAtt = "SELECT studentID, shortDate, Status 
         FROM attendance 
         WHERE shortDate >= '2022-01-01'
         ORDER BY ID ASC"; 

$rAtt = mysqli_query($dbServer, $qAtt);
$dailyStatus = []; 

if ($rAtt) {
    while($row = mysqli_fetch_assoc($rAtt)) {
        $dailyStatus[$row['studentID']][$row['shortDate']] = strtoupper($row['Status']);
    }
}

$attStats = [];
foreach($dailyStatus as $sid => $dates) {
    $gender = $genderMap[$sid]['g'] ?? 'Unknown';
    foreach($dates as $date => $status) {
        $year = intval(substr($date, 0, 4));
        $school = $schoolMap[$year][$sid] ?? null;
        if (!$school) continue; 

        if (!isset($attStats[$year][$school][$gender])) {
            $attStats[$year][$school][$gender] = ['p' => 0, 't' => 0];
        }
        $attStats[$year][$school][$gender]['t']++;
        if (in_array($status, ['Y', 'L', 'P'])) { 
            $attStats[$year][$school][$gender]['p']++;
        }
    }
}

foreach($attStats as $y => $schools) {
    foreach($schools as $sch => $genders) {
        foreach($genders as $gen => $counts) {
            $pct = ($counts['t'] > 0) ? round(($counts['p'] / $counts['t']) * 100, 1) : 0;
            $response['attendance'][$y][$sch][$gen] = $pct;
        }
    }
}

// =========================================================
// 6. KPIs (Basic Global Fallbacks)
// =========================================================
$kpiTotal = 0; $kpiGirls = 0; $kpiKnown = 0;
if (isset($response['enrollment'][$latestDataYear])) {
    foreach($response['enrollment'][$latestDataYear] as $sch => $genders) {
        $t = array_sum($genders);
        $kpiTotal += $t;
        $kpiGirls += ($genders['Girls'] ?? 0);
        $kpiKnown += ($genders['Girls'] ?? 0) + ($genders['Boys'] ?? 0);
    }
}
$response['kpi']['total_students'] = $kpiTotal;
$response['kpi']['girl_percentage'] = ($kpiKnown > 0) ? round(($kpiGirls/$kpiKnown)*100, 1) : 0;

$attSum = 0; $attCount = 0;
if (isset($response['attendance'][$latestDataYear])) {
    foreach($response['attendance'][$latestDataYear] as $sch => $genders) {
        foreach($genders as $val) {
            $attSum += $val; $attCount++;
        }
    }
}
$response['kpi']['avg_attendance'] = $attCount ? round($attSum/$attCount, 1) : 0;

echo json_encode($response, JSON_NUMERIC_CHECK);
?>