<?php
// this file contains static HTML code used for instructions and data entry forms

function PageHeader($site_name)
{
	ob_start();
	?>
	<!DOCTYPE HTML>
	<html>

	<head>
		<title>Fulcrum W&B -
			<?php echo ($site_name); ?>
		</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">

		<style type="text/css">
			<!--
			body {
				background-color: #FFFFFF;
				font-family: Cambria, Tahoma, Verdana;
				font-size: 14px;
			}

			input,
			select {
				font-family: Cambria, Tahoma, Verdana;
				font-size: 11px;
				border: 1px solid #AAAAAA;
			}

			table,
			th,
			td {
				border: 1px solid black;
				border-collapse: collapse;
				padding: 3px;
			}

			th {
				background-color: #4F81BD;
				text-align: center;
			}

			tr:nth-child(odd) {
				background-color: #FFFFFF;
			}

			tr:nth-child(even) {
				background-color: #EEEEEE;
			}

			abbr {
				border-bottom: 1px dashed;
				cursor: help;
			}

			.titletext {
				color: #17365D;
				font-size: 24px;
			}

			.readonly {
				background-color: #CCCCCC;
			}

			.numbers {
				text-align: right;
				width: 70px;
			}

			.numbergals {
				text-align: right;
				width: 40px;
				margin-right: 5px;
			}

			input.hidearrows::-webkit-outer-spin-button,
			input.hidearrows::-webkit-inner-spin-button {
				-webkit-appearance: none;
				margin: 0;
			}

			input.hidearrows {
				-moz-appearance: textfield;
			}

			@media print {
				.noprint {
					display: none;
				}
			}

			#toolbar {
				width: 700px;
				left: 0px;
				right: 0px;
				height: 35px;
				position: fixed;
				bottom: 0px;
				margin-right: auto;
				margin-left: auto;
				z-index: 100;
				background-color: #00304E;
				color: white;
			}

			#toolbar a:link,
			#toolbar a:visited,
			#toolbar a:hover {
				color: white;
				text-decoration: none;
			}
			-->
		</style>
		<?php
}

function PageFooter($site_name, $admin, $ver, $dbver)
{
	?>
		<p class="noprint" style="text-align:center; font-size:12px; font-style:italic;">
			<?php echo ($site_name); ?> - <a href="mailto:<?php echo ($admin); ?>?subject=Fulcrum%20W%26B">
				<?php echo ($admin); ?>
			</a><br />
			<a href="https://github.com/vesterli/fulcrum-wb" target="_blank">Fulcrum W&amp;B</a> open source Weight &amp;
			Balance software</a>
			<?php echo ($ver); ?> (DB
			<?php echo ($dbver); ?>)<br />
			Based on <a href="https://sourceforge.net/projects/tippingpoint/" target="_blank">TippingPoint</a> by Caleb Newville
		</p>
		<div style="height: 20px;"></div>
		</body>

	</html>
	<?php
	ob_end_flush();
}

function showAircraftMenu()
{
	echo "<p>This module edits aircraft weight and balance templates.</p>\n";
            echo "<a href=\"admin.php?func=aircraft&amp;func_do=add\">Add Aicraft</a><br>\n";
            echo "<a href=\"admin.php?func=aircraft&amp;func_do=edit\">Edit Aicraft</a><br>\n";
            echo "<a href=\"admin.php?func=aircraft&amp;func_do=duplicate\">Duplicate Aicraft</a><br>\n";
            echo "<a href=\"admin.php?func=aircraft&amp;func_do=delete\">Delete Aicraft</a>\n";
}

function showAircraftEntryForm()
{
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
    echo "<tr><td style=\"text-align: right\">Weighing Date</td><td><input type=\"date\" name=\"weighing_date\" value=\"" . date("Y-m-d") . "\"></td></tr>\n";
    echo "<tr><td style=\"text-align: right\">Weighing Sheet URL</td><td><input type=\"text\" name=\"weighing_sheet_url\" value=\"https://\"></td></tr>\n";
    echo "<tr><td style=\"text-align: right\">Fuel Unit</td><td><select name=\"fuelunit\"><option value=\"Gallons\">Gallons</option><option value=\"Liters\">Liters</option><option value=\"Pounds\">Pounds</option><option value=\"Kilograms\">Kilograms</option></select></td></tr>\n";
    echo "<tr><td colspan=\"2\" style=\"text-align: center;\"><input type=\"submit\" value=\"Step 2\"></td></tr>\n";
    echo "</table></form>\n";
}
?>