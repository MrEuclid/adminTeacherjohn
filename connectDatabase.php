
<?php
// Ensure PHP 8.1 strict mode for database errors is on so we can catch them safely
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $server = 'localhost';
    $username = 'euclid_pio';
    $password = 'CNNHero2008';
    $database = 'euclid_pio';
    
    // Attempt the connection
    $dbServer = mysqli_connect($server, $username, $password, $database);
    
    // Set charset securely
    mysqli_set_charset($dbServer, "utf8");

} catch (mysqli_sql_exception $e) {
    // If it crashes, this safely catches it and prints the exact error to the screen!
    die("<div style='background: #ffdddd; padding: 20px; border: 1px solid red; font-family: sans-serif;'>
            <h3>Database Connection Failed!</h3>
            <p><strong>Error:</strong> " . $e->getMessage() . "</p>
            <p><em>(Check your  MySQL Databases area to make sure the user is still attached to the 'euclid_pio' database.)</em></p>
         </div>");
}
?>
