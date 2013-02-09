<?php 

require_once("config.php");

$app_id = FB_APP_ID;
$app_secret = FB_APP_SECRET;
$my_url = "http://qotd.ralph-schuster.eu/fb_auth_request.php";

echo "appid=$app_id appsecret=$app_secret";
session_start();

$code = $_REQUEST["code"];

if (empty($code)) {
	// Redirect to Facebook Login Dialog
	$_SESSION['state'] = md5(uniqid(rand(), TRUE)); // CSRF protection
	$dialog_url = "https://www.facebook.com/dialog/oauth?client_id=".
		$app_id . "&redirect_uri=" . urlencode($my_url) . "&state=".
		$_SESSION['state']. "&scope=manage_pages,publish_stream";

	echo("<script> top.location.href='" . $dialog_url . "'</script>");
}

if ($_SESSION['state'] && ($_SESSION['state'] === $_REQUEST['state'])) {
	// state variable matches
	$token_url = "https://graph.facebook.com/oauth/access_token?".
		"client_id=" . $app_id . "&redirect_uri=" . urlencode($my_url).
		"&client_secret=" . $app_secret . "&code=" . $code;

	$response = file_get_contents($token_url);
	$params = null;
	parse_str($response, $params);
	echo "<br/>access_token=".$params['access_token']."<br/>";

	$graph_url = "https://graph.facebook.com/me?access_token=" . $params['access_token'];

	$user = json_decode(file_get_contents($graph_url));
	echo("Hello " . $user->name);
} else { 
	echo("The state does not match. You may be a victim of CSRF.");
}
?>
