<?php
  $year = date("Y") ;
$month = date("m") ;
$day = date("d");
 
if ($month > 9) 
  {
    $y = $year ; // keeps real year
    $year = $year + 1; 
// $schoolYear = $year;
  } 

if ($month < 9)
{
// $y = $year -1 ;
 $y = $year -1;

}

if ($month == 9)
{
  $y = $year  ;
}
$schoolYear = $year;
$currentYear = $y;

// echo $currentYear . ' ' . $schoolYear ;
