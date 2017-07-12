<?php

if (!$_GET)
	die('nada');

if (isset($_GET["error"])) {
	echo("<pre>OAuth Error: " . $_GET["error"]."\n");
	echo('<a href="index.php">Retry</a></pre>');
	die;
}

require('./lib/class.redditconnector.php');


$rc = new RedditConnector();

if (isset($_GET["start"]))
	$rc->getTokenAuth();
elseif (isset($_GET["code"]))
	$rc->getAccessToken();