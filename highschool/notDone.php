<?php 

// missing marks

include "../connectDatabase.php" ;
 include "yearMonth.php";

 $query = "CREATE temporary table hsGrades as 
 SELECT DISTINCT Grade from New_ID_Year_Grade 

 where Year = '$schoolYear' 
 AND School = 'PIOHS' ";



 // echo "<br>" . $query . "<br>";

 mysqli_query($dbServer,$query);
$query = "SELECT * FROM hsGrades ORDER BY Grade";
// include "print_query_data_plain.php";

 

$query = "CREATE temporary table hsSubjects as 
 SELECT DISTINCT subjectID  from hsMarks 
 WHERE substr(testID,1,4) > 2023 
 AND subjectID  NOT IN ('HOME','LIFE','PE','TECH')
  ORDER BY subjectID";


 // echo "<br>" . $query . "<br>";

 mysqli_query($dbServer,$query); 
$query = "SELECT * FROM hsSubjects";
//include "print_query_data_plain.php";

$query = "CREATE temporary table allData AS 

SELECT
    Grade,subjectID,CONCAT(hsGrades.Grade, '-',hsSubjects.subjectID) AS grade_subject
FROM
    hsGrades
CROSS JOIN
    hsSubjects" ;

// echo "<br>" . $query . "<br>";
 mysqli_query($dbServer,$query); 

 $query = "SELECT *  FROM allData";
// include "print_query_data_plain.php";


$query = "SELECT DISTINCT concat(Grade,'-',subjectID) as gs,Grade,subjectID,testID
FROM New_ID_Year_Grade
JOIN hsMarks
     ON New_ID_Year_Grade.Student_ID = hsMarks.studentID
   WHERE Year = '$schoolYear'
  
 AND subjectID  NOT IN ('HOME','LIFE','PE','TECH')

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
  GROUP BY Grade,testID,subjectID";
 

 mysqli_query($dbServer,$query) ;

 $query = "SELECT * FROM allData LIMIT 5";


// include "print_query_data_plain.php";

 // add Grade to hsMarks
$query = "CREATE temporary table marksHS AS SELECT Grade,testID,subjectID,count(mark), concat(Grade,'-',subjectID) as cncat
FROM New_ID_Year_Grade
JOIN hsMarks
     ON New_ID_Year_Grade.Student_ID = hsMarks.studentID
   WHERE Year = '$schoolYear'
 AND subjectID  NOT IN ('HOME','LIFE','PE','TECH')

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
  GROUP BY Grade,testID,subjectID";

mysqli_query($dbServer,$query);
// echo "<br>" . $query . "<br>";
$query = "SELECT * FROM marksHS LIMIT 5";
// include "print_query_data_plain.php";

 // get test IDs for the $schoolYear

 $query = "CREATE temporary table test_ids AS SELECT DISTINCT testID FROM hsMarks 

 WHERE cast(substr(testID,1,4) AS SIGNED) IN ( '$schoolYear','$currentYear')

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
)";

// echo "<br>" . $query . "<br>";
mysqli_query($dbServer,$query);

$query = "SELECT * FROM test_ids";

//include "print_query_data_plain.php" ;

/*
-- 1. Generate all possible required combinations (Grade, subjectID, testID)
--    by combining the reference tables (allData and test_ids).
-- 2. Use a LEFT JOIN to attempt to match these combinations against the actual
--    marks recorded in the hsMarks table.
-- 3. Filter for records where the match failed (i.e., hsMarks.testID IS NULL).

// for each testID find Grade, subjectID which are in allDate but not in 
*/
$query = "

SELECT
    T.testID,
    A.Grade,
    A.subjectID
FROM
    
    allData AS A
CROSS JOIN
    
    test_ids AS T
LEFT JOIN
    
    marksHS AS M ON
        M.testID = T.testID
        AND M.Grade = A.Grade
        AND M.subjectID = A.subjectID
WHERE
    
    M.testID IS NULL
GROUP BY
    T.testID,
    A.Grade,
    A.subjectID
ORDER BY
    T.testID,
    A.Grade,
    A.subjectID";

  //  echo "<br>" . $query . "<br>";

 $result = mysqli_query($dbServer,$query);
$n = mysqli_num_rows($result);
//echo "rows = " .$n;
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