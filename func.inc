<?php
include 'config.inc';
require 'ver.inc';

/* VARIABLES */
$tabindex = 1;

try {
	$con = mysqli_connect($dbserver, $dbuser, $dbpass, $dbname);
	$config_result = mysqli_query($con, "SELECT * FROM configuration;");
	while ($config_row = mysqli_fetch_assoc($config_result)) {
		$config[$config_row['item']] = $config_row['value'];
	}
	// if there is a database version, check it
	if (isset($config['db_version'])) {
		// if the database version does not match the version in ver.inc, give an error
		if ($config['db_version'] != $dbver) {
			echo "Error: Code requires database version " . $dbver . " but the database is version " . $config['db_version'] . ". Please update code or database.";
			exit;
		}
	} // else we are in the setup process
} catch (Exception $e) {
	echo "<div class=\"error\">Error connecting to database server. </div>";
}


function TimezoneList($str)
{
	echo ("<select id=\"timezone\" name=\"timezone\">");
	$timezone_identifiers = DateTimeZone::listIdentifiers();
	foreach ($timezone_identifiers as $value) {
		if (preg_match('/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $value)) {
			$ex = explode("/", $value); //obtain continent,city
			if ($continent != $ex[0]) {
				if ($continent != "")
					echo "</optgroup>\n";
				echo "<optgroup label=\"" . $ex[0] . "\">\n";
			}
			$city = $ex[1];
			if (is_null($ex[2]) == FALSE) {
				$city = $city . "/" . $ex[2];
			}
			$continent = $ex[0];
			echo "<option value=\"" . $value . "\"";
			if ($str == $value) {
				echo " selected";
			}
			echo ">" . $city . "</option>\n";
		}
	}
	echo ("</optgroup></select>");
}

function get_timezone_offset($remote_tz, $origin_tz = null)
{
	if ($origin_tz === null) {
		if (!is_string($origin_tz = date_default_timezone_get())) {
			return false; // A UTC timestamp was returned -- bail out!
		}
	}
	$origin_dtz = new DateTimeZone($origin_tz);
	$remote_dtz = new DateTimeZone($remote_tz);
	$origin_dt = new DateTime("now", $origin_dtz);
	$remote_dt = new DateTime("now", $remote_dtz);
	$offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
	return $offset;
}
$timezoneoffset = get_timezone_offset($config['timezone']);
date_default_timezone_set($config['timezone']);

function AircraftListActive()
{
	require 'config.inc';

	$con = mysqli_connect($dbserver, $dbuser, $dbpass, $dbname);
	if (!mysqli_connect_error()) {
		echo "<select name=\"tailnumber\">\n";
		$result = mysqli_query($con, "SELECT * FROM `aircraft` WHERE `active` = 1 ORDER BY 'tailnumber' ASC;");
		while ($row = mysqli_fetch_array($result)) {
			echo "<option value=\"" . $row['id'] . "\">" . $row['tailnumber'] . " - " . $row['makemodel'] . "</option>\n";
		}
		echo "</select>\n";
	} else {
		echo "Error: " . mysqli_connect_error();
	}
}

function AircraftListAll()
{
	require 'config.inc';
	$con = mysqli_connect($dbserver, $dbuser, $dbpass, $dbname);
	if (!mysqli_connect_error()) {

		echo "<select name=\"tailnumber\">\n";
		echo "<optgroup label=\"Active\">\n";
		$result = mysqli_query($con, "SELECT * FROM `aircraft` WHERE `active` = 1 ORDER BY 'tailnumber' ASC;");
		while ($row = mysqli_fetch_array($result)) {
			echo "<option value=\"" . $row['id'] . "\">" . $row['tailnumber'] . " - " . $row['makemodel'] . "</option>\n";
		}
		echo "</optgroup>\n";
		echo "<optgroup label=\"Inactive\">\n";
		$result = mysqli_query($con, "SELECT * FROM `aircraft` WHERE `active` = 0 ORDER BY 'tailnumber' ASC;");
		while ($row = mysqli_fetch_array($result)) {
			echo "<option value=\"" . $row['id'] . "\">" . $row['tailnumber'] . " - " . $row['makemodel'] . "</option>\n";
		}
		echo "</optgroup>\n";
		echo "</select>\n";
	} else {
		echo "Error: " . mysqli_connect_error();
	}
}

function AuditLog($login_user, $log_message)
{
	require 'config.inc';
	$con = mysqli_connect($dbserver, $dbuser, $dbpass, $dbname);
	$sql_audit_stmt = $con->prepare("INSERT INTO audit (`id`, `timestamp`, `who`, `what`) VALUES (NULL,CURRENT_TIMESTAMP, ?, ?);");
	if (!mysqli_connect_error()) {
		$sql_audit_stmt->bind_param("ss", $login_user, $log_message);
		$sql_audit_stmt->execute();

	} else {
		echo "Error: " . mysqli_connect_error();
	}

}
?>