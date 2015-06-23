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

<div class="menuBtn noselect" name="internetGaps"><t>internet gaps [<div style='color:rgba(0,0,0,0);display:inline-block;'>✓</div>]</t> <select></select></div>

<div id="visualization" style="width:100%"></div>

<script>

	<?php

	date_default_timezone_set("Europe/London");

	$currentDate = "";
	if(isset($_GET["date"]) && preg_match('/^[0-9]+-[0-9]+-[0-9]+$/', $_GET["date"]))
		$currentDate = $_GET["date"];
	else
		$currentDate = date('Y-m-d', time());

	echo "var targetDate = \"" . $currentDate . "\";";
	?>

    var groups = new vis.DataSet();
    <?php
    	$pingTargets = scandir("../pings/");
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

    var customDataset = [];
    var lastInternetGaps = [];
    var internetGapsGroup = 0;
    var lastID = 0;
    var beginIndex = [];

    var graph2d;

    function getGaps(groupID, pingData) {
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
    		if(mergedGaps[i].endX - mergedGaps[i].beginX > 40000) {
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

	function rebuildGapsIfNeeded(targets) {
		var remoteGaps = getGaps(internetGapsGroup, customDataset[internetGapsGroup]);
		// check if the data is any different
		var isDifferent = false;
		if(remoteGaps.length == lastInternetGaps.length) {
			for(var i = 0; i < lastInternetGaps.length; i++) {
				if(remoteGaps[i].endX != lastInternetGaps[i].endX || remoteGaps[i].beginX != lastInternetGaps[i].beginX) {
					isDifferent = true;
					break;
				}
			}
		} else {
			isDifferent = true;
		}
		if(isDifferent) {
			lastInternetGaps = remoteGaps;
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
			dataset.clear();
			for(var i = 0; i < groupCount; i++) {
				dataset.add(customDataset[i]);
			}
			dataset.add(gapData);
		}
	}

	function loadPingData(targets) {
		var loadData = function(targets, group) {
			$.ajax({
				url: "getPings.php?group=" + group + "&begin=" + beginIndex[group] + "&target=" + targets[group] + "&dataId=" + lastID + "&date=" + targetDate,
				async: true,
				success: function(data) {
					var jData = JSON.parse(data);
					if(jData.length == 0) {
						setTimeout(function() { loadPingData(targets); }, 5000);
						return;
					}
					for(var i = 0; i < jData.length; i++) {
						jData[i].x += startPoint;
					}
					customDataset[group] = customDataset[group].concat(jData);
					dataset.add(jData);
					beginIndex[group] += jData.length*2;
					lastID += jData.length;
					if(group < targets.length-1) {
						group++;
						loadData(targets, group);
					} else {
						rebuildGapsIfNeeded(1, targets);
						setTimeout(function() { loadPingData(targets); }, 30000);
					}
				}
			});
		};
		loadData(targets, 0);
	}

    $(document).ready(function() {
    	var targetGroups = [];
    	for(var i = 0; i < groupCount; i++) {
    		targetGroups.push(groups.get(i).content);
    	}

    	var gapsList = $(".menuBtn[name=internetGaps] select");
    	for(var i = 0; i<groupCount; i++) {
    		customDataset.push([]);
    		beginIndex.push(1);
    		gapsList.html(gapsList.html() + "<option value='" + i + "'>" + targetGroups[i] + "</option>");
    	}
    	groups.add({id:9, content:"internet gap", options:{shaded:{orientation:'bottom'}, drawPoints: {enabled:true, size:0}}});

    	graph2d = new vis.Graph2d(container, dataset, groups, visOptions);

    	loadPingData(targetGroups);

    	$(".menuBtn[name=internetGaps] t").click(function() {
    		displayInternetGaps = !displayInternetGaps;
    		visOptions.groups.visibility = {9:displayInternetGaps};
    		graph2d.setOptions(visOptions);

    		if(displayInternetGaps) {
    			$(this).html("internet gaps [✓]");
    		} else {
    			$(this).html("internet gaps [<div style='color:rgba(0,0,0,0);display:inline-block;'>✓</div>]");
    		}
    	});
    	gapsList.change(function() {
    		internetGapsGroup = gapsList.val();
    		rebuildGapsIfNeeded(targetGroups);
    	});
    });

</script>
</body>
</html>