<?php 

// TEMPORARY: Show all errors so we can fix the WSOD crash
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "authCheckPIO.php";
// require_once "authConf.php";
restrictToAdmin();

include "connectDatabase.php";
include "date_data.php" ;
$date = date('d-M-Y') ;
?>

<!DOCTYPE html>
<html lang="en">
  <head>
 
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
      <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    
  <meta name="description" content="">
  <meta name="keywords" content="">
  <!--
  <data-tooltip >Admin - PIO</data-tooltip >
-->
<style type="text/css">

/* Target the link in all states */
a:link, 
a:visited {
    color: white; /* Use your preferred color */
    text-decoration: none; /* Optional: removes the underline */
}
#lblPassword {backdrop-filter: lightgrey; color:black ; font-size: 1.2em ; font-weight: bolder;}
#inputPassword {backdrop-filter: lightblue; color:black; text-align: center; width :16em;}
#errorMessage {color:red; font-weight: bold;}
#option {margin-bottom: 0.6em;}
#topics {margin-bottom: 0.6em;}
#data {margin-bottom: 0.6em;}
p {display: inline-block;}

.c {width: auto;  text-align: center;}

table { margin: auto; width: 100% !important; }

#newUser , #newStudent {margin-bottom: 2em; color:white;}

[data-tooltip] {
  position: relative; /* Required for absolute positioning of the tooltip */
}
/* 1. Ensure the parent creates a reference point */
[data-tooltip] {
  position: relative; 
  cursor: pointer;
}

/* 2. The Tooltip styling */
[data-tooltip]:hover::after {
  content: attr(data-tooltip);
  position: absolute;
  
  /* THIS IS THE FIX */
  z-index: 9999; 
  pointer-events: none; /* Prevents the tooltip from flickering if the mouse touches it */
  
  /* Your existing styles */
  background-color: #333;
  color: #fff;
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 14px; /* standard size for clarity */
  width: 200px; /* Using px or rem is often more predictable than em for width */
  
  /* Positioning logic - moves it above the button */
  bottom: 125%; 
  left: 50%;
  transform: translateX(-50%);
  white-space: normal;
  text-align: center;
}


</style>
</head>
  <body>
  
    <div class  = "container-fluid">


<div id = "data">
<div class = "row">

    <div class = "col-2  text-center">
        <label class = "text-success">Total since 2015
    <p id = "totalEnrolments" ></p>
    </label>
    </div>
    <div class = "col-2  text-center">
              <label class = "text-success">Primary school
    <p id = "psEnrolments" ></p>
    </label>
    </div>
    <div class = "col-2  text-center">
              <label class = "text-success">High school
    <p id = "hsEnrolments" ></p>
    </label>
    </div>
    <div class = "col-2  text-center">      
        <label class = "text-success">New
    <p id = "newEnrolments" ></p>
    </label></div>
   <div class = "col-2  text-center">      
    <label class = "text-success">Left
    <p id = "leavers" ></p>
    </label></div>


       <div class = "col-2  text-center">      
    <label class = "text-success">Shelter
    <p id = "shelter" ></p>
    </label></div>

</div> <!-- row -->


</div> <!-- data -->

      <div class = "row">
        <div class = "col-">
<div class = "h1 text-center text-primary" >Admin - Teacher John - Dashboard
<a href="logout.php" class="btn btn-outline-danger btn-sm">Sign Out</a>
</div>
</div></div>

    <div class = "row justify-content-center">
    <div class = "col-12 text-center">

<a href = "inviteUserPIO.php"><button class = "btn btn-danger" id = "newUser" data-tooltip= "Add an admin or data entry user,">Invite user</button></a>
<a href = "new-student.php"><button class = "btn btn-danger" id = "newStudent" data-tooltip = "Enrol a new student or edit student records">Add / Edit students</button></a>

</div></div>  


<div class = "row justify-content-center">
        <div class = "col-12  text-center">
        <label class = "text-dark">
        <?php // echo $date ; ?>
    </label>

   
   <a href = "findStudentQuick.php">
    <button class = "btn btn-primary mb-2"  id = "quickFind" data-tooltip = "Quick search using student ID" >Quick find</button></a>

<a href = "attendance/indexAttendance.php" ><button class = "btn btn-warning mb-2"  id = "recordAttendnace" data-tooltip  = "Mark attendance each day.">Record attendance</button></a>

   <a href = "/attendance/findAttendanceQuick.php">
      <button class = "btn btn-primary mb-2"  id = "quickAttendance" data-tooltip  = "Totals for student attendance">Attendance Summary</button></a>

   <a href = "/attendance/findAttendanceAll.php">
      <button class = "btn btn-primary mb-2"  id = "quickAttendanceSearch" data-tooltip  = "Check the attendance for a student">Attendance Search</button></a>



        <a href = "attendance/pioMonthlyAttendanceReportNewv2.html">
      <button class = "btn btn-info mb-2"  id = "monthlyAttendance" data-tooltip  = "See attendance for each class, each month"> Attendance monthly</button> </a>

</div></div>

<div class = "row justify-content-center">
        <div class = "col-12  text-center">

       <a href = "/highschool/findMarksQuickVisual.php" >
      <button class = "btn btn-primary mb-2"  id = "quickHSAll" data-tooltip  = "High school marks.">HS Mark List</button></a>


        <a href = "findPSQuick.php">
      <button class = "btn btn-primary mb-2"  id = "quickps" data-tooltip  = "Check results for primary school student.">PS Mark List</button></a>

           <a href = "/highschool/findIncompleteData.php">
      <button class = "btn btn-primary mb-2"  id = "missingMarks" data-tooltip  = "Find out which high school marks are missing from the database." >HS Missing</button></a>
</div></div>

<div class = "row justify-content-center">
        <div class = "col-12  text-center">
   <a href = "https://admin.pio-students.net/newAJAX/moveOneStudentv2.php">
      <button class = "btn btn-warning mb-2"  id = "move1" >Move one student</button></a>
        <a href = "https://admin.pio-students.net/newAJAX/moveClassv2.php">
      <button class = "btn btn-warning mb-2"  id = "moveClass" >Move class</button></a>
      <a href = "https://admin.pio-students.net/newAJAX/indexFixDB.php">
      <button class = "btn btn-warning mb-2" id = "duplicates" target = "_blank">Fix Errors</button></a>
      <a href = "https://admin.pio-students.net/find_studentv2New.php" target = "_blank"></a>
      
</div></div>

<div class = "row justify-content-center">
    <div class = "col-12 text-center">
          <a href = "books/indexBooks.php" data-tooltip  = "Use to lend books or accept returned books." ><button class = "btn btn-success">Library</button></a>
    <a href = "books/findBookQuick.php" ><button class = "btn btn-success" data-tooltip  = "See all the books borrowed since January 2026">Book Report</button></a>
<a href = "books/overDueReportQuick.html" ><button class = "btn btn-success" data-tooltip  = "Get a list of overdue books">Overdue Report</button></a>

      </div></div>
<br>
<div class = "row justify-content-center">
        <div class = "col-12  text-center">
<a href = "find_studentv2New.php">
      <button class = "btn btn-info mb-2"  id = "find" data-tooltip  = "old, slow search for students" >Find old</button></a>
<!--
      <a href = "https://admin.pio-students.net/dashboardPIOSummary.php" target = "_blank">
     <button class = "btn btn-primary"  id = "primary"> Dashboard</button> </a>
-->

          <a href = "highschool/indexHighSchool.php">
      <button class = "btn btn-info mb-2" id = "hsMarks" data-tooltip  = "Add high school marks and view results.">High School marks</button></a>
    
    <a href = "primaryschool/index.php" target = "_blank">
      <button class = "btn btn-info mb-2"  id = "psMarks" data-tooltip  = "primary school mark book. Can be used but not yet updated.">Primary School marks</button></a>
      <a href = "findQuickStats.php">
        
      <button class = "btn btn-info mb-2"  id = "hsperformance" data-tooltip  = "High school performance by subject and test.">HS - Performance</button></a>
  </div></div>
    <div class = "row justify-content-center">

      <div class = "col-12  text-center">
      <a href = "enrollmentTrends.php">
      <button class = "btn btn-primary mb-2"  id = "trendreports" data-tooltip  = "Enrolments numbers and trends.">Enrolment trends</button>
  </a>

      <a href = "schoolNumbers.php">
      <button class = "btn btn-primary mb-2"  id = "trendreports" data-tooltip  = "School numbers.">School numbers</button>
  </a>

               <a href = "sponsorDashboard.php">
      <button class = "btn btn-primary mb-2"  id = "sponsorreports" data-tooltip  = "Sponsor reports.">Sponsor reports</button></a>

                 <a href = "analyst/enrollment.php">
      <button class = "btn btn-primary mb-2"  id = "analystreports" data-tooltip  = "Analyst reports.">Analyst reports</button></a> 

      <a href = "retentionDashboard.php">
      <button class = "btn btn-primary mb-2"  id = "retentionreports" data-tooltip  = "Retention reports.">Retention reports</button></a>
    
  </div></div>
  <div class = "row justify-content-center">
         <div class = "col-12  text-center">
      <a href = "primaryschool/malydaPS.php">
      <button class = "btn btn-success mb-2"  id = "hsreports" data-tooltip  = "Primary school reports.">PS - Analysis</button>
  </a>
 <a href = "highschool/malydaHS.php">
      <button class = "btn btn-success mb-2"  id = "hsreports" data-tooltip  = "High school reports.">HS - Analysis</button>
  </a>

        <a href = "attendance/malydaAttendance.php">
      <button class = "btn btn-success mb-2"  id = "attendancereports" data-tooltip  = "Attendance reports.">Attendance - Analysis</button></a>

           <a href = "highschool/malydaAnalysis.php">
      <button class = "btn btn-success mb-2"  id = "statsreports" data-tooltip  = "Statistics reports.">New students - HS</button></a>

            <a href = "survivalDashboard.php">
      <button class = "btn btn-success mb-2"  id = "survivalreports" data-tooltip  = "Retentiion reports.">Statistics - Retention</button></a>

 
</div></div>



<div id = "topics">
<div class = "row">
        <div class = "col-3 text-center"><p class = "h3  text-bg-primary">Enrolled</p></div>
        <div class = "col-3 text-center"><p class = "h3  text-bg-info">Attendance</p></div>
        <div class = "col-6 text-center"><p class = "h3  text-bg-warning">Low attendance</p></div>
</div>
</div> <!-- topics -->

<div id = "output">
<div class=" row justify-content-center">
        <div  class = "col-3 text-center">
            <p id = "enrolmentData"></p>
        </div>
  <div  class = "col-3 text-center">
      <p id = "attendanceData"></p>
  </div>
    <div  class = "col-6 text-center" id = "lowAttendance"></div>

</div>


</div> <!-- output  -->

</div>  <!-- container -->
<div id = "soFar"></div>
</body>
</html>

<script type="text/javascript">
  
</script>



<script type="text/javascript">
  
</script>

<script>
    $(document).ready(function() {
    // Passing the PHP session variable to a global JS constant
  
    const USER_ROLE = "<?php echo $_SESSION['role']; ?>";
    console.log("Role",USER_ROLE,USER_ROLE != 'admin');
    // not admin hide Home button
    if (USER_ROLE === 'dataEntry')
  { 
   $('#newUser').hide();
   $('#newStudent').hide();
    $('#find').hide();
$('#move1').hide();
$('#moveClass').hide();
$('#duplicates').hide();
$('#hsperformance').hide();
$('#hsreports').hide();
// $('#attendancereports').hide(); dataentry should see as well



}


})
</script>

<script type="text/javascript">
   $(document).ready(function(){


     $('#enrolmentData').load("../newAJAX/currentEnrolments.php").show();
      $('#attendanceData').load("../newAJAX/attendanceSummary.php").show();
       $('#lowAttendance').load("../newAJAX/lowAttendance.php").show();
    $('#totalEnrolments').load("../newAJAX/enrolmentsAll.php").show();
     $('#psEnrolments').load("../newAJAX/enrolmentsSchool.php",{school:'SMC'}).show();
        $('#hsEnrolments').load("../newAJAX/enrolmentsSchool.php",{school:'PIOHS'}).show();
 $('#newEnrolments').load("../newAJAX/newEnrolments.php").show();
  $('#leavers').load("../newAJAX/leavers.php").show();
    $('#shelter').load("../newAJAX/shelter.php").show();
$('#topics').show();
$('#data').show();

$('#soFar').load("../newAJAX/getDailyAttendance.php").show();

   })
</script>
