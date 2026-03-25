

<?php



//print_r($_GET);
// echo isset($_GET['startdate']) . "+" ;
// echo isset($_GET['startdate']) AND isset($GET_['endDate']) . "===" ;
include "../connectDatabase.php" ;
include "yearMonth.php";

// print_r($dbServer);

//echo "Year = " . $year;
//if (isset($_GET['startdate']) AND isset($GET_['endDate']))
// {

/*
$schoolYear = 2022;
$startDate = '2022-05-01' ;
$endDate = '2022-05-31' ;


*/

 $studentID = $_REQUEST['studentID'];
// $studentID = 4937;

$query = "SELECT MAX(Year) FROM New_ID_Year_Grade";
$result = mysqli_query($dbServer,$query) ;
$data = mysqli_fetch_row($result);
$schoolYear = $data[0] ;


// echo "School year" . $schoolYear;

$d1 = $schoolYear-1 . '-10-01';
$d2 = $schoolYear . '-09-30';

// pre-filtering using temporary tables

$query = "CREATE temporary table ns as
(SELECT New_Students.ID AS SID, 
    concat(Family_name,' ', First_name) as english, Gender,Grade,Date_birth
FROM New_Students 
JOIN New_ID_Year_Grade 
ON New_ID_Year_Grade.Student_ID = New_Students.ID
AND Year = '$schoolYear'
AND Gone = 'N'
GROUP BY New_Students.ID
ORDER BY  New_Students.id) ";

mysqli_query($dbServer,$query);

$query = "SELECT * FROM  ns LIMIT 10";
// include "print_query_data_plain.php" ;

// echo "<br>" . $query . "<br>" ;
$query = "CREATE temporary table a as (
SELECT  DISTINCT studentID, shortDate  AS d, 
case when status = 'Y' then 'present' else 'absent' end as status 

FROM attendance

WHERE shortTime < '16:00'
AND shortDate >= '$d1' 
AND shortDate <= '$d2'

)";

mysqli_query($dbServer,$query);

$query = "SELECT * from a LIMIT 10";

// include "print_query_data_plain.php" ;

 // echo "<br>" . $query . "<br>" ;

$query = "SELECT studentID,english,Grade,CAST(d AS DATE) AS date ,status
            FROM a
            JOIN ns
            ON ns.SID = a.StudentID
            AND studentID = '$studentID'
      
ORDER BY d  DESC";

// echo "<br>" . $query . "<br>" ;

$result = mysqli_query($dbServer,$query);

$cnt = 0;
$output = [];

WHILE ($data = mysqli_fetch_assoc($result))
{
$output[$cnt] = $data;
$cnt++ ;
}

echo json_encode($output);

mysqli_close($dbServer);

//include "print_query_data_plain.php" ;

?>

