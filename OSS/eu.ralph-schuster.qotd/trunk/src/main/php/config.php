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

define('OAUTH_CALLBACK', 'http://qotd.ralph-schuster.eu/callback.php');
