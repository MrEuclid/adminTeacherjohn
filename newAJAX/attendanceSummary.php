<?php
include "../connectDatabase.php" ;
include "../date_data.php" ;

//echo $year . '  ' . $current_year . "<br>" ;
// Current timestamp is assumed, so these find first and last day of THIS month
$first_day_this_month = date('Y-m-01'); // hard-coded '01' for first day
$last_day_this_month  = date('Y-m-t');

//echo $first_day_this_month . '  ' . $last_day_this_month . "<br>" ;

$query = "SELECT Grade, 

round(100*sum(case when status = 'Y' then 1 else 0 end) / (sum(case when status = 'Y' then 1 else 0 end) + sum(case when status = 'N' then 1 else 0 end)),0) AS percent 

FROM New_Students 
JOIN attendance ON New_Students.ID = attendance.studentID 
JOIN New_ID_Year_Grade ON New_ID_Year_Grade.Student_ID = New_Students.ID 
AND Year = '$current_year' 
AND School IN ('SMC', 'PIOHS')
AND shortDate >= '$first_day_this_month' 
AND shortDate <= '$last_day_this_month'
AND shortTime < '12:00' AND 
shortDate >= '$first_day_this_month' AND 
shortDate <= '$last_day_this_month' 
GROUP BY Grade
ORDER  BY School, CAST(substr(Grade,2) AS DECIMAL) ,Grade DESC " ;

// echo "<br>" . $query . "<br>" ;

include "../print_query_data_plain.php" ;

?>