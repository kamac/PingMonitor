Ping Monitor
==============

Simple tool for testing your ping against desired targets (be it a website or your router floor below)

Ping Monitor uses vis.js library for ping visualization.

[client](http://i.imgur.com/w8EMBIO.png)

Configuration
--------------

- Modify pingmonitor.py's:
	- **pingTargets** those will be the hosts that will be pinged
	- **pingInterval** to set how often to ping targets, in seconds
	- **fileUpdateInterval** to set how often to write ping data from memory to file, in seconds

How to use
--------------

If you are on windows, right-click run.bat and select "*run as administrator*" (it's required because the python script that this .bat will run is using raw sockets). This will begin pinging your selected hosts at the interval you provided, and saving that data every once in a while to pings/ folder.

To view the data, you might use the provided PHP script, which you can find in the HTML/ folder. So, if you are running nginx, for example, then drop the whole folder containing Ping Monitor to your root folder, and then in your browser type: localhost:8080/pingmonitor/HTML/index.php

You can select which day to display by providing an additional *date* parameter, like so: localhost:8080/pingmonitor/HTML/index.php?date=2015-01-30

License
--------------

Ping Monitor is licensed under the MIT license. For more info, see LICENSE.md. (Or this cool site: https://tldrlegal.com/license/mit-license)