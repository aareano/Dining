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

/* -------------------------- Rating functions -------------------------- */

$app->get('/rate', function() use ($app) {
	echo "you are beautiful. but you sent an HttpGet instead of an HttpPost.";
});

/**
 * Posting a new rating in db
 * url - /rate
 * method - POST
 * params - mac, good, bad
 */
$app->post('/rate', function() use ($app) {

	// user fields
	$mac = $app->request->post('mac_addr');
	$ipv4 = $app->request->post('ipv4');
	$ipv6 = $app->request->post('ipv6');
	// venue fields
	$venue_name = $app->request->post('venue_name');
	// vote fields
	$vote_name = $app->request->post('vote_name');
	
	// remove colons from mac address
	$mac = str_replace(":", "", $mac);
	$user = [
		"mac_addr" => $mac,
		"ipv4" => $ipv4;
		"ipv6" => $ipv6;
	]

	// set time zone and get time 10 seconds ago
	// earliest time for last vote
	date_default_timezone_set('UTC');
	$latestTime = date('Y-m-d H:i:s', strtotime('-10 seconds'));

	$db = new DbHandler();
	$response = array();
	$table = TBL_RATING;

	if (!$db->isRecent($user, $latestTime, $table)) {
		// this ip address hasn't voted in a while

		$success = $db->postVenueVote($user, $vote_name, $venue_name);								// TODO, also, got this far

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