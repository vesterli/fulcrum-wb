# Fulcrum Weight &amp; Balance calculation for GA aircraft

By Sten Vesterli (<sten@vesterli.com>) for use in Roskilde Flyveklub.

## Overview
Fulcrum is a web-based aircraft weight and balance calculator with graphing. It has support for multiple aircraft and administrators. It has a W&B page with a graphical view of the envelope and a separate admin page for maintaining aircraft.

It is a Dec 2020 fork from TippingPoint (http://tippingpoint.sourceforge.net) by Caleb Newville (<caleb@inetwiz.com>). At this time, 99% of the functionality is from that project.

## Requirements
* A web server with PHP 7
* MySQL/MariaDB

## Download
[Download version 1.0](https://github.com/vesterli/fulcrum-wb/archive/v1.0.zip)

## Installation
1. Download the code
2. Extract the archive to a separate directory $FULCRUMDIR (e.g. `fulcrum`) on your webserver
3. Create an empty MySQL database
4. Create a MySQL database user and grant that user all privileges on the database
5. Go to http://(yourserver)/$FULCRUMDIR/setup.php
6. Enter the database connection information and other config information when prompted

## Updating

### Code update
If the update does not affect the database, do the following:
1. Change setup.php to mode 755 (rwxr-xr-x)
2. Overwrite all files on the server with the new version
3. Change setup.php back to mode 000 (---------)
The config.func file contains the connect information and will not be overwritten. The new code will thus
inherit the existing database connection and tables

### Code and database update
When a version is released that changes the database, it will come with a separate update.php file that you need to run after installing the code.

## Re-Installation
If you want to reset your installation:
1. Delete your MySQL database
2. Create a new MySQL database
3. Grant all privileges on the new database to the existing user
4. Delete the `config.inc` file on your web server and set the permissions on `setup.php` back to 755.
5. Run setup.php
You will now be prompted for database connect information and be taken through the setup process again.


### Bug reports and improvement suggestions
Go to the [Issues page on GitHub](https://github.com/vesterli/fulcrum-wb/issues) to see existing bugs and already suggested improvements. If you have a Github account, you can add more.
