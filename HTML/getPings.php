<?php

if(isset($_GET["group"]) && isset($_GET["begin"]) && isset($_GET["target"]) && isset($_GET["dataId"]) && isset($_GET["date"])) {
	$group = intval($_GET["group"]);
	$str = "";
	$dataId = intval($_GET["dataId"]);

	$fileName = "../pings/" . $_GET["target"] . "/" . $_GET["date"];
	$fileData = unpack("V*", file_get_contents($fileName));
	$dataLength = count($fileData);
	for($i = intval($_GET["begin"]); $i < $dataLength; $i++) {
		$time = $fileData[$i++];
		$ping = $fileData[$i];

		$str .= "{\"id\":" . $dataId . ",\"x\":" . ($time*1000) . ",\"y\":" . $ping . ",\"group\":" . $group . "},";
		$dataId++;
	}
	$lastDataLength = $dataLength;

	echo "[" . substr($str, 0, strlen($str)-1) . "]";
}

?>