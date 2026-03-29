<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="utf-8">

    <title>High School Marks Dashboard</title>
 

 
  <link href='https://fonts.googleapis.com/css?family=Khmer' rel='stylesheet' type='text/css'>
    
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    
 
 
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script type="text/javascript">
      google.load('visualization', '1.1', {packages: ['controls']});
    </script>


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
      {window.location.replace("https://admin.pio-students.net/highschool/indexHighSchool.php");}
  
  
})
   
</script>  

  </head>
<body>
<?php
// phpinfo() ;
include "../connectDatabase.php" ;
$subject = $_REQUEST['subject'] ;
$testNumber = $_REQUEST['test'] ;
$studentIDS = $_REQUEST['student'] ;
$maxima = $_REQUEST['maxima'] ;
$grade = $_REQUEST['grade'] ;
//print_r($studentIDS) ;
//echo "<br><br>";
$marks = $_REQUEST['mark'] ;
$l = count($studentIDS);

$a = 0 ;
$b = 0 ;
$c = 0 ;
$d = 0 ;
$e = 0 ;
$f = 0 ;

$cnt = 0 ;
$passed = 0 ;
$dns = 0;
$sum = 0 ;

// gwt subject maximum


echo "<h2>Processing " . $l ." marks for " . $subject . " Test number  ". $testNumber  . "</h2>" ;

?>	

	<h1 class = "c">PIO High School - Markbook</h1>
<h2 class = "c">Test results</h2>
  <div class="container">
<div class = "row">
<div class = "col-sm-12 l">

  <?php include "menu.html" ?>
<?php 
for ($i = 0 ; $i < $l ; $i++)
{
$studentID = $studentIDS[$i] ;
$mark = $marks[$i] ;
if ($mark == "") {$mark = 0 ; $marks[$i] = 0 ;}
$marksPercent[$i] = $mark*100/$maxima ;

// check that mark is not already there

$query = "SELECT * FROM hsMarks WHERE studentID = '$studentID' AND testID = '$testNumber' AND subjectID = '$subject' " ;
// echo "<br>" . $query . "<br>" ;
$result = mysqli_query($dbServer,$query) ;
$n = mysqli_num_rows($result) ;
if ($n == 0 & $mark <= $maxima)
{
  $query = "INSERT INTO hsMarks (studentID,testID,subjectID,mark)
VALUES
(
'$studentID',
'$testNumber',
'$subject',
'$marks[$i]'

)"	;

//echo $query ;
//echo "<br>" ;
mysqli_query($dbServer,$query) ;}
else {echo "<br>" . " Cannot add duplicate result or mark greater than " . $maxima . "<br>" . $query . "<br>" ;}

} // for
?>

</div></div>
<div class = "Row">
<div class = "col-sm-12 c">
	<h2>Here are the results</h2>
<?php
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
$passed = $a + $b + $c + $d + $e ;
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


