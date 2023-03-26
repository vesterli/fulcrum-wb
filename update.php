<?php
// this file updates the 0.8.1 database to 0.8.2

include 'func.inc';
PageHeader($config['site_name']);

// HTML to say that the database is being updated
echo "Updating database to 0.8.2...<br/>";
flush();

// prepared statement on the $con connection to remove the cglimits column from the aircraft table
$remove_cglimits_stmt = mysqli_prepare($con, "ALTER TABLE `aircraft` DROP `cglimits`");
if (!$remove_cglimits_stmt) {
    echo "Error preparing statement to remove cglimits column from aircraft table: " . mysqli_error($con) . "";
    exit;
}
// try to execute the prepared statement to remove the cglimits column from the aircraft table
try {
    mysqli_stmt_execute($remove_cglimits_stmt);
} catch (mysqli_sql_exception $e) {
    // if the error code is 1091, the column does not exist, so we can continue
    if ($e->getCode() == 1091) {
        echo "Column cglimits does not exist in aircraft table. Continuing...<br/>";
    } else {
        echo "Error executing statement to remove cglimits column from aircraft table: " . mysqli_error($con) . "";
        exit;
    }
} finally {
    // close the prepared statement
    mysqli_stmt_close($remove_cglimits_stmt);
}

// prepared statement to update the database version to 0.8.2
$update_dbversion_stmt = mysqli_prepare($con, "UPDATE `configuration` SET `value` = '0.8.2' WHERE `item` = 'db_version';");
if (!$update_dbversion_stmt) {
    echo "Error preparing statement to update database version to 0.8.2: " . mysqli_error($con) . "";
    exit;
}
// try to execute the prepared statement to update the database version to 0.8.2
try {
    mysqli_stmt_execute($update_dbversion_stmt);
} catch (mysqli_sql_exception $e) {
    echo "Error executing statement to update database version to 0.8.2: " . mysqli_error($con) . "";
    exit;
} finally {
    // close the prepared statement
    mysqli_stmt_close($update_dbversion_stmt);
}

// HTML to say the database update is complete
echo "Database update complete.";
flush();

?>