<?php

if (!$_GET)
	die('nada');

if (isset($_GET["error"])) {
	echo("<pre>OAuth Error: " . $_GET["error"]."\n");
	echo('<a href="index.php">Retry</a></pre>');
	die;
}

require('./lib/class.redditconnector.php');
require('./lib/class.slackconnector.php');
require('./lib/class.spotifyconnector.php');

$rc = new RedditConnector();

if (isset($_GET["start"]))
	$ret = $rc->getTokenAuth();
elseif (isset($_GET["code"]))
	$ret = $rc->getAccessToken();

echo $ret;