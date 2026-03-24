

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
    concat(Family_name,' ', First_name) as english, Gender,Grade
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


$query = "CREATE temporary table a as (
SELECT  DISTINCT studentID, substr(shortDate,1,7) as d,

sum(case when (status = 'Y'  OR status = 'P' ) then 1 else 0 end) AS here,
sum(case when status = 'N' then 1 else 0 end) AS abs,

round(100*sum(case when status = 'Y' then 1 else 0 end) / (sum(case when status = 'Y' then 1 else 0 end) + 
    sum(case when status = 'N' then 1 else 0 end)),0) AS percent

FROM attendance


WHERE shortTime < '16:00'
AND shortDate >= '$d1' 
AND shortDate <= '$d2'
GROUP BY studentID,substr(shortDate,1,7)

)";

mysqli_query($dbServer,$query);

// $query = "SELECT * from a LIMIT 10";

// include "print_query_data_plain.php" ;


$query = "SELECT studentID,english,Gender,Grade,d as date,here as Present,  abs as Absent , percent
            FROM a
            JOIN ns
            ON ns.SID = a.StudentID
      
ORDER BY cast(substr(Grade,2,2) as SIGNED) , Grade, percent ";

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

