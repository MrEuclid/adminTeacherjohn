<?php
// include "../includes/connect_db_euclid_pio.php" ; 
include "../connectDatabase.php";
include "../date_data.php" ;

// echo  $year . "  " . $current_year;



/* end point for current year
gets main fields from the query 

*/

$query = "SELECT 
    NS.ID AS Student_ID,
    NS.Family_name,
    NS.First_name,
    NS.Gender,
    NS.Date_birth,
    
    TargetGrade.Grade AS Grade, 
    
   cast(Counts.Total_Occurrences AS SIGNED) AS Years,
   NS.Gone
FROM 
    New_Students NS


JOIN 
    (
        SELECT Student_ID, COUNT(*) as Total_Occurrences 
        FROM New_ID_Year_Grade 
        GROUP BY Student_ID
    ) Counts ON NS.ID = Counts.Student_ID


LEFT JOIN 
    New_ID_Year_Grade TargetGrade ON NS.ID = TargetGrade.Student_ID AND TargetGrade.Year = '$current_year'




WHERE TargetGrade.Grade IS NOT NULL  
ORDER BY NS.ID DESC;" ;
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