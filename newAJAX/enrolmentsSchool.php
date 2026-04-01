<?php

include "../connectDatabase.php" ;

$q = "SELECT MAX(Year) FROM New_ID_Year_Grade";
$r = mysqli_query($dbServer,$q);
$d = mysqli_fetch_row($r);
$year = $d[0] ;

 $school = $_POST['school'] ;
// $school = 'PIOHS' ;

$query = "SELECT count(ID) AS N
FROM `New_ID_Year_Grade` 
WHERE Year = '$year'
AND school = '$school'
AND Student_ID NOT IN (SELECT id FROM New_Students WHERE GONE = 'Y')" ;

$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
echo $data[0];

// include "../includes/print_query_data_plain.php" ;

?>