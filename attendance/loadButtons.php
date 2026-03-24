<?php
// 1. Turn on Output Buffering to "trap" any accidental blank spaces or PHP notices
ob_start();

include "../connectDatabase.php" ;

$grade = $_POST['studentGrade'];

$query  = "SELECT MAX(Year) FROM New_ID_Year_Grade" ;
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$year = $data[0] ;

$query = "SELECT New_Students.ID AS studentID, 
CONCAT(Khmer_family_name,' ', Khmer_first_name) AS khmerName, gender,
CONCAT(Family_name,' ',First_name) AS englishName, grade,
'N' AS status, 'Y:M:D:H:M' AS time
FROM New_Students
JOIN New_ID_Year_Grade
ON New_Students.ID = New_ID_Year_Grade.Student_ID
AND Grade = '$grade'
AND Year = '$year'
ORDER BY Student_ID ASC " ;

$result = mysqli_query($dbServer,$query) ;

$output = [] ;
$i = 0 ;

while ($data = mysqli_fetch_assoc($result))
{
     $output[$i]  = $data;
    $i++ ;
}

// 2. Erase any trapped blank lines, HTML, or errors from the buffer
ob_clean();

// 3. Set the header and send ONLY the pure JSON data
header('Content-Type: application/json');
echo json_encode($output);

// 4. Close connection and strictly exit so no trailing whitespace is added
mysqli_close($dbServer);
exit();
?>