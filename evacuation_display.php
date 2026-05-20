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
 * This module creates the employee current/previous status table.
 */

$row_count = 0;
$page_count = 0;

// Add the Message of the day
echo '
        <div class="row">
          <div class="col-xs-12">
            <div class="box">
         <!--     <div class="box-header">
                <h3 class="box-title">Responsive Hover Table</h3>	
              </div>   
	      -->
              <!-- /.box-header -->
              <div class="box-body table-responsive no-padding">
	      <!-- Current Display Messages -->
	      <table class="table table-hover">
';




// Parse the employee info in the result array
while ($row = mysqli_fetch_array($result)) {
    $display_stamp = "".$row["timestamp"]."";
    $time = date($timefmt, $display_stamp);
    $date = date($datefmt, $display_stamp);

    if ($row_count == 0) {
        if ($page_count == 0) {

        } else {
            // display report name and page number of printed report above the column headings of each printed page //
            $temp_page_count = $page_count + 1;
        }

        echo "
                    <tr>";

        if ($display_notes == "yes") {
        echo "
                            <th>
                                Check
                            </th>";
        }

        if ($display_name == "yes") {
            echo "
                              <th>
                                 Name
                              </th>";
        }

        if ($display_status == "yes") {
            echo "
                              <th>
                                 Status
                              </th>";
        }

        if ($display_date == "yes") {
            echo "
                              <th>
                                 Date
                              </th>";
        }

        if ($display_time == "yes") {
            echo "
                              <th>
                                 Time
                              </th>";
        }

        if ($display_office_name == "yes") {
            echo "
                              <th>
                                 Office
                              </th>";
        }

        if ($display_group_name == "yes") {
            echo "
                              <th>
                                 Group
                              </td>";
        }

       

        echo "
                           </tr>";
    }

    // begin alternating row colors //
    $row_color = ($row_count % 2) ? $color1 : $color2;

    // display the query results //
    $display_stamp = $display_stamp;
    $time = date($timefmt, $display_stamp);
    $date = date($datefmt, $display_stamp);

    echo "
                           <tr>";
    if ($display_notes == "yes") {
    echo "
                            <td>
                            <input type='checkbox' name='accountedfor[]' value=". str_replace(' ', '_', $row["displayname"]).">
                            </td>  
                            ";
    }
    if ($display_name == "yes") {
        if ($show_display_name == "yes") {
            echo stripslashes("
                              <td>
                                 ".$row["displayname"]."
                              </td>");
        } elseif ($show_display_name == "no") {
            echo stripslashes("
                              <td>
                                 ".$row["empfullname"]."
                              </td>");
        }
    }

    if ($display_status == "yes") {
        // Get in or out status of the current status
        $status_query = "SELECT * FROM ".$db_prefix."punchlist ORDER BY punchitems ASC";
        $status = mysqli_query($GLOBALS["___mysqli_ston"], $status_query);

        while ($status_row = mysqli_fetch_array($status)) {
            if ($status_row['punchitems'] == $row["inout"]) {
                echo "
                             <!-- <td> -->";

                if ((($display_status_option == "icon") || ($display_status_option == "both")) && $status_row['in_or_out'] == 0) { // An out status icon
		    echo '<td class="text-red"><i class="glyphicon glyphicon-log-out"></i>';
                } else if ((($display_status_option == "icon") || ($display_status_option == "both")) && $status_row['in_or_out'] == 1) { // An in status icon
                    echo '<td class="text-green"><i class="glyphicon glyphicon-log-in"></i>';
                }

                if (($display_status_option == "text") || ($display_status_option == "both")) { // Add the status.
                    echo "
                                 ".$row["inout"];
                }
                echo "
                              </td> ";
                break;
            }
        }
        ((mysqli_free_result($status) || (is_object($status) && (get_class($status) == "mysqli_result"))) ? true : false);
    }

    if ($display_date == "yes") {
        echo "
                              <td>
                                 ".$date."
                              </td>";
        }

    if ($display_time == "yes") {
        echo "
                              <td>
                                 ".$time."
                              </td>";
    }

    if ($display_office_name == "yes") {
        echo "
                              <td>
                                 ".$row["office"]."
                              </td>";
    }

    if ($display_group_name == "yes") {
        echo "
                              <td>
                                 ".$row["groups"]."
                              </td>";
    }


    // <input type='checkbox' name='techno[]' value='PHP'> 
   //".$row["notes"]." accounted for

    echo "
                           </tr>";
    $row_count++;

    // output 40 rows per printed page //
    // remove new page breakout //
    //if ($row_count == 40) {
    //    echo "
    //                       <tr style=\"page-break-before:always;\">
    //                       </tr>";
    //    $row_count = 0;
    //    $page_count++;
    //}
}
echo "
                        </table> 
      </div>
      <!-- /.box-body -->
    </div>
    <!-- /.box -->
  </div>
</div>";

//if (! isset($_GET['printer_friendly'])) {
///    echo "
//<!-- debug end of display.php-->";
//}
((mysqli_free_result($result) || (is_object($result) && (get_class($result) == "mysqli_result"))) ? true : false);
?>
