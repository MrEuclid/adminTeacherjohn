<?php

$monthArray =[];
$query = "SELECT DISTINCT testID FROM hsMarks ORDER BY id DESC lIMIT 12";
$result = mysqli_query($dbServer,$query);
WHILE ($data =mysqli_fetch_row($result))
{
  $monthArray[] = $data[0];
}

// and add current month

// add this month 
$now = date('Y-m');
array_push($monthArray,$now);
$monthArray = array_unique($monthArray);
?>