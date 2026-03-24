
<?php

$id = $_GET['id'] ;
include "../connectDatabase.php" ;
$query = "SELECT  CONCAT(Khmer_family_name,' ',Khmer_first_name) AS khmerName,Phone,
          Father_work,Father_name, Mother_work, Mother_name
			
			FROM New_Students WHERE id = '$id' " ;
$result = mysqli_query($dbServer,$query);

$data = mysqli_fetch_assoc($result) ;
$student = $data["khmerName"] ;
$phone = $data["Phone"];
$fatherWork = $data["Father_work"] ;
$motherWork = $data["Mother_work"] ;
$fatherName = $data["Father_name"] ;
$motherName = $data["Mother_name"] ;


$query = "SELECT * FROM attendance WHERE studentID = '$id' 
			AND status = 'N' 
			AND shortDate >= '2021-12-01'
			ORDER BY shortDate DESC" ;

$result = mysqli_query($dbServer,$query);

$absences = [] ;

WHILE ($data = mysqli_fetch_assoc($result))
{
	$absences[] = $data["status"] . ' ' . $data["shortDate"] . ' ' . $data["shortTime"]  ;
}

// print_r($absences);

?>
<!DOCTYPE html>
<html lang="en">

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

  <title>Info-<?php echo $id ; ?></title>

  <style type="text/css">
  	.c {width: auto; text-align: center;}
h3 {color: blue; font-size: 1.2em; text-align: center;}
h2 {color: green; font-size: 1.4em; text-align: center;}

p {font-weight: bold;}

  </style>
</head>

<body>
</body>

    <div class  = "container-fluid">


           <div class = "row">
            <div class = "col-12 c">
              <h3>Details for <?php echo $id ; ?> - <?php echo $student; ?></h3>
              <h3>Phone  <?php echo $phone ; ?></h3>
        
              <h3>Parent information</h3>
              <p>Father : <?php echo $fatherName ; ?> Work: <?php echo $fatherWork; ?></p>
              <p>Mother : <?php echo $motherName ; ?> Work: <?php echo $motherWork; ?></p>
                </div></div>    

           <div class = "row">
            <div class = "col-12 c">
                <h2>Absences</h2>

                <?php
                foreach ($absences as $a)
                {
                	echo $a  . "<br>" ;
                }

                ?>
                </div></div>

         <div class = "row">
            <div class = "col-12 c">
            	<a href = "http://attendance.teacherjohn.org/pioMonthlyAttendanceReportNewv2.html"><button>Back</button></a>
            </div></div>

</div>
</html>