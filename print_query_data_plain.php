<?php
// echo $query ;

$result = mysqli_query($dbServer,$query) ;
// $result = mysqli_fetch_row($data);
$rows = mysqli_num_rows($result) ;
$cols = mysqli_field_count($dbServer) ;
$col_name = mysqli_fetch_fields($result) ;


echo "<table  width = \"90%\" class = \"center\" border = \"1\">" ;

echo "<tr>" ;


for ($i = 0 ; $i < $cols ; $i++)
  {
$fieldinfo=mysqli_fetch_field_direct($result,$i);
$fieldname = $fieldinfo->name ;

echo "<th>" ;
echo  $fieldname ;

}
echo "</th>" ;
echo "</tr>" ; 

// process rows
// echo $query . "<br>" ;
for ( $n = 0 ; $n < $rows ; $n++)

{  
$data = mysqli_fetch_row($result);
echo "<tr>" ;
for ($j = 0 ; $j < $cols ; $j++)
{
  IF (is_numeric($data[$j])) 
  {echo "<td class  = \"number\" >" . $data[$j]  . "</td>";}
  ELSE
  { echo "<td class  = \"left\">" . $data[$j]  . "</td>";}
}
  
echo "</tr>" ;
}


echo "</table>" ;
?>