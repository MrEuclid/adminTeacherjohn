<?php
include "../connectDatabase.php";

// Ensure PHP 8.1 strict mode for DB errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_POST['data']) || !is_array($_POST['data'])) {
    die("No data received.");
}

$data = $_POST['data'];
$valuesArray = [];

// 1. Loop through the data and safely prepare the strings for a bulk insert
foreach ($data as $d) {
    // Escape strings to prevent errors if a name has an apostrophe
    $studentID = mysqli_real_escape_string($dbServer, $d["studentID"]);
    $shortTime = mysqli_real_escape_string($dbServer, $d["shortTime"]);
    $shortDate = mysqli_real_escape_string($dbServer, $d["shortDate"]);
    $status    = mysqli_real_escape_string($dbServer, $d["status"]);

    // Add this student's row to our array
    $valuesArray[] = "('$studentID', '$shortTime', '$shortDate', '$status')";
}

// 2. Only execute if we actually have data
if (!empty($valuesArray)) {
    try {
        // Implode joins all the individual ('id', 'time', 'date', 'status') chunks with commas
        $query = "INSERT INTO attendance (studentID, shortTime, shortDate, status) VALUES " . implode(',', $valuesArray);
        
        // Run the ONE massive query instead of 35 small ones
        mysqli_query($dbServer, $query);
        echo "Success";
        
    } catch (mysqli_sql_exception $e) {
        http_response_code(500);
        echo "Database Error: " . $e->getMessage();
    }
}

mysqli_close($dbServer);
?>