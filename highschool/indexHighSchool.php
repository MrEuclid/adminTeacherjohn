<?php 
 require_once "../authCheckPIO.php";
 restrictToAdmin();
include "../connectDatabase.php"; 
include "../yearMonth.php";

echo "Today is " . date('d-M-Y') .  "<br>";

ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>High School Marks home page</title>
    <!-- <link rel="stylesheet" href="css/dentalStyles.css"> -->
    <link href='https://fonts.googleapis.com/css?family=Khmer' rel='stylesheet' type='text/css'>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <!-- Removed old jQuery 1.11.3 to prevent conflicts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>

    <style type="text/css">
        h1 {text-align:center; font-size:24pt; color:blue; font-weight:bold;}
        h2 {text-align:center; font-size:18pt; color:green; font-weight:bold;}
        h3 {text-align:center; font-size:16pt; color:red; font-weight:bold;}
        h4 {text-align:center; font-size:14pt; color:blue; font-weight:bold;}
        .c {text-align: center; margin-left: auto;margin-right: auto;}
        .l {text-align: left;}
        .r {text-align: right;}
        #errorMessage {background-color: yellow; color: red; text-align: center;}
    </style>   
</head>

<body>


<div class="container">

    <!-- LOGIN SECTION -->
    <div id="login">
        <div class="row">
            <div class="col-sm-12 c">
                <h1>PIO High School Markbook
                    <button class="btn btn-warning shadow-sm px-6 font-bold" id="back" onclick="history.back()">GO BACK</button>
                </h1>
                <br><br>
                <h3>Please log in</h3>
                <label>Passwordv2<input type="password" id="pwdText"></label>
                <button id="pwd">Log in 2</button>
            </div>
        </div>
    </div>

    <!-- MAIN APP SECTION -->
    <div id="everything">
        <div class="row">
            <div class="col-sm-12 c">
                <?php 
                include "menu.html"; 

                // get list of all subjects
                $querySubjects = "SELECT code,english,khmer FROM hsSubjects ORDER BY code";
                $resultSubjects = mysqli_query($dbServer,$querySubjects);

                // get classes ordered 
                $queryClasses  = "SELECT DISTINCT Grade FROM New_ID_Year_Grade WHERE School = 'PIOHS' AND Year = '$year' ORDER BY CAST(substr(Grade,2,2) AS UNSIGNED)";
                $resultClasses = mysqli_query($dbServer,$queryClasses);

              //  $queryTests  = "SELECT * from hsTestCodes ORDER BY id";
              //  $resultTests = mysqli_query($dbServer,$queryTests);

                // array of months including the present month
                $monthArray = [];
                for ($m = 1; $m <= $month; $m++) {
                    $fullMonth = ($m < 10) ? '0' . $m : $m;
                    if ($m > 9) {
                        $monthArray[$m] = $y . "-" . $fullMonth;
                    } else { 
                        $monthArray[$m] = $year . "-" . $fullMonth;
                    }
                }

                $monthArray = [];
                $oldYear = $year - 1;
                $oldDate = $oldYear . "-09";

                $query = "SELECT distinct testID FROM hsMarks WHERE testID > concat(".$oldYear.",'-09') ORDER BY id DESC";
                $result = mysqli_query($dbServer,$query);
                $i = 0;
                while ($data = mysqli_fetch_row($result)) {
                    $monthArray[$i] = $data[0];
                    $i++;
                }

                if ($m < 9) {
                    $thisMonth = $year . "-" . $fullMonth;
                    array_push($monthArray, $thisMonth);
                }

                if ($m > 9) {
                    array_push($monthArray, ($year-1) . "-10");
                    array_push($monthArray, ($year-1) . "-11");
                    array_push($monthArray, ($year-1) . "-12");
                }

                // add this month 
                $now = date('Y-m');
                array_push($monthArray,$now);
                $monthArray = array_unique($monthArray);
                ?>
            </div>
        </div>
        
        <p id="errorMessage"></p>
        
        <div id="selectOptions">
            <br><br>
            <h2>Type of test</h2>
            <div class="row">
                <div class="col-sm-12 c">
                    <input type="radio" id="monthly" name="testType" value="month" checked="TRUE"> Monthly or 
                    <input type="radio" id="SEM1" name="testType" value="S1"> Semester 1
                    <input type="radio" id="SEM2" name="testType" value="S2"> Semester 2<br><br>
                    <input type="text" id="testID" readonly="TRUE">         
                </div>
            </div>

            <br>
            <h2>Month, Class & Subject</h2>

            <div class="row">
                <div class="col-sm-3 c">
                    <label>Month</label>
                    <select id="yearMonth" name="yearMonth">
                        <option value="" selected="selected">Month</option>
                        <?php foreach($monthArray as $m) { ?>
                            <option value="<?php echo $m; ?>"><?php echo $m; ?></option>  
                        <?php } ?>
                    </select> 
                </div>
                
                <div class="col-sm-5 c">
                    <label>Subject</label>
                    <select id="subject" name="subject">
                        <option value="" selected="selected">Subject</option>
                        <?php while ($data = mysqli_fetch_Assoc($resultSubjects)) { 
                            $code = $data["code"];
                            $message = $data["english"] . " " . $data["khmer"] . "  " . $data["code"];
                        ?>
                            <option value="<?php echo $code; ?>"><?php echo $message; ?></option>  
                        <?php } ?>
                    </select> 
                </div>
                
                <div class="col-sm-2 c">
                    <label>Class</label>
                    <select id="grade" name="grade">
                        <option value="" selected="selected">Class</option>
                        <?php while ($data = mysqli_fetch_Assoc($resultClasses)) { 
                            $class = $data["Grade"];
                            $message = $data["Grade"] . " " . $date["Year"] . " " . $data["School"];
                        ?>
                            <option value="<?php echo $class; ?>"><?php echo $message; ?></option>  
                        <?php } ?>
                    </select> 
                </div>
                
                <div class="col-sm-2 c">
                    <button id="sendData">Add marks</button>
                </div>
            </div>
        </div> <!-- /selectOptions -->
    </div> <!-- /everything -->

</div> <!-- /bootstrap container -->

<div id="myPage"></div>
<br><br>
<p class="c">The PIO HS Markbook - John Thompson 2026 email: john@teacherjohn.org</p>

<!-- AMALGAMATED JAVASCRIPT -->
<script type="text/javascript">
$(document).ready(function() {

    console.log("Hello! The jQuery block has successfully started!");
  $('#everything').hide();
     $('#selectOptions').show();
    // --- 1. On Load Setup ---

     
    var year = '<?php echo $year; ?>';
    var month = '<?php echo $month; ?>';
    var y = year;
    if (month > 9) { y = parseInt(year - 1); }
    var yearMonth = y + '-' + month;
    $('#testID').val(yearMonth);
    alert(y + ' ' + yearMonth);

    // INTENTIONALLY COMMENTED OUT HIDING LOGIC SO YOU CAN SEE EVERYTHING
    // $('#upload').hide();
    // window.name = "OK"; 
    // var status = window.name;
    // if (status != 'OK') { $("#everything").hide(); $('#login').show(); }
     if (status == 'OK') { $("#everything").show(); $('#login').hide(); }

    $('#pwdText').focus(); // Put cursor in password box

    // --- 2. Button Handlers ---

    // Password Login Button


    $('#pwd').on('click', function(e) {
        e.preventDefault(); 
        alert(this.id);
        if ($('#pwdText').val() != 'abc') {
            // Re-enabling the hide/show here so the button still functionally tests correctly
            $('#everything').show();
            $('#login').hide();
            window.name = "OK"; 
        } else {
            alert("The password is incorrect"); 
            $('#pwdStatus').val('N'); 
            $('#pwdText').val('');
            $('#pwdText').focus(); 
        }
    });

    // Logout Button
    $('#logout').on('click', function() {
        window.name = '';
        $('#pwdText').val(''); // Fixed missing #
        $('#login').show();
        $('#selectOptions').show();
    });

    // Semester Radio Buttons
    $('[id^=SEM]').on('click', function() {
        alert("Checked " + this.id);
        // $('#yearMonth').hide(); // Commented out to keep visible
        yearMonth = year + '-' + this.id;
        console.log(yearMonth, yearMonth.substring(5,8));
        $('#testID').val(yearMonth);
    });

    // Monthly Radio Button
    $('#monthly').on('click', function() {
        alert("Checked " + this.id);
        // $('#yearMonth').show(); // Commented out
        yearMonth = year + '-' + month;
        console.log(yearMonth);
        $('#testID').val(yearMonth);
    });

    // "Add" Button
    $('#add').on('click', function() {
        // $('#selectOptions').show(); // Commented out
    });

    // Send Data (Add Marks) Button
    $('#sendData').on('click', function() {
        var testType = $("input[name='testType']:checked").val();
        var subject = $('#subject option:selected').val();
        var grade = $('#grade option:selected').val();
        var currentYearMonth = $('#testID').val(); // Storing locally to avoid overwriting global

        alert(currentYearMonth);
        if (currentYearMonth.substring(5,8) != "SEM") {
            currentYearMonth = $('#yearMonth option:selected').val();
        }

        alert("Checking form " + subject + ' ' + grade + ' = ' + currentYearMonth);

        if (subject == "") { alert("You need a subject"); return; }  
        if (grade == "") { alert("You need a class"); return; }
        if (currentYearMonth == "") { alert("You need the month"); return; }

        $.ajax({
            dataType: 'text',
            type: 'get',
            url: 'addMarks.php',
            data: {subject: subject, grade: grade, yearMonth: currentYearMonth, testType: testType},
            success: function(response) {
                var i = response.indexOf('!');
                console.log(i, response[8], response[8] == '!');  
                
                // $('#selectOptions').hide(); // Commented out
                if (response[8] == '!') {
                    $('#errorMessage').html(response);
                    alert("edit error");
                    // $('#sendData').hide(); // Commented out
                    // $('#sendDataLabel').hide(); // Commented out
                } else {
                    // $('.wrapper').hide(); // Commented out
                    $('#myPage').html(response); 
                    // $('#sendData').show(); // Commented out
                    // $('#sendDataLabel').show(); // Commented out
                }  
            },
            error: function(xhr, textStatus, errorThrown) {
                alert('request failed');
            }
        });
    });
});
</script>
</body>
</html>