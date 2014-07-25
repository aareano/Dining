<?php

/**
 * Functions to handle all HTTP requests 															// TODO
 *																										handle all URL requests on list
 *
 * @author: Aaron Bowen
 * Date: 6/24/14
 */

/*
 *																											URLs
 *																											
 *																											/create/user 		CHECK
 *																											/create/venue 		CHECK
 *																											/create/recipe 		CHECK
 *																											
 *																											/venue/vote
 *																											/venue/tally
 *																											/venue/tally/user
 *																											/venue/tally/votes
 *																											
 *																											/recipe/vote
 *																											/recipe/tally
 *																											/recipe/tally/user
 *																											/recipe/tally/votes
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

/* -------------------------- Create functions -------------------------- */

/**
 * Create a new user
 * url - /create/user
 * method - POST
 * params - mac, ipv4, ipv6s
 */
$app->post('/create/user', function() use ($app) {

	// get user fields
	$mac = $app->request->post(FIELD_MAC);
	$ipv4 = $app->request->post(FIELD_IPV4);
	$ipv6 = $app->request->post(FIELD_IPV6);

	$db = new DbHandler();

	$success = $db->createUser($mac, $ipv4, $ipv6);

	if ($success) {
		$response["error"] = false;
		$response["message"] = "Successfully created user";
	} else {
		$response["error"] = true;
		$response["message"] = "There was an error, user was not created";
	}

	echoResponse(201, $response);
});

/**
 * Create a new venue
 * url - /create/venue
 * method - POST
 * params - venue_name, venue_class
 */
$app->post('/create/venue', function() use ($app) {

	// get venue fields
	$venueName = $app->request->post(FIELD_NAME);
	$venueClass = $app->request->post(FIELD_CLASS);

	$db = new DbHandler();

	$success = $db->createVenue($venueName, $venueClass);

	if ($success) {
		$response["error"] = false;
		$response["message"] = "Successfully created venue";
	} else {
		$response["error"] = true;
		$response["message"] = "There was an error, venue was not created";
	}

	echoResponse(201, $response);
});

/**
 * Create a new recipe
 * url - /create/recipe
 * method - POST
 * params - recipe_name, recipe_class
 */
$app->post('/create/venue', function() use ($app) {

	// get recipe fields
	$recipeName = $app->request->post(FIELD_NAME);
	$recipeClass = $app->request->post(FIELD_CLASS);

	$db = new DbHandler();

	$success = $db->createVenue($recipeName, $recipeClass);

	if ($success) {
		$response["error"] = false;
		$response["message"] = "Successfully created recipe";
	} else {
		$response["error"] = true;
		$response["message"] = "There was an error, recipe was not created";
	}

	echoResponse(201, $response);
});

/* -------------------------- Venue functions -------------------------- */

/**
 * Posting a new rating in db
 * url - /venue/vote
 * method - POST
 * params - mac, venueName, voteName
 */
$app->post('/venue/vote', function() use ($app) {

	// user fields
	$mac = $app->request->post(FIELD_MAC);
	$venueName = $app->request->post(FIELD_NAME);
	$voteName = $app->request->post(VENUE_VOTE_NAME);
	
	$db = new DbHandler();

	// set time zone and get time 10 seconds ago
	// earliest time for last vote
	date_default_timezone_set('UTC');
	$latestTime = date('Y-m-d H:i:s', strtotime('-10 seconds'));

	$isRecent = $db->isRecentVenueVote($mac, $latestTime);
	
	if (!$isRecent) {
		$success = $db->createVenueVote($mac, $venueName, $voteName);

		if ($success) {
			$response["error"] = false;
			$response["message"] = "Successfully created recipe";
		} else {
			$response["error"] = true;
			$response["message"] = "There was an error, recipe was not created";
		}
	} else {
		$response["error"] = true;
		$response["message"] = "This mac address has voted too recently.";
	}

	echoResponse(201, $response);
}

/**
 * Tally all votes for a specified venue
 * url - /venue/tally
 * method - GET
 * params - venueName
 */
$app->post('/venue/tally', function() use ($app) {
	// TODO...
}

/* -------------------------- Recipe functions -------------------------- */





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
	$mac = $app->request->post('FIELD_MAC');
	$ipv4 = $app->request->post('ipv4');
	$ipv6 = $app->request->post('ipv6');
	// venue fields
	$venue_name = $app->request->post('venue_name');
	// vote fields
	$vote_name = $app->request->post('vote_name');
	
	$user = [
		"mac" => $mac,
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