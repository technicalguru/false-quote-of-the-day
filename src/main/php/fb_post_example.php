<?php

include_once("fb/facebook.php");
include_once("config.php");

//Call Facebook API
$facebook = new Facebook(array(
  'appId'  => FB_APP_ID,
  'secret' => FB_APP_SECRET
));
$facebook->setAccessToken(FB_ACCESS_TOKEN);

$fbuser = $facebook->getUser();

if ($fbuser) {
	/*
	try {
		//Get user pages details using Facebook Query Language (FQL)
		$fql_query = 'SELECT page_id, name, page_url FROM page WHERE page_id IN (SELECT page_id FROM page_admin WHERE uid='.$fbuser.')';
		$postResults = $facebook->api(array( 'method' => 'fql.query', 'query' => $fql_query ));
		foreach ($postResults as $postResult) {
			echo 'id="'.$postResult["page_id"].'" name="'.$postResult["name"].'"<br/>';
			$userPageId = $postResult["page_id"]; break;
		}
	} catch (FacebookApiException $e) {
		echo $e->getMessage();
	}
	*/

	$msg_body = array(
		'message' => 'second message for my wall'
	);

	$post_url = '/'.FB_PAGE_ID.'/feed';
	try {
		$postResult = $facebook->api($post_url, 'post', $msg_body );
		echo "Posted";
	} catch (FacebookApiException $e) {
		echo $e->getMessage();
	}
}

?>

