<?php
include "../connectDatabase.php";

$date = $_POST['date'] ?? date('Y-m-d');
$studentIDs = $_POST['studentIDs'] ?? [];

if (empty($studentIDs)) {
    echo json_encode([]);
    exit;
}

// Sanitize IDs for the SQL IN clause
$sanitizedIDs = array_map('intval', $studentIDs);
$idsString = implode(',', $sanitizedIDs);

// Fetch records for these students for today, ordered by time
$query = "SELECT studentID, shortTime, status FROM attendance WHERE shortDate = '$date' AND studentID IN ($idsString) ORDER BY shortTime ASC";
$result = mysqli_query($dbServer, $query);

$attendanceData = [];

while ($row = mysqli_fetch_assoc($result)) {
    $sid = $row['studentID'];
    
    // Initialize the student array if it doesn't exist
    if (!isset($attendanceData[$sid])) {
        $attendanceData[$sid] = [
            'amStatus' => null,
            'hasPermission' => false
        ];
    }
    
    // Check if the student has a Permission status today
    if ($row['status'] === 'P') {
        $attendanceData[$sid]['hasPermission'] = true;
    }
    
    // Check if the record is from the morning (Before 12:00)
    // We only set it if amStatus is null, to ensure we get their *first* morning record
    $hour = (int)substr($row['shortTime'], 0, 2);
    if ($hour < 12 && $attendanceData[$sid]['amStatus'] === null) {
        $attendanceData[$sid]['amStatus'] = $row['status'];
    }
}

// Return the formatted data to the frontend
echo json_encode($attendanceData);
?>