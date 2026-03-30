
<?php
include "../connectDatabase.php" ;
include "../date_data.php" ;


$query = "SELECT max(Year) FROM New_ID_Year_Grade";
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$year = $data[0] ;

$query = "SELECT DISTINCT CONCAT(Grade , '-' , School) AS schoolGrade FROM New_ID_Year_Grade

WHERE Year = '$year'  
AND School <> 'UNI'
ORDER BY School, Grade ASC" ;

$result = mysqli_query($dbServer,$query);
$output = [];
while ($data = mysqli_fetch_row($result))
{
	$output[] = $data[0];
}

echo json_encode($output);

mysqli_close($dbServer);

?>