<?php

// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once('config.php');
require_once('twitteroauth.php');
include_once("fb/facebook.php");
require_once('rss.php');

// Get DB connection
$con = mysql_connect(DBHOST, DBLOGIN, DBPASSWD);
if (!$con) {
	echo "error while connecting to database: ".mysql_error();
	return '';
}

mysql_select_db(DBNAME, $con);

// The RSS Generator
$rssFeed = new RssFeed('rss.xml', &$con);

$text = '';
$tweetError = 0;

/*
echo "Using CONSUMER_KEY: ".CONSUMER_KEY."<br/>";
echo "Using CONSUMER_SECRET: ".CONSUMER_SECRET."<br/>";
echo "Using ACCESS_TOKEN: ".ACCESS_TOKEN."<br/>";
echo "Using ACCESS_TOKEN_SECRET: ".ACCESS_TOKEN_SECRET."<br/>";
*/

// Connect to Twitter
$connection = new TwitterOAuth(
	CONSUMER_KEY,
	CONSUMER_SECRET, 
	ACCESS_TOKEN, 
	ACCESS_TOKEN_SECRET
);

// Connect to Facebook
$facebook = new Facebook(array(
	'appId'  => FB_APP_ID,
	'secret' => FB_APP_SECRET
));
$facebook->setAccessToken(FB_ACCESS_TOKEN);
$fbuser = $facebook->getUser();

/*
echo addTags('Leistung aus Leidenschaft (Lance Armstrong)', 'deuba armstrong')."<br/>";
echo addTags('Irgendein Text (vom Autor)', 'autor, irgendein')."<br/>";
echo addTags('Irgendein Text (vom Autor)', '')."<br/>";
echo addTags('Irgendein Text (vom Autor)', 'vomautor')."<br/>";
*/

if ($connection->error || !$fbuser) {
	if ($connection->error) echo "error while connecting to Twitter: ".$connection->error."<br/>";
	if (!$fbuser) echo "error while connecting to Facebook <br/>";
	$tweetError = 1;
} else {
	$tweet = getTweetText($con);
	$text = $tweet[0];
	$tags = $tweet[1];
	if ($text) {
		echo "FQOTD: ".$text."<br/>";

		// Twitter it
		$tweettext = addTags($text, $tags);
		$result = $connection->post('statuses/update', array('status' => sanitize($tweettext)));
 		// http://bit.ly/XZYzN7
		if ($result->error) {
			echo "error while tweeting: ".$result->error."<br/>";
			$tweetError = 1;
		}

		if (!$tweetError) {
			echo "Tweeted<br/>";
			// Save the day that we tweeted
			$today = date("Ymd");
			mysql_query("UPDATE qotd_settings SET value='$today' WHERE name='lastTweet'", $con);
			if (!$result) {
				echo "error while saving tweet: ".mysql_error()."<br/>";
			}
		}

		// Post it in Facebook
		if (!$tweetError) {
			$msg_body = array(
				'message' => sanitize($text)
			);

			$post_url = '/'.FB_PAGE_ID.'/feed';
			try {
				$postResult = $facebook->api($post_url, 'post', $msg_body );
				echo "Posted on Facebook<br/>";
			} catch (FacebookApiException $e) {
				echo "error while posting to Facebook: ". $e->getMessage();
				$tweetError = 1;
		        }

			// Save RSS Feed
			$rssFeed->addQuote($GLOBALS['fqotd']);
			$rssFeed->save();
		}

	} else {
		echo "Already posted today<br/>";
	}
}

// Close DB
mysql_close($con);

return;

function getTweetText($con) {
	// What did we last tweet?
	$result = mysql_query("SELECT * FROM qotd_settings WHERE name='lastTweet'", $con);
	if (!$result) {
		echo "error while retrieving last tweet day: ".mysql_error();
		return array('');
	}
	if ($row = mysql_fetch_array($result)) {
		$lastTweet = $row['value'];
	} else {
		$lastTweet = date("Ymd", time() - 86400); // Yesterday
	}

	// What is the current quote?
	$result = mysql_query("SELECT * FROM qotd_settings WHERE name='currentDay'", $con);
	if (!$result) {
		echo "error while retrieving current quote day: ".mysql_error();
		return array('');
	}
	if ($row = mysql_fetch_array($result)) {
		$currentDay = $row['value'];
	} else {
		$currentDay = '';
	}

	// if we did not tweet yet and have a today's quote
	$today = date("Ymd");
	if (($currentDay != $lastTweet) && ($currentDay == $today)) {
		// Get the current quote id
		$result = mysql_query("SELECT * FROM qotd_settings WHERE name='currentId'", $con);
		if (!$result) {
			echo "error while retrieving current quote id: ".mysql_error();
			return array('');
		}
		if ($row = mysql_fetch_array($result)) {
			$currentId = $row['value'];
		}
		// Get the current quote
		$result = mysql_query("SELECT * FROM qotd_quotes WHERE id=$currentId");
		if (!$result) {
			echo "error while retrieving current quote: ".mysql_error();
			return array('');
		}
		if ($row = mysql_fetch_array($result)) {
			$rc= $row['quote'] . ' (' . $row['author'] . ')';
			$tags = $row['hashtags'];
			$GLOBALS['fqotd'] = $row;
		}

	} else {
		$rc = '';
		$tags = '';
	}

	return array($rc, $tags);
}

function addTags($text, $tags) {
	if (!is_array($tags)) {
		$tags = preg_split('/[\s,]+/', $tags);
	}
	$numTags = 0;
	foreach ($tags AS $tag) {
		if (!$tag) continue;
		if (substr($tag, 0, 1) == '#') $tag = substr($tag, 1);
		$pos = stripos($text, $tag);
		if ($pos !== FALSE) {
			if ($pos > 0) $text = substr($text, 0, $pos).'#'.substr($text, $pos);
			else $text = '#'.$text;
		} else {
			$text .= ' #'.$tag;
		}
		$numTags++;
	}
	// No tags? tag the last word (the authors surname)
	if ($numTags == 0) {
		$bracketpos = strrpos($text, '(')+1;
		$pos = strrpos($text, ' ')+1;
		if ($bracketpos > $pos) $pos = $bracketpos;
		$text = substr($text, 0, $pos).'#'.substr($text, $pos);
	}

	return $text.' #fqotd';
}

function sanitize($s) {
	$s = utf8_encode($s);
	return html_entity_decode($s);
}

?>