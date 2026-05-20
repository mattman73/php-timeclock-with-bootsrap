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
$display_status = 'no';
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


/* move to leftmain
// Determine if we should add the message of the day
if (! isset($_GET['printer_friendly']) && ($message_of_the_day != "none")) {
    echo "
                           <!-- Message Of The Day Display -->
                           <tr>
                              <td class=motd colspan=5>
                                 <strong> Message Of The Day: </strong> <br>
                                 ".htmlspecialchars($message_of_the_day)."
                              </td>";
} else if (! isset($_GET['printer_friendly']) && ($message_of_the_day == "none")) {
    echo "
                           <!-- Message Of The Day Display -->
                           <tr>
                              <td colspan=3 >
                                 &nbsp;
                              </td>";
}
end move to leftmain */ 

// Parse the employee info in the result array
while ($row = mysqli_fetch_array($result)) {
    //$display_stamp = "".$row["timestamp"]."";
    //$time = date($timefmt, $display_stamp);
    //$date = date($datefmt, $display_stamp);

    if ($row_count == 0) {
        if ($page_count == 0) {
            // display sortable column headings for main page //
	    

        } else {
            // display report name and page number of printed report above the column headings of each printed page //
            $temp_page_count = $page_count + 1;
        }

        echo "
                           <tr>";

        if ($display_name == "yes") {
            echo "
                              <th>
                                 Name
                              </th>";
        }

        echo "
                           </tr>";
    }

    // begin alternating row colors //
    $row_color = ($row_count % 2) ? $color1 : $color2;

    // display the query results //
    //$display_stamp = $display_stamp;
    //$time = date($timefmt, $display_stamp);
    //$date = date($datefmt, $display_stamp);

    echo "<tr>";
   echo stripslashes("
                        <td>
                           ".$row["fr_employeesname"]."
                        </td></tr>");
    $row_count++;
}
echo "
                        </table> 
      </div>
      <!-- /.box-body -->
    </div>
    <!-- /.box -->
  </div>
</div>";

if (! isset($_GET['printer_friendly'])) {
    echo "
<!-- debug end of display.php-->";
}
((mysqli_free_result($result) || (is_object($result) && (get_class($result) == "mysqli_result"))) ? true : false);
?>
