<?php

/**
 * Function to handle all HTTP requests
 *
 * @author: Aaron Bowen
 * Date: 6/24/14
 */

require_once './include/DbHandler.php';
require_once './include/DbConnect.php';
require './libs/Slim/Slim.php';

\Slim\Slim::registerAutoLoader();

$app = new \SLim\Slim();

/* -------------------------- Welcome -------------------------- */

/**
 * Getting votes from db
 * method GET
 * params - none
 * url - /tally
 */
$app->get('/', function() use ($app) {
	
	echo "Welcome to my abode.";
});

/* -------------------------- Comparison functions -------------------------- */

$app->get('/vote', function() use ($app) {
	echo "you are beautiful. but you sent an httpget instead of an httppost.";
});

/**
 * Posting a new vote in db
 * url - /vote
 * method - POST
 * params - mac, dewick, carm
 */
$app->post('/vote', function() use ($app) {

	$mac = $app->request->post('mac_address');
	$dewick = $app->request->post('dewick');
	$carm = $app->request->post('carm');
	$response = array();

	// set time zone and get time 3 hours ago
	// earliest time for last vote
	date_default_timezone_set('UTC');
	$timestamp = date('Y-m-d H:i:s', strtotime('-10 seconds'));


	$db = new DbHandler();


	// remove colons from mac address
	$mac = str_replace(":", "", $mac);

	$table = TBL_COMPARISON;

	if (!$db->isRecent($mac, $timestamp, $table)) {
		// this ip address hasn't voted in a while

		$success = $db->postVote($mac, $dewick, $carm);

			if ($success != NULL) {
				$response["error"] = false;
				$response["message"] = "Successfully posted vote";
			} else{
				$response["error"] = true;
				$response["message"] = "There was an error; vote was not posted.";
			}
	} else {
		$response["error"] = true;
		$response["message"] = "This address has voted too recently.";
	}

	echoResponse(201, $response);
});


/**
 * Getting votes from db
 * method GET
 * params - none
 * url - /tally
 */
$app->get('/tally_votes', function() use ($app) {

	$response = array();

	$db = new DbHandler();

	// set time zone and get time 3 months ago -- essentially everything
	date_default_timezone_set('UTC');
	$timestamp = date('Y-m-d H:i:s', strtotime('-3 months'));
	
	$total_array = $db->totalVotes($timestamp);


	if ($total_array != NULL) {
		$response["error"] = false;
		$response["message"] = "Successfully retrieved tallies";
		$response["dewick"] = $total_array['dewick'];
		$response["carm"] = $total_array['carm'];
	} else {
		$response["error"] = true;
		$response["message"] = "There was an error, sorry about that.";
	}

	echoResponse(201, $response);
});

/* -------------------------- Rating functions -------------------------- */

$app->get('/rate', function() use ($app) {
	echo "you are beautiful. but you sent an httpget instead of an httppost.";
});

/**
 * Posting a new rating in db
 * url - /rate
 * method - POST
 * params - mac, good, bad
 */
$app->post('/rate', function() use ($app) {

	$mac = $app->request->post('mac_address');
	$good = $app->request->post('good');
	$bad = $app->request->post('bad');
	$response = array();

	// set time zone and get time 3 hours ago
	// earliest time for last vote
	date_default_timezone_set('UTC');
	$timestamp = date('Y-m-d H:i:s', strtotime('-10 seconds'));


	$db = new DbHandler();


	// remove colons from mac address
	$mac = str_replace(":", "", $mac);

	$table = TBL_RATING;


	if (!$db->isRecent($mac, $timestamp, $table)) {
		// this ip address hasn't voted in a while

		$success = $db->postRating($mac, $good, $bad);

			if ($success != NULL) {
				$response["error"] = false;
				$response["message"] = "Successfully posted rating";
			} else{
				$response["error"] = true;
				$response["message"] = "There was an error; rating was not posted.";
			}
	} else {
		$response["error"] = true;
		$response["message"] = "This address has posted a rating too recently.";
	}

	echoResponse(201, $response);
});

/**
 * Getting votes from db
 * method GET
 * params - none
 * url - /tally
 */
$app->get('/tally_ratings', function() use ($app) {

	$response = array();

	$db = new DbHandler();

	// set time zone and get time 3 months ago -- essentially everything
	date_default_timezone_set('UTC');
	$timestamp = date('Y-m-d H:i:s', strtotime('-3 months'));
	
	$total_array = $db->totalRatings($timestamp);

	if ($total_array != NULL) {
		$response["error"] = false;
		$response["message"] = "Successfully retrieved tallies";
		$response["good"] = $total_array['good'];
		$response["bad"] = $total_array['bad'];
	} else {
		$response["error"] = true;
		$response["message"] = "There was an error, sorry about that.";
	}

	echoResponse(201, $response);
});

/**
* Echoing json response to client
* @param String $status_code HTTP response code
* @param Int $response json response
*/
function echoResponse($status_code, $response) {

	$app = \Slim\Slim::getInstance();

	// Http response code
	$app->status($status_code);

	// setting response content type to json
	$app->contentType('application/json');

	echo json_encode($response);
}

$app->run();

?>