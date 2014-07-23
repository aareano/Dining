<?php

/**
* Class to handle all db operations
* This class will have CRUD methods for database tables
*
* @author: Aaron Bowen
* Date: 7/10/14
*/

Class DbHandler {

	private $conn;

	function __construct() {
		require_once dirname(__FILE__) . '/./DbConnect.php';

		// Opening db connection
		$db = new DbConnect();
		$this->conn = $db->connect();
	}

	/**
	 * Determines if an entry will be allowed for this user
	 * @param $mac MAC address of the user
	 * @param $timestamp latest time for this ip to have voted last
	 * @return whther or not $mac has made an entry since $timestamp
	 */
	function isRecent($mac, $timestamp, $table) {		// TODO, only uses MAC address right now

		$query = "SELECT votes.* FROM user_venue_votes votes, users WHERE HEX(users.mac_addr) == ? "
				." AND users.id == votes.user_id AND votes.time_added >= ?"
		$stmt->bind_param("ss", $mac, $timestamp);

		// if binding was successful...
		if ($stmt != false) {
			$stmt->execute();
			// $stmt->store_result();								// TODO, do I need this?
        	$num_rows = $stmt->num_rows;
        	$stmt->close();
        	return $num_rows > 0;
        
        } else 	// something is wrong, so don't allow anything to be posted.
        	return true;
	}



	/**
	 * @param
	 * @return
	 */
	function () {}



/* -------------------------- `users` table methods -------------------------- */
	
	/* ------ Crud ------ */
	/**
	 * Creates a user. Users have an id, MAC, ipv4, ipv6, and a date_added
	 * @param $user holds {MAC, ipv4, ipv6}
	 * @return true if successful, false otherwise
	 */
	function createUser($user) {

	}

	function createUser($mac, ipv4) {
		// TODO create array with null values, call createUser($user)
	}

	function createUser($mac) {
		// TODO create array with null values, call createUser($user)
	}

	/* ------ cRud ------ */
	/**
	 * @param $user holds {MAC, ipv4, ipv6}
	 * @return User that satisfies all parameters
	 */
	function getUser($user) {

	}

	/**
	 * @return Users created after $timestamp
	 */
	function getUser($mac) {

	}

	/**
	 * @return Users created BEFORE $timestamp
	 */
	function getUsersBefore($timestamp) {

	}

	/**
	 * @return Users created AFTER $timestamp
	 */
	function getUsersAfter($timestamp) {

	}

	/**
	 * @return All users in database
	 */
	function getAllUsers() {

	}

	/* ------ crUd ------ */
	/**
	 * @param $field is string name of db column
	 * @param $value is the replacement value
	 * @return true if successful, otherwise false
	 */
	function updateUser($mac, $field, $value) {

	}

	/* ------ cruD ------ */
	/**
	 * @return true if successful, otherwise false
	 */
	function deleteUser($mac) {

	}
	/**
	 * @param Users created BEFORE $timestamp
	 * @return true if successful, otherwise false
	 */
	function deleteUserssBefore($timestamp) {

	}

	/**
	 * @param Users created AFTER $timestamp
	 * @return true if successful, otherwise false
	 */
	function deleteUserBefore($timestamp) {

	}

/* -------------------------- `venues` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * @param $venue includes {name, class}
	 * @return true if successful, otherwise false
	 */
	function createVenue($venue) {

	}

	/* ------ cRud ------ */
	/**
	 * @return venue with correct name in array form
	 */
	function getVenue($name) {

	}

	/**
	 * @return venues of correct class in array form
	 */
	function getVenues($class) {

	}

	/* ------ crUd ------ */
	/**
	 * @param $field is string name of db column
	 * @param $value is the replacement value
	 * @return true if successful, otherwise false
	 */
	function updateVenue($name, $field, $value) {

	}

	/* ------ cruD ------ */
	/**
	 * @return true if successful, otherwise false
	 */
	function deleteVenue($name) {		// TODO I wonder if the $field, $value would work for a lot more functions like this one....

	}

/* -------------------------- `foods` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * @param $food includes {name, class}
	 * @return true if successful, otherwise false
	 */
	function createFood($food) {

	}

	/* ------ cRud ------ */
	/**
	 * @return food with correct name in array form
	 */
	function getFood($name) {

	}

	/**
	 * @return foods of correct class in array form
	 */
	function getFoods($class) {

	}

	/* ------ crUd ------ */
	/**
	 * @param $field is string name of db column
	 * @param $value is the replacement value
	 * @return true if successful, otherwise false
	 */
	function updateFood($name, $field, $value) {

	}

	/* ------ cruD ------ */
	/**
	 * @return true if successful, otherwise false
	 */
	function deleteFood($name) {

	}

/* -------------------------- `venue_vote_types` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * @param $type includes {name, value}
	 * @return true if successful, otherwise false
	 */
	function createVenueVoteType($type) {

	}

	/* ------ cRud ------ */
	/**
	 * @return venueVoteType with correct name in array form
	 */
	function getVenueVoteType($name) {

	}

	/* ------ crUd ------ */
	/**
	 * @param $field is string name of db column
	 * @param $value is the replacement value
	 * @return true if successful, otherwise false
	 */
	function updateVenueVoteType($name, $field, $value) {

	}

	/* ------ cruD ------ */
	/**
	 * @return true if successful, otherwise false
	 */
	function deleteVenueVoteType($name) {

	}

/* -------------------------- `food_vote_types` table methods -------------------------- */

	/* ------ Crud ------ */
	/**
	 * @param $type includes {name, value}
	 * @return true if successful, otherwise false
	 */
	function createFoodVoteType($type) {

	}

	/* ------ cRud ------ */
	/**
	 * @return foodVoteType with correct name in array form
	 */
	function getFoodVoteType($name) {

	}

	/* ------ crUd ------ */
	/**
	 * @param $field is string name of db column
	 * @param $value is the replacement value
	 * @return true if successful, otherwise false
	 */
	function updateFoodVoteType($name, $field, $value) {

	}

	/* ------ cruD ------ */
	/**
	 * @return true if successful, otherwise false
	 */
	function deleteFoodVoteType($name) {

	}

/* -------------------------- `user_venue_votes` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * Creates a vote >> an entry that matches all necessary chunks of data.
	 * Without all parts, the vote (or entry) is not meaningful.
	 * @param all the params needed for a complete entry
	 * @return true if successful, otherwise false
	 */
	function createVenueVote($userId, $venueId, $venueVoteId) {

	}

	/* ------ cRud ------ */
	/**
	 *
	 */
	function isRecent() {}

	function getVotes($venueId) {}

	function getVotes($userId) {}

	function getUsers($venueId) {}

	function getPositiveVotes($venueId) {}

	function getNegativeVotes($venueId) {}

	/* ------ crUd ------ */
	// will fill this out as needed

	/* ------ cruD ------ */
	// will fill this out as needed

/* -------------------------- `user_food_votes` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * Creates a vote >> an entry that matches all necessary chunks of data.
	 * Without all parts, the vote (or entry) is not meaningful.
	 * @param all the params needed for a complete entry
	 * @return true if successful, otherwise false
	 */
	function createFoodVote($userId, $foodId, $foodVoteId) {

	}

	/* ------ cRud ------ */
	/**
	 *
	 */
	function isRecent() {}

	function getVotes($foodId) {}

	function getVotes($userId) {}

	function getUsers($foodId) {}

	function getPositiveVotes($foodId) {}

	function getNegativeVotes($foodId) {}

	/* ------ crUd ------ */
	// will fill this out as needed

	/* ------ cruD ------ */
	// will fill this out as needed



/* -------------------------- `rating` table methods -------------------------- */

	/**
	 * Creating a comparison
	 * @param $mac MAC address of device
	 * @param $dewick vote for dewick
	 * @param $carm vote for carm
	 *
	 * @return mysql table id number
	 */
	public function postRating ($mac, $good, $bad) {
		date_default_timezone_set('UTC');
		$now = date('Y-m-d H:i:s');

		// convert to int
		$mac = base_convert($mac, 16, 10);

		// Create the table if it doesn't exist
		// not sure when this would be needed. I guess I just feel cool being "ROBUST!"
		// hard code table name
		$create = "CREATE TABLE IF NOT EXISTS `rating` (
 						`id` int(20) unsigned NOT NULL AUTO_INCREMENT,
 						`good` int(1) unsigned NOT NULL DEFAULT '0',
						`bad` int(1) unsigned NOT NULL DEFAULT '0',
						`time_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
						`mac_addr` bigint(6) unsigned DEFAULT NULL,
						 PRIMARY KEY (`id`)) 
						 ENGINE=MyISAM AUTO_INCREMENT=110 DEFAULT CHARSET=utf8";

		$stmt = $this->conn->prepare($create);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->close();
		}


		// INSERT statement
		$insert = "INSERT INTO `rating` (good, bad, time_added, mac_addr) VALUES (?, ?, ?, ?)";
		$stmt = $this->conn->prepare($insert);
		$stmt->bind_param("sssi", $good, $bad, $now, $mac);
		
		if (!$stmt)
			return NULL;

		$success = $stmt->execute();
		$stmt->close();

		if ($success) {
			// vote successfully posted
			return true;

		} else {
			// some error happened
			return false;
		}
	}


	/**
	 * Gets a total number of ratings: good or bad
	 * @param ealiest MySql TIMESTAMP to accept 
	 * @return an array of votes for 'good' and 'bad'
	 */
	public function totalRatings ($timestamp) {

		$stmt = $this->conn->prepare("SELECT good, bad FROM rating r WHERE r.time_added >= ?");
		$stmt->bind_param("s", $timestamp);

		if (!$stmt)
			return NULL;
		
		$stmt->execute();

		// bind result variables
		$stmt->bind_result($good, $bad);

		// inititate counters
		$sum_g = 0;
		$sum_b = 0;

		// fetch variables
		while ($row = $stmt->fetch()) {
			
			if ($good != 0) {
				
				$sum_g++;		// each entry only represents 1 vote

			} else if ($bad != 0) {
				
				$sum_b++;
			}
		}

		$stmt->close();
		
		return array("good" => $sum_g, "bad" => $sum_b);
	}




	// make mac address human readable
	function int2macaddress($int) {
    $hex = base_convert($int, 10, 16);
    while (strlen($hex) < 12)
        $hex = '0'.$hex;
    return strtoupper(implode(':', str_split($hex,2)));
	}


}

?>