<?php
include 'func.inc';
PageHeader("Admin Interface");
session_start();
?>

<?php
// Login validation
if (isset($_REQUEST['func']) && $_REQUEST['func'] != "login") {
  $loginuser = $_SESSION["loginuser"];
  $loginpass = $_SESSION["loginpass"];

  $login_stmt = $con->prepare("SELECT * FROM users WHERE username = ?;");
  $login_stmt->bind_param("s", $loginuser);
  $login_stmt->execute();
  $login_query = $login_stmt->get_result();
  $pass_verify = mysqli_fetch_assoc($login_query);
  if (password_verify($loginpass, $pass_verify['password'])) {
    $loginresult = mysqli_fetch_assoc($login_query);
    $loginlevel = $pass_verify['superuser'];
    $_SESSION["user_name"] = $pass_verify['name'];
  } else {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?func=login&sysmsg=invalid');
  }
}

echo "<body>\n";
// Verify database version OK
if ($dbver != $config['db_version'] && $loginlevel == "1") {
  echo "<div style=\"text-align: center; background-color: #FFFF80\">\n";
  echo "Setup error: Fulcrum database is version " . $config['db_version'] . ", expected " . $dbver . ".<br>\n";
  echo "Please update the database. <br>\n";
  echo "</div>\n";
}

// Display system messages
echo "<table style=\"width:700px; margin-left:auto; margin-right:auto;\"><tr><td>";
if (isset($_REQUEST['sysmsg'])) {
  if ($_REQUEST['sysmsg'] == "logout") {
    echo "<p style=\"color: #00AA00; text-align: center;\">You have been logged out.</p>\n\n";
  } elseif ($_REQUEST['sysmsg'] == "login") {
    echo "<p style=\"color: #00AA00; text-align: center;\">You have been logged in.  Select a function above.</p>\n\n";
  } elseif ($_REQUEST['sysmsg'] == "unauthorized") {
    echo "<p style=\"color: #00AA00; text-align: center;\">Sorry, you are not allowed to access that module.</p>\n\n";
  } elseif ($_REQUEST['sysmsg'] == "invalid") {
    echo "<p style=\"color: #00AA00; text-align: center;\">You have entered an invalid username/password combination.</p>\n\n";
  } elseif ($_REQUEST['sysmsg'] == "acdeleted") {
    echo "<p style=\"color: #00AA00; text-align: center;\">The aircraft has been deleted.</p>\n\n";
  }
}

if (isset($_REQUEST["func"])) {
  // there is a function to perform
  switch ($_REQUEST["func"]) {
    case "login":
      if (isset($_REQUEST['username']) && $_REQUEST['username'] != "") {
        // user has submitted login form
        $_SESSION["loginuser"] = $_REQUEST['username'];
        $_SESSION["loginpass"] = $_REQUEST['password'];
        header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?sysmsg=login');
      } else {
        // print login form
        echo "<div class=\"titletext\">Fulcrum Administration</div>";
        echo "<form method=\"post\" action=\"admin.php\">\n";
        echo "<input type=\"hidden\" name=\"func\" value=\"login\">\n";
        echo "<table style=\"margin-left: auto; margin-right: auto;\">\n";
        echo "<tr><td style=\"text-align: right\">Username</td><td><input type=\"text\" name=\"username\"></td></td>\n";
        echo "<tr><td style=\"text-align: right\">Password</td><td><input type=\"password\" name=\"password\"></td></tr>\n";
        echo "<tr><td colspan=\"2\" style=\"text-align: center;\"><input type=\"submit\" value=\"Login\"></td></tr>\n";
        echo "</table></form>\n";
      }
      break;

    case "logout":
      session_unset();
      session_destroy();
      header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=login&sysmsg=logout');
      break;

    case "system":
      //verify user is superuser
      if ($loginlevel != "1") {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?sysmsg=unauthorized');
      }

      echo "<div class=\"titletext\">System Module</div>";
      if (isset($_REQUEST['message']) && $_REQUEST['message'] == "updated") {
        echo "<p style=\"color: #00AA00; text-align: center;\">Settings Updated.</p>\n\n";
      }
      if (isset($_REQUEST["func_do"]) && $_REQUEST["func_do"] == "update") {
        // user has submitted system config changes
        foreach ($_POST as $k => $v) {
          if ($k != "func" && $k != "func_do") {
            $sql_update_stmt = $con->prepare("UPDATE configuration SET `value` = ? WHERE `item` = ?;");
            $sql_update_stmt->bind_param("ss", $v, $k);
            $sql_update_stmt->execute();
            $audit_text = "UPDATE configuration SET " . $k . " = " . $v;
            AuditLog($loginuser, $audit_text);
          }
        }
        header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=system&message=updated');
      } else {
        // func = system but no func_do, ie. no DB update. Show instructions
        echo "<p>This module adjusts settings that affect the entire software package.</p>";
        echo "<form method=\"post\" action=\"admin.php\"><input type=\"hidden\" name=\"func\" value=\"system\"><input type=\"hidden\" name=\"func_do\" value=\"update\">";
        echo "<table style=\"margin-left: auto; margin-right: auto;\">"
          . "<tr><td style=\"text-align: right\">Site/Organization Name</td><td><input type=\"text\" name=\"site_name\" value=\"" . $config['site_name'] . "\"></td></tr>"
          . "<tr><td style=\"text-align: right\">Administrator E-mail Address</td><td><input type=\"email\" name=\"administrator\" value=\"" . $config['administrator'] . "\"></td></tr>"
          . "<tr><td style=\"text-align: right\">Local Time Zone</td><td>";
        TimeZoneList($config['timezone']);
        echo "</td></tr>"
          . "<tr><td colspan=\"2\" style=\"text-align: center;\"><input type=\"submit\" value=\"Save\"></td></tr></table></form>";
      }
      break;

    case "aircraft":
      echo "<div class=\"titletext\">Aircraft Module</div>";

      if (isset($_REQUEST["func_do"])) {
        switch ($_REQUEST["func_do"]) {
          case "add":
            if (isset($_REQUEST["step"]) && $_REQUEST["step"] == "2") {
              // step 2 means we have gathered data from new aircraft form and are ready to insert in database
              $insert_aircraft_stmt = $con->prepare("INSERT INTO `aircraft` (`active`, `tailnumber`, `makemodel`, `emptywt`,"
                . " `emptycg`, `maxwt`, `cgwarnfwd`, `cgwarnaft`, `weighing_date`, `weighing_sheet_url`, `fuelunit`) "
                . " VALUES ('0', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
              $insert_aircraft_stmt->bind_param(
                "ssssssssss"
                , $_REQUEST['tailnumber']
                , $_REQUEST['makemodel']
                , $_REQUEST['emptywt']
                , $_REQUEST['emptycg']
                , $_REQUEST['maxwt']
                , $_REQUEST['cgwarnfwd']
                , $_REQUEST['cgwarnaft']
                , $_REQUEST['weighing_date']
                , $_REQUEST['weighing_sheet_url']
                , $_REQUEST['fuelunit']
              );
              $insert_aircraft_stmt->execute();

              // create record in the audit log
              $audit_text = "INSERT INTO `aircraft` (`active`, `tailnumber`, `makemodel`, `emptywt`,"
                . " `emptycg`, `maxwt`, `cgwarnfwd`, `cgwarnaft`, `weighing_date`, `weighing_sheet_url`, `fuelunit`) "
                . " VALUES ('0', " . $_REQUEST['tailnumber'] . ", " . $_REQUEST['makemodel'] . ", " . $_REQUEST['emptywt'] . ", "
                . $_REQUEST['emptycg'] . ", " . $_REQUEST['maxwt'] . ", " . $_REQUEST['cgwarnfwd'] . ", " . $_REQUEST['cgwarnaft'] . ", "
                . $_REQUEST['weighing_date'] . ", " . $_REQUEST['weighing_sheet_url'] . ", " . $_REQUEST['fuelunit'] . ");";
              AuditLog($loginuser, $audit_text);
              
              // get the aircraft ID
              $aircraft_id_stmt = $con->prepare("SELECT id FROM aircraft WHERE tailnumber = ?;");
              $aircraft_id_stmt->bind_param("s", $_REQUEST['tailnumber']);
              $aircraft_id_stmt->execute();
              $aircraft_id_stmt->bind_result($aircraft_id);
              $aircraft_id_stmt->fetch();
              $aircraft_id_stmt->close();

              echo "<p>Aircraft " . $_REQUEST['tailnumber'] . " added successfully.  Now go to the <a href=\"admin.php?func=aircraft&amp;func_do=edit&amp;tailnumber=" . $aircraft_id . "\">aircraft editor</a> to complete the CG envelope and loading zones.</p>\n";
            } else {
              // not in step 2 where we have received data, show instructions instead
              echo "<p>To add a new aircraft, we will first define the basics about the aircraft.</p>\n";
              echo "<p style=\"font-size:11px; font-style:italic\">Default values are included to help you know what to fill in to each field.  When you click in the field, it will be cleared so you can fill in your data. \n";
              echo "Some fields have an underline, if you mouse over them, a help pop-up will appear.</p>\n";
              echo "<form method=\"post\" action=\"admin.php\">\n";
              echo "<input type=\"hidden\" name=\"func\" value=\"aircraft\">\n";
              echo "<input type=\"hidden\" name=\"func_do\" value=\"add\">\n";
              echo "<input type=\"hidden\" name=\"step\" value=\"2\">\n";
              echo "<table style=\"width: 100%; border-style: none;\">\n";
              echo "<tr><td style=\"text-align: right; width: 50px;\">Tail Number</td><td style=\"width: 50%\"><input type=\"text\" name=\"tailnumber\" value=\"N123AB\" onfocus=\"javascript:if(this.value=='N123AB') {this.value='';}\" onblur=\"javascript:if(this.value=='') {this.value='N123AB'}\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Make and Model</td><td><input type=\"text\" name=\"makemodel\" value=\"Cessna Skyhawk\" onfocus=\"javascript:if(this.value=='Cessna Skyhawk') {this.value='';}\" onblur=\"javascript:if(this.value=='') {this.value='Cessna Skyhawk'}\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Empty Weight</td><td><input type=\"number\" step=\"any\" name=\"emptywt\" class=\"numbers\" value=\"1556.3\" onfocus=\"javascript:if(this.value=='1556.3') {this.value='';}\" onblur=\"javascript:if(this.value=='') {this.value='1556.3'}\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Empty CG</td><td><input type=\"number\" step=\"any\" name=\"emptycg\" class=\"numbers\" value=\"38.78\" onfocus=\"javascript:if(this.value=='38.78') {this.value='';}\" onblur=\"javascript:if(this.value=='') {this.value='38.78'}\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Maximum Gross Weight</td><td><input type=\"number\" step=\"any\" name=\"maxwt\" class=\"numbers\" value=\"2550\" onfocus=\"javascript:if(this.value=='2550') {this.value='';}\" onblur=\"javascript:if(this.value=='') {this.value='2550'}\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\"><abbr title=\"This value will be used to pop up a warning if the calculated CG is less than this value.\">Forward CG Warning</abbr></td><td><input type=\"number\" step=\"any\" name=\"cgwarnfwd\" class=\"numbers\" value=\"35\" onfocus=\"javascript:if(this.value=='35') {this.value='';}\" onblur=\"javascript:if(this.value=='') {this.value='35'}\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\"><abbr title=\"This value will be used to pop up a warning if the calculated CG is greater than this value.\">Aft CG Warning</abbr></td><td><input type=\"number\" step=\"any\" name=\"cgwarnaft\" class=\"numbers\" value=\"47.3\" onfocus=\"javascript:if(this.value=='47.3') {this.value='';}\" onblur=\"javascript:if(this.value=='') {this.value='47.3'}\"></td></tr>\n";
              // add field for weighing date
              echo "<tr><td style=\"text-align: right\">Weighing Date</td><td><input type=\"date\" name=\"weighing_date\" value=\"" . date("Y-m-d") . "\"></td></tr>\n";
              // add field for weighing sheet URL
              echo "<tr><td style=\"text-align: right\">Weighing Sheet URL</td><td><input type=\"text\" name=\"weighing_sheet_url\" value=\"https://\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Fuel Unit</td><td><select name=\"fuelunit\"><option value=\"Gallons\">Gallons</option><option value=\"Liters\">Liters</option><option value=\"Pounds\">Pounds</option><option value=\"Kilograms\">Kilograms</option></select></td></tr>\n";
              echo "<tr><td colspan=\"2\" style=\"text-align: center;\"><input type=\"submit\" value=\"Step 2\"></td></tr>\n";
              echo "</table></form>\n";
            }
            break;
          case "delete":
            if (isset($_REQUEST['tailnumber']) && $_REQUEST['tailnumber'] != "") {
              if ($_REQUEST['confirm'] == "DELETE FOREVER") {
                $sql_query1 = "DELETE FROM aircraft_cg WHERE `tailnumber` = ?";
                $stmt = mysqli_prepare($con, $sql_query1);
                mysqli_stmt_bind_param($stmt, "s", $_REQUEST['tailnumber']);
                mysqli_stmt_execute($stmt);
                $sql_query2 = "DELETE FROM aircraft_weights WHERE `tailnumber` = ?";
                $stmt = mysqli_prepare($con, $sql_query2);
                mysqli_stmt_bind_param($stmt, "s", $_REQUEST['tailnumber']);
                mysqli_stmt_execute($stmt);
                $sql_query3 = "DELETE FROM aircraft WHERE `id` = ?";
                $stmt = mysqli_prepare($con, $sql_query3);
                mysqli_stmt_bind_param($stmt, "s", $_REQUEST['tailnumber']);
                mysqli_stmt_execute($stmt);
                // Enter in the audit log
                mysqli_query($con, "INSERT INTO audit (`id`, `timestamp`, `who`, `what`) VALUES (NULL, CURRENT_TIMESTAMP, '" . $loginuser . "', 'ACDELETE: " . addslashes($sql_query1) . " " . addslashes($sql_query2) . " " . addslashes($sql_query3) . "');");
                header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=aircraft&sysmsg=acdeleted');
              } else {
                echo "<p>Aircraft NOT deleted.</p><p>In the confirmation box, you must type the words \"DELETE FOREVER\", in all caps.  Use \n"
                  . "your browser's back button to try again.\n";
              }
            } else {
              echo "<p>Aircraft deletion is a permanant action, the aircraft and all of it's associated data will be gone forever.  If you wish to temporarily \n"
                . "deactivate an aircraft profile, use the <a href=\"admin.php?func=aircraft&amp;func_do=edit\">edit</a> screen.  This is useful for a single "
                . "aircraft with multiple configurations, ie: wheels/skis/floats.</p>\n\n";
              echo "<div style=\"color:red; font-weight: bold; text-align: center\">*** WARNING: This is permanant, and CANNOT be undone! ***</div>\n";
              echo "<form method=\"post\" action=\"admin.php\">\n";
              echo "<input type=\"hidden\" name=\"func\" value=\"aircraft\">\n";
              echo "<input type=\"hidden\" name=\"func_do\" value=\"delete\">\n";
              echo "<table style=\"margin-left:auto; margin-right:auto; border-style: none;\">\n";
              echo "<tr><td style=\"text-align: right\">Choose an aircraft to delete:</td><td>\n";
              AircraftListAll();
              echo "</td></tr><tr><td style=\"text-align: right\">Type the words \"DELETE FOREVER\":</td><td><input type=\"text\" name=\"confirm\"></td></tr>\n";
              echo "<tr><td colspan=\"2\" style=\"text-align: center;\"><input type=\"submit\" value=\"Delete\" onClick=\"return window.confirm('Are you REALLY sure you want to PERMANENTLY delete this aircraft?');\"></td></tr>";
              echo "</table></form>\n\n";
            }
            break;
          case "duplicate":
            if (isset($_REQUEST['tailnumber']) && $_REQUEST['tailnumber'] != "") {
              // retrieve the aircraft data to be duplicated              
              $sql = "SELECT * FROM aircraft WHERE id = ?";
              $stmt = mysqli_prepare($con, $sql);
              mysqli_stmt_bind_param($stmt, "s", $_REQUEST['tailnumber']);
              mysqli_stmt_execute($stmt);
              $aircraft_result = mysqli_stmt_get_result($stmt);
              $aircraft = mysqli_fetch_assoc($aircraft_result);

              if (
                !mysqli_query(
                  $con,
                  "INSERT INTO aircraft (
                  `active`
                , `tailnumber`
                , `makemodel`
                , `emptywt`
                , `emptycg`
                , `maxwt`
                , `cgwarnfwd`
                , `cgwarnaft`
                , `weighing_date`
                , `weighing_sheet_url`
                , `fuelunit`
                ) VALUES (
                  '0'
                , '" . $_REQUEST['newtailnumber'] . "'
                , '" . $_REQUEST['newmakemodel'] . "'
                , '" . $aircraft['emptywt'] . "'
                , '" . $aircraft['emptycg'] . "'
                , '" . $aircraft['maxwt'] . "'
                , '" . $aircraft['cgwarnfwd'] . "'
                , '" . $aircraft['cgwarnaft'] . "'
                , '" . $aircraft['weighing_date'] . "'
                , '" . $aircraft['weighing_sheet_url'] . "'
                , '" . $aircraft['fuelunit'] . "'
                );"
                )
              ) {
                error_log("Error duplicating aircraft: " . mysqli_error($con));
              }

              // get id of new aircraft
              $sql = "SELECT * FROM aircraft WHERE tailnumber = ? ORDER BY id DESC LIMIT 1";
              $stmt = mysqli_prepare($con, $sql);
              mysqli_stmt_bind_param($stmt, "s", $_REQUEST['newtailnumber']);
              mysqli_stmt_execute($stmt);
              $aircraft_result = mysqli_stmt_get_result($stmt);
              $aircraft_new = mysqli_fetch_assoc($aircraft_result);

              // duplicate the weights
              $sql = "SELECT * FROM aircraft_weights WHERE tailnumber = ?";
              $stmt = mysqli_prepare($con, $sql);
              mysqli_stmt_bind_param($stmt, "s", $_REQUEST['tailnumber']);
              mysqli_stmt_execute($stmt);
              $weights_result = mysqli_stmt_get_result($stmt);

              while ($row = mysqli_fetch_assoc($weights_result)) {
                if (empty($row['fuelwt'])) {
                  // if we don't have a fuel weight don't try to insert
                  if (
                    !mysqli_query(
                      $con,
                      "INSERT INTO aircraft_weights (
                      `tailnumber`
                    , `order`
                    , `item`
                    , `weight`
                    , `arm`
                    , `emptyweight`
                    , `fuel`
                    ) VALUES (
                        '" . $aircraft_new['id'] . "'
                      , '" . $row['order'] . "'
                      , '" . $row['item'] . "'
                      , '" . $row['weight'] . "'
                      , '" . $row['arm'] . "'
                      , '" . $row['emptyweight'] . "'
                      , '" . $row['fuel'] . "'
                    );"
                    )
                  ) {
                    error_log("Error duplicating weight: " . mysqli_error($con));
                  }
                } else {
                  //we do have fuel weight, insert
                  if (
                    !mysqli_query(
                      $con,
                      "INSERT INTO aircraft_weights (
                      `tailnumber`
                    , `order`
                    , `item`
                    , `weight`
                    , `arm`
                    , `emptyweight`
                    , `fuel`
                    , `fuelwt`
                    ) VALUES (
                        '" . $aircraft_new['id'] . "'
                      , '" . $row['order'] . "'
                      , '" . $row['item'] . "'
                      , '" . $row['weight'] . "'
                      , '" . $row['arm'] . "'
                      , '" . $row['emptyweight'] . "'
                      , '" . $row['fuel'] . "'
                      , '" . $row['fuelwt'] . "'
                    );"
                    )
                  ) {
                    error_log("Error duplicating weight: " . mysqli_error($con));
                  }
                }
              }

              // duplicate the cg envelope
              $sql = "SELECT * FROM aircraft_cg WHERE tailnumber = ?";
              $stmt = mysqli_prepare($con, $sql);
              mysqli_stmt_bind_param($stmt, "s", $_REQUEST['tailnumber']);
              mysqli_stmt_execute($stmt);
              $cg_result = mysqli_stmt_get_result($stmt);

              while ($row = mysqli_fetch_assoc($cg_result)) {
                mysqli_query($con, "INSERT INTO aircraft_cg (`tailnumber`, `arm`, `weight`) VALUES ('" . $aircraft_new['id'] . "', '" . $row['arm'] . "', '" . $row['weight'] . "');");
              }

              // Enter in the audit log
              $log_entry = "DUPLICATE: (" . $aircraft['id'] . ", " . $aircraft['tailnumber'] . ", "
                . $aircraft['makemodel'] . ") AS (" . $aircraft_new['id'] . ", " . $_REQUEST['newtailnumber']
                . ", " . $_REQUEST['newmakemodel'] . ")";
              AuditLog($loginuser, $log_entry);

              echo "<p>Aircraft duplicated, proceed to the <a href=\"admin.php?func=aircraft&amp;func_do=edit&amp;tailnumber=" . $aircraft_new['id'] . "\">edit</a> screen.</p>";
            } else {
              echo "<p>Aircraft duplication will let you clone an existing aircraft.  This speeds up creating of an aircraft, and is useful in cases such \n"
                . "as an aircraft with multiple configurations, ie: wheels/skis/floats. or similar model.</p>\n\n"
                . "<form method=\"post\" action=\"admin.php\">\n"
                . "<input type=\"hidden\" name=\"func\" value=\"aircraft\">\n"
                . "<input type=\"hidden\" name=\"func_do\" value=\"duplicate\">\n"
                . "<table style=\"margin-left:auto; margin-right:auto; border-style: none;\">\n"
                . "<tr><td style=\"text-align: right\">Choose an aircraft to duplicate:</td><td>\n";
              AircraftListAll();
              echo "</td></tr>\n"
                . "<tr><td style=\"text-align: right\">New Tail Number</td><td><input type=\"text\" name=\"newtailnumber\" value=\"N123AB\" onfocus=\"javascript:if(this.value=='N123AB') {this.value='';}\" onblur=\"javascript:if(this.value=='') {this.value='N123AB'}\"></td></tr>\n"
                . "<tr><td style=\"text-align: right\">New Make and Model</td><td><input type=\"text\" name=\"newmakemodel\" value=\"Cessna Skyhawk\" onfocus=\"javascript:if(this.value=='Cessna Skyhawk') {this.value='';}\" onblur=\"javascript:if(this.value=='') {this.value='Cessna Skyhawk'}\"></td></tr>\n"
                . "<tr><td colspan=\"2\" style=\"text-align: center;\"><input type=\"submit\" value=\"Duplicate\"></td></tr>\n"
                . "</table></form>\n\n";
            }
            break;
          case "edit":
            if (isset($_REQUEST['tailnumber']) && $_REQUEST['tailnumber'] != "") {

              $sql = "SELECT * FROM aircraft WHERE id = ?";
              $stmt = mysqli_prepare($con, $sql);
              mysqli_stmt_bind_param($stmt, "s", $_REQUEST['tailnumber']);
              mysqli_stmt_execute($stmt);
              $aircraft_result = mysqli_stmt_get_result($stmt);
              $aircraft = mysqli_fetch_assoc($aircraft_result);

              echo "<p>Editing aircraft " . $aircraft['tailnumber'] . ".</p>\n";

              if (isset($_REQUEST['message']) && $_REQUEST['message'] == "updated") {
                echo "<p style=\"color: #00AA00; text-align: center;\">Aircraft Updated</p>\n\n";
              }

              // Aircraft basic information
              echo "<hr><h3 style=\"text-align: center\">Aircraft Basic Information</h3>\n";
              echo "<form method=\"post\" action=\"admin.php\">\n";
              echo "<input type=\"hidden\" name=\"id\" value=\"" . $aircraft['id'] . "\">\n";
              echo "<input type=\"hidden\" name=\"func\" value=\"aircraft\">\n";
              echo "<input type=\"hidden\" name=\"func_do\" value=\"edit_do\">\n";
              echo "<input type=\"hidden\" name=\"what\" value=\"basics\">\n";
              echo "<table style=\"width: 100%; border-style: none;\">\n";
              echo "<tr><td style=\"text-align: right; width: 50px;\"><abbr title=\"Should this aircraft show up in the list to be able to run weight and balance?\">Active</abbr></td><td style=\"width: 50%\"><input type=\"checkbox\" name=\"active\" value=\"1\"";
              if ($aircraft['active'] == 1) {
                echo " checked";
              }
              echo "></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Tail Number</td><td><input type=\"text\" name=\"tailnumber\" value=\"" . $aircraft['tailnumber'] . "\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Make and Model</td><td><input type=\"text\" name=\"makemodel\" value=\"" . $aircraft['makemodel'] . "\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Empty Weight</td><td><input type=\"number\" step=\"any\" name=\"emptywt\" class=\"numbers\" value=\"" . $aircraft['emptywt'] . "\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Empty CG</td><td><input type=\"number\" step=\"any\" name=\"emptycg\" class=\"numbers\" value=\"" . $aircraft['emptycg'] . "\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Maximum Gross Weight</td><td><input type=\"number\" step=\"any\" name=\"maxwt\" class=\"numbers\" value=\"" . $aircraft['maxwt'] . "\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\"><abbr title=\"This value will be used to pop up a warning if the calculated CG is less than this value.\">Forward CG Warning</abbr></td><td><input type=\"number\" step=\"any\" name=\"cgwarnfwd\" class=\"numbers\" value=\"" . $aircraft['cgwarnfwd'] . "\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\"><abbr title=\"This value will be used to pop up a warning if the calculated CG is greater than this value.\">Aft CG Warning</abbr></td><td><input type=\"number\" step=\"any\" name=\"cgwarnaft\" class=\"numbers\" value=\"" . $aircraft['cgwarnaft'] . "\"></td></tr>\n";
              // add field for entering weighing date
              echo "<tr><td style=\"text-align: right\">Weighing Date</td><td><input type=\"date\" name=\"weighing_date\" value=\"" . $aircraft['weighing_date'] . "\"></td></tr>\n";
              // add field for entering weighing sheet URL
              echo "<tr><td style=\"text-align: right\">Weighing Sheet URL</td><td><input type=\"text\" name=\"weighing_sheet_url\" value=\"" . $aircraft['weighing_sheet_url'] . "\"></td></tr>\n";
              echo "<tr><td style=\"text-align: right\">Fuel Unit</td><td><select name=\"fuelunit\">\n<option value=\"Gallons\"";
              if ($aircraft['fuelunit'] == "Gallons") {
                echo " selected";
              }
              echo ">Gallons</option>\n<option value=\"Liters\"";
              if ($aircraft['fuelunit'] == "Liters") {
                echo " selected";
              }
              echo ">Liters</option>\n<option value=\"Pounds\"";
              if ($aircraft['fuelunit'] == "Pounds") {
                echo " selected";
              }
              echo ">Pounds</option>\n<option value=\"Kilograms\"";
              if ($aircraft['fuelunit'] == "Kilograms") {
                echo " selected";
              }
              echo ">Kilograms</option></select></td></tr>\n";
              echo "<tr><td colspan=\"2\" style=\"text-align: center;\"><input type=\"submit\" value=\"Save\"></td></tr>\n";
              echo "</table></form><hr>\n\n";

              // Aicraft CG envelope
              echo "<h3 style=\"text-align: center\">Center of Gravity Envelope</h3>\n";
              echo "<p style=\"text-align: center; font-size: 12px\">Enter the data points for the CG envelope.  It does not matter which point you start with or if you go clockwise or counter-clockwise, but they must be entered in order.  "
                . "The last point will automatically be connected back to the first.  The graph below will update as you go.</p>\n";
              // get existing CG envelope
              $sql = "SELECT * FROM aircraft_cg WHERE tailnumber = ?";
              $stmt = mysqli_prepare($con, $sql);
              mysqli_stmt_bind_param($stmt, "s", $aircraft['id']);
              mysqli_stmt_execute($stmt);
              $cg_result = mysqli_stmt_get_result($stmt);

              echo "<form method=\"post\" action=\"admin.php\" name=\"cg\">\n";
              echo "<input type=\"hidden\" name=\"tailnumber\" value=\"" . $aircraft['id'] . "\">\n";
              echo "<input type=\"hidden\" name=\"func\" value=\"aircraft\">\n";
              echo "<input type=\"hidden\" name=\"func_do\" value=\"edit_do\">\n";
              echo "<input type=\"hidden\" name=\"what\" value=\"cg\">\n";
              echo "<table style=\"margin-left:auto; margin-right:auto; border-style: none;\">\n";
              echo "<tr><th>Arm</th><th>Weight</th><th>&nbsp;</th></tr>\n";
              while ($cg = mysqli_fetch_assoc($cg_result)) {
                echo "<tr><td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"cgarm" . $cg['id'] . "\" value=\"" . $cg['arm'] . "\" class=\"numbers\"></td>\n"
                  . "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"cgweight" . $cg['id'] . "\" value=\"" . $cg['weight'] . "\" class=\"numbers\"></td><td>\n"
                  . "<input type=\"button\" value=\"Save\" onClick=\"parent.location='http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?func=aircraft&amp;func_do=edit_do&amp;what=cg&amp;id=" . $cg['id'] . "&amp;cgarm=' + document.cg.cgarm" . $cg['id'] . ".value + '&amp;cgweight=' + document.cg.cgweight" . $cg['id'] . ".value + '&amp;tailnumber=" . $aircraft['id'] . "'\">\n"
                  . "<input type=\"button\" value=\"Delete\" onClick=\"parent.location='http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?func=aircraft&amp;func_do=edit_do&amp;what=cg_del&amp;id=" . $cg['id'] . "&amp;tailnumber=" . $aircraft['id'] . "'\"></td></tr>\n";
              }
              echo "<tr><td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"new_arm\" class=\"numbers\"></td><td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"new_weight\" class=\"numbers\"></td><td style=\"text-align: center;\"><input type=\"submit\" value=\"Add\"></td></tr>\n";
              echo "</table></form>\n";
              echo "<embed src=\"scatter.php?size=small&amp;tailnumber=" . $aircraft['id'] . "\" width=\"412\" height=\"212\" style=\"display: block; margin-left: auto; margin-right: auto;\"></embed><hr>\n\n";

              // Aicraft loading zones
              echo "<h3 style=\"text-align: center\">Loading Zones</h3>\n";
              echo "<p style=\"text-align: center; font-size: 12px\">Enter the data for each reference datum. ";
              echo "A description of what should be entered in each field is available by hovering over the column name. ";
              echo "If you have saved a value for max weight for a position and want to remove it, ";
              echo "you must delete the line and add it back without a max weight value.</p>\n";
              // get existing loading zones 
              $sql = "SELECT * FROM aircraft_weights WHERE tailnumber = ? ORDER BY aircraft_weights.order";
              $stmt = mysqli_prepare($con, $sql);
              mysqli_stmt_bind_param($stmt, "s", $aircraft['id']);
              mysqli_stmt_execute($stmt);
              $weights_result = mysqli_stmt_get_result($stmt);

              echo "<form method=\"post\" action=\"admin.php\" name=\"loading\">\n";
              echo "<input type=\"hidden\" name=\"tailnumber\" value=\"" . $aircraft['id'] . "\">\n";
              echo "<input type=\"hidden\" name=\"func\" value=\"aircraft\">\n";
              echo "<input type=\"hidden\" name=\"func_do\" value=\"edit_do\">\n";
              echo "<input type=\"hidden\" name=\"what\" value=\"loading\">\n";
              echo "<table style=\"margin-left:auto; margin-right:auto; border-style: none;\">\n";
              echo "<tr><th><abbr title=\"This is a number which determines the vertical listing order of the row.\">Order</abbr></th><th><abbr title=\"A short textual description of the row.\">Item</abbr></th>"
                . "<th><abbr title=\"Checking this box will cause the weight column to be locked on the spreadsheet so it cannot be changed.\">Empty Weight</abbr></th>"
                . "<th><abbr title=\"Checking this box causes the spreadsheet to take it's entry in fuel and automatically compute the weight.\">Fuel</abbr></th>"
                . "<th><abbr title=\"If this row is used for fuel, specify how much a unit of fuel weighs (ie: 6 for AVGAS)\">Fuel Unit Weight</abbr></th>"
                . "<th><abbr title=\"The default weight to be used for a row.  If this is a fuel row, the default number of " . $aircraft['fuelunit'] . ".\">Weight or " . $aircraft['fuelunit'] . "</abbr></th>"
                . "<th><abbr title=\"The maximum allowable weight for this position.\">Max weight</abbr></th>"
                . "<th><abbr title=\"The number of inches from the reference datum for the row.\">Arm</abbr></th><th>&nbsp;</th></tr>\n";
              while ($weights = mysqli_fetch_assoc($weights_result)) {
                echo "<tr>"
                // sort order
                  . "<td style=\"text-align: center;\"><input type=\"number\" name=\"order" . $weights['id'] . "\" value=\"" . $weights['order'] . "\" class=\"numbers\" style=\"width: 35px;\"></td>\n"
                // item description
                  . "<td style=\"text-align: center;\"><input type=\"text\" name=\"item" . $weights['id'] . "\" value=\"" . $weights['item'] . "\" style=\"width: 125px;\"></td>\n"
                // emptyweight checkbox
                  . "<td style=\"text-align: center;\"><input type=\"checkbox\" name=\"emptyweight" . $weights['id'] . "\" value=\"true\"";
                  if ($weights['emptyweight'] == "true") {
                    echo (" checked");
                  }
                echo "></td>\n";
                // fuel checkbox
                echo "<td style=\"text-align: center;\"><input type=\"checkbox\" name=\"fuel" . $weights['id'] . "\" value=\"true\" onclick=\"if(document.loading.fuel" . $weights['id'] . ".checked==false) {document.loading.fuelwt" . $weights['id'] . ".disabled=true;} else {document.loading.fuelwt" . $weights['id'] . ".disabled=false;}\"";
                  if ($weights['fuel'] == "true") {
                    echo (" checked");
                  }
                echo "></td>\n";
                // fuel weight
                echo "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"fuelwt" . $weights['id'] . "\" value=\"" . $weights['fuelwt'] . "\" class=\"numbers\"";
                  if ($weights['fuel'] == "false") {
                    echo (" disabled");
                  }
                echo "></td>\n";
                // weight
                echo "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"weight" . $weights['id'] . "\" value=\"" . $weights['weight'] . "\" class=\"numbers\"></td>\n";
                // maxweight
                echo "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"maxweight" . $weights['id'] . "\" value=\"" . $weights['maxweight'] . "\" class=\"numbers\"></td>\n"
                // arm
                  . "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"arm" . $weights['id'] . "\" value=\"" . $weights['arm'] . "\" class=\"numbers\"></td>\n"
                // Save button
                  . "<td style=\"text-align: center;\"><input type=\"button\" value=\"Save\" onClick=\"parent.location='http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?func=aircraft&amp;func_do=edit_do&amp;what=loading"
                  . "&amp;id=" . $weights['id'] 
                  . "&amp;order=' + document.loading.order" . $weights['id'] . ".value + '"
                  . "&amp;item=' + document.loading.item" . $weights['id'] . ".value + '"
                  . "&amp;emptyweight=' + document.loading.emptyweight" . $weights['id'] . ".checked + '"
                  . "&amp;fuel=' + document.loading.fuel" . $weights['id'] . ".checked + '"
                  . "&amp;fuelwt=' + document.loading.fuelwt" . $weights['id'] . ".value + '"
                  . "&amp;weight=' + document.loading.weight" . $weights['id'] . ".value + '"
                  . "&amp;maxweight=' + document.loading.maxweight" . $weights['id'] . ".value + '"
                  . "&amp;arm=' + document.loading.arm" . $weights['id'] . ".value + '"
                  . "&amp;tailnumber=" . $aircraft['id'] . "'\">\n"
                // Delete button
                  . "<input type=\"button\" value=\"Delete\" onClick=\"parent.location='http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?func=aircraft&amp;func_do=edit_do&amp;what=loading_del&amp;id=" . $weights['id'] . "&amp;tailnumber=" . $aircraft['id'] . "'\">"
                  . "</td>";
                echo "</tr>\n\n";
              }
              // empty row for adding new rows
              echo "<tr>"
                . "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"new_order\" class=\"numbers\" style=\"width: 35px;\"></td>"
                . "<td style=\"text-align: center;\"><input type=\"text\" name=\"new_item\" style=\"width: 125px;\"></td>"
                . "<td style=\"text-align: center;\"><input type=\"checkbox\" name=\"new_emptyweight\" value=\"true\"></td>"
                . "<td style=\"text-align: center;\"><input type=\"checkbox\" name=\"new_fuel\" value=\"true\" onclick=\"if(document.loading.new_fuel.checked==false) {document.loading.new_fuelwt.disabled=true;} else {document.loading.new_fuelwt.disabled=false;}\"></td>"
                . "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"new_fuelwt\" class=\"numbers\" disabled></td>"
                . "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"new_weight\" class=\"numbers\"></td>"
                . "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"new_maxweight\" class=\"numbers\"></td>"
                . "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"new_arm\" class=\"numbers\"></td>"
                . "<td style=\"text-align: center;\"><input type=\"submit\" value=\"Add\"></td></tr>\n";
              echo "</table></form>\n\n";
            } else {
              echo "<p>Choose an aircraft to modify.</p>\n";
              echo "<form method=\"post\" action=\"admin.php\">\n";
              echo "<input type=\"hidden\" name=\"func\" value=\"aircraft\">\n";
              echo "<input type=\"hidden\" name=\"func_do\" value=\"edit\">\n";
              AircraftListAll();
              echo "<input type=\"submit\" value=\"Select\"></form>\n\n";
            }
            break;

          case "edit_do":
            switch ($_REQUEST["what"]) {
              case "basics":
                if (!isset($_REQUEST['active'])) {
                  $newActive = 0;
                } else {
                  $newActive = $_REQUEST['active'];
                }
                // SQL statement to edit basic aircraft information
                $sql = "UPDATE aircraft SET active = ?, tailnumber = ?, makemodel = ?, emptywt = ?, emptycg = ?, maxwt = ?,  cgwarnfwd = ?, cgwarnaft = ?, weighing_date = ?, weighing_sheet_url = ?, fuelunit = ? WHERE id = ?";
                $stmt = mysqli_prepare($con, $sql);
                mysqli_stmt_bind_param($stmt, "issssssssssi", 
                  $newActive, 
                  $_REQUEST['tailnumber'], 
                  $_REQUEST['makemodel'], 
                  $_REQUEST['emptywt'], 
                  $_REQUEST['emptycg'], 
                  $_REQUEST['maxwt'], 
                  $_REQUEST['cgwarnfwd'], 
                  $_REQUEST['cgwarnaft'],
                  $_REQUEST['weighing_date'],
                  $_REQUEST['weighing_sheet_url'],
                  $_REQUEST['fuelunit'], 
                  $_REQUEST['id']
                );
                $sql_query = mysqli_stmt_execute($stmt);

                if (!$sql_query) {
                  error_log("Error editing aircraft basics: " . mysqli_error($con));

                }

                // log SQL and bind parameters
                $sql = "UPDATE aircraft SET active = ?, tailnumber = ?, makemodel = ?, emptywt = ?, emptycg = ?, maxwt = ?,  cgwarnfwd = ?, cgwarnaft = ?, weighing_date = ?, weighing_sheet_url = ?, fuelunit = ? WHERE id = ?";
                $bind = array(
                  $newActive, 
                  $_REQUEST['tailnumber'], 
                  $_REQUEST['makemodel'], 
                  $_REQUEST['emptywt'], 
                  $_REQUEST['emptycg'], 
                  $_REQUEST['maxwt'], 
                  $_REQUEST['cgwarnfwd'], 
                  $_REQUEST['cgwarnaft'],
                  $_REQUEST['weighing_date'],
                  $_REQUEST['weighing_sheet_url'],
                  $_REQUEST['fuelunit'], 
                  $_REQUEST['id']
                );
                // combine SQL and bind parameters into one string
                $sql = $sql . " " . implode(", ", $bind);
                AuditLog($loginuser, $sql);
                
                // redirect back to edit page with message
                header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=aircraft&func_do=edit&tailnumber=' . $_REQUEST['id'] . '&message=updated');
                break;
              case "cg":
                if ($_REQUEST['new_arm'] != "" && $_REQUEST['new_weight'] != "") {
                  // SQL to add a new CG line
                  $sql_query = "INSERT INTO aircraft_cg (`id`, `tailnumber`, `arm`, `weight`) VALUES (NULL, ?, ?, ?);";
                  $stmt = mysqli_prepare($con, $sql);
                  mysqli_stmt_bind_param($stmt, "sss", $_REQUEST['tailnumber'], $_REQUEST['new_arm'], $_REQUEST['new_weight']);
                  mysqli_stmt_execute($stmt);

                  // Enter in the audit log
                  $aircraft_query = mysqli_prepare($con, "SELECT * FROM aircraft WHERE id = ?");
                  mysqli_stmt_bind_param($aircraft_query, 'i', $_REQUEST['tailnumber']);
                  mysqli_stmt_execute($aircraft_query);
                  $aircraft_result = mysqli_stmt_get_result($aircraft_query);
                  $aircraft = mysqli_fetch_array($aircraft_result);
                  $log_entry = $aircraft['tailnumber'] . ": " . addslashes($sql_query);

                  $sql_query = "INSERT INTO audit (`id`, `timestamp`, `who`, `what`) VALUES (NULL, CURRENT_TIMESTAMP, ?, ?)";
                  $stmt = mysqli_prepare($con, $sql_query);
                  mysqli_stmt_bind_param($stmt, 'ss', $loginuser, $log_entry);
                  mysqli_stmt_execute($stmt);
                  header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=aircraft&func_do=edit&tailnumber=' . $_REQUEST['tailnumber'] . '&message=updated');
                } else {
                  // SQL to edit CG information
                  $sql_query = "UPDATE aircraft_cg SET arm = ?, weight = ? WHERE id = ?";
                  $stmt = mysqli_prepare($con, $sql_query);
                  mysqli_stmt_bind_param($stmt, 'ssi', $_REQUEST['cgarm'], $_REQUEST['cgweight'], $_REQUEST['id']);
                  mysqli_stmt_execute($stmt);

                  // Enter in the audit log
                  $aircraft_query = mysqli_prepare($con, "SELECT * FROM aircraft WHERE id = ?");
                  mysqli_stmt_bind_param($aircraft_query, 'i', $_REQUEST['tailnumber']);
                  mysqli_stmt_execute($aircraft_query);
                  $aircraft_result = mysqli_stmt_get_result($aircraft_query);
                  $aircraft = mysqli_fetch_array($aircraft_result);
                  $log_entry = $aircraft['tailnumber'] . ": " . addslashes($sql_query);

                  $sql_query = "INSERT INTO audit (`id`, `timestamp`, `who`, `what`) VALUES (NULL, CURRENT_TIMESTAMP, ?, ?)";
                  $stmt = mysqli_prepare($con, $sql_query);
                  mysqli_stmt_bind_param($stmt, 'ss', $loginuser, $log_entry);
                  mysqli_stmt_execute($stmt);
                  header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=aircraft&func_do=edit&tailnumber=' . $_REQUEST['tailnumber'] . '&message=updated');
                }
                break;
              case "cg_del":
                // SQL  to delete CG information
                $sql_query = "DELETE FROM aircraft_cg WHERE id = ?";
                $stmt = mysqli_prepare($con, $sql_query);
                mysqli_stmt_bind_param($stmt, 'i', $_REQUEST['id']);
                mysqli_stmt_execute($stmt);

                // Enter in the audit log
                $aircraft_query = mysqli_prepare($con, "SELECT * FROM aircraft WHERE id = ?");
                mysqli_stmt_bind_param($aircraft_query, 'i', $_REQUEST['tailnumber']);
                mysqli_stmt_execute($aircraft_query);
                $aircraft_result = mysqli_stmt_get_result($aircraft_query);
                $aircraft = mysqli_fetch_array($aircraft_result);
                $log_entry = $aircraft['tailnumber'] . ": " . addslashes($sql_query);

                $sql_query = "INSERT INTO audit (`id`, `timestamp`, `who`, `what`) VALUES (NULL, CURRENT_TIMESTAMP, ?, ?)";
                $stmt = mysqli_prepare($con, $sql_query);
                mysqli_stmt_bind_param($stmt, 'ss', $loginuser, $log_entry);
                mysqli_stmt_execute($stmt);
                header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=aircraft&func_do=edit&tailnumber=' . $_REQUEST['tailnumber'] . '&message=updated');
              // no break
              case "loading":
                if (
                  isset($_REQUEST['new_order']) && $_REQUEST['new_order']
                  && isset($_REQUEST['new_item']) && $_REQUEST['new_item']
                  && isset($_REQUEST['new_arm']) && $_REQUEST['new_arm'] != ""
                ) {
                  // we have enough data to add a line
                  // set emptyweight based on checkbox 
                  if (isset($_REQUEST['new_emptyweight']) && $_REQUEST['new_emptyweight'] == "true") {
                    $new_emptyweight = 'true';
                  } else {
                    $new_emptyweight = 'false';
                  }
                  // check if new fuel checkbox is set
                  if (isset($_REQUEST['new_fuel']) && $_REQUEST['new_fuel'] == "true") {
                    $new_fuel = 'true';
                    if (isset($_REQUEST['new_fuelwt'])) {
                      $new_fuelwt = $_REQUEST['new_fuelwt'];
                    } else {
                      $new_fuelwt = 0;
                    }
                  } else {
                    $new_fuel = 'false';
                  }
                  // Get weight or set to zero if not set
                  if (isset($_REQUEST['new_weight']) && $_REQUEST['new_weight'] != "") {
                    $new_weight = $_REQUEST['new_weight'];
                  } else {
                    $new_weight = 0;
                  }
                  // SQL  to add a new loading line
                  $sql_query = "INSERT INTO aircraft_weights "
                    . "(`id`, `tailnumber`, `order`, `item`, `weight`, `arm`, `emptyweight`, `fuel`, `fuelwt`)"
                    . " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                  $stmt = mysqli_prepare($con, $sql_query);
                  mysqli_stmt_bind_param(
                    $stmt,
                    'iisssssss',
                    $_REQUEST['id'], $_REQUEST['tailnumber'], $_REQUEST['new_order'],
                    $_REQUEST['new_item'],
                    $new_weight, $_REQUEST['new_arm'],
                    $new_emptyweight,
                    $new_fuel,
                    $new_fuelwt
                  );
                  mysqli_stmt_execute($stmt);

                  $log_entry = $aircraft['tailnumber'] . ": " . addslashes($sql_query);
                  AuditLog($loginuser, $log_entry);

                  // redirect back with message
                  header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=aircraft&func_do=edit&tailnumber=' . $_REQUEST['tailnumber'] . '&message=updated');
                } else {
                  // no data in the new line, just update the existing lines
                  // set emptyweight based on checkbox 
                  if (isset($_REQUEST['emptyweight']) && $_REQUEST['emptyweight'] == "true") {
                    $emptyweight = 'true';
                  } else {
                    $emptyweight = 'false';
                  }
                  // set fuel based on checkbox 
                  if (isset($_REQUEST['fuel']) && $_REQUEST['fuel'] == "true") {
                    $fuel = 'true';
                  } else {
                    $fuel = 'false';
                  }
                  // SQL  to edit loading zones except max weight
                  $sql_query = "UPDATE aircraft_weights "
                    . "SET `order` = ?, `item` = ?, `weight` = ?, "
                    . "`arm` = ?, `emptyweight` = ?, `fuel` = ?, "
                    . "`fuelwt` = ? WHERE id = ?";
                  $stmt = mysqli_prepare($con, $sql_query);
                  mysqli_stmt_bind_param(
                    $stmt,
                    'sssssssi',
                    $_REQUEST['order'], $_REQUEST['item'], $_REQUEST['weight'],
                    $_REQUEST['arm'], $emptyweight, $fuel,
                    $_REQUEST['$fuelwt'], $_REQUEST['id']
                  );
                  mysqli_stmt_execute($stmt);

                  // SQL to edit max weight if set
                  if (isset($_REQUEST['maxweight']) && $_REQUEST['maxweight'] != "") {
                    $sql_query = "UPDATE aircraft_weights SET maxweight = ? WHERE id = ?";
                    $stmt = mysqli_prepare($con, $sql_query);
                    mysqli_stmt_bind_param($stmt, 'si', $_REQUEST['maxweight'], $_REQUEST['id']);
                    mysqli_stmt_execute($stmt);
                  }

                  // Enter in the audit log
                  $log_entry = $aircraft['tailnumber'] . ": " . addslashes($sql_query);
                  AuditLog($loginuser, $log_entry);
                  // redirect back with messqage
                  header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=aircraft&func_do=edit&tailnumber=' . $_REQUEST['tailnumber'] . '&message=updated');
                }
                break;
              case "loading_del":
                // SQL query to delete loading information
                $sql_query = "DELETE FROM aircraft_weights WHERE id = ?";
                $stmt = mysqli_prepare($con, $sql_query);
                mysqli_stmt_bind_param($stmt, 'i', $_REQUEST['id']);
                mysqli_stmt_execute($stmt);

                // Enter in the audit log
                $aircraft_query = mysqli_prepare($con, "SELECT * FROM aircraft WHERE id = ?");
                mysqli_stmt_bind_param($aircraft_query, 'i', $_REQUEST['tailnumber']);
                mysqli_stmt_execute($aircraft_query);
                $aircraft_result = mysqli_stmt_get_result($aircraft_query);
                $aircraft = mysqli_fetch_array($aircraft_result);
                $log_entry = $aircraft['tailnumber'] . ": " . addslashes($sql_query);
                $sql_query = "INSERT INTO audit (`id`, `timestamp`, `who`, `what`) VALUES (NULL, CURRENT_TIMESTAMP, ?, ?)";
                $stmt = mysqli_prepare($con, $sql_query);
                mysqli_stmt_bind_param($stmt, 'ss', $loginuser, $log_entry);
                mysqli_stmt_execute($stmt);
                header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=aircraft&func_do=edit&tailnumber=' . $_REQUEST['tailnumber'] . '&message=updated');
            }
            break;
          default:
            // if we got invalid aircraft func_do order, just show instructions
            echo "<p>This module edits aircraft weight and balance templates.</p>\n";
            echo "<a href=\"admin.php?func=aircraft&amp;func_do=add\">Add Aicraft</a><br>\n";
            echo "<a href=\"admin.php?func=aircraft&amp;func_do=edit\">Edit Aicraft</a><br>\n";
            echo "<a href=\"admin.php?func=aircraft&amp;func_do=duplicate\">Duplicate Aicraft</a><br>\n";
            echo "<a href=\"admin.php?func=aircraft&amp;func_do=delete\">Delete Aicraft</a>\n";
        } // end aircraft switch
      } else {
        // no func_do instruction to make any change, show aircraft change instructions
        echo "<p>This module edits aircraft weight and balance templates.</p>\n";
        echo "<a href=\"admin.php?func=aircraft&amp;func_do=add\">Add Aicraft</a><br>\n";
        echo "<a href=\"admin.php?func=aircraft&amp;func_do=edit\">Edit Aicraft</a><br>\n";
        echo "<a href=\"admin.php?func=aircraft&amp;func_do=duplicate\">Duplicate Aicraft</a><br>\n";
        echo "<a href=\"admin.php?func=aircraft&amp;func_do=delete\">Delete Aicraft</a>\n";
      }
      break;

    case "users":
      echo "<div class=\"titletext\">Users Module</div>";
      // display any messages
      if (isset($_REQUEST['message'])) {
        if ($_REQUEST['message'] == "added") {
          echo "<p style=\"color: #00AA00; text-align: center;\">User account added.</p>\n\n";
        } elseif ($_REQUEST['message'] == "edited") {
          echo "<p style=\"color: #00AA00; text-align: center;\">User account edited.</p>\n\n";
        } elseif ($_REQUEST['message'] == "deleted") {
          echo "<p style=\"color: #00AA00; text-align: center;\">User account deleted.</p>\n\n";
        }
      }

      if (isset($_REQUEST["func_do"])) {
        // got an instruction to do something
        switch ($_REQUEST["func_do"]) {
          case "add":
            if (isset($_REQUEST['what']) && $_REQUEST['what'] == "Add") {
              // we got data for a new user account, add it to the database
              $sql_query = "INSERT INTO users (`username`, `password`, `name`, `email`, `superuser`) VALUES (?, ?, ?, ?, ?);";
              $stmt = mysqli_prepare($con, $sql_query);
              $password = md5($_REQUEST['password']);
              if (isset($_REQUEST['superuser'])) {
                $superuser = 1;
              } else {
                $superuser = 0;
              }
              mysqli_stmt_bind_param($stmt, "sssss", $_REQUEST['username'], $password, $_REQUEST['name'], $_REQUEST['email'], $superuser);
              mysqli_stmt_execute($stmt);
              $log_entry = addslashes($sql_query);
              AuditLog($loginuser, $log_entry);
              // redirect backt to users page with addition confirmation message
              header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=users&message=added');
            } else {
              // request to add a user, show the Add form
              echo "<form method=\"post\" action=\"admin.php\">\n"
                . "<input type=\"hidden\" name=\"func\" value=\"users\">\n"
                . "<input type=\"hidden\" name=\"func_do\" value=\"add\">\n"
                . "<table style=\"width: 100%; border-style: none;\">\n"
                . "<tr><td style=\"text-align: right; width: 50px;\">Username</td><td style=\"width: 50%\"><input type=\"text\" name=\"username\"></td></tr>\n"
                . "<tr><td style=\"text-align: right\">Password</td><td><input type=\"password\" name=\"password\"></td></tr>\n"
                . "<tr><td style=\"text-align: right\">Name</td><td><input type=\"text\" name=\"name\"></td></tr>\n"
                . "<tr><td style=\"text-align: right\">E-mail</td><td><input type=\"email\" name=\"email\"></td></tr>\n"
                . "<tr><td style=\"text-align: right\"><abbr title=\"Administrative users can edit system settings, all users, and view the audit log.\">Admin User</abbr></td><td><input type=\"checkbox\" name=\"superuser\" value=\"1\"></td></tr>\n"
                . "<tr><td colspan=\"2\" style=\"text-align: center;\"><input type=\"submit\" name=\"what\" value=\"Add\"></td></tr>\n"
                . "</table></form>\n";
            }
            break;
          case "edit":
            if (isset($_REQUEST['what']) && $_REQUEST['what'] == "Save") {
              // we got updated data for a user account, update database
              if ($_REQUEST['superuser'] == "1") {
                $update_superuser = "1";
              } else {
                $update_superuser = "0";
              }
              $sql_stmt = "UPDATE users SET `username` = ?, `password` = ?, `name` = ?, `email` = ?, `superuser` = ? WHERE id = ?";
              $stmt = mysqli_prepare($con, $sql_stmt);
              $password_hash = password_hash($_REQUEST['password'], PASSWORD_DEFAULT);
              mysqli_stmt_bind_param($stmt, "sssssi", $_REQUEST['username'], $password_hash, $_REQUEST['name'], $_REQUEST['email'], $update_superuser, $_REQUEST['id']);
              mysqli_stmt_execute($stmt);
              $log_entry = "USERS: " . addslashes($sql_stmt);
              AuditLog($loginuser, $log_entry);
              // redirect back to users page with edit confirmation message
              header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=users&message=edited');
            } elseif (isset($_REQUEST['what']) && $_REQUEST['what'] == "Delete") {
              // we got a request to delete a user account, delete it from the database
              $sql_stmt = "DELETE FROM users WHERE id = ?";
              $stmt = mysqli_prepare($con, $sql_stmt);
              mysqli_stmt_bind_param($stmt, "i", $_REQUEST['id']);
              mysqli_stmt_execute($stmt);
              $log_entry = "USERS: " . addslashes($sql_stmt);
              AuditLog($loginuser, $log_entry);
              // redirect back to users page with delete confirmation message
              header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?func=users&message=deleted');
            } else {
              // didn't get a form submission, show the Edit form with the user's data
              $query = mysqli_prepare($con, "SELECT * FROM users WHERE id = ?");
              mysqli_stmt_bind_param($query, "i", $_REQUEST['id']);
              mysqli_stmt_execute($query);
              $user_result = mysqli_stmt_get_result($query);
              $user = mysqli_fetch_assoc($user_result);
              echo "<form method=\"post\" action=\"admin.php\">\n"
                . "<input type=\"hidden\" name=\"id\" value=\"" . $user['id'] . "\">\n"
                . "<input type=\"hidden\" name=\"func\" value=\"users\">\n"
                . "<input type=\"hidden\" name=\"func_do\" value=\"edit\">\n"
                . "<table style=\"width: 100%; border-style: none;\">\n"
                . "<tr><td style=\"text-align: right; width: 50px;\">Username2</td><td style=\"width: 50%\"><input type=\"text\" name=\"username\" value=\"" . $user['username'] . "\"></td></tr>\n"
                . "<tr><td style=\"text-align: right\">Password</td><td><input type=\"password\" name=\"password\"></td></tr>\n"
                . "<tr><td style=\"text-align: right\">Name</td><td><input type=\"text\" name=\"name\" value=\"" . $user['name'] . "\"></td></tr>\n"
                . "<tr><td style=\"text-align: right\">E-mail</td><td><input type=\"email\" name=\"email\" value=\"" . $user['email'] . "\"></td></tr>\n";
              if ($loginlevel == "1") {
                echo "<tr><td style=\"text-align: right\"><abbr title=\"Administrative users can edit system settings, all users, and view the audit log.\">Admin User</abbr></td><td><input type=\"checkbox\" name=\"superuser\" value=\"1\"";
                if ($user['superuser'] == "1") {
                  echo " checked";
                }
                echo "></td></tr>\n";
              }
              echo "<tr><td colspan=\"2\" style=\"text-align: center;\"><input type=\"submit\" name=\"what\" value=\"Save\">"
                . "&nbsp;&nbsp;&nbsp;"
                . "<input type=\"submit\" name=\"what\" value=\"Delete\" onClick=\"return window.confirm('Are you REALLY sure you want to PERMANENTLY delete this account?');\"></td></tr>\n"
                . "</table></form>\n";
            }
            break;
          default:
            break;
        }
      } else {
        echo "<p>This module edits system users.</p>\n";
        echo "<form method=\"post\" action=\"admin.php\">\n";
        echo "<input type=\"hidden\" name=\"func\" value=\"users\">\n";
        echo "<input type=\"hidden\" name=\"func_do\" value=\"add\">\n";
        echo "<table style=\"margin-left: auto; margin-right: auto;\">\n";
        echo "<tr><th>Username</th><th>Name</th><th>Admin</th><th>&nbsp;</th></tr>\n";
        $result = mysqli_query($con, "SELECT * FROM users ORDER BY `name`");
        while ($row = mysqli_fetch_array($result)) {
          echo "<tr><td>" . $row['username'] . "</td><td>" . $row['name'] . "</td><td>";
          if ($row['superuser'] == "1") {
            echo "Yes";
          } else {
            echo "No";
          }
          echo "</td><td>\n";
          if ($loginuser == $row['username'] || $loginlevel == "1") {
            echo "<input type=\"button\" name=\"edit\" value=\"Edit\" onClick=\"parent.location='http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?func=users&amp;func_do=edit&amp;id=" . $row['id'] . "'\">\n";
          } else {
            echo "&nbsp;";
          }
          echo "</td></tr>\n";
        }
        echo "</table></form>\n";
        if ($loginlevel == "1") {
          echo "<div style=\"text-align: center;\"><a href=\"admin.php?func=users&amp;func_do=add\">Add New User</a></div>\n";
        }
      }

      break;

    case "audit":
      // only admins can view the audit log
      if ($loginlevel != "1") {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?sysmsg=unauthorized');
      }

      echo "<div class=\"titletext\">Audit Log Module</div>";
      echo "<br/>";
      echo "<div style=\"font-family: courier; font-size: 10px;\">";
      // pagination
      if (!isset($_REQUEST['offset']) || $_REQUEST['offset'] == "") {
        $lower = 0;
      } else {
        $lower = $_REQUEST['offset'];
      }
      $upper = ($lower + 100);

      // get audit rows
      $audit_query = mysqli_prepare($con, "SELECT * FROM audit ORDER BY id DESC LIMIT ?, ?");
      mysqli_stmt_bind_param($audit_query, "ii", $lower, $upper);
      mysqli_stmt_execute($audit_query);
      $audit_result = mysqli_stmt_get_result($audit_query);

      // display audit rows
      while ($row = mysqli_fetch_array($audit_result)) {
        echo $row['timestamp'] . " | " . $row['who'] . " | " . $row['what'] . "<br><br>\n";
      }
      echo "</div><div style=\"text-align: center;\">";

      // pagination links
      if ($lower != 0) {
        echo "<a href=\"admin.php?func=audit&amp;offset=" . ($lower - 100) . "\">Previous Page</a> ";
      }
      $total_audit_rows = mysqli_num_rows(mysqli_query($con, "SELECT * FROM audit"));
      if ($total_audit_rows > ($upper + 1)) {
        echo " <a href=\"admin.php?func=audit&amp;offset=" . ($upper) . "\">Next Page</a>";
      }

      echo "</div>\n\n";
      break;

    default:
      // user didn't ask for any of the valid functions
      echo "<div class=\"titletext\">Fulcrum Administration</div>";
      echo "<p>Choose a menu item from the bottom toolbar.</p>";
  }
} else {
  // user didn't ask for any function
  echo "<div class=\"titletext\">Fulcrum Administration</div>";
  echo "<p>Choose a menu item from the bottom toolbar.</p>";
}
echo "</td></tr></table>";

?>

<div id="toolbar" class="noprint" style="line-height:35px;">
  <span style="width: 200px; float: left; line-height:40px;">&nbsp;&nbsp;
    <abbr title="Fulcrum W&amp;B is free, open source weight and balance software.">
      <a href="https://github.com/vesterli/fulcrum-wb" target="_blank" style="font-size:20px; color: white;">Fulcrum
        W&amp;B</a>
    </abbr>
  </span>
  <span style="width: 500px; text-align:center; float: center; line-height:45px;">
    <a href="admin.php?func=system">Edit System Settings</a> | <a href="admin.php?func=aircraft">Edit Aircraft</a> | <a
      href="admin.php?func=users">Edit Users</a> |
    <a href="admin.php?func=audit">Audit Log</a> | <a href="admin.php?func=logout">Logout
      <?php echo $_SESSION["user_name"]; ?>
    </a>
  </span>
</div>

<?php
PageFooter($config['site_name'], $config['administrator'], $ver, $dbver);
// mysqli_close();
?>