<?php

/**
* This module creates the interface for an employee to punch their status.
*/

session_start();

include 'config.inc.php';
include 'header.php';
//include './pinpad/demos/custom-command.inc';
include 'theme/templates/mainstart.inc';
include 'Clockinout_topmain.php';

// javascript
echo "<script>
    function addNumber(element){
        var input = document.getElementById('pinpad').value;
        

        if (element.value=='<') {
                document.getElementById('pinpad').value = input.substr(0,input.length-1);
        }
        else
        {
            if (input.length>3){
                exit;
            }
            document.getElementById('pinpad').value = document.getElementById('pinpad').value+element.value;
        }

  }
  </script>
  ";

if (! isset($_GET['printer_friendly'])) {
    if (isset($_SESSION['valid_user'])) {
        $set_logout = "1";
    }
}

$self = $_SERVER['PHP_SELF'];
$request = $_SERVER['REQUEST_METHOD'];

// display form to submit signin/signout information //
echo '<style>
body {
  background: #2352B5;
}
h1 { color: #FFFFFF; }

.centerDiv
	{
		width: 70%;
		height:450px;
		margin: 0 auto;
		background-color:#2352B5 ;
		position: fixed;
  		top: 10%;
  		left: 50%;
  		margin-top: -0px;
  		margin-left: -100px;
	}
	
	.callout callout-success
	{
		width: 50%;
		height:70px;
        margin: 0 auto;
        position: fixed;
    }
    
    .callout callout-info
	{
		width: 50%;
		height:70px;
        margin: 0 auto;
        position: fixed;
    }
	.container
	{
		width: 100%;
		height:175px;
		background-color:#2352B5 ;
		margin: 0 auto;
	}
	.div2
	{
		width: 100%;
		height:175px;
		background-color:#2352B5 ;
        margin: 0 auto;
        
    }

</style>';

echo "<form id='Clockinout' onload='startTime()' autocomplete='off' role='form' name='Clockinout' action='$self' method='post'>";
include 'AnalogClock.html';
echo "</div>";

echo "<div class='centerDiv'>";

echo "
    <div class='container'>
    <div style='width: 75%; float:right' font color='white'><h1>Scan barcode</h1></div>";
	//</div>';


/*echo "<div style='width: 25%; float:left'><input type='button' class='fbutton' name='1' value='1' id='1'  style=font-size:60pt;  onClick=addNumber(this); />
<input type='button' class='fbutton' name='2' value='2' id='2' style=font-size:60pt; onClick=addNumber(this); />
<input type='button' class='fbutton' name='3' value='3' id='3' style=font-size:60pt; onClick=addNumber(this); />
<br>
<input type='button' class='fbutton' name='4' value='4' id='4' style=font-size:60pt; onClick=addNumber(this); />
<input type='button' class='fbutton' name='5' value='5' id='5' style=font-size:60pt; onClick=addNumber(this); />    
<input type='button' class='fbutton' name='6' value='6' id='6' style=font-size:60pt; onClick=addNumber(this); />
<br>
<input type='button' class='fbutton' name='7' value='7' id='7' style=font-size:60pt; onClick=addNumber(this); />
<input type='button' class='fbutton' name='8' value='8' id='8' style=font-size:60pt; onClick=addNumber(this); />
<input type='button' class='fbutton' name='9' value='9' id='9' style=font-size:60pt; onClick=addNumber(this); />
<br><br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type='button' class='fbutton' name='0' value='0' id='0' style=font-size:60pt; onClick=addNumber(this); />
<input type='button' class='fbutton' name='backspace' value='<' id='space2' style=font-size:60pt; onClick=addNumber(this); />
</div>";
*/
// <input type='button' class='fbutton' name='space1' value='  ' id='space1' style=font-size:75pt disabled; />
// <input type='button' class='fbutton' name='space2' value='  ' id='space2' style=font-size:75pt disabled; />
echo "<div class='div2'><div style='width: 75%; float:right'><input id='pinpad' onblur='this.focus()' autofocus='autofocus' title='Click here' name='BadgeID' style='text-align:center;font-size:65pt; width:200px;'><br>";
echo "<button type='submit' class='btn btn-lg btn-primary'  style='display:none;' style=font-size:58pt>Enter</button></div></div>";

// End leftnav here and put the rest in main.	
/*
echo '
	<div class="row">
	<!-- extra messages -->
	';

echo "
";
*/

if ($request == 'POST') { // Process employee's punch information
    // signin/signout data passed over from timeclock.php //
    $inout = $_POST['left_inout'];
    $displayname = $_POST['left_displayname'];
    $BadgeID = $_POST['BadgeID'];

//    $notes = ereg_replace("[^[:alnum:] \,\.\?-]","",strtolower($_POST['left_notes']));
    $notes = preg_replace("[^[:alnum:] \,\.\?-]","",strtolower($_POST['left_notes']));

    // begin post validation //
    if ($use_passwd == "yes") {
        $employee_passwd = crypt($_POST['employee_passwd'], 'xy');
    }

    $query = "select punchitems from ".$db_prefix."punchlist";
    $punchlist_result = mysqli_query($GLOBALS["___mysqli_ston"], $query);

    while ($row = mysqli_fetch_array($punchlist_result)) {
        $tmp_inout = "".$row['punchitems']."";
    }

    if (! isset($tmp_inout)) {
	    echo '<div class="col-md-4">
 <div class="callout callout-danger">
                <h4><i class="fa fa-bullhorn"></i> Error</h4>
                <p>Status is not in the database.</p>
</div>
</div>';

        exit;
    }
    // end post validation //


    // Get all the possible punch status names
    $query = "select punchitems from ".$db_prefix."punchlist";
    $punchlist_result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    // We need to get the full name
    $query = "select * from ".$db_prefix."employees where employees_BadgeID  = '".$BadgeID."'";
    $sel_result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    while ($row = mysqli_fetch_array($sel_result)) {
        $fullname = stripslashes("".$row["empfullname"]."");
        $fullname = addslashes($fullname);
        $BadgeID = $row["employees_BadgeID"];
        $currentinout = $row["employees_inout"];
    }

    // Not given the option to select in or out 
    // just swap info from database.
    if ($currentinout=='in')
    {
        $inout = 'out';
    }
    else
    {
        $inout = 'in';
    }

    @$fullname = addslashes($fullname);
    @$displayname = addslashes($displayname);

    // configure timestamp to insert/update //
    $time = time();
    $hour = date('H',$time);
    $min = date('i',$time);
    $sec = date('s',$time);
    $month = date('m',$time);
    $day = date('d',$time);
    $year = date('Y',$time);
    $tz_stamp = time ($hour, $min, $sec, $month, $day, $year);

    if ($show_display_name == "yes") {
        $sel_query = "select * from ".$db_prefix."employees where employees_BadgeID = '".$BadgeID."'";
        $sel_result = mysqli_query($GLOBALS["___mysqli_ston"], $sel_query);

        while ($row=mysqli_fetch_array($sel_result)) {
            $fullname = stripslashes("".$row["empfullname"]."");
            $fullname = addslashes($fullname);
		    //$BadgeID = $row["employees_BadgeID"];
        }
    }

    if (strtolower($ip_logging) == "yes") {
        $query = "insert into ".$db_prefix."info (fullname, BadgeID, `inout`, timestamp, notes, ipaddress) values ('".$fullname."','".$BadgeID."', '".$inout."', '".$tz_stamp."', '".$notes."', '".$connecting_ip."')";
    } else {
        $query = "insert into ".$db_prefix."info (fullname, BadgeID, `inout`, timestamp, notes) values ('".$fullname."','".$BadgeID."', '".$inout."', '".$tz_stamp."', '".$notes."')";
    }

    
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);

    //$update_query = "update ".$db_prefix."employees set tstamp = '".$tz_stamp."' where employees_BadgeID = '".$BadgeID."'";
    $update_query = "update ".$db_prefix."employees set tstamp = '".$tz_stamp."', employees_inout = '".$inout."' where empfullname = '".$fullname."'";
    $other_result = mysqli_query($GLOBALS["___mysqli_ston"], $update_query);

    if ($currentinout=='in'){
        echo '
        <div style="width: 550px; float:left">
        <div class="callout callout-info">
<br>
                       <h1><i class="fa fa-bullhorn" ></i> '.$fullname.'</h1>
                        <h1><p> Action Type: Clock '.$inout.'.</p></h1>

       </div></div>';
       echo "<head> <meta http-equiv='refresh' content=5;url=Clockinout.php></head>";
    }
    else {
        echo '
        <div style="width: 550px; float:left">
        <div class="callout callout-success">
<br>
                        <h1><i class="fa fa-bullhorn" ></i> '.$fullname.'</h1>
                        <h1><p> Action Type: Clock '.$inout.'.</p></h1>
        </div></div>';
        echo "<head> <meta http-equiv='refresh' content=5;url=Clockinout.php></head>";
    }

    echo '</form></body></html>';
}

// Determine if we should add the message of the day
/*
if (! isset($_GET['printer_friendly']) && ($message_of_the_day != "none")) {
	echo '
		<!-- Message Of The Day Display -->
	        <div class="col-md-4">
		<div class="callout callout-success">
                <h4>Message Of The Day:</h4>

                <p>'.htmlspecialchars($message_of_the_day).'</p>
              </div>
	      </div>
	      ';
	      

} else if (! isset($_GET['printer_friendly']) && ($message_of_the_day == "none")) {
    echo " ";
}
*/
//echo "</div>"
?>

