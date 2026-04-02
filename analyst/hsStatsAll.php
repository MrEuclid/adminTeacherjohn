<?php
// hsStatsAll.php - Anonymous Statistical Engine
// Folder: analyst/ (Requires ../ to reach includes)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FIX 1: Corrected Paths
include "../connectDatabase.php"; 
include "../yearMonth.php"; 

if (!isset($schoolYear)) { $schoolYear = date("Y"); }

// FIX 2: Date Logic (Ensures we catch the "Tail" of previous year)
// Matches: "2026-", "2025-10", "2025-11", "2025-12"
$prevYear = $schoolYear - 1;
$dateCondition = " (
    m.testID LIKE '$schoolYear-%' OR 
    m.testID LIKE '$prevYear-10%' OR 
    m.testID LIKE '$prevYear-11%' OR 
    m.testID LIKE '$prevYear-12%' 
) ";

// FIX 3: Updated Non-Academic Subjects
$nonAcademic = ['PE', 'LIFE', 'HOME', 'TECH', 'Sport'];

// --- HELPER: T-SCORE CALCULATION ---
function getStandardizedScore($rawScore, $rawMean, $rawSD) {
    if ($rawSD == 0) return $rawScore; 
    $z = ($rawScore - $rawMean) / $rawSD;
    $calcScore = 65 + ($z * 10); // Target Mean 65, SD 10
    if ($calcScore > 100) return 100;
    if ($calcScore < 0) return 0;
    return $calcScore;
}

// 1. MAXIMA CACHE
$maximaCache = []; 
$qMaxAll = "SELECT level, subjectCode, max FROM hsMaxima";
$rMaxAll = mysqli_query($dbServer, $qMaxAll);
if ($rMaxAll) {
    while ($row = mysqli_fetch_assoc($rMaxAll)) {
        $l = trim($row['level']); 
        $s = trim($row['subjectCode']);
        $m = intval($row['max']);
        if (!isset($maximaCache[$l])) $maximaCache[$l] = [];
        $maximaCache[$l][$s] = $m;
        
        if (is_numeric($l)) {
            $intVal = intval($l);
            $maximaCache[(string)$intVal][$s] = $m;
            $maximaCache["0".$intVal][$s] = $m;
            $maximaCache["G".$intVal][$s] = $m;
        }
    }
}

// 2. GET ACTIVE GRADES
// We use the JOIN to ensure we only process grades that have Marks AND Enrollment
$qGrades = "SELECT DISTINCT nyg.Grade 
            FROM hsMarks m
            JOIN New_ID_Year_Grade nyg ON m.studentID = nyg.Student_ID
            WHERE $dateCondition
            AND nyg.Year = '$schoolYear'
            ORDER BY nyg.Grade ASC";
// echo "<br>" . $qGrades . "<br>" ;
$rGrades = mysqli_query($dbServer, $qGrades);
$grades = [];
if ($rGrades) {
    while ($r = mysqli_fetch_assoc($rGrades)) $grades[] = $r['Grade'];
}

$byTest = []; 

// 3. MAIN DATA FETCH
foreach ($grades as $g) {
    
    // We only need Student_ID (Anonymous) + Grade
    $q = "SELECT 
            m.studentID, 
            m.testID,
            m.subjectID,
            m.Mark,
            nyg.Grade
          FROM hsMarks m
          JOIN New_ID_Year_Grade nyg ON m.studentID = nyg.Student_ID
          WHERE $dateCondition 
            AND nyg.Year = '$schoolYear' 
            AND nyg.Grade = '$g'
          ORDER BY m.studentID, m.testID";
 //  echo "<br>" .$q . "<br>"   ;    
    $res = mysqli_query($dbServer, $q);
    
    if (!$res) continue;

    $students = [];
    
    while($row = mysqli_fetch_assoc($res)) {
        $sid = $row['studentID'];
        $tid = $row['testID'];
        $sub = $row['subjectID'];
        
        // Skip non-academic
        if (in_array($sub, $nonAcademic)) continue;
        
        $key = $sid . "_" . $tid;
        if (!isset($students[$key])) {
            $students[$key] = [
                'Student_ID' => $sid,
                'testID' => $tid,
                'Grade'  => $g,
                'total_mark' => 0,
                'total_max'  => 0
            ];
        }
        
        // Maxima Lookup
        $max = 100;
        if (isset($maximaCache[$g][$sub])) {
            $max = $maximaCache[$g][$sub];
        } elseif (isset($maximaCache[str_replace("G","",$g)][$sub])) {
             $max = $maximaCache[str_replace("G","",$g)][$sub];
        }

        $students[$key]['total_mark'] += floatval($row['Mark']);
        $students[$key]['total_max'] += $max;
    }

    // Convert to Percentages
    foreach($students as $key => $data) {
        if ($data['total_max'] > 0) {
            $data['finalPct'] = ($data['total_mark'] / $data['total_max']) * 100;
        } else {
            $data['finalPct'] = 0;
        }
        $tid = $data['testID'];
        if (!isset($byTest[$tid])) $byTest[$tid] = [];
        $byTest[$tid][] = $data;
    }
}

// 4. STATISTICAL TRANSFORMATION
$finalOutput = [];

foreach($byTest as $tid => $testRows) {
    
    // PASS 1: Calculate Mean & SD
    $scores = array_column($testRows, 'finalPct');
    $count = count($scores);
    
    if ($count > 0) {
        $rawMean = array_sum($scores) / $count;
        $variance = 0.0;
        foreach ($scores as $s) $variance += pow($s - $rawMean, 2);
        $divisor = ($count > 1) ? ($count - 1) : 1;
        $rawSD = sqrt($variance / $divisor);
    } else {
        $rawMean = 0; $rawSD = 0;
    }

    // PASS 2: Sort, Normalize, Output
    usort($testRows, function($a, $b) {
        if ($a['finalPct'] == $b['finalPct']) return 0;
        return ($a['finalPct'] < $b['finalPct']) ? 1 : -1;
    });

    foreach($testRows as $data) {
        $rawScore = $data['finalPct'];
        $stdScore = getStandardizedScore($rawScore, $rawMean, $rawSD);

        $row = [];
        $row["StudentID"]    = $data['Student_ID'];
        $row["Grade"]        = $data['Grade'];
        $row["TestID"]       = $data['testID'];
        $row["Subject"]      = "Total"; 
        $row["Percentage"]   = round($rawScore, 1);
        $row["StdPercentage"]= round($stdScore, 1);
        
        $finalOutput[] = $row;
    }
}

echo json_encode($finalOutput);
?>