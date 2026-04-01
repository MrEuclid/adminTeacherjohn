<?php

include "../connectDatabase.php" ;


$q = "SELECT MAX(Year) FROM New_ID_Year_Grade";
$r = mysqli_query($dbServer,$q);
$d = mysqli_fetch_row($r);
$year = $d[0] ;

$query = "SELECT count(ID) AS N
FROM `New_ID_Year_Grade` 
WHERE Year = '$year'
AND Student_ID IN (SELECT id FROM New_Students WHERE Gone <> 'Y' AND Shelter = 'Y')
AND School <> 'UNI' ";
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
echo $data[0];
?>