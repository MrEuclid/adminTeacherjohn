<?php
// psStatsAll.php - Final Version (Restricted to SMC Primary)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../connectDatabase.php";
include "../yearMonth.php"; 

// 1. SETTINGS
if (!isset($schoolYear)) { $schoolYear = date("Y"); } 
$prevYear = $schoolYear - 1; 

// Valid Date Patterns (Oct 2025 onwards)
$validDatePatterns = ["$schoolYear-", "$prevYear-10", "$prevYear-11", "$prevYear-12"];

// The 5 Core Subjects
$targetSubjects = ['ENG', 'KH', 'MAT', 'SOC', 'SCI'];

// 2. PRE-CALC TENURE
$studentTenure = [];
$rTenure = mysqli_query($dbServer, "SELECT Student_ID, COUNT(DISTINCT Year) as c FROM New_ID_Year_Grade GROUP BY Student_ID");
if ($rTenure) {
    while ($row = mysqli_fetch_assoc($rTenure)) {
        $studentTenure[trim($row['Student_ID'])] = $row['c'];
    }
}

// 3. THE QUERY
// Added: AND n.School = 'SMC' to remove High Schoolers
$query = "SELECT 
            n.Student_ID, 
            CONCAT(s.Family_name,' ',s.First_name) as Name,
            s.Gender,
            n.Grade,
            m.testID, 
            m.subjectID, 
            m.mark
          FROM New_ID_Year_Grade n
          JOIN New_Students s ON s.id = n.Student_ID
          LEFT JOIN psMarks m ON (
              TRIM(n.Student_ID) = TRIM(m.studentID) 
              AND (
                  m.testID LIKE '%$schoolYear-%' OR 
                  m.testID LIKE '%$prevYear-10%' OR 
                  m.testID LIKE '%$prevYear-11%' OR 
                  m.testID LIKE '%$prevYear-12%'
              )
          )
          WHERE n.Year = '$schoolYear' 
            AND n.School = 'SMC'
          ORDER BY n.Grade ASC, m.testID DESC";

$result = mysqli_query($dbServer, $query);

// 4. PROCESS DATA
$aggData = []; 

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        
        $sid = trim($row['Student_ID']);
        $tid = empty($row['testID']) ? "No_Marks_Recorded" : $row['testID'];

        // Initialize Student
        if (!isset($aggData[$tid][$sid])) {
            $aggData[$tid][$sid] = [
                'Info' => [
                    'Name' => $row['Name'], 
                    'Gender' => $row['Gender'], 
                    'Grade' => $row['Grade']
                ],
                'Subs' => []
            ];
        }

        // Add Mark
        if (isset($row['mark']) && $row['mark'] !== null && $row['mark'] >= 0) {
            $subID = trim(strtoupper($row['subjectID']));
            foreach ($targetSubjects as $base) {
                if (strpos($subID, $base) === 0) {
                    $aggData[$tid][$sid]['Subs'][$base][] = floatval($row['mark']);
                    break; 
                }
            }
        }
    }
}

// 5. CALCULATE & OUTPUT
$stats = [];

foreach ($aggData as $tid => $students) {
    foreach ($students as $sid => $data) {
        $totalSum = 0;
        
        foreach ($targetSubjects as $baseSub) {
            $avg = 0;
            if (isset($data['Subs'][$baseSub])) {
                $comps = $data['Subs'][$baseSub];
                if (count($comps) > 0) $avg = array_sum($comps) / count($comps);
            }
            $totalSum += $avg;

            $stats[] = [
                'StudentID' => $sid,
                'Name'      => $data['Info']['Name'],
                'Gender'    => $data['Info']['Gender'],
                'Grade'     => $data['Info']['Grade'],
                'TestID'    => $tid,
                'Subject'   => $baseSub,
                'Mark'      => round($avg, 2),
                'Maxima'    => 10,
                'Percentage'=> round(($avg / 10) * 100, 0),
                'Rank'      => 0,
                'YearsEnrolled' => isset($studentTenure[$sid]) ? $studentTenure[$sid] : 1
            ];
        }

        // Total
        $stats[] = [
            'StudentID' => $sid,
            'Name'      => $data['Info']['Name'],
            'Gender'    => $data['Info']['Gender'],
            'Grade'     => $data['Info']['Grade'],
            'TestID'    => $tid,
            'Subject'   => 'Total',
            'Mark'      => round($totalSum, 2),
            'Maxima'    => 50,
            'Percentage'=> round(($totalSum / 50) * 100, 0),
            'Rank'      => 0,
            'YearsEnrolled' => isset($studentTenure[$sid]) ? $studentTenure[$sid] : 1
        ];
    }
}

// header('Content-Type: application/json');
echo json_encode($stats, JSON_NUMERIC_CHECK);
?>