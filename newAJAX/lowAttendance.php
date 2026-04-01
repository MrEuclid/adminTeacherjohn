<?php
include "../connectDatabase.php" ;
include "../date_data.php" ;

//echo $year . '  ' . $current_year . "<br>" ;
// Current timestamp is assumed, so these find first and last day of THIS month
$first_day_this_month = date('Y-m-01'); // hard-coded '01' for first day
$last_day_this_month  = date('Y-m-t');
$first_day_this_year = date('Y-10-01');
//echo $first_day_this_month . '  ' . $last_day_this_month . "<br>" ;
$last_year = $current_year -1 ;
$first_day = $last_year . '-10-01';
if ($month >= 1 AND $month <= 9) 
	{$first_day_this_year = date($first_day);}

// echo $first_day_this_year;
$query  = "select studentID,Family_name,First_name, Grade,present,
absent, 
round(100*absent/(absent + present),0) as percent
FROM New_Students
JOIN 
(SELECT studentID,
sum(case when (status = 'Y' OR status = 'P') then 1 else 0 end) as present,
sum(case when status = 'N' then 1 else 0 end) as absent
FROM attendance
WHERE shortDate >= '$first_day_this_year'
AND shortDate <= '$last_day_this_month'
AND shortTime <= '16:00'
GROUP BY studentID) as a
 ON New_Students.ID = a.studentID
 JOIN New_ID_Year_Grade ON New_ID_Year_Grade.Student_ID = New_Students.ID
 AND Year = '$current_year'
 AND Gone <> 'Y'
 AND School IN ('PIOHS','SMC')

HAVING percent > 50 OR absent > 20
 ORDER BY percent DESC, Grade";

 // echo "<br>" . $query . "<br>" ;

include "../print_query_data_plain.php" ;

?>