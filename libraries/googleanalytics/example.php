<?php
 
  // session_start for caching
  session_start();
  
  require 'analytics.class.php';
  
  try {
      
	  $username = "GOOGLE_ANALYTICS_USERNAME";
	  $password = "GOOGLE_ANALYTICS_PASSWORD";
      
	  // construct the class
      $oAnalytics = new analytics($username, $password);
      
      // set it up to use caching
      $oAnalytics->useCache();
      
      //$oAnalytics->setProfileByName('southernlakehome.com');
      $oAnalytics->setProfileById('ga:59747120');
      //$oAnalytics->setProfileById('ga:34265687');
      
      // set the date range
      //$oAnalytics->setMonth(date('n'), date('Y'));
      $oAnalytics->setDateRange(date('Y-m-d', mktime(0, 0, 0, date("m")-6, date("d"), date("Y"))), date('Y-m-d'));
      
      /*
	  echo '<pre>';
      // print out visitors for given period
      print_r($oAnalytics->getVisitors());
      
      // print out pageviews for given period
      print_r($oAnalytics->getPageviews());
      
      // use dimensions and metrics for output
      // see: http://code.google.com/intl/nl/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
      print_r($oAnalytics->getData(array(   'dimensions' => 'ga:keyword',
                                            'metrics'    => 'ga:visits',
                                            'sort'       => 'ga:keyword')));
	  */
      
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
        <title>Google Analytics PHP API example - SWIS BV</title>
        <meta name="description" content="">
        <meta name="keywords" content="">
        <style type="text/css">
            body{font: 11px Arial, Helvetica, sans-serif}
        </style>
		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<script type="text/javascript">google.load("visualization", "1", {packages:["controls","corechart"]});</script>
	</head>
    <body>
		<h2>Unique Pageviews:</h2>
        <?php 
		$result = $oAnalytics->getData(array('dimensions' => 'ga:date',
                                            'metrics'    => 'ga:uniquePageviews',
                                            'sort'    => 'ga:date'
											));
		$i = 0;
		$dimension = array();
		$vals = array();
		// Add up day totals for each month
		foreach ($result as $key => $val) {
			$key = strtotime($key);
			$dimension[] = array("new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key).")",$val);
			/*
			if (date('d', $key) == date('t', $key) || date('Y-m-d', $key) == date('Y-m-d', time())) {
				$dimension[] = array("{v:new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key)."), f:'".date('M', $key).", ".date('Y', $key)."'}",($vals[$i]+$val));
				$i++;
			} else {
				$vals[$i] = ($vals[$i]+$val);
			}
			*/
		}	
		$metric = array(
			array("'date'", "'Date'"),
			array("'number'", "'Unique Pageviews'")
		);
		graph($dimension, $metric, 'Unique Pageviews:', true, 'AreaChart', ", focusTarget: 'category'"); 
        ?>
        <div id="uniquepageviews">
			<div id="chartuniquepageviews"></div>
			<div id="controluniquepageviews"></div>
		</div>
        
		<h2>Unique Visitors:</h2>
        <?php 
		$result = $oAnalytics->getData(array('dimensions' => 'ga:date',
                                            'metrics'    => 'ga:visitors', 
                                            'sort'    => 'ga:date'
											));
		$i = 0;
		$dimension = array();
		$vals = array();
		// Add up day totals for each month
		foreach ($result as $key => $val) {
			$key = strtotime($key);
			$dimension[] = array("new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key).")",$val);
			/*
			if (date('d', $key) == date('t', $key) || date('Y-m-d', $key) == date('Y-m-d', time())) {
				$dimension[] = array("{v:new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key)."), f:'".date('M', $key).", ".date('Y', $key)."'}",($vals[$i]+$val));
				$i++;
			} else {
				$vals[$i] = ($vals[$i]+$val);
			}
			*/
		}		
		$metric = array(
			array("'date'", "'Date'"),
			array("'number'", "'Unique Visitors'")
		);
		graph($dimension, $metric, 'Unique Visitors:', true, 'AreaChart', ", focusTarget: 'category'"); 
		?>
        <div id="uniquevisitors">
			<div id="chartuniquevisitors"></div>
			<div id="controluniquevisitors"></div>
		</div>

		<h2>Total Visits:</h2>
		<?php 
		$result = $oAnalytics->getData(array('dimensions' => 'ga:date',
                                            'metrics'    => 'ga:visits', 
                                            'sort'    => 'ga:date'
											));
		$i = 0;
		$dimension = array();
		$vals = array();
		// Add up day totals for each month
		foreach ($result as $key => $val) {
			$key = strtotime($key);
			$dimension[] = array("new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key).")",$val);
			/*
			if (date('d', $key) == date('t', $key) || date('Y-m-d', $key) == date('Y-m-d', time())) {
				$dimension[] = array("{v:new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key)."), f:'".date('M', $key).", ".date('Y', $key)."'}",($vals[$i]+$val));
				$i++;
			} else {
				$vals[$i] = ($vals[$i]+$val);
			}
			*/
		}		
		$metric = array(
			array("'date'", "'Date'"),
			array("'number'", "'Visits'")
		);
		graph($dimension, $metric, 'Visits:', true, 'AreaChart', ", focusTarget: 'category'"); 
		?>
         <div id="visits">
			<div id="chartvisits"></div>
			<div id="controlvisits"></div>
		 </div>

        <h2>Total Pageviews:</h2>
        <?php 
		$result = $oAnalytics->getData(array('dimensions' => 'ga:date',
                                            'metrics'    => 'ga:pageviews', 
                                            'sort'    => 'ga:date'
											));
		$i = 0;
		$dimension = array();
		$vals = array();
		// Add up day totals for each month
		foreach ($result as $key => $val) {
			$key = strtotime($key);
			$dimension[] = array("new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key).")",$val);
			/*
			if (date('d', $key) == date('t', $key) || date('Y-m-d', $key) == date('Y-m-d', time())) {
				$dimension[] = array("{v:new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key)."), f:'".date('M', $key).", ".date('Y', $key)."'}",($vals[$i]+$val));
				$i++;
			} else {
				$vals[$i] = ($vals[$i]+$val);
			}
			*/
		}		
		$metric = array(
			array("'date'", "'Date'"),
			array("'number'", "'Pageviews'")
		);
		graph($dimension, $metric, 'Pageviews:', true, 'AreaChart', ", focusTarget: 'category'"); 
		?>
         <div id="pageviews">
			<div id="chartpageviews"></div>
			<div id="controlpageviews"></div>
		 </div>
        
        <h2>Visits per Hour:</h2>
        <?php 
		$result = $oAnalytics->getVisitsPerHour();
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Hour'"),
			array("'number'", "'Visits'")
		);
		graph($dimension, $metric, 'Visits per Hour:', false, 'AreaChart', ", focusTarget: 'category'"); 
		?>
         <div id="visitsperhour"></div>
       
        <h2>Pages:</h2>
        <?php 
		$result = $oAnalytics->getPageviewsByPage();
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Page'"),
			array("'number'", "'Pageviews'")
		);
		graph($dimension, $metric, 'Pages:', false, 'PieChart', ", is3D: true, height: 500, legend: {textStyle: {fontSize: 9}}"); 
		?>
        <div id="pages"></div>
        
        <h2>Browsers:</h2>
        <?php 
		$result = $oAnalytics->getBrowsers();
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Browser'"),
			array("'number'", "'Visits'")
		);
		graph($dimension, $metric, 'Browsers:', false, 'PieChart', ", is3D: true, height: 500, legend: {/*title: 'Year',  */textStyle: {fontSize: 9}}"); 
		?>
        <div id="browsers"></div>
        
        <h2>Referrers:</h2>
        <?php 
		$result = $oAnalytics->getReferrers();
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Referrer'"),
			array("'number'", "'Visits'")
		);
		graph($dimension, $metric, 'Referrer:', false, 'PieChart', ", is3D: true, height: 500, legend: {/*title: 'Year',  */textStyle: {fontSize: 9}}"); 
		?>
        <div id="referrer"></div>
        
        <h2>Search words:</h2>
        <?php 
		$result = $oAnalytics->getSearchWords();
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Search words'"),
			array("'number'", "'Searches'")
		);
		graph($dimension, $metric, 'Search words:', false, 'PieChart', ", is3D: true, height: 500, legend: {/*title: 'Year',  */textStyle: {fontSize: 9}}"); 
		?>
        <div id="searchwords"></div>
        
        <h2>Screen resolution:</h2>
		<?php 
		$result = $oAnalytics->getScreenResolution();
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Screen resolution'"),
			array("'number'", "'Visits'")
		);
		graph($dimension, $metric, 'Screen resolution:', false, 'PieChart', ", is3D: true, height: 500, legend: {/*title: 'Year',  */textStyle: {fontSize: 9}}"); 
		?>
        <div id="screenresolution"></div>
        
		<h2>Operating System:</h2>
		<?php 
		$result = $oAnalytics->getOperatingSystem();
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Operating system'"),
			array("'number'", "'Visits'")
		);
		graph($dimension, $metric, 'Operating system:', false, 'PieChart', ", is3D: true, height: 500, legend: {/*title: 'Year',  */textStyle: {fontSize: 9}}"); 
		?>
        <div id="operatingsystem"></div>
		
	</body>
</html>
<?php
	} catch (Exception $e) { 
      echo 'Caught exception: ' . $e->getMessage(); 
  }
  
 /**
* Basic html table for displaying graphs
* 
* @param array $aData
*/
function graph($aData = array(), $metric = array(), $title = '', $range = false, $chart = 'AreaChart', $options = ''){
    $iMax = max($aData);
	$safe_title = preg_replace("[^A-Za-z0-9]", '', (strtolower($title)));
	echo "<script type=\"text/javascript\">\n";
    if ($iMax == 0){
        echo "document.getElementById('".$safe_title."').innerHTML = 'No data'";
    /*
	} else if ($iMax == 1) {
		echo "document.getElementById('".$safe_title."').innerHTML = '".."';";
	*/
	} else {
		$col_count = count($metric[0]);
		$col_string = '';
		for ($i=1; $i<$col_count;$i++) {
			$col_string .= ', '.$i;
		}
		if ($range === true) {
			echo "function drawVisualization".$safe_title."() {
				var dashboard = new google.visualization.Dashboard(
				   document.getElementById('".$safe_title."'));

				var control = new google.visualization.ControlWrapper({
				 'controlType': 'ChartRangeFilter',
				 'containerId': 'control".$safe_title."',
				 'options': {
				   // Filter by the date axis.
				   'filterColumnIndex': 0,
				   'ui': {
					 'chartType': 'LineChart',
					 'chartOptions': {
					   'height': 80,
					   /*'chartArea': {'width': '90%'},*/
					   'hAxis': {'baselineColor': 'none'}
					 },
					 // Display a single series of first two columns
					 'chartView': {
					   'columns': [0".$col_string."]
					 },
					 // 1 day in milliseconds = 24 * 60 * 60 * 1000 = 86,400,000
					 'minRangeSize': 86400000
				   }
				 },
				 // Initial range
				 'state': {'range': {'start': new Date(".date('Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y"))).", ".(date('n', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")))-1).", ".date('j', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")))."), 'end': new Date(".date('Y').", ".(date('n')-1).", ".date('j').")}}
				});

				var chart = new google.visualization.ChartWrapper({
				 'chartType': '".$chart."',
				 'containerId': 'chart".$safe_title."',
				 'options': {
				   'height': 300,
				   /*
				   'chartArea': {'height': '80%', 'width': '90%'},
				   'vAxis': {'viewWindow': {'min': 0, 'max': 2000}},
				   */
				   'hAxis': {'slantedText': false, textStyle: {fontSize: 10}},
				   'focusTarget': 'category',
				   'legend': {'position': 'none'}
				 },
				 // Display a single series of first two columns
				 // Convert the first column from 'date' to 'string'.
				 'view': {
				   'columns': [
					 {
					   'calc': function(dataTable, rowIndex) {
						 return dataTable.getFormattedValue(rowIndex, 0);
					   },
					   'type': 'string'
					 }".$col_string."]
				 }
				});
			";
		} else {
			echo "function drawChart".$safe_title."() {\n";
		}
		echo "var data = new google.visualization.DataTable();\n";
		foreach ($metric as $m) {
			echo "data.addColumn(".implode(',',$m).");\n";
		}
		foreach ($aData as $d) {
			echo "data.addRow([".implode(',',$d)."]);\n";
		}
		if ($range === true) {
			echo "dashboard.bind(control, chart);\n
				dashboard.draw(data);\n
			}
			google.setOnLoadCallback(drawVisualization".$safe_title.");\n";
		} else {
			echo "var options = {
				  title: '',focusTarget:'category'".$options."
				};
				var chart = new google.visualization.".$chart."(document.getElementById('".$safe_title."'));\n
				chart.draw(data, options);\n
			}
			google.setOnLoadCallback(drawChart".$safe_title.");\n";
		}
	}
    echo "</script>
	";
}
?>