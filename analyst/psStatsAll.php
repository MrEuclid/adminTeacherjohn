<?php
// FILE: analyst/psStatsAll.php
// PURPOSE: Primary School Statistics (Raw Marks)
// LOGIC: Raw Percentage (Mark out of 10 -> %) without T-Score normalization

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../connectDatabase.php";
include "../yearMonth.php"; 

if (!isset($schoolYear)) { $schoolYear = date("Y"); }

// 1. DATE LOGIC (Same as High School)
$prevYear = $schoolYear - 1;
$dateCondition = " (
    m.testID LIKE '$schoolYear-%' OR 
    m.testID LIKE '$prevYear-10%' OR 
    m.testID LIKE '$prevYear-11%' OR 
    m.testID LIKE '$prevYear-12%' 
) ";

// 2. CORE SUBJECTS
// We group marks by these prefixes. 
// Example: "ENG-Reading" and "ENG-Writing" become just "ENG"
$coreSubjects = ['ENG', 'KH', 'MAT', 'SOC', 'SCI'];

// 3. GET ACTIVE GRADES (SMC Primary Only)
$qGrades = "SELECT DISTINCT nyg.Grade 
            FROM psMarks m
            JOIN New_ID_Year_Grade nyg ON m.Student_ID = nyg.Student_ID
            WHERE $dateCondition
            AND nyg.Year = '$schoolYear'
            AND nyg.School = 'SMC' 
            ORDER BY nyg.Grade ASC";

$rGrades = mysqli_query($dbServer, $qGrades);
$grades = [];
if ($rGrades) {
    while ($r = mysqli_fetch_assoc($rGrades)) $grades[] = $r['Grade'];
}

$byTest = []; 

// 4. MAIN DATA FETCH
foreach ($grades as $g) {
    
    // Fetch Marks
    $q = "SELECT 
            m.Student_ID, 
            m.testID,
            m.Subject,
            m.Mark,
            nyg.Grade
          FROM psMarks m
          JOIN New_ID_Year_Grade nyg ON m.Student_ID = nyg.Student_ID
          WHERE $dateCondition 
            AND nyg.Year = '$schoolYear' 
            AND nyg.Grade = '$g'
            AND nyg.School = 'SMC'
          ORDER BY m.Student_ID, m.testID";
          
    $res = mysqli_query($dbServer, $q);
    if (!$res) continue;

    $students = [];
    
    while($row = mysqli_fetch_assoc($res)) {
        $sid = $row['Student_ID'];
        $tid = $row['testID'];
        $subRaw = $row['Subject']; // e.g., "ENG-Read"
        
        // --- SUBJECT GROUPING LOGIC ---
        // We need to find which core bucket this falls into
        $baseSub = null;
        foreach ($coreSubjects as $core) {
            // Check if subject starts with ENG, KH, etc.
            if (strpos($subRaw, $core) === 0) {
                $baseSub = $core;
                break;
            }
        }
        
        if (!$baseSub) continue; // Skip non-core subjects (Sport, Art, etc.)
        
        $key = $sid . "_" . $tid;
        if (!isset($students[$key])) {
            $students[$key] = [
                'Student_ID' => $sid,
                'testID' => $tid,
                'Grade'  => $g,
                'scores' => [] // Store arrays of scores per subject
            ];
        }
        
        // Store the mark in the subject bucket
        if (!isset($students[$key]['scores'][$baseSub])) {
            $students[$key]['scores'][$baseSub] = [];
        }
        $students[$key]['scores'][$baseSub][] = floatval($row['Mark']);
    }

    // --- AGGREGATION LOGIC ---
    foreach($students as $key => $data) {
        $totalSum = 0;
        $subjectCount = 0;
        
        // Calculate average for each subject (e.g. Avg of ENG-Read & ENG-Write)
        foreach ($data['scores'] as $sub => $marks) {
            if (count($marks) > 0) {
                $subAvg = array_sum($marks) / count($marks);
                
                // Normalization: 
                // If mark is <= 10, it's out of 10.
                // If mark is > 10, it's likely out of 50 or 100.
                // WE STANDARDIZE EVERYTHING TO A 0-10 SCALE FOR CALCULATION
                if ($subAvg > 10) {
                    if ($subAvg <= 50) $subAvg = $subAvg / 5; // Convert 50 -> 10
                    else $subAvg = $subAvg / 10; // Convert 100 -> 10
                }
                
                $totalSum += $subAvg;
                $subjectCount++;
            }
        }
        
        // Final Percentage Calculation
        // If they have 5 subjects, max score is 50.
        // Percentage = (TotalSum / (SubjectCount * 10)) * 100
        // Which simplifies to: (TotalSum / SubjectCount) * 10
        
        if ($subjectCount > 0) {
            $avgOutOf10 = $totalSum / $subjectCount;
            $data['finalPct'] = $avgOutOf10 * 10; // Convert 7.5 -> 75%
        } else {
            $data['finalPct'] = 0;
        }
        
        $tid = $data['testID'];
        if (!isset($byTest[$tid])) $byTest[$tid] = [];
        $byTest[$tid][] = $data;
    }
}

// 5. OUTPUT
$finalOutput = [];

foreach($byTest as $tid => $testRows) {
    
    // Sort High to Low (Standard ranking)
    usort($testRows, function($a, $b) {
        if ($a['finalPct'] == $b['finalPct']) return 0;
        return ($a['finalPct'] < $b['finalPct']) ? 1 : -1;
    });

    foreach($testRows as $data) {
        $rawScore = $data['finalPct'];

        $row = [];
        $row["StudentID"]    = $data['Student_ID'];
        $row["Grade"]        = $data['Grade'];
        $row["TestID"]       = $data['testID'];
        $row["Subject"]      = "Total"; 
        
        // KEY DIFFERENCE: Only output 'Percentage'. 
        // We intentionally OMIT 'StdPercentage' so the dashboard uses this raw value.
        $row["Percentage"]   = round($rawScore, 1); 
        
        $finalOutput[] = $row;
    }
}

echo json_encode($finalOutput);
?>