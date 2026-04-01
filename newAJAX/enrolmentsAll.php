<?php

include "../connectDatabase.php" ;


$q = "SELECT MAX(Year) FROM New_ID_Year_Grade";
$r = mysqli_query($dbServer,$q);
$d = mysqli_fetch_row($r);
$year = $d[0] ;

$query = "SELECT count(ID) AS N
FROM `New_ID_Year_Grade` 
WHERE Year = '$year'
AND Student_ID NOT IN (SELECT id FROM New_Students WHERE GONE = 'Y')" ;

$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$thisYear = $data[0];
// get all enrolments since 2015

$query = "SELECT count(DISTINCT Student_ID) AS N FROM New_ID_Year_Grade";
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$allTime = $data[0] ; // since 2015
echo  $allTime;



?>
<?php

include "../connectDatabase.php" ;


$q = "SELECT MAX(Year) FROM New_ID_Year_Grade";
$r = mysqli_query($dbServer,$q);
$d = mysqli_fetch_row($r);
$year = $d[0] ;

$query = "SELECT count(ID) AS N
FROM `New_ID_Year_Grade` 
WHERE Year = '$year'
AND Student_ID NOT IN (SELECT id FROM New_Students WHERE GONE = 'Y')" ;

$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$thisYear = $data[0];
// get all enrolments since 2015

$query = "SELECT count(DISTINCT Student_ID) AS N FROM New_ID_Year_Grade";
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$allTime = $data[0] ; // since 2015
echo  $allTime;



?>