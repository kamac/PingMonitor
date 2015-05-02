<?php
date_default_timezone_set("Europe/London");

$currentDate = "";
if(isset($_GET["date"]) && preg_match('/^[0-9]+-[0-9]+-[0-9]+$/', $_GET["date"]))
	$currentDate = $_GET["date"];
else
	$currentDate = date('Y-m-d', time());

$pingTargets = scandir("../pings/");

?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Network Ping</title>

    <style>
        body, html {
            font-family: arial, sans-serif;
            font-size: 11pt;
        }
        span.label {
            width:150px;
            display:inline-block;
        }
    </style>

    <!-- note: moment.js must be loaded before vis.js, else vis.js uses its embedded version of moment.js -->
    <script src="js/moment.min.js"></script>
    <script src="js/visjs/vis.min.js"></script>
    <link href="js/visjs/vis.min.css" rel="stylesheet" type="text/css" />
    <link href="main.css" rel="stylesheet" type="text/css" />
</head>

<body>
<div id="visualization" style="width:100%"></div>

<script>
    var groups = new vis.DataSet();
    <?php
        $group = 0;
        foreach($pingTargets as $pingTarget) {
            if($pingTarget == "." || $pingTarget == "..")
                continue;
            echo "groups.add({id:" . $group . ",content:\"" . $pingTarget . "\"});";
            $group++;
        }
    ?>
    // create a dataset with items
    var now = moment().minutes(0).seconds(0).milliseconds(0);
    var dataset = new vis.DataSet({
        type: {start: 'ISODate', end: 'ISODate' }
    });

    var startPoint = moment().startOf('day').minutes(0).seconds(0).milliseconds(0);
    var endPoint = moment().endOf('day').minutes(0).seconds(0).milliseconds(0);

    var container = document.getElementById('visualization');
    var options = {
        defaultGroup: 'ungrouped',
        legend: true,
        sampling: true,
        drawPoints: {enabled:false, size:3},
        catmullRom: false,
        height: window.innerHeight + "px",
        start: startPoint,
        end: endPoint
    };

    dataset.add([<?php
        $group = 0;
        $str = "";
        $id = 0;
        foreach($pingTargets as $pingTarget) {
            if($pingTarget == "." || $pingTarget == "..")
                continue;

            $fileName = "../pings/" . $pingTarget . "/" . $currentDate;
            $fileData = unpack("V*", file_get_contents($fileName));
            $dataLength = count($fileData);

        	for($i = 1; $i < $dataLength; $i++) {
    			$time = $fileData[$i++];
    			$ping = $fileData[$i];

    			$str .= "{id:" . $id . ",x:startPoint+" . $time*1000 . ",y:" . $ping . ",group:" . $group . "},";
    			$id++;
    		}

            $group++;
        }

        echo substr($str, 0, strlen($str) - 1);

    ?>]);

    var graph2d = new vis.Graph2d(container, dataset, groups, options);
</script>
</body>
</html>