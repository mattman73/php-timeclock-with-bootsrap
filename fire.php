<?php
/***************************************************************************
 *   Copyright (C) 2006 by Ken Papizan                                     *
 *   Copyright (C) 2008 by phpTimeClock Team                               *
 *   http://sourceforge.net/projects/phptimeclock                          *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 *   This program is distributed in the hope that it will be useful,       *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *   GNU General Public License for more details.                          *
 *                                                                         *
 *   You should have received a copy of the GNU General Public License     *
 *   along with this program; if not, write to the                         *
 *   Free Software Foundation, Inc.,                                       *
 *   51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA.             *
 ***************************************************************************/

/**
 * This module creates the current status information of the employees in
 * right area of the interface.
 */

session_start();

$self = $_SERVER['PHP_SELF'];
$request = $_SERVER['REQUEST_METHOD'];

include 'config.inc.php';
include 'header.php';


if (! isset($_GET['printer_friendly'])) {
    if (isset($_SESSION['valid_user'])) {
        $set_logout = "1";
    }

include 'theme/templates/mainstart.inc';
    //include 'topmain.php';
    //include 'leftmain.php';
}

if ($request=='GET'){



$current_page = "Quick_Report.php";

if (! isset($_GET['printer_friendly'])) {
    echo " <!-- debug caller 1 --> ";
}

// code to allow sorting by Name, In/Out, Date, Notes //
// Not very bootstrappy //


if ($show_display_name == "yes") {
    if (! isset($_GET['sortcolumn'])) {
        $sortcolumn = "displayname";
    } else {
        $sortcolumn = $_GET['sortcolumn'];
    }
} else {
    if (! isset($_GET['sortcolumn'])) {
        $sortcolumn = "fullname";
    } else {
        $sortcolumn = $_GET['sortcolumn'];
    }
}

if (! isset($_GET['sortdirection'])) {
    $sortdirection = "asc";
} else {
    $sortdirection = $_GET['sortdirection'];
}

if ($sortdirection == "asc") {
    $sortnewdirection = "desc";
} else {
    $sortnewdirection = "asc";
}


// determine what users, office, and/or group will be displayed on main page //
if (($display_current_users == "yes") && ($display_office == "all") && ($display_group == "all")) {
    $current_users_date = strtotime(date($datefmt));
    $calc = 86400;
    $a = $current_users_date + $calc - @$tzo;
    $b = $current_users_date - @$tzo;

    $query = "select ".$db_prefix."info.*, ".$db_prefix."employees.*, ".$db_prefix."punchlist.* from ".$db_prefix."info, ".$db_prefix."employees, ".$db_prefix."punchlist where ".$db_prefix."info.timestamp = ".$db_prefix."employees.tstamp and ".$db_prefix."info.fullname = ".$db_prefix."employees.empfullname and ".$db_prefix."info.`inout` = ".$db_prefix."punchlist.punchitems and ((".$db_prefix."info.timestamp < '".$a."') and (".$db_prefix."info.timestamp >= '".$b."')) and ".$db_prefix."employees.disabled <> '1' and ".$db_prefix."employees.empfullname <> 'admin' order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "yes") && ($display_office != "all") && ($display_group == "all")) {
    $current_users_date = strtotime(date($datefmt));
    $calc = 86400;
    $a = $current_users_date + $calc - @$tzo;
    $b = $current_users_date - @$tzo;

    $query = "select ".$db_prefix."info.*, ".$db_prefix."employees.*, ".$db_prefix."punchlist.* from ".$db_prefix."info, ".$db_prefix."employees, ".$db_prefix."punchlist where ".$db_prefix."info.timestamp = ".$db_prefix."employees.tstamp and ".$db_prefix."info.fullname = ".$db_prefix."employees.empfullname and ".$db_prefix."info.`inout` = ".$db_prefix."punchlist.punchitems and ".$db_prefix."employees.office = '".$display_office."' and ((".$db_prefix."info.timestamp < '".$a."') and (".$db_prefix."info.timestamp >= '".$b."')) and ".$db_prefix."employees.disabled <> '1' and ".$db_prefix."employees.empfullname <> 'admin' order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "yes") && ($display_office == "all") && ($display_group != "all")) {
    $current_users_date = strtotime(date($datefmt));
    $calc = 86400;
    $a = $current_users_date + $calc - @$tzo;
    $b = $current_users_date - @$tzo;

    $query = "select ".$db_prefix."info.*, ".$db_prefix."employees.*, ".$db_prefix."punchlist.* from ".$db_prefix."info, ".$db_prefix."employees, ".$db_prefix."punchlist where ".$db_prefix."info.timestamp = ".$db_prefix."employees.tstamp and ".$db_prefix."info.fullname = ".$db_prefix."employees.empfullname and ".$db_prefix."info.`inout` = ".$db_prefix."punchlist.punchitems and ".$db_prefix."employees.groups = '".$display_group."' and ((".$db_prefix."info.timestamp < '".$a."') and (".$db_prefix."info.timestamp >= '".$b."')) and ".$db_prefix."employees.disabled <> '1' and ".$db_prefix."employees.empfullname <> 'admin' order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "yes") && ($display_office != "all") && ($display_group != "all")) {
    $current_users_date = strtotime(date($datefmt));
    $calc = 86400;
    $a = $current_users_date + $calc - @$tzo;
    $b = $current_users_date - @$tzo;

    $query = "select ".$db_prefix."info.*, ".$db_prefix."employees.*, ".$db_prefix."punchlist.* from ".$db_prefix."info, ".$db_prefix."employees, ".$db_prefix."punchlist where ".$db_prefix."info.timestamp = ".$db_prefix."employees.tstamp and ".$db_prefix."info.fullname = ".$db_prefix."employees.empfullname and ".$db_prefix."info.`inout` = ".$db_prefix."punchlist.punchitems and ".$db_prefix."employees.office = '".$display_office."' and ".$db_prefix."employees.groups = '".$display_group."' and ((".$db_prefix."info.timestamp < '".$a."') and (".$db_prefix."info.timestamp >= '".$b."')) and ".$db_prefix."employees.disabled <> '1' and ".$db_prefix."employees.empfullname <> 'admin' order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "no") && ($display_office == "all") && ($display_group == "all")) {
    $query = "select ".$db_prefix."info.*, ".$db_prefix."employees.*, ".$db_prefix."punchlist.* from ".$db_prefix."info, ".$db_prefix."employees, ".$db_prefix."punchlist where ".$db_prefix."info.timestamp = ".$db_prefix."employees.tstamp and ".$db_prefix."info.fullname = ".$db_prefix."employees.empfullname and ".$db_prefix."info.`inout` = ".$db_prefix."punchlist.punchitems and ".$db_prefix."employees.disabled <> '1' and ".$db_prefix."employees.empfullname <> 'admin' order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "no") && ($display_office != "all") && ($display_group == "all")) {
    $query = "select ".$db_prefix."info.*, ".$db_prefix."employees.*, ".$db_prefix."punchlist.* from ".$db_prefix."info, ".$db_prefix."employees, ".$db_prefix."punchlist where ".$db_prefix."info.timestamp = ".$db_prefix."employees.tstamp and ".$db_prefix."info.fullname = ".$db_prefix."employees.empfullname and ".$db_prefix."info.`inout` = ".$db_prefix."punchlist.punchitems and ".$db_prefix."employees.office = '".$display_office."' and ".$db_prefix."employees.disabled <> '1' and ".$db_prefix."employees.empfullname <> 'admin' order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "no") && ($display_office == "all") && ($display_group != "all")) {
    $query = "select ".$db_prefix."info.*, ".$db_prefix."employees.*, ".$db_prefix."punchlist.* from ".$db_prefix."info, ".$db_prefix."employees, ".$db_prefix."punchlist where ".$db_prefix."info.timestamp = ".$db_prefix."employees.tstamp and ".$db_prefix."info.fullname = ".$db_prefix."employees.empfullname and ".$db_prefix."info.`inout` = ".$db_prefix."punchlist.punchitems and ".$db_prefix."employees.groups = '".$display_group."' and ".$db_prefix."employees.disabled <> '1' and ".$db_prefix."employees.empfullname <> 'admin' order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "no") && ($display_office != "all") && ($display_group != "all")) {
    $query = "select ".$db_prefix."info.*, ".$db_prefix."employees.*, ".$db_prefix."punchlist.* from ".$db_prefix."info, ".$db_prefix."employees, ".$db_prefix."punchlist where ".$db_prefix."info.timestamp = ".$db_prefix."employees.tstamp and ".$db_prefix."info.fullname = ".$db_prefix."employees.empfullname and ".$db_prefix."info.`inout` = ".$db_prefix."punchlist.punchitems and ".$db_prefix."employees.office = '".$display_office."' and ".$db_prefix."employees.groups = '".$display_group."' and ".$db_prefix."employees.disabled <> '1' and ".$db_prefix."employees.empfullname <> 'admin' order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
}

$defaulttimezone = date_default_timezone_get();
$time = time();
$tclock_hour = date('H',$time);
$tclock_min = date('i',$time);
$tclock_sec = date('s',$time);
$tclock_month = date('m',$time);
$tclock_day = date('d',$time);
$tclock_year = date('Y',$time);
// $tclock_stamp = mktime($tclock_hour, $tclock_min, $tclock_sec, $tclock_month, $tclock_day, $tclock_year);
$tclock_stamp = time($tclock_hour, $tclock_min, $tclock_sec, $tclock_month, $tclock_day, $tclock_year);

//$tclock_stamp = $tclock_stamp;
$tclock_time = date($timefmt, $tclock_stamp);
$tclock_date = date($datefmt, $tclock_stamp);
$report_name="Current Status Report";

echo '  <!-- start misc -->
	<section class="content-header">
	      <h1>'
	        .$report_name.
	        '<small> As of: '.$tclock_time.', '.$tclock_date.'</small>
	      </h1>
        </section>';
        
        
// form post
echo "<form name='form' action='$self' method='post'>\n";

// add submit button
echo '
    <button type="submit" name="submit" value="Submitlist" class="btn btn-info">Submit List</button>   
    ';

// Add the current status of the employees are retrieved from the querry stored in $result
include 'evacuation_display.php';

}

elseif ($request=='POST'){


// post

$defaulttimezone = date_default_timezone_get();
$time = time();
$tclock_hour = date('H',$time);
$tclock_min = date('i',$time);
$tclock_sec = date('s',$time);
$tclock_month = date('m',$time);
$tclock_day = date('d',$time);
$tclock_year = date('Y',$time);
// $tclock_stamp = mktime($tclock_hour, $tclock_min, $tclock_sec, $tclock_month, $tclock_day, $tclock_year);
$tclock_stamp = time($tclock_hour, $tclock_min, $tclock_sec, $tclock_month, $tclock_day, $tclock_year);

//$tclock_stamp = $tclock_stamp;
$tclock_time = date($timefmt, $tclock_stamp);
$tclock_date = date($datefmt, $tclock_stamp);

$checkbox1=$_POST['accountedfor'];  
$chk="";
$peoplecount=0;

foreach($checkbox1 as $chk1)  
   {  
       // get info from database regarding user for saving to fire register table
       // Name
       // clock in status
       // date
       // time
      $tempchk =  str_replace('_', ' ', $chk1);
      $query = "SELECT * FROM timeclock.employees WHERE empfullname ='". $tempchk ."';";
      $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
      // pull info from table
      while ($row = mysqli_fetch_array($result)) {
        $display_stamp = "".$row["tstamp"]."";
        $time = date($timefmt, $display_stamp);
        $date = date($datefmt, $display_stamp);
        $display_name = $row["displayname"];
        $employees_inout = $row["employees_inout"];
        $accountedfor = true;
      }

      // build value input
      $rowvalue = "(default,'" . $tempchk ."','" . $time . "','" . $date . "','" . $employees_inout ."','" .
                         $tclock_date . "','" . $tclock_time . "','" . $accountedfor . "')";

       // build first part of query for insert
       // insert into timeclock.fire_register (Idx, fr_employeesname,fr_date,fr_time, fr_employeeinout,fr_reportdate,fr_reporttime,fr_accountedfor)
       // VALUES (default, 'Alexandra Petcu','14:03','06/Nov/2020','out','15:42','21/Jan/2121','1')
       $firstquery = "insert into timeclock.fire_register (Idx, fr_employeesname,fr_date,fr_time, fr_employeeinout,fr_reportdate,fr_reporttime,fr_accountedfor) VALUES $rowvalue";
       $result1 = mysqli_query($GLOBALS["___mysqli_ston"], $firstquery);

       if($result1==1){
        //echo'<script>alert("Inserted Successfully")</script>'; 
        $Susscessfullyupdated=true;
        $peoplecount=+1;
       }
       else
       {
        $Susscessfullyupdated=false;
        //echo'<script>alert("Failed To Insert")</script>';
       }
   }  


// show update page
if ($Susscessfullyupdated==true){
    // all update ok
    // show the total of added people
    echo "<h1>Fire register report added to the system </h1> " .
         "<td>Report date ". $tclock_date . "-" . $tclock_time . "</td>";

    // SELECT * FROM timeclock.fire_register
    // WHERE fr_reportdate = '22/Jan/2121';
    $query = "SELECT * FROM timeclock.fire_register  WHERE fr_reportdate = '" . $tclock_date . "'";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    include 'display_accountedfor.php';

}
else
{
    // error in update

}
//   $in_ch=mysqli_query($con,"insert into request_quote(technology) values ('$chk')");  
//if($in_ch==1)  
//   {  
//      echo'<script>alert("Inserted Successfully")</script>';  
//   }  
//else  
//   {  
//      echo'<script>alert("Failed To Insert")</script>';  
//  }  
  

}

?>

