
<html>
<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>

    <link href='http://fonts.googleapis.com/css?family=Khmer' rel='stylesheet' type='text/css'>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
 
 
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>



<title>Attendance Custom Report</title>

<style>
    th {font-weight: bolder; background-color: lightblue; color: black; font-size: 1em;  border-width: 1pt;}
    td {text-align: center; font-size: 0.8em;  border-width: 1pt;}

    .c {text-align: center;}
   h1 {color: blue; font-size: 1.6em; text-align: center;}
    h2 {color: green; font-size: 1.2em;}


.c {width: auto; text-align: center;}


</style>
</head>

<body>

    <div class  = "container-fluid">

       <div class = "row">
            <div class = "col-12 c">
              <a href = "attendanceReports.html"><button>Home</button></a>
         </div></div>  

        <div class = "row">
            <div class = "col-12 c">
              <h1>PIO Custom Attendance Report</h1>
                </div></div>

   <div class = "row">
            <div class = "col-12 c">
<form action = "" method = "GET">
    <!--
    <label>School Year</label><input type = "number"  name = "schoolYear" value = 2021>
-->
<label>Start Date</label><input type = "date" name = "startDate" id = "startDate">
<label>End Date</label><input type = "date" name = "endDate" id = "endDate">
<button type = "submit">Go</button>
</form>
</div></div>

  <div class = "row">
            <div class = "col-12 c">
              <table align = "center">
                <tr>
                    <th>ID</th>
                    <th>Family</th>
                    <th>First</th>
                    <th>Gender</th>
                    <th>DOB</th>
                    <th>Grade</th>
                    <th>Shelter</th>
                    <th>Gone</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Attendance %</th>
                </tr>
                </div></div>

<?php



//print_r($_GET);
// echo isset($_GET['startdate']) . "+" ;
// echo isset($_GET['startdate']) AND isset($GET_['endDate']) . "===" ;
include "../connectDatabase.php" ;
include "yearMonth.php";

echo "Year = " . $year;
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

if (isset($_GET['startDate'] ) AND isset($_GET['endDate'] ))
{

/*
$schoolYear = 2022;
$startDate = '2022-05-01' ;
$endDate = '2022-05-31' ;
*/


 $startDate = $_GET['startDate'] ;
 $endDate = $_GET['endDate'] ;
$shortDate = substr($startDate,0,7) ;
// $schoolYear = $_GET['schoolYear'] ;


// $schoolyear = $_POST['schoolYear'] ;

$table  = [] ; // stores student info and attendance summary.
/*
$query = "SELECT yoursHumanly.studentID, Family_name, First_name, Gender,date_birth,Grade, Shelter ,active,

sum(case when (status = 'Y'  OR status = 'P' ) then 1 else 0 end) AS here,
sum(case when status = 'N' then 1 else 0 end) AS abs,

round(100*sum(case when status = 'Y' then 1 else 0 end) / (sum(case when status = 'Y' then 1 else 0 end) + 
    sum(case when status = 'N' then 1 else 0 end)),0) AS percent

FROM yoursHumanly 
JOIN New_Students ON New_Students.ID = yoursHumanly.studentID 
JOIN New_ID_Year_Grade 
ON New_ID_Year_Grade.Student_ID = yoursHumanly.studentID 
AND Year = '$schoolYear'
LEFT JOIN attendance ON attendance.studentID = New_Students.ID
AND substr(shortDate,1,7) >= '$shortDate'
AND shortTime < '12:00'
AND shortDate >= '$startDate' 
AND shortDate <= '$endDate'

   
    GROUP BY yoursHumanly.studentID
    HAVING active = 'Y'
 
    
    ORDER BY Family_name,First_name";
*/

// pre-filtering using temporary tables

    $query = "CREATE temporary table ns 
SELECT New_Students.ID AS SID, Family_name, First_name, Gender,Date_birth,Grade

FROM New_Students 

JOIN New_ID_Year_Grade 
ON ( New_ID_Year_Grade.Student_ID = New_Students.ID
AND Year = '2026')
AND Gone = 'N'


    GROUP BY New_Students.ID
    
ORDER BY  cast(substr(Grade,1,2) as signed) ,New_Students.id ";

include  "print_query_data_plain.php";

/*

$query =  "SELECT New_Students.ID AS SID, Family_name, First_name, Gender,Date_birth,Grade, Shelter ,Gone,

sum(case when (status = 'Y'  OR status = 'P' ) then 1 else 0 end) AS here,
sum(case when status = 'N' then 1 else 0 end) AS abs,

round(100*sum(case when status = 'Y' then 1 else 0 end) / (sum(case when status = 'Y' then 1 else 0 end) + 
    sum(case when status = 'N' then 1 else 0 end)),0) AS percent

FROM New_Students 

JOIN New_ID_Year_Grade 
ON ( New_ID_Year_Grade.Student_ID = New_Students.ID
AND Year = '$year')
aND substr(School,1,1) <> 'U'
LEFT JOIN attendance ON attendance.studentID = New_Students.ID

AND shortTime < '12:00'
AND shortDate >= '$startDate' 
AND shortDate <= '$endDate'
AND School IN ('SMC','PIOHS')

    GROUP BY New_Students.ID
    HAVING Gone = 'N'  
ORDER BY  Grade " ;

*/

 echo "<br>" . $query . "<br>" ;
$result = mysqli_query($dbServer,$query);

while ($data = mysqli_fetch_row($result))
{
 $table[] = $data ;
}


set_time_limit(180); // maximum running time for script in seconds

// } // if form sent 

// ELSE { echo "Waiting ..." ;}

//print_r($table) ;

/*
echo "<br>" ;

foreach($table as $t)
{
    echo "<tr>" ;
   for ($i = 0 ; $i <= 10; $i++)
   { echo "<td>" . $t[$i] . "</td>" ; }
echo "</tr>" ;

}

$sdate = date_create($startDate);
$edate = date_create($endDate);

echo "<br><h2>Attendance from " . date_format($sdate,'d-M-Y') . " to " . date_format($edate,'d-M-Y') . "</h2><br>" ;
mysqli_close($dbServer);
} // only do if set

else {echo "Waiting..." ;}
*/
?>
</table>
</div>
</body>
</html>
