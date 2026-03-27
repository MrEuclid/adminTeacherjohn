<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="utf-8">

    <title>Edit high school marks</title>
 
   
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
//    alert('Status = ' + window.name) ;

    if (status != 'OK')
      {window.location.replace("http://admin.teacherjohn.org");}
  
  $('#submit').show() ;
})
   
</script> 

  </head>

<body>

<?php 

include "../connectDatabase.php" ; 
$subject = trim($_POST["subject"]) ;
$grade = trim($_POST["grade"]) ;
// $year = trim($_POST["year"]) ;
$test = trim($_POST["test"]) ;

include "../yearMonth.php";

 print_r($_POST) ;

$query = "SELECT english from hsSubjects WHERE code = '$subject' ";
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$englishName = $data[0] ;

 echo "Grade = " . $grade . "<br>";

// calculate level for maxima

$letters = array("A", "B", "C", "D","E","F");
$digits = array(0,1,2) ;
$l = strlen($grade) ;
$lastChar = substr($grade,$l-1,1);
//echo $l . " = length of grade". "<br>" ;
//echo $lastChar . " = last digit of class". "<br>" ;
if ($l == 2) {$level = $lastChar ;}

if ($l == 3 AND in_array($lastChar, $digits))
{$level = substr($grade,1,2) ;}

if ($l == 3 AND in_array($lastChar, $letters))
{$level = substr($grade,1,1) ;}

if ($l == 4 AND in_array($lastChar, $letters))
{$level = substr($grade,1,2) ;}

if ($grade == 'G12A') {$level = '12SOC' ;}
if ($grade == 'G12B') {$level = '12SCI' ;}

if ($grade == 'G11A') {$level = '11SOC' ;}
if ($grade == 'G11B') {$level = '11SCI' ;}

// echo "<br>Level = " . $level . "<br>" ;

// print_r($_GET) ;

//echo "Looking for current test ID ...";

$query = "SELECT max from hsMaxima WHERE subjectCode = '$subject' AND level = '$level' " ;
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$maxima = $data[0] ;
echo "<br>Maxima = " . $maxima . "<br>" ;
echo $query . "<br>";
?>

<div class = "row">
<div class = "col-sm-12 c">
  
  <h2>PIO - High School - Edit marks <?php echo $subject . '-' . $grade . ' ' . $test . ' Maximum ' . $maxima . ' ' . $level ;?></h2>
  </span>
    <?php 
    //echo $englishName . " - " . $grade . " - " . $newTestNumber . " Maximum = " . $maxima;
    // load maximum mark for this subject at this level
// echo "School year " . ($year-1) . " / " .$year ;
include "menu.html" ; 
?>
</h2>
</div></div>
<!--
  <div id = "editField">Class list goes here</div>  
-->
<form action = "writeUpdatedMarks.php" method = "POST">

  <input type = "hidden" value = <?php echo $maxima ; ?>>
  <?php 

  //phpinfo() ; 

  $grade = $_POST["grade"] ;
  $subject = $_POST["subject"] ;
  $test = $_POST["test"] ;

  $grade = TRIM($grade);
  $subject = TRIM($subject);
  $test = TRIM($test);
?>
   <input type = "hidden" value = "<?php echo $maxima ; ?>" name = "maxima">
  <input type = "hidden" value = "<?php echo $subject ; ?>" name = "subject">
  <input type = "hidden" value = "<?php echo $test ; ?>" name = "test">
  <div class = "row">
    <div class = col-md-12 r>
      <p id = "errorMessage"></p>
    </div></div>

<?php 

 // print_r($_POST) ;
  $query = "SELECT New_Students.ID AS ID , 
    concat(khmer_family_name,' ',khmer_first_name) AS Khmer,
concat(Family_name, ' ',First_name) AS English,
Grade,subjectID,testID,mark

FROM New_Students
JOIN New_ID_Year_Grade
ON New_Students.ID = New_ID_Year_Grade.Student_ID
JOIN hsMarks 
ON hsMarks.studentID  = New_Students.ID

WHERE 
School = 'PIOHS'
AND 
subjectID = '$subject'
AND 
Grade = '$grade'
AND Year = '$year'

AND testID = '$test'

ORDER BY New_Students.ID, khmer_family_name, khmer_first_name" ;

 // echo "<br>". $query . "<br>" ;

// include "print_query_data_plain.php" ;
$result = mysqli_query($dbServer,$query) ;

$id = 0 ;
while ($data = mysqli_fetch_assoc($result))
{
// print_r($data) ;
// echo "<br>" ;
?>
<div class = "row">
<div class = "col-sm-3 r">
<?php echo $data["ID"] ;?>
<input type = "hidden" name = "studentIDS[]" value  = <?php echo $data["ID"]; ?> > 
</div>
<div class = "col-sm-3">
<?php echo $data["Khmer"] ;?>
</div>
<div class = "col-sm-3">
<?php echo $data["English"] ;?>
</div>
<div class = "col-sm-3 l">
<?php $m_id = "m-" . $id ; ?>


<input type= "text" readonly="TRUE"  
value = "<?php echo $data['mark'] ; ?>" size = "2" name = "marks[]" >

<input id = "<?php echo $m_id; ?>"  type= "text"  onblur = "overLimit(this.value,id)"  
value = "<?php echo $data['mark'] ; ?>" onkeydown="testForEnter()" size = "4" name = "newMarks[]" >

</div>
</div>
<?php
$id++;
} 



  ?>
  <div class = "row">
<div class = "col-sm-12 c">
  <input type = "submit" value = "Update marks" id = "submitBtn">
  </form>
</div></div>

</body>
</html>


<script type="text/javascript">
   $(document).ready(function(){

$('#add').hide() ; // disable better
$('#submitBtn').show() ;

})
// #select option:selected").val();
</script>
 
<SCRIPT LANGUAGE="javascript"> 
function testForEnter() 
{    
  if (event.which == 13 || event.keyCode == 13) 
  
  {        
    event.cancelBubble = true;
    event.returnValue = false;
         }
     
} 
</SCRIPT>

<script LANGUAGE="javascript">

function overLimit(data,id)

{
//alert('Getting ' + id + ' data ' + data)
var max = '<?php echo $maxima; ?>' ;

var min = -0.01 ; // lowest allowable mark 

var n = parseFloat(data) ;
var l = data.length ;
var s = typeof n ;
var m = data.length ;
var test = isNaN(n) ;

var idCurrent = id ;

var error = false ;
  $('#'+id).css("background-color", "white");
  $('#'+id).css("color", "black");

if ((Number(data) > Number(max)) | isNaN(data) | (Number(data) < min) )

  {error = true ; 

    $('#'+id).css("background-color", "red");
    $('#'+id).css("color", "yellow");
  }
else {
  error = false ;
  $('#'+id).css("background-color", "green");
  $('#'+id).css("color", "white");
  $('#'+id).css("text-align", "center");
  $('#errorMessage').text('Mark is OK') ;
   $('#errorMessage').css("color", "green");

}


  

// alert($focused) ;
// alert("Data details =" + " type = " + s + " number " + n + " length data " + l + " test number " + test) ;

// alert("Length  " +  l) ;

if (Number(data) > Number(max))

 {
 // alert("The mark entered is greater than " + Number(max) + " please re-enter" + "This = " + id) ; 
//  $('#'+id).css("background-color", "red");
  //       $(this).css("color", "white");
         
$('#errorMessage').text("The mark entered is greater than " + max + " please re-enter");
  $('#errorMessage').css("color", "red");
 document.getElementById(id).focus();

}



if (isNaN(data))
  {

  $('#errorMessage').text("The text entered is not a number!  "  + " please re-enter");  
    $('#errorMessage').css("color", "red");
  //  alert('You must enter a number between 0 and ' + max);
 // 
 document.getElementById(id).focus(); }
// Check if data > 0
if (Number(data) < min) 
{
  // alert("Mark must be greater than 0, leave blank if student is absent." ) ; 
// document.getElementById(id).focus();
$('#errorMessage').text("The mark entered is too small " +  " please re-enter");
  $('#errorMessage').css("color", "red");
document.getElementById(x).focus();
}
    

}


</SCRIPT> 

<script>

    $(document).ready(function(){
        $('#submitBtn').on('click', function(){

          alert('Updating marks');

          $('#submitBtn').hide() ;

        })
      })
      

</script>