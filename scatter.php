<?php
include 'func.inc';

// connect to the database, checking version
connectDB(true);

// DEFINE VARIABLES
$chart_size = (isset($_GET["size"])) ? $_GET["size"] : "large";
$plot_envelope = "";

$totarm_to = (isset($_GET["totarm_to"])) ? $_GET["totarm_to"] : "null";
$totwt_to = (isset($_GET["totwt_to"])) ? $_GET["totwt_to"] : "null";
$totarm_ldg = (isset($_GET["totarm_ldg"])) ? $_GET["totarm_ldg"] : "null";
$totwt_ldg = (isset($_GET["totwt_ldg"])) ? $_GET["totwt_ldg"] : "null";

// GET AIRCRAFT CG ENVELOPE DATA
$result = mysqli_query($con,"SELECT * FROM aircraft_cg WHERE tailnumber=" . $_REQUEST['tailnumber']);
while($row = mysqli_fetch_array($result)) {
	$arm[] = $row['arm'];
	$weight[] = $row['weight'];
	$plot_envelope = $plot_envelope . "[" . $row['arm'] . ", " . $row['weight'] . ", null, null],\n";
}
	// We have to add the first point back to the end so we have a connected graph
	// Only add it if there is anything to add it to, to avoid undef var error
	if(isset($arm)) {
		$arm[] = $arm[0];
	}
	if(isset($weight)) {
		$weight[] = $weight[0];
	}
	if(isset($arm) && isset($weight)) {
		// if we have any data, plot envelope
		$plot_envelope = $plot_envelope . "[" . $arm[0] . ", " . $weight[0] . ", null, null]";
	}
// NEW CODE HERE ///////////////////////////////////////////////////////////////
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<?php
	if ($chart_size=="small") {
		echo "<div id=\"chart_div\" style=\"width: 400px; height: 195px\"></div>\n";
	} elseif ($chart_size=="print") {
		echo "<div id=\"chart_div\" style=\"width: 720px; height: 950px\"></div>\n";
	} elseif ($chart_size=="large") {
		echo "<div id=\"chart_div\" style=\"width: 700px; height: 340px\"></div>\n";
	}
?>

<script type="text/javascript">

google.charts.load('current', {
  packages: ['corechart', 'line']
});
google.charts.setOnLoadCallback(drawBackgroundColor);

function drawBackgroundColor() {
  var data = new google.visualization.DataTable();
  data.addColumn('number', 'Weight');
  data.addColumn('number', 'CG Envelope');
  data.addColumn('number', 'Takeoff');
  data.addColumn('number', 'Landing');
	<?php
	  // only try to add data if we have any
	  if (isset($plot_envelope) && $plot_envelope != "") {
			echo "data.addRows([\n";
			echo $plot_envelope . ",\n";
			echo "[" . $totarm_to . ", null, " . $totwt_to . ", null],\n";
			echo "[" . $totarm_ldg . ", null, null, " . $totwt_ldg . "]\n";
			echo "]);\n";
		}
	?>


  var options = {
    hAxis: {
      title: 'Meters From Reference Datum'
    },
    vAxis: {
      title: 'Kg',
      textPosition: 'out',
      viewWindowMode: 'maximized'
    },
    backgroundColor: '#ffffff',
    series: {
      0: {
        color: 'blue',
        visibleInLegend: true
      },
      1: {
        color: 'green',
				<?php if ($chart_size=="small") {
					echo "visibleInLegend: false\n";
				} else { echo "visibleInLegend: true\n"; }
				?>
      },
      2: {
        color: 'red',
				<?php if ($chart_size=="small") {
					echo "visibleInLegend: false\n";
				} else { echo "visibleInLegend: true\n"; }
				?>
      }
    },
    chartArea: {
			<?php if ($chart_size=="small") {
				echo "
						left: 60,
						top: 10,
						right: 10,
						bottom: 30\n";
} else {
	echo "
      left: 60,
      top: 50,
      right: 10,
      bottom: 40\n";
}?>
    },
    legend: {
      position: 'top'
    },
    pointSize: 5
  };

<?php
  if (isset($plot_envelope)) {
  	echo "var chart = new google.visualization.LineChart(document.getElementById('chart_div'));";
		echo "chart.draw(data, options);";
	}
?>
}


</script>
