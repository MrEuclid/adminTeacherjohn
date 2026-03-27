<?php
  $year = date("Y") ;
$month = date("m") ;
$day = date("d");
 $y = $year;
if ($month > 9) 
  {
    $y = $year ; // keeps real year
    $year = $year + 1; 

  } 

/*
  echo $month . "<br>" ;
if ($month < 9)
{echo $y; } 
else {echo $year;}
echo "<br>";

*/
    ?>