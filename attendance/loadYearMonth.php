<?php
include "../connectDatabase.php";

// Get distinct yearMonth from the last 12 months
// We use DATE_SUB to limit the scan, and LEFT() which is slightly faster than substr()
$query = "SELECT DISTINCT LEFT(shortDate, 7) AS yearMonth 
          FROM attendance 
          WHERE shortDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
          ORDER BY yearMonth DESC";

$result = mysqli_query($dbServer, $query);

// Safely handle the query result, especially important in PHP 8.1+
if (!$result) {
    die(json_encode(["error" => "Query failed: " . mysqli_error($dbServer)]));
}

$output = [];

while ($data = mysqli_fetch_assoc($result)) {
    $output[] = $data["yearMonth"];
}

echo json_encode($output);

mysqli_close($dbServer);
exit();
?>

