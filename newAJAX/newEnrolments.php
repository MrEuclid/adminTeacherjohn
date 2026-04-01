<?php

include "../connectDatabase.php" ;

$q = "SELECT MAX(Year) FROM New_ID_Year_Grade";
$r = mysqli_query($dbServer,$q);
$d = mysqli_fetch_row($r);
$year = $d[0] ;

$previousYear = $year -1 ;

$query = "SELECT sum(case when Student_ID NOT IN 
	(SELECT Student_ID FROM New_ID_Year_Grade WHERE Year <= '$previousYear') then 1 else 0 end) as New
FROM `New_ID_Year_Grade` 
WHERE Year = '$year'" ;

$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
echo $data[0];
?>