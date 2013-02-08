<?php

/**
 * @file
 * A single location to store configuration.
 */
$lines = file('qotd.ini');

// Durchgehen des Arrays und Anzeigen des HTML-Quelltexts inkl. Zeilennummern
foreach ($lines as $line) {
    	$arr = preg_split("/[\s,]+/", $line);
	$key = $arr[0];
	$value = $arr[1];
	define($key, $value);
}

define('OAUTH_CALLBACK', 'http://qotd.ralph-schuster.eu/callback.php');
