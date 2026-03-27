<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="utf-8">

    <title>Edit high school marks</title>
 
   
	<link href='https://fonts.googleapis.com/css?family=Khmer' rel='stylesheet' type='text/css'>
    
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
      {window.location.replace("https://highschool.pio-students.net");}
  
  $('#submitBtn').show() ;
})
   
</script>  

  </head>

<body>

  <?php
 // $year = date("Y") ;
//$month = date("m") ;
// echo "<br><strong>School year " . ($year-1) . " / " .$year . "</strong>";
// echo $year . "-" . $month ;
// if ($month == "10" OR $month == "11" OR $month == "12") {$year = $year + 1 ;} else {$year = $year ;}

include "../connectDatabase.php" ;
include "../yearMonth.php" ;
echo "Year " . $year ;
?>
 
 <div class = "row">
    <div class = "col-md-12 c">

<?php include "menu.html" ?>
</div></div>

 <div class = "row">
    <div class = "col-md-12 c">
      <h2 class = "c">Edit High School Marks</h2>
    </div></div>


 <div class = "row">
    <div class = "col-md-12 c">

<form action = "updateMarks.php" method = "post">
      <label>Class</label>



<?php

// $y is real year
// handles marks in Nov / Dec

$queryGrade = "SELECT DISTINCT Grade 
              FROM New_ID_Year_Grade 
              WHERE School = 'PIOHS'
              AND Year = '$year' 
              ORDER BY  CAST(substr(Grade,2,2) AS UNSIGNED) " ;

$resultGrade = mysqli_query($dbServer,$queryGrade) ;



?>
<select id = "grade" name = "grade" required="TRUE">

 <option value="" selected ="selected">Class</option>
 
<?php
while ($data = mysqli_fetch_Assoc($resultGrade))
{
$grade = $data["Grade"] ;
$message = $data["Grade"] ;
?>
 <option value="<?php echo $grade; ?> "><?php echo $message ; ?></option>  
<?php  
}

?>

</select> 
     

<?php
$querySubjects = "SELECT code,english,khmer FROM hsSubjects ORDER BY code";
$resultSubjects = mysqli_query($dbServer,$querySubjects) ;
?> 

      <label>Subject</label>

<select id = "subject" name = "subject" required="TRUE">

 <option value="" selected ="selected">Subject</option>
 
<?php
while ($data = mysqli_fetch_Assoc($resultSubjects))
{
$code = $data["code"] ;
$message = $data["english"] . " " . $data["khmer"] . "  " . $data["code"] ;
?>
 <option value="<?php echo $code ; ?> "><?php echo $message ; ?></option>  
<?php  
}

?>

</select> 


<?php

include "makeMonths.php";

// add this month 
$now = date('Y-m');
array_push($monthArray,$now);
$monthArray = array_unique($monthArray);
// $monthArray = array_reverse($monthArray) ;

?>
  <select id = "testID" name = "test" required="TRUE">
 <option value="" selected = "selected">Test</option>
<?php
foreach($monthArray as $m)

{
  $message = $m ;
?>
 <option value="<?php echo $m ; ?> "><?php echo $m ; ?></option>  
<?php  
}
?>
</select> 



<input type = "hidden" name = "year" value = "<?php echo $y ; ?>">

<input  id = "submitBtn" type = "submit"  value = "List marks" >


     
    </form>

      </div>

    </div></div>

</body>



<script>

    $(document).ready(function(){
        $('#submitBtn').on('click', function(){

          alert('Updating edited marks');

          $('#submitBtn').hide() ;

        })
      })
      

</script>