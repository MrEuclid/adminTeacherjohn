<?php 
// api for enrolment data using temporary tables
// TEMPORARY: Show all errors so we can fix the WSOD crash
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// require_once "authCheckPIO.php";
// require_once "authConf.php";
// restrictToAdmin();

include "../connectDatabase.php";

$date = date('d-M-Y') ;
$query = "SELECT max(Year) as maxYear FROM New_ID_Year_Grade" ;
$result = mysqli_query($dbServer,$query) or die("Error fetching max year: ");
$data = mysqli_fetch_assoc($result) ;
$schoolYear = $data['maxYear'] ;
// table for addresses built for tbladdress
// conditional aggregation to deal with duplicated rows in tbladdress
// tsa = templorary_student_
$query = "CREATE TEMPORARY TABLE tsa AS
SELECT 
    Student_ID,
    
    -- Current Address fields
    MAX(CASE WHEN AddressType = 'current_address' THEN Village END) AS currentVillage,
    MAX(CASE WHEN AddressType = 'current_address' THEN Commune END) AS currentCommune,
    MAX(CASE WHEN AddressType = 'current_address' THEN District END) AS currentDistrict,
    MAX(CASE WHEN AddressType = 'current_address' THEN Province END) AS currentProvince,
    
    -- Birth Address fields
    MAX(CASE WHEN AddressType = 'place_of_birth' THEN Village END) AS birthVillage,
    MAX(CASE WHEN AddressType = 'place_of_birth' THEN Commune END) AS birthCommune,
    MAX(CASE WHEN AddressType = 'place_of_birth' THEN District END) AS birthDistrict,
    MAX(CASE WHEN AddressType = 'place_of_birth' THEN Province END) AS birthProvince

FROM tbladdress
GROUP BY Student_ID" ; 
mysqli_query($dbServer,$query) or die("Error creating temporary table: " . mysqli_error($dbServer)) ;
// join with New_ID_Year_Grade filtered on Year
$query = "SELECT 
Grade, Year, New_ID_Year_Grade.Student_ID, 
CONCAT(Khmer_family_name, ' ', Khmer_first_name) AS Khmer_Name,
CONCAT(Family_name, ' ', First_name) AS English_Name,
Gender,
Date_birth,
birthVillage, birthCommune, birthDistrict, birthProvince,
Father_name, Father_work,
Mother_name Mother_work,
currentVillage, currentCommune, currentDistrict, currentProvince,
Phone

FROM New_Students 
LEFT JOIN New_ID_Year_Grade
ON New_Students.id = New_ID_Year_Grade.Student_ID
LEFT JOIN tsa 
ON New_ID_Year_Grade.Student_ID = tsa.Student_ID
WHERE Year = $schoolYear";
mysqli_query($dbServer,$query) or die("Error executing main query: " . mysqli_error($dbServer)) ;
$result = mysqli_query($dbServer,$query);

$cnt = 0;
$output = [];

WHILE ($data = mysqli_fetch_assoc($result))
{
$output[$cnt] = $data;
$cnt++ ;
}


echo json_encode($output);

mysqli_close($dbServer);

?>