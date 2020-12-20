# Fulcrum Weight &amp; Balance calculation for GA aircraft

By Sten Vesterli (<sten@vesterli.com>) for use in Roskilde Flyveklub.

## Overview
Fulcrum is a web-based aircraft weight and balance calculator with graphing. It has support for multiple aircraft and administrators. It has a W&B page with a graphical view of the envelope and a separate admin page for maintaining aircraft.

It is a Dec 2020 fork from TippingPoint (http://tippingpoint.sourceforge.net) by Caleb Newville (<caleb@inetwiz.com>). At this time, 99% of the functionality is from that project.

## Requirements
* A web server with PHP 7
* MySQL/MariaDB

## Download
[Download version 0.8.1](https://github.com/vesterli/fulcrum-wb/archive/v0.8.1.zip)

## Installation
1. Download the code
2. Extract the archive to a separate directory $FULCRUMDIR (e.g. `fulcrum`) on your webserver
3. Create an empty MySQL database and a database user, and associate the user with the Fulcrum database.
4. Go to http://(yourserver)/$FULCRUMDIR/setup.php
5. Enter the database connection information and other config information when prompted

## Re-Installation
If you want to reset your installation, delete the `config.inc` file on your web server and set the permissions on `setup.php` back to 755.

### Bug reports and improvement suggestions
Go to the [Issues page on GitHub](https://github.com/vesterli/fulcrum-wb/issues) to see existing bugs and already suggested improvements. If you have a Github account, you can add more.
