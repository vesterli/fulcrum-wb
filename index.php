<?php include 'func.inc';
   PageHeader($config['site_name']); ?>

<?php
if (!isset($_REQUEST['tailnumber']) || ($_REQUEST['tailnumber']=="")) {
       // NO AIRCRAFT SPECIFIED, SHOW ACTIVE AIRCRAFT LIST
       echo "<body>\n";
       echo "<table border=\"1\" cellpadding=\"3\" width=\"700\" align=\"center\">\n";
       echo "<tr><td>\n";
       echo "<div class=\"titletext\">" . $config['site_name'] . "</div>\n";
       if (isset($_REQUEST['message']) && $_REQUEST['message']=="invalid") {
           echo "<p style=\"color: #00AA00; text-align: center;\">You have selected an invalid aircraft.</p>\n\n";
       } elseif (isset($_REQUEST['message']) && $_REQUEST['message']=="inactive") {
           echo "<p style=\"color: #00AA00; text-align: center;\">The aircraft you have selected is currently inactive.</p>\n\n";
       }
       echo "<p>Select aircraft tail number.</p>\n";

       echo "<form method=\"get\" action=\"index.php\">\n";
       AircraftListActive();
       echo "<input type=\"submit\" value=\"Go\"></form>\n";

       echo "</td></tr></table>\n";
   } elseif (isset($_REQUEST['tailnumber'])) {
       // TAILNUMBER PROVIDED, VALIDATE
       // GET AIRCRAFT INFORMATION
       $aircraft_result_stmt = $con->prepare("SELECT * FROM aircraft WHERE id=?;");
       $aircraft_result_stmt->bind_param("i", $_REQUEST['tailnumber']);
       $aircraft_result_stmt->execute();
       $aircraft_result = $aircraft_result_stmt->get_result();
       $aircraft = mysqli_fetch_assoc($aircraft_result);

       if (mysqli_num_rows($aircraft_result)=="0") {
           header("Location: http://" . $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"] . "?message=invalid");
       } elseif ($aircraft['active']=="0") {
           header("Location: http://" . $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"] . "?message=inactive");
       } ?>

<script type="text/javascript">

<!-- Hide script
function WeightBal() {
var df = document.forms[0];

<?php
  $weights_query_stmt = $con->prepare("SELECT * FROM aircraft_weights WHERE tailnumber = ? ORDER BY 'order' ASC");
  $weights_query_stmt->bind_param("i", $aircraft['id']);
  $weights_query_stmt->execute();
  $weights_query = $weights_query_stmt->get_result();
       while ($weights = mysqli_fetch_assoc($weights_query)) {
           if ($weights['fuel']=="true") {
               echo "df.line" . $weights['id'] . "_gallons_to.value = ";
               if (empty($_REQUEST["line" . $weights['id'] . "_gallons_to"])) {
                   echo($weights['weight']);
               } else {
                   echo($_REQUEST["line" . $weights['id'] . "_gallons_to"]);
               }
               echo ";\n";
               echo "df.line" . $weights['id'] . "_liters_to.value = ";
               if (empty($_REQUEST["line" . $weights['id'] . "_gallons_to"])) {
                   echo($weights['weight']);
               } else {
                   echo(number_format((int)$_REQUEST["line" . $weights['id'] . "_gallons_to"] * 3.78541178, 1));
               }
               echo ";\n";
               echo "df.line" . $weights['id'] . "_wt_to.value = ";
               if (empty($_REQUEST["line" . $weights['id'] . "_gallons_to"])) {
                   echo(($weights['weight'] * $weights['fuelwt']));
               } else {
                   echo(($_REQUEST["line" . $weights['id'] . "_gallons_to"] * $weights['fuelwt']));
               }
               echo ";\n";
               echo "df.line" . $weights['id'] . "_gallons_ldg.value = ";
               if (empty($_REQUEST["line" . $weights['id'] . "_gallons_ldg"])) {
                   echo($weights['weight']);
               } else {
                   echo($_REQUEST["line" . $weights['id'] . "_gallons_ldg"]);
               }
               echo ";\n";
               echo "df.line" . $weights['id'] . "_liters_ldg.value = ";
               if (empty($_REQUEST["line" . $weights['id'] . "_gallons_ldg"])) {
                   echo($weights['weight']);
               } else {
                   echo(number_format((int)$_REQUEST["line" . $weights['id'] . "_gallons_ldg"] * 3.78541178, 1));
               }
               echo ";\n";
               echo "df.line" . $weights['id'] . "_wt_ldg.value = ";
               if (empty($_REQUEST["line" . $weights['id'] . "_gallons_ldg"])) {
                   echo(($weights['weight'] * $weights['fuelwt']));
               } else {
                   echo(($_REQUEST["line" . $weights['id'] . "_gallons_ldg"] * $weights['fuelwt']));
               }
               echo ";\n";
           } else {
               echo "df.line" . $weights['id'] . "_wt.value = ";
               if (empty($_REQUEST["line" . $weights['id'] . "_wt"])) {
                   echo($weights['weight']);
               } else {
                   echo($_REQUEST["line" . $weights['id'] . "_wt"]);
               }
               echo  ";\n";
           }
           echo "df.line" . $weights['id'] . "_arm.value = Number(" . $weights['arm'] . ").toFixed(2);\n\n";
       } ?>

  Process();
}

function Process() {
  var df = document.forms[0];

<?php
  $weights_query_stmt = $con->prepare("SELECT * FROM aircraft_weights WHERE tailnumber = ? ORDER BY 'order' ASC");
  $weights_query_stmt->bind_param("i", $aircraft['id']);
  $weights_query_stmt->execute();
  $weights_query = $weights_query_stmt->get_result();

       while ($weights = mysqli_fetch_assoc($weights_query)) {
           echo "var line" . $weights['id'] . "_arm = Number(df.line" . $weights['id'] ."_arm.value);" . "\n";
           if ($weights['fuel']=="true") {
               echo "var line" . $weights['id'] . "_gallons_to = df.line" . $weights['id'] . "_gallons_to.value;\n";
               echo "df.line" . $weights['id'] . "_liters_to.value = (line" . $weights['id'] . "_gallons_to * 3.78541178).toFixed(1);\n";
               echo "var line" . $weights['id'] . "_wt_to = line" . $weights['id'] . "_gallons_to * " . $weights['fuelwt'] . ";\n";
               echo "df.line" . $weights['id'] . "_wt_to.value = (line" . $weights['id'] . "_gallons_to * " . $weights['fuelwt'] . ").toFixed(2);\n";
               echo "var line" . $weights['id'] . "_mom_to = line" . $weights['id'] . "_wt_to * line" . $weights['id'] . "_arm;\n";
               echo "df.line" . $weights['id'] . "_mom_to.value = line" . $weights['id'] . "_mom_to.toFixed(2);\n";

               echo "var line" . $weights['id'] . "_gallons_ldg = df.line" . $weights['id'] . "_gallons_ldg.value;\n";
               echo "df.line" . $weights['id'] . "_liters_ldg.value = (line" . $weights['id'] . "_gallons_ldg * 3.78541178).toFixed(1);\n";
               echo "var line" . $weights['id'] . "_wt_ldg = line" . $weights['id'] . "_gallons_ldg * " . $weights['fuelwt'] . ";\n";
               echo "df.line" . $weights['id'] . "_wt_ldg.value = (line" . $weights['id'] . "_gallons_ldg * " . $weights['fuelwt'] . ").toFixed(2);\n";
               echo "var line" . $weights['id'] . "_mom_ldg = line" . $weights['id'] . "_wt_ldg * line" . $weights['id'] . "_arm;\n";
               echo "df.line" . $weights['id'] . "_mom_ldg.value = line" . $weights['id'] . "_mom_ldg.toFixed(2);\n\n";

               if (!isset($momentlist_to)) {
                   $momentlist_to = array(" -line" . $weights['id'] . "_mom_to");
               } else {
                   $momentlist_to[0] = $momentlist_to[0] . " -line" . $weights['id'] . "_mom_to";
               }
               if (!isset($wtlist_to)) {
                   $wtlist_to = array(" -line" . $weights['id'] . "_wt_to");
               } else {
                   $wtlist_to[0] = $wtlist_to[0] . " -line" . $weights['id'] . "_wt_to";
               }
               if (!isset($momentlist_ldg)) {
                   $momentlist_ldg = array(" -line" . $weights['id'] . "_mom_ldg");
               } else {
                   $momentlist_ldg[0] = $momentlist_ldg[0] . " -line" . $weights['id'] . "_mom_ldg";
               }
               if (!isset($wtlist_ldg)) {
                   $wtlist_ldg = array(" -line" . $weights['id'] . "_wt_ldg");
               } else {
                   $wtlist_ldg[0] = $wtlist_ldg[0] . " -line" . $weights['id'] . "_wt_ldg";
               }
           } else {
               echo "var line" . $weights['id'] . "_wt = Number(df.line" . $weights['id'] . "_wt.value);" . "\n";
               echo "var line" . $weights['id'] . "_mom = (Number(line" . $weights['id'] . "_wt) * Number(line" . $weights['id'] . "_arm));\n";
               echo "df.line" . $weights['id'] . "_mom.value = Number(line" . $weights['id'] . "_mom).toFixed(2);\n\n";

               if (!isset($momentlist)) {
                   $momentlist = array(" -line" . $weights['id'] . "_mom");
               } else {
                   $momentlist[0] = $momentlist[0] . " -line" . $weights['id'] . "_mom";
               }
               if (!isset($wtlist)) {
                   $wtlist = array(" -line" . $weights['id'] . "_wt");
               } else {
                   $wtlist[0] = $wtlist[0] . " -line" . $weights['id'] . "_wt";
               }
           }
       }
       echo "var totmom_to = -1 * (" . print_r($momentlist[0], true) . print_r($momentlist_to[0], true) . ");\n";
       echo "df.totmom_to.value = totmom_to.toFixed(2);\n";
       echo "var totmom_ldg = -1 * (" . print_r($momentlist[0], true) . print_r($momentlist_ldg[0], true) . ");\n";
       echo "df.totmom_ldg.value = totmom_ldg.toFixed(2);\n\n";

       echo "var totwt_to = -1 * (" . print_r($wtlist[0], true) . print_r($wtlist_to[0], true) . ");\n";
       echo "df.totwt_to.value = totwt_to.toFixed(2);\n";
       echo "var totwt_ldg = -1 * (" . print_r($wtlist[0], true) . print_r($wtlist_ldg[0], true) . ");\n";
       echo "df.totwt_ldg.value = totwt_ldg.toFixed(2);\n\n";

       echo "var totarm_to = totmom_to / totwt_to;\n";
       echo "df.totarm_to.value = Math.round(totarm_to*100)/100;\n\n";
       echo "var totarm_ldg = totmom_ldg / totwt_ldg;\n";
       echo "df.totarm_ldg.value = Math.round(totarm_ldg*100)/100;\n\n";

       echo "var w1 = " . $aircraft['maxwt'] . ";\n";
       echo "var c1 = " . $aircraft['cgwarnfwd'] .";\n";
       echo "var w2 = " . $aircraft['emptywt'] . ";\n";
       echo "var c2 = " . $aircraft['cgwarnaft'] . ";\n";
       echo "var overt  = Math.round(totwt_to - " . $aircraft['maxwt'] . ");\n\n";

       echo "document.getElementById(\"wbimage\").setAttribute(\"src\",\"scatter.php?tailnumber=" . $aircraft['id'] . "&totarm_to=\" + totarm_to + \"&totwt_to=\" + totwt_to + \"&totarm_ldg=\" + totarm_ldg + \"&totwt_ldg=\" + totwt_ldg + \"\")"; ?>

// WARNINGS
if  (parseFloat(Math.round(totwt_to))>w1) {
        var message = "\nBased on the provided data,\n"
            message += "this aircraft will be overweight at takeoff!\n"
       alert(message + "By " + overt + " kg")
        inuse_flag = false
    }

if  (parseFloat(Math.round(totarm_to*100)/100)>c2) {
        var message = "\nBased on the provided data,\n"
        message += "The takeoff CG may be AFT of limits\n"
        message += "for this aircraft. Please check the\n"
        message += "CG limitations as it applies to the\n"
        message += "weight and category of your flight.\n"
        alert(message)
        inuse_flag = false
    }

if  ( (parseFloat(Math.round(totarm_to*100)/100)>c2)&&
         (parseFloat(Math.round(totarm_to*100)/100)<c1) &&
          (parseFloat(Math.round(totwt_to))> (w1 - ((w1-w2)/(c1-c2))*c1 + ((w1-w2)/(c1-c2))*(parseFloat(Math.round(totarm_to*100)/100)))))
            {
        var message = "\n(1)Based on the provided data,\n"
        message += "The takeoff CG may be FWD of limits\n"
        message += "for this aircraft. Please check the\n"
        message += "CG limitations as it applies to the\n"
        message += "weight and category of your flight.\n"
        alert(message)
        inuse_flag = false
    }

if  (parseFloat(Math.round(totarm_to*100)/100)<c1) {
        var message = "\n(2)Based on the provided data,\n"
        message += "The takeoff CG may be FWD of limits\n"
        message += "for this aircraft. Please check the\n"
        message += "CG limitations as it applies to the\n"
        message += "weight and category of your flight.\n"
        alert(message)
        inuse_flag = false
    }
 }
// -->

isamap = new Object();
isamap[0] = "_df"
isamap[1] = "_ov"
isamap[2] = "_ot"
isamap[3] = "_dn"

</script>
</head>

<body onload="WeightBal();">

<form method="get" action="index.php"><input type="hidden" name="tailnumber" value="<?php echo($aircraft['id']); ?>">
<table style="width:700px; margin-left:auto; margin-right:auto;">
  <tr>
	  <td colspan="4" rowspan="6">
      <?php echo "<div class=\"titletext\">" . $config['site_name'] . "<br>" . $aircraft['makemodel'] . " " . $aircraft['tailnumber'] . "</div>";
        $updated_query_stmt = $con->prepare("SELECT `timestamp` FROM `audit` WHERE `what` LIKE ? ORDER BY `timestamp` DESC LIMIT 1");
        $bind_var_with_wildcards = "%" . $aircraft['tailnumber'] . "%";
        $updated_query_stmt->bind_param("s", $bind_var_with_wildcards);
        $updated_query_stmt->execute();
       $updated_query = $updated_query_stmt->get_result();
       $updated = mysqli_fetch_assoc($updated_query);
       echo "Aircraft last updated: " . date("j M Y", strtotime($updated['timestamp'])-$timezoneoffset) . "<br>\n"; ?>
      <p><b>PILOT SIGNATURE  X__________________________________________________</b><br>
      The Pilot In Command is responsible for ensuring all calculations are correct.<br>
      <?php echo date("D, j M Y H:i:s T"); ?></p>
    </td>

    <th>Empty Wt</th>
  </tr>
  <tr>
    <td style="text-align: center;"><?php echo $aircraft['emptywt']; ?> kg</td>
  </tr>
  <tr>
    <th>Empty CG</th>
  </tr>
  <tr>
    <td style="text-align: center;"><?php echo $aircraft['emptycg']; ?> m</td>
  </tr>
  <tr>
    <th>MTOW</th>
  </tr>
  <tr>
    <td style="text-align: center;"><?php echo $aircraft['maxwt']; ?> kg</td>
  </tr>
  <tr>
    <th style="width:385px" colspan="2">Item</th>
    <th style="width:105px">Weight (kg)</th>
    <th style="width:105px">Arm (m)</th>
    <th style="width:105px">Moment (kgm)</th>
  </tr>

<?php
  $weights_query_stmt = $con->prepare("SELECT * FROM aircraft_weights WHERE tailnumber = ? ORDER BY 'order' ASC");
  $weights_query_stmt->bind_param("i", $aircraft['id']);
  $weights_query_stmt->execute();
  $weights_query = $weights_query_stmt->get_result();
    while ($weights = mysqli_fetch_assoc($weights_query)) {
        echo "<tr><td";
        if ($weights['fuel']=="false") {
            echo " colspan=\"2\"";
        }
        echo ">" . $weights['item'] . "</td>\n";
        if ($weights['fuel']=="true") {
            echo "<td>";
            echo "<div style=\"display: flex; flex-direction: row; font-size: 10pt;\">";
            echo "<div style=\"width: 50%;\">";
            echo "<input type=\"number\" step=\"any\" name=\"line" . $weights['id'] . "_gallons_to\" tabindex=\"" . $tabindex . "\" onblur=\"Process()\" class=\"numbergals\"><span>" . $aircraft['fuelunit'] . " Takeoff</span><br>\n";
            echo "<input type=\"number\" step=\"any\" name=\"line" . $weights['id'] . "_gallons_ldg\" tabindex=\"" . $tabindex . "\" onblur=\"Process()\" class=\"numbergals\"><span>" . $aircraft['fuelunit'] . " Landing</span>";
            echo "</div>";
            if ($aircraft['fuelunit']=="Gallons") {
                echo "<div style=\"width: 50%;\">";
                echo "<input type=\"number\" class=\"numbergals readonly hidearrows\" name=\"line" . $weights['id'] . "_liters_to\" readonly><span>Liters Takeoff</span><br>\n";
                echo "<input type=\"number\" class=\"numbergals readonly hidearrows\" name=\"line" . $weights['id'] . "_liters_ldg\" readonly><span>Liters Landing</span>";
                echo "</div>";
            }
            $tabindex++;
            echo "</td>\n";
            echo "<td style=\"text-align: center;\"><div><input type=\"number\" name=\"line" . $weights['id'] . "_wt_to\" readonly class=\"readonly numbers\"> kg</div>";
            echo "<div><input type=\"number\" name=\"line" . $weights['id'] . "_wt_ldg\" readonly class=\"readonly numbers\"> kg</div></td>\n";
        } else {
            if ($weights['emptyweight']=="true") {
                echo "<td style=\"text-align: center;\"><input type=\"number\" name=\"line" . $weights['id'] . "_wt\" readonly class=\"readonly numbers\"> kg</td>\n";
            } else {
                echo "<td style=\"text-align: center;\"><input type=\"number\" step=\"any\" name=\"line" . $weights['id'] . "_wt\" tabindex=\"" . $tabindex . "\" onblur=\"Process()\" class=\"numbers\"> kg</td>\n";
            }
        }
        echo "<td style=\"text-align: center;\"><input type=\"number\" name=\"line" . $weights['id'] . "_arm\" readonly class=\"readonly numbers\"></td>\n";
        if ($weights['fuel']=="true") {
            echo "<td style=\"text-align: center;\"><div><input type=\"number\" name=\"line" . $weights['id'] . "_mom_to\" readonly class=\"readonly numbers\">";
            echo "\n</div><div><input type=\"number\" name=\"line" . $weights['id'] . "_mom_ldg\" readonly class=\"readonly numbers\">";
        } else {
            echo "<td style=\"text-align: center;\"><div><input type=\"number\" name=\"line" . $weights['id'] . "_mom\" readonly class=\"readonly numbers\">";
        }
        echo "</div></td></tr>\n\n";
        $tabindex++;
    } ?>

<tr style="background-color: #FFFF80">
<td style="text-align: right; font-weight: bold;" colspan="2">Totals at Takeoff<br>Landing</td>
<td style="text-align: center;"><div><input type="number" name="totwt_to" readonly class="readonly numbers"> kg</div><div><input type="number" name="totwt_ldg" readonly class="readonly numbers"> kg</div></td>
<td style="text-align: center;"><div><input type="number" name="totarm_to" readonly class="readonly numbers"></div><div><input type="number" name="totarm_ldg" readonly class="readonly numbers"></div></td>
<td style="text-align: center;"><div><input type="number" name="totmom_to" readonly class="readonly numbers"></div><div><input type="number" name="totmom_ldg" readonly class="readonly numbers"></div></td>
</tr>

<tr style="background-color: #FFFF80"><td colspan="5"><span style="font-weight: bold;">CG limits: </span><span style="font-family: monospace"><?php echo $aircraft['cglimits']; ?></span></td></tr>
</table>

<?php
echo "<iframe id=\"wbimage\" src=\"loading.png\" width=\"710\" height=\"360\" style=\"border:0px; display: block; margin-left: auto; margin-right: auto;\"></iframe>\n\n"; ?>

<!-- <div class="noprint" style="text-align:center; font-style:italic;">(click graph to enlarge)</div> -->

<div id="toolbar" class="noprint" style="line-height:35px;">
<span style="width: 130px; float: left; line-height:40px;"><abbr title="TippingPoint is free, open source weight and balance software.  Click to find out how to use it for your flight department, flight school, FBO or even your own personal aircraft.">
<a href="http://tippingpoint.sf.net" target="_blank" style="font-size:22px; color: yellow;">TippingPoint</a></abbr></span>
<span style="width: 700px; text-align:center; float: right; line-height:45px;">
<a href="admin.php?func=system">Edit System Settings</a> | <a href="admin.php?func=aircraft">Edit Aircraft</a> | <a href="admin.php?func=users">Edit Users</a> |
<a href="admin.php?func=audit">Audit Log</a> | <a href="admin.php?func=logout">Logout <?php echo $_SESSION["user_name"]; ?></a>
</span></div>

<div id="toolbar" class="noprint" style="line-height:35px;">
  <span style="width: 200px; float: left; line-height:40px;">&nbsp;&nbsp;
    <abbr title="Fulcrum W&amp;B is free, open source weight and balance software.">
      <a href="https://github.com/vesterli/fulcrum-wb" target="_blank" style="font-size:20px; color: white;">Fulcrum W&amp;B</a>
    </abbr>
  </span>
  <span style="width: 300px; text-align:center; float: center; line-height:45px;">
    <input type="submit" name="Submit" value="Calculate" tabindex="<?php echo($tabindex);
       $tabindex++; ?>" onClick="Process()">&nbsp;&nbsp;
    <input type="button" name="Reset" value="Reset" onclick="WeightBal()">&nbsp;&nbsp;
    <input type="button" value="Print" onClick="window.print()">&nbsp;&nbsp;
  </span>
  <span style="width: 200px; text-align:right; float: right; line-height:45px;">
    <a href="index.php">Choose Another Aircraft</a>&nbsp;&nbsp;
  </span>
</div>

</form>

<?php
   }
?>

<?php PageFooter($config['site_name'], $config['administrator'], $ver, $dbver);
// mysqli_close();
?>
