<?php
// this file updates the 0.8.2 database to 0.9.0

include 'func.inc';
PageHeader($config['site_name']);

// HTML to say that the database is being updated
echo "Updating database to 0.9.0...<br/>";
flush();

// prepared statement on the $con connection to add column maxweight float to aircraft_weights table
$add_maxweight_stmt = mysqli_prepare($con, "ALTER TABLE `aircraft_weights` ADD `maxweight` FLOAT NULL AFTER `weight`;");
if (!$add_maxweight_stmt) {
    echo "Error preparing statement to add maxweight column to aircraft_weight table: " . mysqli_error($con) . "";
    exit;
}
// try to execute the prepared statement to add the maxweight column to the aircraft_weight table
try {
    mysqli_stmt_execute($add_maxweight_stmt);
} catch (mysqli_sql_exception $e) {
    // if the error code is 1060, the column already exists, so we can continue
    if ($e->getCode() == 1060) {
        echo "Column maxweight already exists in aircraft_weight table. Continuing...<br/>";
    } else {
        echo "Error executing statement to add maxweight column to aircraft_weight table: " . mysqli_error($con) . "";
        exit;
    }
} finally {
    // close the prepared statement
    mysqli_stmt_close($add_maxweight_stmt);
}

// prepared statement to update the database version to 0.9.0
$update_dbversion_stmt = mysqli_prepare($con, "UPDATE `configuration` SET `value` = '0.9.0' WHERE `item` = 'db_version';");
if (!$update_dbversion_stmt) {
    echo "Error preparing statement to update database version to 0.9.0: " . mysqli_error($con) . "";
    exit;
}
// try to execute the prepared statement to update the database version to 0.9.0
try {
    mysqli_stmt_execute($update_dbversion_stmt);
} catch (mysqli_sql_exception $e) {
    echo "Error executing statement to update database version to 0.9.0: " . mysqli_error($con) . "";
    exit;
} finally {
    // close the prepared statement
    mysqli_stmt_close($update_dbversion_stmt);
}

// HTML to say the database update is complete
echo "Database update complete.";
flush();

?>