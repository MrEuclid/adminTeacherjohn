<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="utf-8">

    <title>Update high school marks</title>
 
   
	<link href='http://fonts.googleapis.com/css?family=Khmer' rel='stylesheet' type='text/css'>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
 
	<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">

<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

<!-- Latest compiled JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>



<style type="text/css">
h1 {text-align:center; font-size:24pt;
         color:blue ;
         font-weight:bold;}
h2 {text-align:center; font-size:18pt;
         color:green ;
         font-weight:bold;}

h3 {text-align:center; font-size:16pt;
       color:red ;
     font-weight:bold;}

h4 {text-align:center; font-size:14pt;
         color:blue ;
         font-weight:bold;}

.c {text-align: center; margin-left: auto;margin-right: auto ;}

.l {text-align: left;}

.r {text-align: right;}


  </style>      

  <script type="text/javascript">
  
  $( document ).ready(function() {

    var status = window.name ;
 //   alert('Status = ' + window.name) ;

    if (status != 'OK')
      {window.location.replace("http://admin.teacherjohn.org");}
  
  
})
   
</script> 

  </head>

<body>

<?php 

include "../connectDatabase.php" ; 
//phpinfo() ;
$maxima = $_POST['maxima'] ;
$test = trim($_POST["test"]) ;
$subject = trim($_POST["subject"]) ;
$mark = $_POST["marks"] ;
$newMark = $_POST["newMarks"] ;
$studentID = $_POST["studentIDS"] ;
$sid = $studentID[0] ; // grab the 1st one

$query = "SELECT Year, Grade FROM New_ID_Year_Grade WHERE Student_ID = '$sid'
ORDER BY Year DESC LIMIT 1";

$result = mysqli_query($dbServer,$query) ;
$data= mysqli_fetch_row($result) ;
$year = $data[0];
$grade = $data[1] ;

//print_r($_POST) ;

?>

<h2 class = "c">Test results</h2>
  <div class="container">
<div class = "row">
<div class = "col-sm-12 l">
  <?php include "menu.html" ?>
<?php 
$l = count($mark) ;
// echo "l = " . $l ; 
for ($i = 0 ; $i < $l ; $i++)
{
$student = $studentID[$i] ;
$m = $mark[$i] ;
$nM = $newMark[$i] ;

$marksPercent[$i] = $nM*100/$maxima ;
$query = " UPDATE `hsMarks` SET mark= '$nM' 
WHERE mark <> '$nM' AND studentID = '$student' aND subjectID = '$subject' AND testiD = '$test'"  ;

// echo $query ;
// echo "<br>" ;
mysqli_query($dbServer,$query) ;

} // for
?>

</div></div>
<div class = "Row">
<div class = "col-sm-12 c">
  <h2>Here are the results</h2><h2>Here are the new results for Test# <?php echo $test ; ?> in subject <?php echo $subject ; ?></h2>
 <?php
$query = "SELECT New_Students.ID AS ID , 
    concat(khmer_family_name,' ',khmer_first_name) AS Khmer,
concat(Family_name, ' ',First_name) AS English,
Grade, testiD,subjectID,mark

FROM New_Students
JOIN New_ID_Year_Grade
ON New_Students.ID = New_ID_Year_Grade.Student_ID
JOIN hsMarks ON New_Students.ID = hsMarks.studentID
AND Year = '$year'
AND Grade = '$grade'

AND 
testID = '$test'
AND
subjectiD = '$subject' 

ORDER BY New_Students.ID ";
// echo $query ;
include  "../print_query_data_plain.php" ;

?>


 
<?php

$cnt = 0 ;
$sum = 0 ;
$dns = 0 ;

$a = 0 ;
$b = 0 ;
$c = 0 ;
$d = 0 ;
$e = 0 ;
$f = 0 ;

// process array marks 

for ($i = 0 ; $i < $l ; $i++)
{
if ($marksPercent[$i] > 0 ) {$cnt = $cnt + 1 ; $sum = $sum + $marksPercent[$i];
if ($marksPercent[$i] < 50) {$f = $f + 1 ;}
if ($marksPercent[$i] >= 50 AND $marksPercent[$i] < 60) {$e = $e + 1 ;}
if ($marksPercent[$i] >= 60 AND $marksPercent[$i] < 70) {$d = $d + 1 ;}
if ($marksPercent[$i] >= 70 AND $marksPercent[$i] < 80) {$c = $c + 1 ;}
if ($marksPercent[$i] >= 80 AND $marksPercent[$i] < 90) {$e = $b + 1 ;}
if ($marksPercent[$i] >= 90) {$a = $a + 1 ;}

}
else {$dns = $dns + 1 ;}


}
$passed = $a + $b + $c + $d + $e  ;
echo "There are " . $cnt . " results<br>" ;
echo $passed . " (" . round(100*$passed/$cnt,0) . ")% passed" . "<br>" ; 
echo "Did not sit = " . $dns . "<br>" ;
?>
<h3>Results for those that sat the test</h3>
A <?php echo $a . " " . round(100*$a/$cnt,0) . "%<br>" ; ?>
B <?php echo $b . " " . round(100*$b/$cnt,0) . "%<br>" ; ?>
C <?php echo $c . " " . round(100*$c/$cnt,0) . "%<br>" ; ?>
D <?php echo $d . " " . round(100*$d/$cnt,0) . "%<br>" ; ?>
E <?php echo $e . " " . round(100*$e/$cnt,0) . "%<br>" ; ?>
F <?php echo $f . " " . round(100*$f/$cnt,0) . "%<br>" ; ?>
<br>
The average is <?php echo round($sum/$cnt,0) . "%<br>" ; ?>


<?php

// print_r($marksPercent) ;

?>
</div></div>

<div class = "Row">
<div class = "col-sm-12 c">
 
</div></div>
</body>  
</html>






</body>
</html>