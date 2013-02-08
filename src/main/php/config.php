<?php

/**
 * @file
 * A single location to store configuration.
 */
$lines = file('qotd.ini');
$CONFIG = array();

// Durchgehen des Arrays und Anzeigen des HTML-Quelltexts inkl. Zeilennummern
foreach ($lines as $line) {
    echo "Line : " . htmlspecialchars($line) . "<br>\n";
    $arr = preg_split("/[\s,]+/", $line);
    $key = $arr[0];
    $value = $arr[1];
    $CONFIG[$key] = $value;
    echo "     " . $key . " == \&gt; " . $value . "<br>\n";
}

define('CONSUMER_KEY', $CONFIG['CONSUMER_KEY']);
define('CONSUMER_SECRET', $CONFIG['CONSUMER_SECRET']);
define('ACCESS_TOKEN', $CONFIG['ACCESS_TOKEN']);
define('ACCESS_TOKEN_SECRET', $CONFIG['ACCESS_TOKEN_SECRET']);
define('OAUTH_CALLBACK', 'http://qotd.ralph-schuster.eu/callback.php');
