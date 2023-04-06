<?php
include 'config.inc';
require 'ver.inc';

/* VARIABLES */
$tabindex = 1;

// connect to the database, check database version	
function connectDB($checkdb) {
	global $dbserver, $dbuser, $dbpass, $dbname, $dbver, $config, $con;
	try {
		$con = mysqli_connect($dbserver, $dbuser, $dbpass, $dbname);
		$config_result = mysqli_query($con, "SELECT * FROM configuration;");
		while ($config_row = mysqli_fetch_assoc($config_result)) {
			$config[$config_row['item']] = $config_row['value'];
		}
		// if there is a database version, check it
		if (($checkdb == true) && (isset($config['db_version']))) {
			// if the database version does not match the version in ver.inc, give an error
			if ($config['db_version'] != $dbver) {
				echo "Error: Code requires database version " . $dbver . " but the database is version " . $config['db_version'] . ". Please update code or database.";
				exit;
			}
		} // else we are in the setup process or updating, silently exit
	} catch (Exception $e) {
		echo "<div class=\"error\">Error connecting to database server. </div>";
		exit;
	}
}
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