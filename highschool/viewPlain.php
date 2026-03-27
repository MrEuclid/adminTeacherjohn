<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="utf-8">

    <title>High School Marks view plain</title>

   <link href='https://fonts.googleapis.com/css?family=Khmer' rel='stylesheet' type='text/css'>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <!-- Removed old jQuery 1.11.3 to prevent conflicts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>



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

#errorMessage {background-color: yellow; color: red; text-align: center;}


  </style>   
  </head>
<body>

<?php 
include "../connectDatabase.php" ; 

include "../yearMonth.php" ;

// echo "Today is " . $year . '-' . $month . '-' . $day . "<br>" ;
?>

  <div class="container">
<div class = "row">
<div class = "col-sm-12 c">
   <h1>PIO High School Markbook - View Data</h1>
</div></div>
 


<?php 
 // include "menu.html" ; 
// get classes in ordered list  

// use academic  not calendar year

$queryClasses  = "SELECT DISTINCT Grade FROM New_ID_Year_Grade WHERE School = 'PIOHS'
AND Year >= '$year' ORDER BY  CAST(substr(Grade,2,2) AS UNSIGNED) " ;
//echo "<br>" . $queryClasses . "<br>" ;
$resultClasses = mysqli_query($dbServer,$queryClasses) ;


   $querySubjects = "SELECT code,english,khmer FROM hsSubjects ORDER BY code";
                $resultSubjects = mysqli_query($dbServer,$querySubjects);

include "makeMonths.php";
?>
<div class = "row">
<div class = "col-sm-12 c">
  <p id = "errorMessage"></p>

</div></div>


<div class = "row">
<div class = "col-sm-12 c">
<h2>Month & Class</h2>
</div></div>

<div class = "row">
<div class = "col-sm-12 c">

<label>Month</label>
<select id = "yearMonth" name = "yearMonth">
<option value="" selected ="selected">Month</option> 
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

<label>Class</label>
<select id = "grade" name = "grade">
 <option value="" selected ="selected">Class</option>
 <?php
while ($data = mysqli_fetch_Assoc($resultClasses))
{
$class = $data["Grade"] ;
$message = $data["Grade"] . " " . $date["Year"] . " " . $data["School"];
?>
 <option value="<?php echo $class; ?> "><?php echo $message ; ?></option>  
<?php  
}
?>
</select> 

<button  id = "viewData">View marks</button>
</div></div>

</div>  <!-- wrapper -->
</div>  <!-- bootstrap container  -->

<div id = "myPage"></div>

<br><br>
<p class = "c">The PIO HS Markbook - John Thompson 2026 email: john@teacherjohn.org</p>


  </body>
</html>

<script>

   $(document).ready(function(){
 $('#viewData').on('click', function() {

// #select option:selected").val();

   var testType = $("input[name='testType']:checked").val();
            if(testType){
            //    alert("Your are choosing a - " + testType + ' test');
              }


 grade = $('#grade option:selected').val() ;
 year = '<?php echo $year; ?>' ;
 yearMonth = $('#yearMonth option:selected').val() ;
 var data = 0 ;

 alert("Checking form "  + grade + ' ' + yearMonth) ;



  if (grade == "") {
    alert("You need a class"); return ;}

 if (yearMonth == "") {
    alert("You need the month"); return ;}

   $.ajax({
    
    dataType: 'text',
    type: 'post',
    url: 'hsResultTotalsMonth.php',
    
    data: {grade:grade, yearMonth:yearMonth,testType:testType},
    
    
    success: function(response){
      var i = response.indexOf('!') ;
     console.log(i,response[8],response[8] == '!') ;  
// 8 to allow for /n not being trimmed
$('#selectOptions').hide() ;
     if (response[8] == '!') 
     {
      $('#errorMessage').html(response);
      alert("edit error");
      $('#viewData').hide() ;
     }
     else
     {
      $('.wrapper').hide() ;

       $('#myPage').html(response); 
      $('#viewData').show() ;
      }  
     
        }, // success
    
   
      error: function(xhr, textStatus, errorThrown){
                        alert('request failed');
                      }
                });
              // if


  });
});

 </script>





<script type="text/javascript">
  
   $(document).ready(function(){
     $('#viewData').on('click', function(){
         $('.wrapper').show() ;



})
   })
</script>


<script type="text/javascript">

  $( document ).ready(function() {

    var status = window.name ;

    $('#pwd').focus() ;
  //  alert('Status = ' + window.name) ;
    if (status != 'OK')
    { $("#everything").hide();
     $('#login').show() ;}

 if (status == 'OK')
    { $("#everything").show();
     $('#login').hide() ;}
   
})
   
</script> 



