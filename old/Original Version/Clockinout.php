<?php

/**
* This module creates the interface for an employee to punch their status.
*/

session_start();

include 'config.inc.php';
include 'header.php';
include './pinpad/demos/custom-command.inc';

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

.containerold {
  margin: 0 auto;
  width:275px;
  height:75px;
  position: fixed;
  background-color: #00FF00;
  top: 50%;
  left: 50%;
  margin-top: -100px;
  margin-left: -100px;
}

.centerDiv
	{
		width: 45%;
		height:200px;
		margin: 0 auto;
		background-color:#FFA500 ;
		position: fixed;
  		top: 50%;
  		left: 40%;
  		margin-top: -100px;
  		margin-left: -100px;
	}
	
	.callout callout-success
	{
		width: 50%;
		height:70px;
		background-color:#A52A2A ;
		margin: 0 auto;
	}
	.container
	{
		width: 50%;
		height:70px;
		background-color:#FFA500 ;
		margin: 0 auto;
	}
	.div2
	{
		width: 70%;
		height:70px;
		background-color:#0081111 ;
		margin: 0 auto;
	}
	.div3
	{
		width: 50%;
		height:70px;
		background-color:#008000 ;
		margin: 0 auto;
	}

</style>';

echo "<div class='centerDiv'>";

echo "<form id='Clockinout' autocomplete='off' role='form' name='Clockinout' action='$self' method='post'>";

echo '
    <div class="container">
    <h1>Please clock in</h1>
	</div>';

echo "<div class='div2'><input id='pinpad' title='Click here' name='BadgeID' style=font-size:18pt;>";

echo "
    <button type='submit' class='btn btn-lg btn-primary'>Enter</button>
    </div>";

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
    // We need to get the full name if we're only displaying the display name
    if ($show_display_name == "yes") {
        $query = "select * from ".$db_prefix."employees where employees_BadgeID  = '".$BadgeID."'";
        $sel_result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        while ($row = mysqli_fetch_array($sel_result)) {
            $fullname = stripslashes("".$row["empfullname"]."");
            $fullname = addslashes($fullname);
            $BadgeID = $row["employees_BadgeID"];
            $currentinout = $row["employees_inout"];
        }
    }

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
  //  $tz_stamp = mktime ($hour, $min, $sec, $month, $day, $year);
  // testing better ways 
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
	    echo '
 <div class="callout callout-success">
                <h4><i class="fa fa-bullhorn"></i> </h4>
                <p> Status changed successfully for '.$fullname.' to a status of '.$inout.'.</p>
</div></form>';

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

