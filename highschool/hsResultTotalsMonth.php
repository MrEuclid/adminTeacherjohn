<?php

include "../connectDatabase.php" ;

$sub = array()   ;
$year = date("Y") ;
$month = date("m") ;

// include "yearMonth.php";

 $year = date("Y") ;
$month = date("m") ;
$day = date("d");
 
if ($month > 9) 
  {
    $y = $year ; // keeps real year
    $year = $year + 1; 

  } 

$testID = $_POST['yearMonth'] ;
$grade = $_POST['grade'] ;

/*
$testID = '2022-01' ;
$grade = 'G10A' ;
*/

$querySub = "SELECT code FROM hsSubjects WHERE 
code IN (select subjectID FROM hsMarks where substr(testID,1,4) = '$year')" ;

 // echo "<br>" . $querySub . "<br>";
$resultSub = mysqli_query($dbServer,$querySub) ;
$i = 0 ;
while ($data = mysqli_fetch_row($resultSub))
{$sub[$i] = $data[0] ;
$i++ ;}

// print_r($data);
// echo $year . "-" . $month ;
// if ($month == "10" OR $month == "11" OR $month == "12") {$year = $year + 1 ;} else {$year = $year ;}

$query = "SELECT  
New_Students.ID, 
concat(New_Students.Family_name, ' ',
New_Students.First_name) AS Student , 
concat(Khmer_family_name,' ',Khmer_first_name) AS Khmer,
Grade,testID , ";
$l = count($sub) ;

foreach ($sub as $s) 
{
$q = "" ;
$q = "sum(case when subjectID = '$s' then round(mark,0) else 0 end) AS '$s' " . " ," ;	
$query = $query . $q ;
}

// add sum

$q = " round(sum(mark),0) AS Total" ;
$query = $query . $q ; 

$q = " FROM `hsMarks` 
JOIN New_ID_Year_Grade
ON hsMarks.studentID = New_ID_Year_Grade.Student_ID
JOIN New_Students ON
New_Students.ID = hsMarks.studentID
AND School = 'PIOHS'  


AND Year = '$year'
AND testID = '$testID'
AND Grade = '$grade'
GROUP BY New_Students.ID

ORDER BY Grade,subjectID , concat(Khmer_Family_name,'-',Khmer_First_name)";

$query = $query . $q ;

// echo "<br>" . $query . "<br>" ;
//$result = mysqli_query($dbServer,$query);

//echo json_encode($output) ;
include "../print_query_data_plain.php" ;
//  include "makeJSONFromQuery.php" ;
?>