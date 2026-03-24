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



<title>Attendance all</title>

<style>
    th {font-weight: bolder; background-color: lightblue; color: green; font-size: 0.8em;}
    td {text-align: center; font-size: 0.75em;}
    .c {text-align: center;}
    h1 {color: blue; font-size: 1.6em;}
    h2 {color: green; font-size: 1.2em;}
</style>    

<script type="text/javascript">
     $(document).ready(function () {

     })

</script>
</head>

<body>

<?php
include "../connectDatabase.php" ;

// $yearMonth = '2025-08';
// $grade = 'G7A' ;

// $yearMonth = '2025-08' ; 

 $grade =       $_POST['grade'] ;
 $yearMonth =   $_POST['yearMonth'] ;



set_time_limit(180); // maximum running time for script in seconds

?>
    <div class  = "container-fluid">

        <div class = "row">
            <div class = "col-12 c">
              <h1>Student Attendance v2</h1>
         </div></div>    



  <div class = "row">
            <div class = "col-12 c">
              <p id = "message"></p>
         </div></div>   
<?php



$firstDay = $yearMonth . '-01' ;
$year = substr($yearMonth,0,4);
$month = substr($yearMonth,5,2);
// echo ' yearMonth ' . $month . ' + ' . $year . "<br>" ;

$lastDayNumber = cal_days_in_month(CAL_GREGORIAN, $month, $year); 
$lastDay = $yearMonth . '-' . $lastDayNumber;

//  echo $firstDay . ' ' . $lastDay  . ' ' . $lastDayNumber;
// get school year

$query = "SELECT MAX(Year) FROM New_ID_Year_Grade";
$result = mysqli_query($dbServer,$query) ;
$data = mysqli_fetch_row($result);
$schoolYear = $data[0] ;

// get beginning and end dates for the month
// OPTIMIZATION 1: Use range >= <= instead of substr() for index scanning

$query = "SELECT DISTINCT shortDate 
            FROM attendance 
            WHERE shortDate >= '$firstDay' AND shortDate <= '$lastDay'
            ORDER BY shortDate";
 // echo "<br>" . $query . "<br>";

$result = mysqli_query($dbServer,$query) ;
$dates = [] ;
$dates[0] = $yearMonth;

// make sure that format is YYYY-MM-DD
// build an array of dates for the month from the attendance table

for ($i = 1 ; $i <= $lastDayNumber; $i++)
{
    if ($i < 10) {$d = '0' . $i ;} else {$d = $i;}
    $dates[$i] = $yearMonth . '-' . $d;
}
?>

<?php
 // echo "Dates " ;
 // print_r($dates);
 // echo "<br>";
?>
   <div class = "row">
            <div class = "col-12 c">
<?php
// echo "<h2>" . "Attendance from " . $firstDay . ' to ' . $lastDay . "<h2>";
?>
  
         </div></div>  

<?php

// get studentID, khmer name, english name ,Grade and Gender
// limit to students whose id are in the attendance table for the month
// need school year to get the correct grade

$query = "SELECT Student_ID AS studentID,
concat(Khmer_family_name, ' ' , Khmer_first_name) as Khmer_name,
concat(Family_name, ' ' , First_name) as English_name,
Grade,Gender 
FROM New_Students
JOIN New_ID_Year_Grade
ON New_Students.ID = New_ID_Year_Grade.Student_ID
AND Student_ID IN 
(SELECT studentID FROM attendance WHERE
shortDate >= '$firstDay' 
AND shortDate <= '$lastDay'
AND shortTime <= '16:00')
AND New_ID_Year_Grade.Year = '$schoolYear'
AND Gone <> 'Y'
AND Grade = '$grade' 
ORDER BY  Grade, Student_ID   " ; 
// echo "<br>". $query . "<br>" ;
$studentData = [] ;
$row = 0 ; 
$result = mysqli_query($dbServer,$query);
while ($data = mysqli_fetch_assoc($result))
{
    $row++ ;
    $studentData[$row] = $data ;  // store in $studentData array
}

//  echo "<br>" . $query . "<br>";
// echo "<br>" . print_r($studentData) . "<br>";

// copy attendance into an array
// OPTIMIZATION 2: Only select needed columns and use date ranges

$query = "SELECT studentID, shortDate, status FROM attendance
            WHERE shortDate >= '$firstDay' AND shortDate <= '$lastDay'
            AND studentID IN 
            (SELECT Student_ID FROM New_ID_Year_Grade WHERE Year = '$schoolYear' AND Grade = '$grade')
            ORDER BY shortDate, shortTime" ;

$result = mysqli_query($dbServer,$query) ;

// OPTIMIZATION 3: Group into a Smart Dictionary instead of a flat array
$attendanceDict = [];

while ($data = mysqli_fetch_assoc($result)) {
    $sID = $data['studentID'];
    $sDate = $data['shortDate'];
    
    // Group statuses by Student ID and Date so we can look them up instantly later
    if (!isset($attendanceDict[$sID][$sDate])) {
        $attendanceDict[$sID][$sDate] = '';
    }
    // Append the status string (e.g., 'Y' becomes 'YY' for multiple entries)
    $attendanceDict[$sID][$sDate] .= $data['status']; 
}

$nDates = count($dates);

// data array to hold all results
$dataArray = [];

// make header
// student info
$dataArray[0][0] = 'ID';  
$dataArray[0][1] = 'Khmer_name';
$dataArray[0][2] = 'English_name';
$dataArray[0][3] = 'Gender';
$dataArray[0][4] = 'Grade';

$offset = 4 ; // allows for student info

// add the dates
for ($i = 1 ; $i <= $lastDayNumber ; $i++)   // for each day of the month so far
{
    $index = $i + $offset ; // skip the student data
    $dataArray[0][$index] =  $dates[$i] ;
}

for ($j = $offset + 1 ; $j <= $lastDayNumber + $offset ; $j++)
{
    $day = $j - $offset ;

    // change format
    // OK because the date used is in $dates
    if ($day <= $lastDayNumber)
    {$thisDay= date_create($dates[$day] );
    $td = date_format($thisDay,"D d M");}
    else
    {
      $td = '--';
    }

    $dataArray[0][$j] = $td ; // build the header

}

 $dataArray[0][$lastDayNumber + $offset + 1] = '?' ;
 $dataArray[0][$lastDayNumber + $offset + 2] = 'Y' ;
 $dataArray[0][$lastDayNumber + $offset + 3] = 'N' ;
 $dataArray[0][$lastDayNumber + $offset + 4] = 'P' ;
 $dataArray[0][$lastDayNumber + $offset + 5] = 'T' ;
 $dataArray[0][$lastDayNumber + $offset + 6] = 'L' ;

 $l = count($studentData);  // number of rows in the data array

 // fill $dataArray

 for ($i = 1 ; $i <= $l ; $i++)  // traverse rows of the dataArray
 {
    $dataArray[$i][0] = $studentData[$i]["studentID"];
    $dataArray[$i][1] = $studentData[$i]["Khmer_name"];
    $dataArray[$i][2] = $studentData[$i]["English_name"];
    $dataArray[$i][3] = $studentData[$i]["Gender"];
    $dataArray[$i][4] = $studentData[$i]["Grade"];

    for($p = $offset + 1 ; $p <= $lastDayNumber + $offset + 1 + 5   ; $p++)   // extra 5 is YNPTL
    {
        $dataArray[$i][$p] = '+' ; // initalisation of date cells 
    }  

    // now add the daily status (AM and PM) for each studentID and shortDate = $dataArray[0][n]

    // initialise counts
    $absenceCount = 0 ;
    $presentCount = 0 ; 
    $permissionCount = 0 ;
    $notRecorded = 0 ;
    $lateCount = 0 ;
   
    $index = $offset + 1  ; // moves along adding status for days

    // for each data
    for ($j = 1 ; $j <= $lastDayNumber ; $j++)
    {
        $targetDate = $dates[$j] ;
        $targetID = $studentData[$i]["studentID"] ;

        // OPTIMIZATION 4: Instant Dictionary Lookup instead of looping 900,000 times!
        if (isset($attendanceDict[$targetID][$targetDate])) {
            $status = $attendanceDict[$targetID][$targetDate];
        } else {
            $status = '0';
        }

        if (strlen($status) > 4) {$status = substr($status,0,1)  ;}

        switch ($status) 
        {
          case "Y": 
          $status = "Y" ;
         $presentCount = $presentCount + 1  ;
            break;

            case "YY": 
          $status = "Y" ;
         $presentCount = $presentCount + 1  ;
            break;

         case "YYYY": 
          $status = "Y" ;
         $presentCount = $presentCount + 1  ;
            break;

              case "NY": 
          $status = "L" ;
         $presentCount = $presentCount + 1  ;
         $lateCount = $lateCount + 1;
            break;

        case "NNYY": 
          $status = "L" ;
         $presentCount = $presentCount + 1  ;
         $lateCount = $lateCount + 1;
            break;

          case "N" :
          $status = "N";
            $absenceCount = $absenceCount + 1  ;
            break;

            case "NN" :
          $status = "N";
            $absenceCount = $absenceCount + 1  ;
            break;

               case "NNNN" :
          $status = "N";
            $absenceCount = $absenceCount + 1  ;
            break;

             case "NNYY" :
          $status = "N";
            $absenceCount = $absenceCount + 1  ;
            break;

              case "YN" :
          $status = "N";
            $absenceCount = $absenceCount + 1  ;
            break;
          
            case "P":
            $status = "P" ;
            $permissionCount = $permissionCount + 1 ;
            break;

              case "PP":
            $status = "P" ;
            $permissionCount = $permissionCount + 1 ;
            break;

            case "PY":
            $status = "Y" ;
            $permissionCount = $permissionCount + 1 ;
            break;
        
         case "0" :
         $status = "0" ;
            $notRecorded = $notRecorded + 1  ;
            break;

         case "00" :
         $status = "0" ;
            $notRecorded = $notRecorded + 1  ;
            break;
     

        default:
    $status = '?';
    $notRecorded = $notRecorded + 1  ;

            } // case for status


 $dataArray[$i][$index] = $status ;

    
        $index++ ; // advance pointer
       
        

    }  // next $j

        $dataArray[$i][$offset + 1 + $lastDayNumber + 1] =  $presentCount ;
        $dataArray[$i][$offset + 1 + $lastDayNumber + 2] = $absenceCount ;
        $dataArray[$i][$offset + 1 + $lastDayNumber + 3] = $permissionCount ;
       // $dataArray[$i][40] = round(100 * ($presentCount / ($permissionCount + $absenceCount + $presentCount)),1) ;
        $dataArray[$i][$offset + 1 + $lastDayNumber + 4] = $presentCount + $absenceCount + $permissionCount  ;
         $dataArray[$i][$offset + 1 + $lastDayNumber + 5] = $lateCount ;

    
 }  // next $i
// print_r($data[1]);
$temp = $dataArray[0];
unset($dataArray[0]);

// $columns = array_column($dataArray, '41');
// array_multisort($columns, SORT_DESC, $dataArray);

$columns = array_column($dataArray, '4');  // sorting by Grade Grade
array_multisort($columns, SORT_ASC, $dataArray);

//$columns = array_column($dataArray, '40');
//array_multisort($columns, SORT_ASC, $dataArray);

array_unshift($dataArray , $temp);
// output results to a table 

 ?>
   <div class = "row">
            <div class = "col-12 c">
 <table width = "100%" border = "1'">
    <tr>
<?php
for ($i = 0; $i <= $lastDayNumber + $offset + 1 + 5; $i++)
{
    echo "<th>" . $dataArray[0][$i] . "</th>" ;  // td cells
}
echo "</tr>";

// end of header row 


for ($i = 1 ; $i <= $l; $i++)
{
echo "<tr>" ;


  for ($k =  0 ; $k <= $lastDayNumber + $offset + 6 ; $k++)
    {
    $style = "style=background-color:white";
    if($dataArray[$i][$k] == 'Y' )
        {$style = "style=background-color:green;color:white"; }

    elseif ($dataArray[$i][$k] == 'N' ) 
        { $style = "style=background-color:red;color:yellow";}

   elseif ($dataArray[$i][$k] == 0 ) 
       { $style = "style=background-color:white;color:black";} 

    if ($dataArray[$i][$k] == 'L') 
        { $style = "style=background-color:orange;color:yellow";}

    if ($dataArray[$i][$k] == 'P' ) 
        { $style = "style=background-color:blue;color:white";}

      if ($k > $offset + $lastDayNumber)
      {$style = "style=background-color:white;color:black";}
 

if ($k > 0)
 {echo  "<td " . $style . ">" . $dataArray[$i][$k] . "</td>  " ;}
else  // make button link to new tab
 {
    $id = $dataArray[$i][0] ;
    
 ?>
<td> <a href = "showStudentInfo.php?id=<?php echo $id ; ?>"><?php echo $id ; ?></a> </td>
<?php 
}  // $k > 0 else 

}  // for $k
echo "</tr>" ;


} // for $i

?>

</table>
</div></div>

<?php

mysqli_close($dbServer);

?>
</div>
</body>
</html>