<?php
// print_query_data_plain.php

/**
 * Executes a query and renders a strictly formatted HTML table for DataTables.
 * 
 * @param mysqli $dbServer The active database connection
 * @param string $query The SQL query to execute
 */
function renderDataTable($dbServer, $query) {
    $result = mysqli_query($dbServer, $query);
    
    // Add basic error handling so the page doesn't just die silently
    if (!$result) {
        echo "<div class='alert alert-danger'>Query failed: " . mysqli_error($dbServer) . "</div>";
        return;
    }

    $cols = mysqli_field_count($dbServer);

    // Added an ID and Bootstrap classes for a cleaner look
    echo "<table id='marksTable' class='table table-striped table-bordered' style='width:100%;'>";
    
    // --- HEADER SECTION ---
    echo "<thead>";
    echo "<tr>";
    
    for ($i = 0; $i < $cols; $i++) {
        $fieldinfo = mysqli_fetch_field_direct($result, $i);
        $fieldname = $fieldinfo->name;
        
        // BUG FIXED: The <th> is properly opened AND closed inside the loop
        echo "<th>" . htmlspecialchars($fieldname) . "</th>";
    }
    
    echo "</tr>"; 
    echo "</thead>";

    // --- BODY SECTION ---
    echo "<tbody>";
    
    // Switched to a while loop, which is standard practice for fetching rows
    while ($data = mysqli_fetch_row($result)) {  
        echo "<tr>";
        for ($j = 0; $j < $cols; $j++) {
            if (is_numeric($data[$j])) {
                echo "<td class='number'>" . htmlspecialchars($data[$j]) . "</td>";
            } else {
                echo "<td class='left'>" . htmlspecialchars($data[$j]) . "</td>";
            }
        }
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}
?>