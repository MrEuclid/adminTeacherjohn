<?php
// hsStatsAll.php - Numeric Fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../connectDatabase.php";
include "../yearMonth.php"; 

if (!isset($schoolYear)) { $schoolYear = date("Y"); }
$y = $schoolYear - 1;
$pastYear = "(" . "'" . $y . "-10" . "'" . "," . "'" . $y. "-11'" . "," . "'". $y . "-12'" . ")" ;

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
            $v1 = (string)$intVal;     
            $v2 = "0" . $intVal;       
            $v3 = "G" . $intVal;       
            
            if (!isset($maximaCache[$v1])) $maximaCache[$v1] = [];
            $maximaCache[$v1][$s] = $m;
            if (!isset($maximaCache[$v2])) $maximaCache[$v2] = [];
            $maximaCache[$v2][$s] = $m;
            if (!isset($maximaCache[$v3])) $maximaCache[$v3] = [];
            $maximaCache[$v3][$s] = $m;
        }
    }
}

// 2. HELPER
function getBaseLevel($g) {
    if ($g == 'G11A') return '11SOC';
    if ($g == 'G11B') return '11SCI';
    if ($g == 'G12A') return '12SOC';
    if ($g == 'G12B') return '12SCI';
    $clean = str_replace("G", "", $g); 
    return (string)intval($clean);
}

// 3. FETCH LISTS
$grade = [];
$q = "SELECT DISTINCT Grade FROM New_ID_Year_Grade WHERE Year = '$schoolYear' ORDER BY Grade";
$r = mysqli_query($dbServer, $q);
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $grade[] = $row['Grade']; } }

$subject = [];
$q = "SELECT DISTINCT subjectID FROM hsMarks ORDER BY subjectID"; 
$r = mysqli_query($dbServer, $q);
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $subject[] = $row['subjectID']; } }

// 4. PRE-CALC YEARS
$studentTenure = [];
$qTenure = "SELECT Student_ID, COUNT(DISTINCT Year) as yearsCount FROM New_ID_Year_Grade GROUP BY Student_ID";
$rTenure = mysqli_query($dbServer, $qTenure);
if ($rTenure) {
    while ($row = mysqli_fetch_assoc($rTenure)) {
        $cleanID = trim($row['Student_ID']);
        $studentTenure[$cleanID] = $row['yearsCount'];
    }
}

$stats = []; 
$gradeMaximaSum = []; 

// --- LOOP A: SUBJECTS ---
foreach ($grade as $g) {
    if (!isset($gradeMaximaSum[$g])) { $gradeMaximaSum[$g] = 0; }
    $lev = getBaseLevel($g);

    foreach ($subject as $s) {
        $maxima = 0;
        $sTrim = trim($s);
        if (isset($maximaCache[$lev][$sTrim])) $maxima = $maximaCache[$lev][$sTrim];
        
        $query = "SELECT 
                    New_ID_Year_Grade.Student_ID, 
                    CONCAT(Family_name,' ',First_name) as Name,
                    New_Students.Gender,
                    hsMarks.testID, 
                    hsMarks.subjectID, 
                    hsMarks.mark
                  FROM hsMarks 
                  JOIN New_ID_Year_Grade ON (hsMarks.studentID = New_ID_Year_Grade.Student_ID)
                  JOIN New_Students ON (New_Students.id = New_ID_Year_Grade.Student_ID)
                  WHERE Year = '$schoolYear'
                    AND (substr(testID,1,4) = '$schoolYear' OR substr(testID,1,7) IN $pastYear)
                    AND Grade = '$g' 
                    AND subjectID = '$s'
                    AND subjectID NOT IN ('PE', 'LIFE', 'HOME', 'TECH')
                  ORDER BY hsMarks.testID"; 

        $result = mysqli_query($dbServer, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $rawRows = [];
            $highestMark = 0;
            while ($data = mysqli_fetch_assoc($result)) {
                if ($data['mark'] > $highestMark) $highestMark = $data['mark'];
                $rawRows[] = $data;
            }
            if ($maxima == 0) $maxima = ($highestMark > 50) ? 100 : 50;
            if (!in_array($s, ['LIFE','HOME','PE','TECH'])) $gradeMaximaSum[$g] += $maxima;

            $byTest = [];
            foreach($rawRows as $r) {
                $tid = $r['testID'];
                $r['finalPct'] = ($maxima > 0) ? ($r['mark'] / $maxima) * 100 : 0;
                $byTest[$tid][] = $r;
            }

            foreach($byTest as $tid => $testRows) {
                // Strict Numeric Sort
                usort($testRows, function($a, $b) {
                    if ($a['finalPct'] == $b['finalPct']) return ($a['mark'] < $b['mark']) ? 1 : -1;
                    return ($a['finalPct'] < $b['finalPct']) ? 1 : -1;
                });

                $rank = 0; $rowNum = 0; $lastPct = -1;
                foreach($testRows as $data) {
                    $rowNum++;
                    if (abs($data['finalPct'] - $lastPct) > 0.001) $rank = $rowNum; 
                    $lastPct = $data['finalPct'];

                    $row = [];
                    $row["StudentID"] = $data['Student_ID'];
                    $row["Rank"]      = $rank; // Should be Int
                    $row["Name"]      = $data['Name'];
                    $row["Gender"]    = $data['Gender'];
                    $row["Grade"]     = $g;
                    $row["TestID"]    = $data['testID'];
                    $row["Subject"]   = $data['subjectID'];
                    $row["Mark"]      = $data['mark']; // Should be Int/Float
                    $row["Maxima"]    = $maxima;
                    $row["Percentage"] = round($data['finalPct'], 0); // Should be Int
                    
                    $sid = trim($data['Student_ID']);
                    $row["YearsEnrolled"] = isset($studentTenure[$sid]) ? $studentTenure[$sid] : 1;

                    array_push($stats, $row);
                }
            }
        }
    }
}

// --- LOOP B: TOTALS ---
foreach ($grade as $g) {
    $grandMax = isset($gradeMaximaSum[$g]) ? $gradeMaximaSum[$g] : 1;
    $query = "SELECT 
                New_ID_Year_Grade.Student_ID, 
                CONCAT(Family_name,' ',First_name) as Name,
                New_Students.Gender,
                hsMarks.testID, 
                'Total' as subjectID, 
                SUM(hsMarks.mark) as total_mark
              FROM hsMarks 
              JOIN New_ID_Year_Grade ON (hsMarks.studentID = New_ID_Year_Grade.Student_ID)
              JOIN New_Students ON (New_Students.id = New_ID_Year_Grade.Student_ID)
              WHERE Year = '$schoolYear'
                AND (substr(testID,1,4) = '$schoolYear' OR substr(testID,1,7) IN $pastYear)
                AND Grade = '$g' 
                AND subjectID NOT IN ('LIFE','HOME','PE','TECH') 
              GROUP BY hsMarks.testID, New_ID_Year_Grade.Student_ID
              ORDER BY hsMarks.testID"; 

    $result = mysqli_query($dbServer, $query);

    if ($result) {
        $rawRows = [];
        while ($data = mysqli_fetch_assoc($result)) { $rawRows[] = $data; }

        $byTest = [];
        foreach($rawRows as $r) {
            $tid = $r['testID'];
            $r['finalPct'] = ($grandMax > 0) ? ($r['total_mark'] / $grandMax) * 100 : 0;
            $byTest[$tid][] = $r;
        }

        foreach($byTest as $tid => $testRows) {
            usort($testRows, function($a, $b) {
                if ($a['finalPct'] == $b['finalPct']) return ($a['total_mark'] < $b['total_mark']) ? 1 : -1;
                return ($a['finalPct'] < $b['finalPct']) ? 1 : -1;
            });

            $rank = 0; $rowNum = 0; $lastPct = -1;
            foreach($testRows as $data) {
                $rowNum++;
                if (abs($data['finalPct'] - $lastPct) > 0.001) $rank = $rowNum; 
                $lastPct = $data['finalPct'];

                $row = [];
                $row["StudentID"] = $data['Student_ID'];
                $row["Rank"]      = $rank;
                $row["Name"]      = $data['Name'];
                $row["Gender"]    = $data['Gender'];
                $row["Grade"]     = $g; 
                $row["TestID"]    = $data['testID'];
                $row["Subject"]   = "Total"; 
                $row["Mark"]      = $data['total_mark']; 
                $row["Maxima"]    = $grandMax;
                $row["Percentage"] = round($data['finalPct'], 0);

                $sid = trim($data['Student_ID']);
                $row["YearsEnrolled"] = isset($studentTenure[$sid]) ? $studentTenure[$sid] : 1;

                array_push($stats, $row);
            }
        }
    }
}

// OUTPUT: Force Numbers to be Numbers!
header('Content-Type: application/json');
echo json_encode($stats, JSON_NUMERIC_CHECK);
?>