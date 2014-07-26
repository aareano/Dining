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
 *																											/venue/vote 		CHECK
 *																											/venue/tally 		CHECK
 *																											/venue/tally/user 	CHECK
 *																											/venue/tally/votes 	_____
 *																											
 *																											/recipe/vote 		CHECK
 *																											/recipe/tally 		CHECK
 *																											/recipe/tally/user 	CHECK
 *																											/recipe/tally/votes _____
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
 * params - mac, ipv4, ipv6
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
 * params - venueName, venueClass
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
 * params - recipeName, recipeClass
 */
$app->post('/create/recipe', function() use ($app) {

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
 * Post a new vote for a venue
 * url - /venue/vote
 * method - POST
 * params - mac, venueName, voteName
 */
$app->post('/venue/vote', function() use ($app) {

	// user fields
	$mac = $app->request->post(FIELD_MAC);
	$venueName = $app->request->post(FIELD_NAME);
	$voteName = $app->request->post(VOTE_NAME);
	
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
			$response["message"] = "Successfully posted vote for ". $venueName;
		} else {
			$response["error"] = true;
			$response["message"] = "There was an error, vote for ". $venueName ." not posted"; 
		}
	} else {
		$response["error"] = true;
		$response["message"] = "This mac address has voted too recently.";
	}

	echoResponse(201, $response);
});

/**
 * Tally all votes for a specified venue
 * url - /venue/tally
 * method - POST
 * params - venueName
 */
$app->post('/venue/tally', function() use ($app) {
	
	$venueName = $app->request->post(FIELD_NAME);

	$db = new DbHandler();

	// get array of votes and ids of pos/neg venue votes
	$venueVotes = $db->getVotesByVenue($venueName);
	$posVote = $db->getVenueVoteType(VOTE_POS);
	$negVote = $db->getVenueVoteType(VOTE_NEG);

	$voteCounts = [
		VOTE_POS => 0,
		VOTE_NEG => 0
	];
	if ($venueVotes and count($venueVotes) > 1) {	// best practice, check posVote, negVote, and venueVotes??
		foreach ($venueVotes as $row) {
			if ($row[FIELD_VENUE_VOTE_ID] = $posVote[FIELD_ID])
				$voteCounts[VOTE_POS]++;
			elseif ($row[FIELD_VENUE_VOTE_ID] = $negVote[FIELD_ID])
				$voteCounts[VOTE_NEG]++;
		}
	
		$response["error"] = false;
		$response["message"] = "Successfully retrieved ". $venueName ."'s vote tallies";
		$response[VOTE_POS] = $voteCounts[VOTE_POS];
		$response[VOTE_NEG] = $voteCounts[VOTE_NEG];
	
	} else {
		$response["error"] = true;
		$response["message"] = "There was an error, ". $venueName ."'s vote tallies not retrieved";
	}

	echoResponse(201, $response);
});

/**
 * Tally all votes for by specified user
 * url - /venue/tally/user
 * method - POST
 * params - mac
 */
$app->post('/venue/tally/user', function() use ($app) {
	
	$mac = $app->request->post(FIELD_MAC);

	$db = new DbHandler();

	// get array of votes and ids of pos/neg venue votes
	$userVotes = $db->getVenueVotesByUser($mac);
	$posVote = $db->getVenueVoteType(VOTE_POS);
	$negVote = $db->getVenueVoteType(VOTE_NEG);

	$voteCounts = [
		VOTE_POS => 0,
		VOTE_NEG => 0
	];
	if ($userVotes and count($userVotes) > 1) {	// best practice, check posVote, negVote, and userVotes??
		foreach ($userVotes as $row) {
			if ($row[FIELD_VENUE_VOTE_ID] = $posVote[FIELD_ID])
				$voteCounts[VOTE_POS]++;
			elseif ($row[FIELD_VENUE_VOTE_ID] = $negVote[FIELD_ID])
				$voteCounts[VOTE_NEG]++;
		}
	
		$response["error"] = false;
		$response["message"] = "Successfully retrieved user's vote tallies";
		$response[VOTE_POS] = $voteCounts[VOTE_POS];
		$response[VOTE_NEG] = $voteCounts[VOTE_NEG];
	
	} else {
		$response["error"] = true;
		$response["message"] = "There was an error, user's vote tallies not retrieved";
	}

	echoResponse(201, $response);
});

/* -------------------------- Recipe functions -------------------------- */

/**
 * Post a new vote for a recipe
 * url - /recipe/vote
 * method - POST
 * params - mac, recipeName, voteName
 */
$app->post('/recipe/vote', function() use ($app) {

	// user fields
	$mac = $app->request->post(FIELD_MAC);
	$recipeName = $app->request->post(FIELD_NAME);
	$voteName = $app->request->post(VOTE_NAME);

	$db = new DbHandler();

		$success = $db->createRecipeVote($mac, $recipeName, $voteName);

	if ($success) {
		$response["error"] = false;
		$response["message"] = "Successfully posted vote for ". $recipeName;
	} else {
		$response["error"] = true;
		$response["message"] = "There was an error, vote for ". $recipeName ." not posted"; 
	}

	echoResponse(201, $response);
});

/**
 * Tally all votes for a specified recipe
 * url - /recipe/tally
 * method - POST
 * params - recipeName
 */
$app->post('/recipe/tally', function() use ($app) {
	
	$recipeName = $app->request->post(FIELD_NAME);

	$db = new DbHandler();

	// get array of votes and ids of pos/neg venue votes
	$recipeVotes = $db->getVotesByRecipe($recipeName);
	$posVote = $db->getRecipeVoteType(VOTE_POS);
	$negVote = $db->getRecipeVoteType(VOTE_NEG);

	$voteCounts = [
		VOTE_POS => 0,
		VOTE_NEG => 0
	];
	if ($recipeVotes and count($recipeVotes) > 1) {	// best practice, check posVote, negVote, and recipeVotes??
		foreach ($recipeVotes as $row) {
			if ($row[FIELD_VENUE_VOTE_ID] = $posVote[FIELD_ID])
				$voteCounts[VOTE_POS]++;
			elseif ($row[FIELD_VENUE_VOTE_ID] = $negVote[FIELD_ID])
				$voteCounts[VOTE_NEG]++;
		}
	
		$response["error"] = false;
		$response["message"] = "Successfully retrieved vote tallies for ". $recipeName;
		$response[VOTE_POS] = $voteCounts[VOTE_POS];
		$response[VOTE_NEG] = $voteCounts[VOTE_NEG];
	
	} else {
		$response["error"] = true;
		$response["message"] = "There was an error, vote tallies for ". $recipeName ." not retrieved";
	}

	echoResponse(201, $response);
});

/**
 * Tally all votes by a specified user
 * url - /recipe/tally/user
 * method - POST
 * params - mac
 */
$app->post('/recipe/tally/user', function() use ($app) {
	
	$mac = $app->request->post(FIELD_MAC);

	$db = new DbHandler();

	// get array of votes and ids of pos/neg venue votes
	$userVotes = $db->getRecipeVotesByUser($mac);
	$posVote = $db->getRecipeVoteType(VOTE_POS);
	$negVote = $db->getRecipeVoteType(VOTE_NEG);

	$voteCounts = [
		VOTE_POS => 0,
		VOTE_NEG => 0
	];
	if ($userVotes and count($userVotes) > 1) {	// best practice, check posVote, negVote, and userVotes??
		foreach ($userVotes as $row) {
			if ($row[FIELD_RECIPE_VOTE_ID] = $posVote[FIELD_ID])
				$voteCounts[VOTE_POS]++;
			elseif ($row[FIELD_RECIPE_VOTE_ID] = $negVote[FIELD_ID])
				$voteCounts[VOTE_NEG]++;
		}
	
		$response["error"] = false;
		$response["message"] = "Successfully retrieved user's recipe vote tallies";
		$response[VOTE_POS] = $voteCounts[VOTE_POS];
		$response[VOTE_NEG] = $voteCounts[VOTE_NEG];
	
	} else {
		$response["error"] = true;
		$response["message"] = "There was an error, user's recipe vote tallies not retrieved";
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