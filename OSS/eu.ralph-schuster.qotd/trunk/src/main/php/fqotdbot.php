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
$facebook->setFileUploadSupport(true);

echo "<pre>";

// Get the Access Token for the FB Page
$accounts = $facebook->api('/me/accounts', 'GET', array('access_token' => FB_ACCESS_TOKEN));
$data = $accounts['data'];
foreach($data as $account) {
	if( $account['id'] == 464015660326313 ) {
		define(FB_PAGE_ACCESS_TOKEN, $account['access_token']);
	}
}
//$facebook->setAccessToken(FB_PAGE_ACCESS_TOKEN);


/*
echo addTags('Leistung aus Leidenschaft (Lance Armstrong)', 'deuba armstrong')."<br/>";
echo addTags('Irgendein Text (vom Autor)', 'autor, irgendein')."<br/>";
echo addTags('Irgendein Text (vom Autor)', '')."<br/>";
echo addTags('Irgendein Text (vom Autor)', 'vomautor')."<br/>";
*/

if ($connection->error || !$fbuser) {
	if ($connection->error) echo "error while connecting to Twitter: ".$connection->error."<br/>";
	if (!$fbuser) {
		echo "error while connecting to Facebook <br/>";
		// provoke an exception to get more information
		try {
			$user_profile = $facebook->api('/me','GET');
			echo "Name: " . $user_profile['name'];
		} catch(FacebookApiException $e) {
			// If the user is logged out, you can have a 
			// user ID even though the access token is invalid.
			// In this case, we'll get an exception, so we'll
			// just ask the user to login again here.
			echo "error-type: ".$e->getType()."<br/>\n";
			echo "error-message: ".$e->getMessage()."<br/>\n";
			//echo "Application ID: ".FB_APP_ID."<br/>\n";
			// echo "Application Secret: ".FB_APP_SECRET."<br/>\n";
			// echo "Client Token: ".FB_CLIENT_TOKEN."<br/>\n";
			//echo "Access Token: ".FB_ACCESS_TOKEN."<br/>\n";

		}   
	}
	$tweetError = 1;
} else {
	$quote = getTweet($con);
	print_r($quote);
	echo "\n\n";

	if (!$quote['tweeted']) {
		// Twitter it
		echo "Not twittered yet...\n";
		$tweettext = addTags("$quote[quote] ($quote[author])", $quote['hashtags']);
		echo "   Twittering: $tweettext\n";
		$result = $connection->post('statuses/update', array('status' => sanitize($tweettext)));
 		// http://bit.ly/XZYzN7
		if ($result->error) {
			echo "   error while tweeting: ".$result->error."\n";
			$tweetError = 1;
		}

		if (!$tweetError) {
			echo "   Tweeted\n";
			// Save the day that we tweeted
			$today = date("Ymd");
			$result = mysql_query("UPDATE qotd_settings SET value='$today' WHERE name='lastTweet'", $con);
			if (!$result) {
				echo "   error while saving tweet: ".mysql_error()."\n";
			}
		}
	} else {
		echo "Already tweeted today\n";
	}

	if (!$quote['facebookPosted']) {
		// Post it in Facebook
		echo "Not posted on FB yet...\n";
		$fbText = "$quote[quote] ($quote[author])";
		$file = "quote.gif";

		// Creating the image
		shell_exec("./textimage.pl \"$quote[quote]\" \"$quote[author]\"");
		echo "   <img src=\"quote.gif\"/>\n";

		$msg_body = array(
			'access_token' => FB_PAGE_ACCESS_TOKEN,
			'no_story' => 0,
			'message' => "Zitat des Tages (".date('d.m.Y').")",
			'source' => '@'.realpath($file)
		);
		$post_url = '/'.FB_PAGE_ID.'/photos';
		try {
			echo "   Posting image for: $fbText\n";
			$postResult = $facebook->api($post_url, 'post', $msg_body );
			$photoId = $postResult['id'];
			echo "   Posted with ID $photoId\n";

		} catch (FacebookApiException $e) {
			echo "   error while posting to Facebook: ". $e->getMessage()."\n";
			$fbError = 1;
		}

		/*
		$msg_body = array(
			'message' => sanitize($fbText)
		);
		$post_url = '/'.FB_PAGE_ID.'/feed';
		try {
			echo "   Posting: $fbText\n";
			$postResult = $facebook->api($post_url, 'post', $msg_body );
			echo "   Posted on Facebook\n";
		} catch (FacebookApiException $e) {
			echo "   error while posting to Facebook: ". $e->getMessage()."\n";
			$fbError = 1;
	        }
		*/

		if (!$fbError) {
			// Save the day that we posted
			$today = date("Ymd");
			$result = mysql_query("UPDATE qotd_settings SET value='$today' WHERE name='lastFBPost'", $con);
			if (!$result) {
				echo "   error while saving post: ".mysql_error()."\n";
			}
		}
	} else {
		echo "Already posted on FB today<br/>";
	}

	// Save RSS Feed
	$rssFeed->addQuote($GLOBALS['fqotd']);
	$rssFeed->save();
}

// Close DB
mysql_close($con);

echo "</pre>";
return;

function getTweet($con) {
	// Load all settings
	$result = mysql_query("SELECT * FROM qotd_settings", $con);
	if (!$result) {
		echo "error while retrieving settings: ".mysql_error();
		return array();
	}
	while ($row = mysql_fetch_assoc($result)) {
		$settings[$row['name']] = $row['value'];
	}

	// What is the current quote?
	$result = mysql_query("SELECT * FROM qotd_quotes WHERE id=$settings[currentId]", $con);
	if (!$result) {
		echo "error while retrieving current quote: ".mysql_error();
		return array();
	}
	$rc = mysql_fetch_assoc($result);

	// Was it tweeted?
	$result = mysql_query("SELECT * FROM qotd_settings WHERE name='lastTweet'", $con);
	if (!$result) {
		echo "error while retrieving last tweet day: ".mysql_error();
		return array('');
	}
	if ($row = mysql_fetch_assoc($result)) {
		$lastTweet = $row['value'];
	} else {
		$lastTweet = date("Ymd", time() - 86400); // Yesterday
	}
	$rc['tweeted'] = $lastTweet == date("Ymd", time());

	// Was it posted on Facebook?
	$result = mysql_query("SELECT * FROM qotd_settings WHERE name='lastFBPost'", $con);
	if (!$result) {
		echo "error while retrieving last FB post day: ".mysql_error();
		return array('');
	}
	if ($row = mysql_fetch_assoc($result)) {
		$lastFBPost = $row['value'];
	} else {
		$lastFBPost = date("Ymd", time() - 86400); // Yesterday
	}
	$rc['facebookPosted'] = $lastFBPost == date("Ymd", time());

	$rc['quote'] = utf8_encode($rc['quote']);
	$rc['author'] = utf8_encode($rc['author']);
	return $rc;
}

function addTags($text, $tags) {
	if (!is_array($tags)) {
		$tags = preg_split('/[\s,]+/', $tags);
	}
	$numTags = 0;
	foreach ($tags AS $tag) {
		if (!$tag) continue;
		$originalText = $text;
		if (substr($tag, 0, 1) == '#') $tag = substr($tag, 1);
		$pos = stripos($text, $tag);
		if ($pos !== FALSE) {
			if ($pos > 0) $text = substr($text, 0, $pos).'#'.substr($text, $pos);
			else $text = '#'.$text;
		} else {
			$text .= ' #'.$tag;
		}
		if (strlen($text) <= 140) {
			$numTags++;
		} else {
			$text = $originalText;
		}
	}
	// No tags? tag the last word (the authors surname)
	if (($numTags == 0) && (strlen($text) < 140)) {
		$bracketpos = strrpos($text, '(')+1;
		$pos = strrpos($text, ' ')+1;
		if ($bracketpos > $pos) $pos = $bracketpos;
		$text = substr($text, 0, $pos).'#'.substr($text, $pos);
	}

	// fqotd tag
	if (strlen($text) < 133) $text .= " #fqotd";
	return $text;
}

function sanitize($s) {
	$s = utf8_encode($s);
	return html_entity_decode($s);
}

?>
