<?php
// this file updates the 0.8.1 (initial), 0.8.2 and 0.9.0 database to 1.0.0

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

// HTML to say that the database is being updated
echo "Updating database to 1.0.0...<br/>";
flush();

// prepared statement on the $con connection to add date field for weighing_date to aircraft table
$add_weighingdate_stmt = mysqli_prepare($con, "ALTER TABLE `aircraft` ADD `weighing_date` DATE NULL AFTER `cgwarnaft`;");
if (!$add_weighingdate_stmt) {
    echo "Error preparing statement to add weighing_date column to aircraft table: " . mysqli_error($con) . "";
    exit;
}
// try to execute the prepared statement to add the weighing_date column to the aircraft table
try {
    mysqli_stmt_execute($add_weighingdate_stmt);
} catch (mysqli_sql_exception $e) {
    // if the error code is 1060, the column already exists, so we can continue
    if ($e->getCode() == 1060) {
        echo "Column weighing_date already exists in aircraft table. Continuing...<br/>";
    } else {
        echo "Error executing statement to add weighing_date column to aircraft table: " . mysqli_error($con) . "";
        exit;
    }
} finally {
    // close the prepared statement
    mysqli_stmt_close($add_weighingdate_stmt);
}

// prepared statement on the $con connection to add text field for weighing_sheet_url to aircraft table
$add_weighingsheeturl_stmt = mysqli_prepare($con, "ALTER TABLE `aircraft` ADD `weighing_sheet_url` TEXT NULL AFTER `weighing_date`;");
if (!$add_weighingsheeturl_stmt) {
    echo "Error preparing statement to add weighing_sheet_url column to aircraft table: " . mysqli_error($con) . "";
    exit;
}
// try to execute the prepared statement to add the weighing_sheet_url column to the aircraft table
try {
    mysqli_stmt_execute($add_weighingsheeturl_stmt);
} catch (mysqli_sql_exception $e) {
    // if the error code is 1060, the column already exists, so we can continue
    if ($e->getCode() == 1060) {
        echo "Column weighing_sheet_url already exists in aircraft table. Continuing...<br/>";
    } else {
        echo "Error executing statement to add weighing_sheet_url column to aircraft table: " . mysqli_error($con) . "";
        exit;
    }
} finally {
    // close the prepared statement
    mysqli_stmt_close($add_weighingsheeturl_stmt);
}

// prepared statement to update the database version to 1.0.0  
$update_dbversion_stmt = mysqli_prepare($con, "UPDATE `configuration` SET `value` = '1.0.0' WHERE `item` = 'db_version';");
if (!$update_dbversion_stmt) {
    echo "Error preparing statement to update database version to 1.0.0: " . mysqli_error($con) . "";
    exit;
}
// try to execute the prepared statement to update the database version to 1.0.0
try {
    mysqli_stmt_execute($update_dbversion_stmt);
} catch (mysqli_sql_exception $e) {
    echo "Error executing statement to update database version to 1.0.0: " . mysqli_error($con) . "";
    exit;
} finally {
    // close the prepared statement
    mysqli_stmt_close($update_dbversion_stmt);
}

// HTML to say the database update is complete
echo "Database update complete.";
flush();

?>