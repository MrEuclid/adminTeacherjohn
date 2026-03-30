<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>High School Marks View</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    
    <!-- Fonts -->
    <link href='https://fonts.googleapis.com/css?family=Khmer' rel='stylesheet' type='text/css'>
    
    <!-- Modernized Bootstrap 3.3.5 & DataTables CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">

    <style type="text/css">
        body {
            background-color: #f8f9fa;
            font-family: Arial, 'Khmer', sans-serif;
        }
        .header-title {
            text-align: center; 
            font-size: 24pt;
            color: #2c3e50;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .section-title {
            text-align: center; 
            font-size: 18pt;
            color: #27ae60;
            font-weight: bold;
        }
        .control-panel {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            text-align: center;
        }
        select.form-control {
            display: inline-block;
            width: auto;
            margin: 0 10px;
        }
        #errorMessage {
            display: none;
            background-color: #ffeeba; 
            color: #856404; 
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 15px;
        }
        .footer-text {
            text-align: center;
            margin-top: 40px;
            color: #7f8c8d;
        }
    </style>   
</head>
<body>

<?php 
    include "../connectDatabase.php"; 
    include "../yearMonth.php";
?>
<button class="btn btn-warning shadow-sm px-6 font-bold" id="back" onclick="history.back()">GO BACK</button>
<div class="container">
    <div class="row">
        <div class="col-sm-12  text-center">
             <?php include "menu.html"; ?>
            <h1 class="header-title">PIO High School Markbook - View Data</h1>
        </div>
    </div>

    <?php 
        // Fetch classes 
        $queryClasses  = "SELECT DISTINCT Grade FROM New_ID_Year_Grade WHERE School = 'PIOHS' AND Year >= '$year' ORDER BY CAST(substr(Grade,2,2) AS UNSIGNED)";
        $resultClasses = mysqli_query($dbServer, $queryClasses);

        // Fetch subjects
        $querySubjects = "SELECT code,english,khmer FROM hsSubjects ORDER BY code";
        $resultSubjects = mysqli_query($dbServer, $querySubjects);

        include "makeMonths.php";
    ?>

    <div class="row">
        <div class="col-sm-12">
            <div id="errorMessage"></div>
        </div>
    </div>

    <div class="row wrapper">
        <div class="col-sm-12">
            <div class="control-panel">
                <h2 class="section-title">Select Month & Class</h2>
                <hr>
                
                <form class="form-inline" onsubmit="return false;">
                    <div class="form-group">
                        <label for="yearMonth">Month: </label>
                        <select id="yearMonth" name="yearMonth" class="form-control">
                            <option value="" selected="selected">Select Month</option> 
                            <?php foreach($monthArray as $m): ?>
                                <option value="<?php echo $m; ?>"><?php echo $m; ?></option>  
                            <?php endforeach; ?>
                        </select> 
                    </div>

                    <div class="form-group">
                        <label for="grade">Class: </label>
                        <select id="grade" name="grade" class="form-control">
                            <option value="" selected="selected">Select Class</option>
                            <?php while ($data = mysqli_fetch_assoc($resultClasses)): ?>
                                <option value="<?php echo $data['Grade']; ?>">
                                    <?php echo $data['Grade'] . " (" . $data['Grade'] . ")"; ?>
                                </option>  
                            <?php endwhile; ?>
                        </select> 
                    </div>

                    <button id="viewData" class="btn btn-primary">View Marks</button>
                </form>
            </div>
        </div>
    </div> 
</div> 

<!-- Data Container -->
<div class="container-fluid">
    <div id="myPage"></div>
</div>

<p class="footer-text">The PIO HS Markbook - John Thompson 2026 email: john@teacherjohn.org</p>

<!-- Scripts -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    // Basic status check logic from original file
    var status = window.name;
    if (status !== 'OK') { 
        $("#everything").hide();
        $('#login').show();
    } else { 
        $("#everything").show();
        $('#login').hide();
    }

    $('#viewData').on('click', function() {
        var testType = $("input[name='testType']:checked").val();
        var grade = $('#grade option:selected').val().trim();
        var yearMonth = $('#yearMonth option:selected').val().trim();

        if (grade === "") {
            alert("You need to select a class."); 
            return;
        }
        if (yearMonth === "") {
            alert("You need to select the month."); 
            return;
        }

        // Hide errors if previous run failed
        $('#errorMessage').hide();

        $.ajax({
            dataType: 'text',
            type: 'post',
            url: 'hsResultTotalsMonth.php',
            data: { grade: grade, yearMonth: yearMonth, testType: testType },
            success: function(response) {
                // Checking for the '!' error indicator exactly as your previous code did
                if (response.substring(0, 15).indexOf('!') !== -1) { 
                    $('#errorMessage').html(response).show();
                    alert("Data retrieval error. Check the yellow banner.");
                } else {
                    // Inject the HTML table
                    $('#myPage').html(response); 
                    
                    // Initialize DataTables for modern sorting/searching
                    // We check if it's already a DataTable to prevent re-initialization errors
                    var $table = $('#myPage').find('table');
                    if ($table.length > 0) {
                        // Add Bootstrap table classes if they aren't included in print_query_data_plain.php
                        $table.addClass('table table-striped table-bordered table-hover');
                        
                        if ($.fn.DataTable.isDataTable($table)) {
                            $table.DataTable().destroy();
                        }
                        
                        $table.DataTable({
                            "pageLength": 25,
                            "order": [], // Let the original SQL ordering stand initially
                            "language": {
                                "search": "Filter students:"
                            }
                        });
                    }
                }  
            },
            error: function(xhr, textStatus, errorThrown) {
                alert('AJAX request failed.');
            }
        });
    });
});
</script>

</body>
</html>