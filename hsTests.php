 <?php

include "../connectDatabase.php" ;
 include "../yearMonth.php";



$sub = array()   ;



$month = date("m") ;
$day = date("d");


 $year = date("Y") ;
$month = date("m") ;
$day = date("d");
 
if ($month > 9) 
  {
    $y = $year ; // keeps real year
    $year = $year + 1; 

  } 

include "yearMonth.php" ;

// bind subjects to year in javascript file 
// echo $schoolYear ;
 $year = date("Y") ;
$startYear = $schoolYear ;

$ignore = "('HOME','TECH','LIFE','PE')";

$querySub = "SELECT DISTINCT code FROM hsSubjects WHERE 
code IN (select subjectID FROM hsMarks) 
AND code NOT IN " . $ignore  ;

// echo "<br>" . $querySub . "<br>";
$resultSub = mysqli_query($dbServer,$querySub) ;
$i = 0 ;
while ($data = mysqli_fetch_row($resultSub))
{$sub[$i] = $data[0] ;
$i++ ;}


$query = "SELECT  
New_Students.ID AS studentID, 
concat(New_Students.Family_name, ' ',
New_Students.First_name) AS Student , 
concat(Khmer_family_name,' ',Khmer_first_name) AS Khmer, Gender,Gone,
Grade,testID as date , ";
$l = count($sub) ;
$total = 0;

foreach ($sub as $s) 
{
$q = "" ;
$q = "sum(case when subjectID = '$s' then mark else 0 end) AS '$s' " . " ," ;	
$query = $query . $q ;
}

// add sum

//$y = 2024;
// $y = $startYear;
$q = " sum(mark) AS Total" ;
$query = $query . $q ; 

$q = " FROM `hsMarks` 
JOIN New_ID_Year_Grade
ON hsMarks.studentID = New_ID_Year_Grade.Student_ID
JOIN New_Students ON
New_Students.ID = hsMarks.studentID
AND School = 'PIOHS'  
AND Year = '$schoolYear'


AND cast(substr(testID,1,4) AS SIGNED) IN ( '$schoolYear','$currentYear')

AND testID NOT IN  (concat('$currentYear','-','SEM1'),concat('$currentYear','-','SEM2'))
AND testID NOT IN (
concat('$currentYear','-','01'),
concat('$currentYear','-','02'),
concat('$currentYear','-','03'),
concat('$currentYear','-','04'),
concat('$currentYear','-','05'),
concat('$currentYear','-','06'),
concat('$currentYear','-','07'),
concat('$currentYear','-','08'),
concat('$currentYear','-','09')
)


GROUP BY studentID, testID

ORDER BY Grade,New_Students.ID, testID   DESC ";

$query = $query . $q ;

//  echo "<br>" . $query . "<br>" ;
//$result = mysqli_query($dbServer,$query);

$queryT1 = "CREATE TEMPORARY table t1 " . $query;
mysqli_query($dbServer,$queryT1);

$query = "SELECT * FROM t1 WHERE  Gone <> 'Y' " ;

// include "print_query_data_plain.php";
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

 ?>
