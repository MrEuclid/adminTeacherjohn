<?php 
 require_once "../authCheckPIO.php";
 restrictToAdmin();

ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<?php 

include "../connectDatabase.php" ; 
$subject = trim($_GET["subject"]) ;
$grade = trim($_GET["grade"]) ;
$yearMonth = trim($_GET["yearMonth"]) ;
$year = substr($yearMonth,0,4) ;
$testID = $yearMonth;

// check that the class doesn't already have marks for that month
$month = date("m") ;
// echo "<br><strong>School year " . ($year-1) . " / " .$year . "</strong>";
// echo $year . "-" . $month ;
//if ($month == "10" OR $month == "11" OR $month == "12") {$year = $year + 1 ;} else {$year = $year ;}

include "../yearMonth.php" ;

$query = "SELECT * FROM hsMarks 
WHERE subjectID = '$subject' 
AND testID = '$yearMonth'
AND studentiD IN (SELECT Student_ID FROM New_ID_Year_Grade WHERE Year = '$year' AND Grade = '$grade')" ;

// echo "<br>" . $query . "<br>" ;

$result = mysqli_query($dbServer,$query);
$n = mysqli_num_rows($result);

// echo $n  . "<br>" ;
if ($n <  0) 
  {
    $m =  "Error! " .$grade . "-" . $subject . " already has marks for " . $yearMonth . ".<br>Please use <b> Edit marks </b> if you want to change a student's mark. <br>";
    $n = trim($m) ;
    echo $n ;
 //   exit();

  }


$testType = trim($_GET["testType"]) ;
// echo "<br>" . $year . ' ' . $yearMonth . "<br>" ;
$query = "SELECT english from hsSubjects WHERE code = '$subject' ";
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$englishName = $data[0] ;

// echo "Grade = " . $grade . "<br>";

// calculate level for maxima

$letters = array("A", "B", "C", "D","E","F");
$digits = array(0,1,2) ;
$l = strlen($grade) ;
$lastChar = substr($grade,$l-1,1);
// echo $l . " = length of grade". "<br>" ;
// echo $lastChar . " = last digit of class". "<br>" ;
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

 echo "<br>Level = " . $level . "<br>" ;

 print_r($_GET) ;

// echo "Looking for current test ID ...";

$query = "SELECT max from hsMaxima WHERE subjectCode = '$subject' AND level = '$level' " ;
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
$maxima = $data[0] ;

 echo "<br>Maxima = " . $maxima . "<br>" ;

if ($testType == 'month')
{
  
  $query = "SELECT testID FROM hsMarks 
JOIN New_ID_Year_Grade ON New_ID_Year_Grade.Student_ID = hsMarks.studentID
WHERE subjectID = '$subject' AND Grade = '$grade' AND Year = '$year'
AND substr(testID,4,2) NOT IN ('S1', 'S2') ORDER BY testID DESC limit 1 ";

// echo "<br>" . $query . "<br>" ;


$testID = $yearMonth ;
/*
$result = mysqli_query($dbServer,$query) ;
$data = mysqli_fetch_row($result);
//print_r($data);
$testNumberMax = $data[0] ; 

//echo "<br>" . $testNumberMax . " Last test number<br>" ;

$lastDigit = substr($testNumberMax,4,2);
//echo "Last digit = " . $lastDigit;

$newDigit = $lastDigit + 1 ;

// get next test number

if ($lastDigit > 8) {$d = "error " ;echo $d;}
else {
$newTestNumber  = ($year -2000) . '-0' . $newDigit; 
//echo "<br>" . $newTestNumber . " new test number<br>" ;
}
*/

$newTestNumber = $testID;
} // month

if ($testType == 'semester')

{$query = "SELECT testID FROM hsMarks WHERE subjectID = '$subject' 
AND substr(testID,4,2)  IN ('S1', 'S2') ORDER BY testID DESC limit 1 ";
$result = mysqli_query($dbServer,$query);
$data = mysqli_fetch_row($result);
//print_r($data);
$testNumberMax = $data[0] ; 
// echo "<br>" . $query . "<br>";
//echo "<br>" . $testNumberMax . " Last test number<br>" ;
// $newTestNumber = 0 ;
$lastDigit = substr($testNumberMax,4,1);
// echo "Last digit = " . $lastDigit;

if ($lastDigit == 0) {$newDigit = 1 ;}
if ($lastDigit == 1) {$newDigit = 2 ;}
if ($lastDigit >= 2) {$newDigit = 2 ;}




$newTestNumber  = $year . '-S' . $newDigit; 
// echo "<br>" . $newTestNumber . " new test number<br>" ;

}
?>

<div class = "row">
<div class = "col-sm-12 c">
  <h1>PIO - High School - Add new marks </h1>
  <h2>
    <?php 
    $newTestNumber = $testID;
    //echo $englishName . " - " . $grade . " - " . $newTestNumber . " Maximum = " . $maxima;
    // load maximum mark for this subject at this level
echo $grade . " " . $subject . " " . $newTestNumber  . ' ' . $maxima . ' ' . $level; 

?>
</h2>
</div></div>


<div class = "row">
<div class = "col-sm-12 c">
  <?php
    $query = "SELECT New_Students.ID AS ID , 
    concat(khmer_family_name,' ',khmer_first_name) AS Khmer,
concat(Family_name, ' ',First_name) AS English,
Grade

FROM New_Students
JOIN New_ID_Year_Grade
ON New_Students.ID = New_ID_Year_Grade.Student_ID

WHERE 
School = 'PIOHS'
AND 
Year = '$year'
AND 
Grade = '$grade'


ORDER BY New_Students.ID" ;

// removed this 
/*

AND New_Students.ID NOT IN 
(SELECT studentID FROM hsMarks WHERE testID = '$testID' AND subjectiD = '$subject' )

*/
// replaced newTestNumber with $testID
 echo "<br>" . $query . "<br>" ;
?>
</div></div>
<?php
$result = mysqli_query($dbServer,$query) ;


?>
<form action = "addHSMarksToDB.php" method = "POST">



<input type = "hidden" name = "subject" value  = <?php echo $subject; ?> > 
<input type = "hidden" name = "test" value  = <?php echo $newTestNumber; ?> > 
<input type = "hidden" name = "grade" value = <?php echo $grade; ?>>
<input type = "hidden" name = "maxima" value = <?php echo $maxima; ?>>


<?php  
$id = 0 ;
$cnt = 0 ;
while ($data = mysqli_fetch_assoc($result))
{
// print_r($data) ;
// echo "<br>" ;
?>
<div class = "row">
<div class = "col-sm-3 r pink">
<?php echo $data["ID"] ;?>
<input type = "hidden" name = "student[]" value  = <?php echo $data["ID"]; ?> > 
</div>
<div class = "col-sm-3 highlight">
<?php echo $data["Khmer"] ;
if ($cnt % 2 == 0 )
{}

?>
</div>
<div class = "col-sm-3 highlight r ">
<?php echo $data["English"] ;?>
</div>
<div class = "col-sm-3 l ">
<?php $m_id = "m-" . $id ; ?>
<input class = "pink" id = "<?php echo $m_id; ?>"  type= "text"  onblur = "overLimit(this.value,id)"  onkeydown="testForEnter()" size = "6" name = "mark[]" >

</div>
</div>
<?php
$id++;
$cnt++ ;
} 

    ?>
<div class = "row">
<div class = "col-sm-12 c">    
    <input type = "submit" value = "Add marks" name = "submit" id = "submitBtn">
  </div></div>
 </form>     

</div></div>

</body>
</html>

<script type="text/javascript">
   $(document).ready(function(){

$('#submitBtn').show() ;
 })
// #select option:selected").val();
</script>

<script type="text/javascript">
   $(document).ready(function(){

$('#add').hide() ; // disable better
     $('#add').on('click', function(){

})
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

if ((Number(data) > Number(max)) | isNaN(data) | Number(data) < min )

  { 
    error = true ;
    $('#'+id).css("background-color", "red");
    $('#'+id).css("color", "yellow");
  }
else {
  
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
    error = true ;     
$('#errorMessage').text("The mark is greater than " + max + " please re-enter");
  $('#errorMessage').css("color", "red");
 document.getElementById(id).focus();

}



if (isNaN(data))
  {
error = true ;
  $('#errorMessage').text("The text is not a number!  "  + " please re-enter");  
    $('#errorMessage').css("color", "red");
  //  alert('You must enter a number between 0 and ' + max);
 // 
 document.getElementById(id).focus(); }
// Check if data > 0
if (Number(data) < min) 
{
  error = true ;
  // alert("Mark must be greater than 0, leave blank if student is absent." ) ; 
// document.getElementById(id).focus();
$('#errorMessage').text("The mark is too small " +  " please re-enter");
  $('#errorMessage').css("color", "red");
document.getElementById(id).focus();
}

if (error == false)
{
$('#errorMessage').text('Mark is OK') ;
  $('#'+id).css("background-color", "green");
  $('#'+id).css("color", "white");
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