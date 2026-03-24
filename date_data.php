<?php
 date_default_timezone_set("Asia/Phnom_Penh");

$date = date("Y-m-d") ;
$date_minus_30 = date('Y-m-d', strtotime('-30 days'));
$today  = date("Y-m-d") ;
$year_month = date("Y-m") ;
$month = date('n') ;
$year = DATE("Y") ; 
$current_year = date('Y');

IF ($month >= 9) {$current_year++ ;}
$months = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
$month_names = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
$month = DATE("m") ;
$changeover = $today ; // initialise
// retrieve changeover date from database
// retrieve changeover date from database
$query = "SELECT ID, Date FROM Changeover_date WHERE YEAR(Date) = '$year' AND Date >= '$today' LIMIT 1" ;
$result = mysqli_query($dbServer, $query) ;

// 1. Set safe defaults in case the database returns nothing
$id = 0;
$changeover = $today; 
$next_changeover = $today;
$previous_changeover = $today;

// 2. Check if we actually got a result from the database
if ($result && mysqli_num_rows($result) > 0) {
    $data = mysqli_fetch_row($result) ;
    $id = $data[0] ;
    $changeover = $data[1] ;

    // next changeover
    $nextid = $id + 1 ; // Fixed to properly add 1
    $query_next = "SELECT Date FROM Changeover_date WHERE ID = '$nextid' " ;
    $result_next = mysqli_query($dbServer, $query_next) ;
    if ($result_next && mysqli_num_rows($result_next) > 0) {
        $data_next = mysqli_fetch_row($result_next) ;
        $next_changeover = $data_next[0] ;
    }

    // previous changeover
    $previousid = $id - 1 ;
    $query_prev = "SELECT Date FROM Changeover_date WHERE ID = '$previousid' " ;
    $result_prev = mysqli_query($dbServer, $query_prev) ;
    if ($result_prev && mysqli_num_rows($result_prev) > 0) {
        $data_prev = mysqli_fetch_row($result_prev) ;
        $previous_changeover = $data_prev[0] ;
    }
}

// get test date 
$testday = 20 ;  // after the ...

$today = DATE('Y-m-d') ;
$testdate = date("Y-m-t", strtotime($today));
// echo date("Y-m-t", strtotime($today));echo " for date = " . $today ;
// English tests are at the end of the month 
// test for day
$day = DATE('d') ;
;

IF ($day > $testday)
{$testdate = date("Y-m-t", strtotime($today)) ; }
ELSE
{ $testdate = date("Y-m-t", strtotime('last day of previous month'));
 } 
 
// echo "The date you want is ". $testdate ;


?>
