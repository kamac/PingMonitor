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

    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
    <!-- note: moment.js must be loaded before vis.js, else vis.js uses its embedded version of moment.js -->
    <script src="js/moment.min.js"></script>
    <script src="js/visjs/vis.min.js"></script>
    <script src="js/jquery-2.1.4.min.js"></script>
    <link href="js/visjs/vis.min.css" rel="stylesheet" type="text/css" />
    <link href="main.css" rel="stylesheet" type="text/css" />
</head>

<body>

<div class="menuBtn noselect" name="internetGaps">toggle internet gaps</div>

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
        echo "var groupCount = " . $group . ";";
    ?>
    // create a dataset with items
    var now = moment().minutes(0).seconds(0).milliseconds(0);
    var dataset = new vis.DataSet({
        type: {start: 'ISODate', end: 'ISODate' }
    });

    var startPoint = moment().startOf('day').minutes(0).seconds(0).milliseconds(0);
    var endPoint = moment().endOf('day').minutes(0).seconds(0).milliseconds(0);

    var container = document.getElementById('visualization');
    var displayInternetGaps = false;
    var visOptions = {
        defaultGroup: 'ungrouped',
        legend: true,
        sampling: true,
        drawPoints: {enabled:false, size:0},
        catmullRom: false,
        height: window.innerHeight + "px",
        start: startPoint,
        end: endPoint,
        groups: {
        	visibility: {
        		9: displayInternetGaps
        	}
        }
    };

    pingData = [<?php
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

    ?>];
    var lastID = <?php echo $id; ?>;

    dataset.add(pingData);

    var graph2d;

    function getGaps(groupID) {
    	// look for the lack of internet accessibility fields
    	var gaps = [];
    	var currGap = null;
    	for(var i=0; i < pingData.length; i++) {
    		if(i != pingData.length-1 && pingData[i].group == groupID && (pingData[i].y > 300 || pingData[i].y == 0)) {
    			if(currGap == null) {
    				currGap = {beginX:pingData[i].x, beginID:pingData[i].id}
    			}
    		} else if(currGap != null) {
    			currGap.endX = pingData[i].x;
    			gaps.push(currGap);
    			currGap = null;
    		}
    	}
    	// merge close gaps
    	var mergedGaps = [];
    	for(var i=0; i < gaps.length; i++) {
    		var currentGap = gaps[i];
    		if(i < (gaps.length-1))
    		{
	    		var nextGap = gaps[i+1];
	    		do
	    		{
		    		if(nextGap.beginX - currentGap.endX < 20000) {
		    			currentGap.endX = nextGap.endX;
		    			i++;
		    			if(i < gaps.length-1) {
		    				nextGap = gaps[i+1];
		    			} else {
		    				break;
		    			}
		    		} else {
		    			break;
		    		}
		    	} while(true);
		   	}
	    	mergedGaps.push(currentGap);
    	}
    	// get rid of too small gaps
    	gaps = [];
    	for(var i=0; i < mergedGaps.length; i++) {
    		if(mergedGaps[i].endX - mergedGaps[i].beginX > 20000) {
    			gaps.push(mergedGaps[i]);
    		}
    	}
    	return gaps;
    }

    function msToTime(s) {
		var ms = s % 1000;
		s = (s - ms) / 1000;
		var secs = s % 60;
		s = (s - secs) / 60;
		var mins = s % 60;
		var hrs = (s - mins) / 60;

		if(secs >= 30) {
			mins++;
		}
		var out = "";
		if(hrs != 0) {
			out = out + hrs + "h ";
		}
		if(mins != 0) {
			out = out + mins + "m ";
		}
		return "~" + out;
	}

    $(document).ready(function() {
    	groups.add({id:9, content:"internet gap", options:{shaded:{orientation:'bottom'}, drawPoints: {enabled:true, size:0}}});
    	var remoteGaps = getGaps(1);
    	var gapData = [];
    	gapData.push({id:lastID++,x:startPoint,y:-1,group:9});
    	for(var i = 0; i < remoteGaps.length; i++) {
    		var p = msToTime(remoteGaps[i].endX - remoteGaps[i].beginX);
    		var pingL = {
		    	content:p, xOffset:1, yOffset:20, className: "pLabel"
		    }
    		gapData.push({id:lastID++,x:remoteGaps[i].beginX-1,y:-1,group:9});
    		gapData.push({id:lastID++,x:remoteGaps[i].beginX,y:999,group:9,label:pingL});
    		gapData.push({id:lastID++,x:remoteGaps[i].endX,y:999,group:9});
    		gapData.push({id:lastID++,x:remoteGaps[i].endX+1,y:-1,group:9});
    	}
    	gapData.push({id:lastID++,x:endPoint,y:-1,group:9});
    	dataset.add(gapData);

    	graph2d = new vis.Graph2d(container, dataset, groups, visOptions);


    	$(".menuBtn[name=internetGaps]").click(function() {
    		displayInternetGaps = !displayInternetGaps;
    		visOptions.groups.visibility = {9:displayInternetGaps};
    		graph2d.setOptions(visOptions);
    	});
    });

</script>
</body>
</html>