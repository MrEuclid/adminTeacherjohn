<?php
// /analyst/update_analyst_session.php
session_start();

if (isset($_POST['school']))    $_SESSION['ana_school']    = $_POST['school'];
if (isset($_POST['year_from'])) $_SESSION['ana_year_from'] = $_POST['year_from'];
if (isset($_POST['year_to']))   $_SESSION['ana_year_to']   = $_POST['year_to'];

header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
?>