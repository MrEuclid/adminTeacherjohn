<?php
include "../connectDatabase.php" ;

 $grade = $_POST['studentGrade'];




$query  = "SELECT MAX(Year) FROM New_ID_Year_Grade" ;
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$year = $data[0] ;

// echo $year ;

// status is used to set attendance status y, n , p,

$query = "SELECT New_Students.ID AS studentID, 

CONCAT(Khmer_family_name,' ', Khmer_first_name) AS khmerName, gender,
CONCAT(Family_name,' ',First_name) AS englishName, grade,
'N' AS status, 'Y:M:D:H:M' AS time

FROM New_Students
JOIN 
New_ID_Year_Grade
ON New_Students.ID = New_ID_Year_Grade.Student_ID
AND Grade = '$grade'
AND Year = '$year'
ORDER BY Student_ID ASC " ;

// echo "<br>" . $query . "<br>" ;

$result = mysqli_query($dbServer,$query) ;

$output = [] ;
$i = 0 ;

while ($data = mysqli_fetch_assoc($result))
{
	 $output[$i]  = $data;

	$i++ ;
	// print_r($data);
}

header('Content-Type: application/json');
echo json_encode($output);

mysqli_close($dbServer)



?>