<?php

/**
* Class to handle all db operations
* This class will have CRUD methods for database tables
*
* Conventions of this class:
*	use ip2long() and long2ip() for IPv4 addresses
*	use inet_ntop() and inet_pton() for IPv6 addresses
*
* Notes on class:
*	limit frequency of votes only for venue votes, not food votes
*																														TODOs
*																															create if statements for every $stmt
*																															clean up PHPDoc
*																															complete createIfNotExists()
*																															look for methods that aren't needed or don't make sense
*																															
*																															time createIfNotExists(), is it worth having for every func?
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

/* -------------------------- `users` table methods -------------------------- */
	
	/* ------ Crud ------ */
	/**
	 * Creates `users` table if it doesn't exist.
	 */
	function createIfNotExists($table) {
		$mysql = NULL;

		if ($table = TBL_USERS) {
			$mysql = "CREATE TABLE IF NOT EXISTS `users` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
				 `mac_addr` bigint(6) unsigned NOT NULL COMMENT 'MAC address',
				 `ipv4_addr` int(10) unsigned NOT NULL COMMENT 'IPv4 address',
				 `ipv6_addr` binary(16) NOT NULL COMMENT 'IPv6 address',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of entry',
				 `last_updated` timestamp NOT NULL COMMENT  'TIMESTAMP of last update',
				 PRIMARY KEY (`id`),
				 UNIQUE KEY `mac_addr` (`mac_addr`,`date_added`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A table of unique users'"
		} elseif ($table = TBL_VENUES) {
			$mysql = "CREATE TABLE IF NOT EXISTS `venues` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
				 `name` text NOT NULL COMMENT 'Name',
				 `class` text NOT NULL COMMENT 'Class, or type',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of entry',
				 PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A table of unique food venues'" 
		} elseif ($table = TBL_VENUE_VOTE_TYPES) {
			$mysql = "CREATE TABLE IF NOT EXISTS `venue_vote_types` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
				 `name` text NOT NULL COMMENT 'Name of vote type',
				 `value` int(11) NOT NULL DEFAULT '0' COMMENT 'Value of vote type',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of entry',
				 PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A table of all possible values of a vote for a venue'" 
		} elseif ($table = TBL_USER_VENUE_VOTES) {
			$mysql = "CREATE TABLE IF NOT EXISTS `user_venue_votes` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID of entry',
				 `user_id` int(20) unsigned NOT NULL COMMENT 'ID of user who voted',
				 `venue_id` int(20) unsigned NOT NULL COMMENT 'ID of venue the user voted for',
				 `venue_vote_id` int(20) unsigned NOT NULL COMMENT 'ID of vote belonging to user',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of when the user voted',
				 PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='This user voted for this venue with this type of vote'" 
		} elseif (...)																							// TODO


		$stmt = $this->conn->prepare($mysql);
		
		if (!$stmt)
			return;
		$stmt->execute();
		$stmt->close();
	}

	/**
	 * Creates a user. Users have an id, MAC, ipv4, ipv6, and a date_added
	 * @param $mac, $ipv4, $ipv6
	 * @return true if successful, false otherwise
	 */
	function createUser($mac, $ipv4, $ipv6) {
		createIfNotExists(TBL_USERS);
		// remaining fields of id and date_added will be automatically filled in
		$mysql = "INSERT INTO ". TBL_USERS ." (". FIELD_MAC .", ". FIELD_IPV4 .", ". FIELD_IPV6 .") VALUES (?, ?, ?)";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("sss", mac2int($mac), ip2long($ipv4), inet_pton($ipv6));
		$stmt->execute();
		$stmt->store_result();
		$rows = $stmt->num_rows;
		$stmt->close();

		return $rows > 0;
	}

	/* ------ cRud ------ */
	/**
	 * @param $user holds {MAC, ipv4, ipv6}
	 * @return User that satisfies all parameters
	 */
	function getUser($user) {
		// TODO...
	}

	/**
	 * @return user array {id, mac, ipv4, ipv6, date_added}
	 */
	function getUser($mac) {
		createIfNotExists(TBL_USERS);
		
		$mysql = "SELECT * FROM ". TBL_USERS ." WHERE ". FIELD_MAC ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", mac2int($mac));
		$stmt->execute();
		$stmt->store_result();
		$row = $stmt->fetch_assoc()
		$stmt->close();

		$user = [
			FIELD_ID => $row[FIELD_ID],
			FIELD_MAC => $row[FIELD_MAC],
			FIELD_IPV4 => $row[FIELD_IPV4],
			FIELD_IPV6 => $row[FIELD_IPV6],
			FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
		];

		return $user;
	}

	/**
	 * @return Array of users created BEFORE $timestamp
	 */
	function getUsersBefore($timestamp) {
		createIfNotExists(TBL_USERS);
		
		$mysql = "SELECT * FROM ". TBL_USERS ." WHERE ". FIELD_DATE_ADDED ." < ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $timestamp);
		$stmt->execute();
		$stmt->store_result();

		$users = array();
		while ($row = $stmt->fetch_assoc()) {
			$user = [
				FIELD_ID => $row[FIELD_ID],
				FIELD_MAC => $row[FIELD_MAC],
				FIELD_IPV4 => $row[FIELD_IPV4],
				FIELD_IPV6 => $row[FIELD_IPV6],
				FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
			];
			array_push($users, $user);
		}
		$stmt->close();

		return $users;
	}

	/**
	 * @return Array of users created AFTER $timestamp
	 */
	function getUsersAfter($timestamp) {
		createIfNotExists(TBL_USERS);
		
		$mysql = "SELECT * FROM ". TBL_USERS ." WHERE ". FIELD_DATE_ADDED ." > ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $timestamp);
		$stmt->execute();
		$stmt->store_result();

		$users = array();
		while ($row = $stmt->fetch_assoc()) {
			$user = [
				FIELD_ID => $row[FIELD_ID],
				FIELD_MAC => $row[FIELD_MAC],
				FIELD_IPV4 => $row[FIELD_IPV4],
				FIELD_IPV6 => $row[FIELD_IPV6],
				FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
			];
			array_push($users, $user);
		}
		$stmt->close();

		return $users;
	}

	/**
	 * @return Number of users in database
	 */
	function getNumOfUsers() {
		createIfNotExists(TBL_USERS);
		
		$mysql = "SELECT * FROM ". TBL_USERS;
		$stmt->$this->conn->prepare($mysql);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}

	/**
	 * I feel like this would be expense to call
	 * @return All users in database
	 */
	function getAllUsers() {
		createIfNotExists(TBL_USERS);
		
		$mysql = "SELECT * FROM ". TBL_USERS;
		$stmt->$this->conn->prepare($mysql);
		$stmt->execute();
		$stmt->store_result();

		$users = array();
		while ($row = $stmt->fetch_assoc()) {
			$user = [
				FIELD_ID => $row[FIELD_ID],
				FIELD_MAC => $row[FIELD_MAC],
				FIELD_IPV4 => $row[FIELD_IPV4],
				FIELD_IPV6 => $row[FIELD_IPV6],
				FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
			];
			array_push($users, $user);
		}
		$stmt->close();

		return $users;
	}

	/* ------ crUd ------ */
	/**
	 * @param $field is string name of db column
	 * @param $value is the replacement value
	 * @return Number of rows affected
	 */
	function updateUser($mac, $field, $value) {
		createIfNotExists(TBL_USERS);

		date_default_timezone_set('UTC');
		$now = date('Y-m-d H:i:s');

		$mysql = "UPDATE ". TBL_USERS ." SET ". $field ." = ?, ". FIELD_LAST_UPDATED ." = ? WHERE ". FIELD_MAC ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("sss", $value, $now, $mac);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}

	/* ------ cruD ------ */
	/**
	 * @return Number of rows affected
	 */
	function deleteUser($mac) {
		createIfNotExists(TBL_USERS);

		$mysql = "DELETE FROM ". TBL_USERS ." WHERE ". FIELD_MAC ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $mac);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}
	/**
	 * Deletes users created BEFORE $timestamp
	 * @return true if successful, otherwise false
	 */
	function deleteUserssBefore($timestamp) {
		// TODO as needed
	}

	/**
	 * Deletes users created AFTER $timestamp
	 * @return true if successful, otherwise false
	 */
	function deleteUserAfter($timestamp) {
		// TODO as needed
	}

/* -------------------------- `venues` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * @param $name, $class
	 * @return true if successful, otherwise false
	 */
	function createVenue($name, $class) {
		createIfNotExists(TBL_VENUES);
		// remaining fields of id and date_added will be automatically filled in
		$mysql = "INSERT INTO ". TBL_VENUES ." (". FIELD_NAME .", ". FIELD_CLASS .") VALUES (?, ?)";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("ss", $name, $class);
		$stmt->execute();
		$stmt->store_result();
		$rows = $stmt->num_rows;
		$stmt->close();

		return $rows > 0;
	}

	/* ------ cRud ------ */
	/**
	 * @return array of venue {id, name, class, date_added}
	 */
	function getVenue($name) {
		createIfNotExists(TBL_VENUES);
		
		$mysql = "SELECT * FROM ". TBL_VENUES ." WHERE ". FIELD_NAME ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->store_result();
		$row = $stmt->fetch_assoc()
		$stmt->close();

		$venue = [
			FIELD_ID => $row[FIELD_ID],
			FIELD_NAME => $row[FIELD_NAME],
			FIELD_CLASS => $row[FIELD_CLASS],
			FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
		];

		return $venue;
	}

	/**
	 * @return numeric array of venues
	 */
	function getVenues($class) {
		createIfNotExists(TBL_VENUES);
		
		$mysql = "SELECT * FROM ". TBL_VENUES ." WHERE ". FIELD_CLASS ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $class);
		$stmt->execute();
		$stmt->store_result();
		
		$venues = array();
		while ($row = $stmt->fetch_assoc()) {
			$venue = [
				FIELD_ID => $row[FIELD_ID],
				FIELD_NAME => $row[FIELD_NAME],
				FIELD_CLASS => $row[FIELD_CLASS],
				FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
		];
			array_push($venues, $venue);
		}
		$stmt->close();

		return $venues;
	}

	/* ------ crUd ------ */
	/**
	 * @param $field is string name of db column
	 * @param $value is the replacement value
	 * @return Number of rows affected
	 */
	function updateVenue($name, $field, $value) {
		createIfNotExists(TBL_VENUES);

		$mysql = "UPDATE ". TBL_VENUES ." SET ". $field ." = ? WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("ss", $value, $name);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}

	/* ------ cruD ------ */
	/**
	 * @return Number of rows affected
	 */
	function deleteVenue($name) {		// TODO I wonder if the $field, $value would work for a lot more functions like this one...
		createIfNotExists(TBL_VENUES);	// answer: yes, it would. but we really don't need flexibility like that.

		$mysql = "DELETE FROM ". TBL_VENUES ." WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}

/* -------------------------- `foods` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * @param $food includes {name, class}
	 * @return true if successful, otherwise false
	 */
	function createFood($food) {
		createIfNotExists(TBL_FOODS);
		// remaining fields of id and date_added will be automatically filled in
		$mysql = "INSERT INTO ". TBL_FOODS ." (". FIELD_NAME .", ". FIELD_CLASS .") VALUES (?, ?)";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("ss", $name, $class);
		$stmt->execute();
		$stmt->store_result();
		$rows = $stmt->num_rows;
		$stmt->close();

		return $rows > 0;
	}

	/* ------ cRud ------ */
	/**
	 * @return Array of food {id, name, class, date_added}
	 */
	function getFood($name) {
		createIfNotExists(TBL_FOODS);
		
		$mysql = "SELECT * FROM ". TBL_FOODS ." WHERE ". FIELD_NAME ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->store_result();
		$row = $stmt->fetch_assoc()
		$stmt->close();

		$food = [
			FIELD_ID => $row[FIELD_ID],
			FIELD_NAME => $row[FIELD_NAME],
			FIELD_CLASS => $row[FIELD_CLASS],
			FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
		];

		return $food;
	}

	/**
	 * @return foods of correct class in array form
	 */
	function getFoods($class) {
		createIfNotExists(TBL_FOODS);
		
		$mysql = "SELECT * FROM ". TBL_FOODS ." WHERE ". FIELD_CLASS ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $class);
		$stmt->execute();
		$stmt->store_result();
		
		$foods = array();
		while ($row = $stmt->fetch_assoc()) {
			$food = [
				FIELD_ID => $row[FIELD_ID],
				FIELD_NAME => $row[FIELD_NAME],
				FIELD_CLASS => $row[FIELD_CLASS],
				FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
		];
			array_push($foods, $food);
		}
		$stmt->close();

		return $foods;
	}

	/* ------ crUd ------ */
	/**
	 * @param $field is string name of db column
	 * @param $value is the replacement value
	 * @return Number of rows affected
	 */
	function updateFood($name, $field, $value) {
		createIfNotExists(TBL_FOODS);

		$mysql = "UPDATE ". TBL_FOODS ." SET ". $field ." = ? WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("ss", $value, $name);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}

	/* ------ cruD ------ */
	/**
	 * @return Number of rows affected
	 */
	function deleteFood($name) {
		createIfNotExists(TBL_FOODS);

		$mysql = "DELETE FROM ". TBL_FOODS ." WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}

/* -------------------------- `venue_vote_types` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * @param $name, $value
	 * @return true if successful, otherwise false
	 */
	function createVenueVoteType($name, $value) {
		createIfNotExists(TBL_VENUE_VOTE_TYPES);
		// remaining fields of id and date_added will be automatically filled in
		$mysql = "INSERT INTO ". TBL_VENUE_VOTE_TYPES ." (". FIELD_NAME .", ". FIELD_VALUE .") VALUES (?, ?)";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("si", $name, $value);
		$stmt->execute();
		$stmt->store_result();
		$rows = $stmt->num_rows;
		$stmt->close();

		return $rows > 0;
	}

	/* ------ cRud ------ */
	/**
	 * @return Arrray of venueVoteType with correct name in array form
	 */
	function getVenueVoteType($name) {
		createIfNotExists(TBL_VENUE_VOTE_TYPES);
		
		$mysql = "SELECT * FROM ". TBL_VENUE_VOTE_TYPES ." WHERE ". FIELD_NAME ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->store_result();
		$row = $stmt->fetch_assoc()
		$stmt->close();

		$venueVoteType = [
			FIELD_ID => $row[FIELD_ID],
			FIELD_NAME => $row[FIELD_NAME],
			FIELD_VALUE => $row[FIELD_VALUE],
			FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
		];

		return $venueVoteType;
	}

	/* ------ crUd ------ */
	/**
	 * @param $field is string name of db column
	 * @param $value is the replacement value
	 * @return Number of rows affected
	 */
	function updateVenueVoteType($name, $field, $value) {
		createIfNotExists(TBL_VENUE_VOTE_TYPES);

		$mysql = "UPDATE ". TBL_VENUE_VOTE_TYPES ." SET ". $field ." = ? WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("ss", $value, $name);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}

	/* ------ cruD ------ */
	/**
	 * @return Number of rows affected
	 */
	function deleteVenueVoteType($name) {
		createIfNotExists(TBL_VENUE_VOTE_TYPES);

		$mysql = "DELETE FROM ". TBL_VENUE_VOTE_TYPES ." WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}

/* -------------------------- `food_vote_types` table methods -------------------------- */

	/* ------ Crud ------ */
	/**
	 * @param $name, $value
	 * @return true if successful, otherwise false
	 */
	function createFoodVoteType($type) {
		createIfNotExists(TBL_FOOD_VOTE_TYPES);
		// remaining fields of id and date_added will be automatically filled in
		$mysql = "INSERT INTO ". TBL_FOOD_VOTE_TYPES ." (". FIELD_NAME .", ". FIELD_VALUE .") VALUES (?, ?)";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("si", $name, $value);
		$stmt->execute();
		$stmt->store_result();
		$rows = $stmt->num_rows;
		$stmt->close();

		return $rows > 0;
	}

	/* ------ cRud ------ */
	/**
	 * @return foodVoteType with correct name in array form
	 */
	function getFoodVoteType($name) {
		createIfNotExists(TBL_FOOD_VOTE_TYPES);
		
		$mysql = "SELECT * FROM ". TBL_FOOD_VOTE_TYPES ." WHERE ". FIELD_NAME ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->store_result();
		$row = $stmt->fetch_assoc()
		$stmt->close();

		$foodVoteType = [
			FIELD_ID => $row[FIELD_ID],
			FIELD_NAME => $row[FIELD_NAME],
			FIELD_VALUE => $row[FIELD_VALUE],
			FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
		];

		return $foodVoteType;
	}

	/* ------ crUd ------ */
	/**
	 * @param $field is string name of db column
	 * @param $value is the replacement value
	 * @return Number of rows affected
	 */
	function updateFoodVoteType($name, $field, $value) {
		createIfNotExists(TBL_FOOD_VOTE_TYPES);

		$mysql = "UPDATE ". TBL_FOOD_VOTE_TYPES ." SET ". $field ." = ? WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("ss", $value, $name);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}

	/* ------ cruD ------ */
	/**
	 * @return Number of rows affected
	 */
	function deleteFoodVoteType($name) {
		createIfNotExists(TBL_FOOD_VOTE_TYPES);

		$mysql = "DELETE FROM ". TBL_FOOD_VOTE_TYPES ." WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->store_result();
		$numRows = $stmt->num_rows;
		$stmt->close();

		return $numRows;
	}

/* -------------------------- `user_venue_votes` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * Creates a vote >> an entry that matches all necessary chunks of data.
	 * Without all parts, the vote (or entry) is not meaningful.
	 * @param $userId, $venueId, $venueVoteId - all the params needed for a complete entry
	 * @return true if successful, otherwise false
	 */
	function createVenueVote($userId, $venueId, $venueVoteId) {
		createIfNotExists(TBL_USER_VENUE_VOTES);

		$mysql = "INSERT INTO ". TBL_USER_VENUE_VOTES ." ( ". FIELD_USER_ID .", "
			. FIELD_VENUE_ID .", ". FIELD_VENUE_VOTE_ID .") VALUES (?, ?, ?)";
		$stmt->bind_param("iii", $userId, $venueId, $venueVoteId);
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
        	$num_rows = $stmt->num_rows;
        	$stmt->close();
        	return $num_rows > 0;

        } else 	// something is wrong, so don't allow anything to be posted.
        	return false;
	}

	/**
	 * Creates a vote >> an entry that matches all necessary chunks of data.
	 * @param $mac, $venueName, $voteName
	 * @return true if successful, otherwise false
	 */
	function createVenueVote($mac, $venueName, $venueVoteName) {
		createIfNotExists(TBL_USER_VENUE_VOTES);

		$user = getUser($mac);
		$venue = getVenue($venueName);
		$venueVoteType = getVenueVoteType($venueVoteName);

		$success = createVenueVote($user[FIELD_ID], $venue[FIELD_ID], $venueVoteType[FIELD_ID]);
		return $success;
	}

	/* ------ cRud ------ */
	/**
	 * Determines if an entry will be allowed for this user in TBL_USER_VENUE_VOTES
	 * @param $mac
	 * @param $timestamp latest time for this ip to have voted last
	 * @return whther or not $mac has made an entry since $timestamp
	 */
	function isRecentVenueVote($mac, $timestamp) {
		createIfNotExists(TBL_USER_VENUE_VOTES);

		$mysql = "SELECT votes.* FROM ". TBL_USER_VENUE_VOTES ." votes, users"
				." WHERE users.". FIELD_MAC ." = ?"
				." AND users.". FIELD_ID ." = votes.". FIELD_USER_ID ." AND votes.". FIELD_TIME_ADDED ." >= ?";
		$stmt->bind_param("ss", mac2int($mac), $timestamp);

		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
        	$num_rows = $stmt->num_rows;
        	$stmt->close();
        	return $num_rows > 0;
        
        } else 	// something is wrong, so don't allow anything to be posted.
        	return true;
	}

	/**
	 * Gets all votes for a given venue
	 * @param $venueName
	 * @return assoc. array of votes
	 */
	function getVotes($venueName) {
		createIfNotExists(TBL_USER_VENUE_VOTES);

		$venue = getVenue($venueName);

		$mysql = "SELECT * FROM ". TBL_USER_VENUE_VOTES ." WHERE ". FIELD_VENUE_ID ." = ?";
		$stmt->bind_param("i", $venue[FIELD_ID]);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			
			// $votes has keys: {id, user_id, venue_id, venue_vote_id, date_added}
        	$votes = array();
        	while ($row = $stmt->fetch_assoc()) {
        		array_push($votes, $row)
        	}

        	$stmt->close();
        	return $votes;
        
        } else 	// something is wrong
        	return SENTINEL;
	}

	/**
	 * Gets all votes by a given user
	 * @param $mac - MAC address of user
	 * @return assoc. array of votes
	 */
	function getVotes($mac) {
		createIfNotExists(TBL_USER_VENUE_VOTES);

		$user = getUser($mac);

		$mysql = "SELECT * FROM ". TBL_USER_VENUE_VOTES ." WHERE ". FIELD_USER_ID ." = ?";
		$stmt->bind_param("i", $user[FIELD_ID]);
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();

			// $votes has keys: {id, user_id, venue_id, venue_vote_id, date_added}
        	$votes = array();
        	while ($row = $stmt->fetch_assoc()) {
        		array_push($votes, $row)
        	}
        	
        	$stmt->close();
        	return $votes;
        
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ crUd ------ */
	// TODO will fill this out as needed

	/* ------ cruD ------ */
	// TODO will fill this out as needed

/* -------------------------- `user_food_votes` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * Creates a vote >> an entry that matches all necessary chunks of data.
	 * Without all parts, the vote (or entry) is not meaningful.
	 * @param $userId, $foodId, $foodVoteId - all the params needed for a complete entry
	 * @return true if successful, otherwise false
	 */
	function createFoodVote($userId, $foodId, $foodVoteId) {
		createIfNotExists(TBL_USER_FOOD_VOTES);

		$mysql = "INSERT INTO ". TBL_USER_FOOD_VOTES ." ( ". FIELD_USER_ID .", "
			. FIELD_FOOD_ID .", ". FIELD_FOOD_VOTE_ID .") VALUES (?, ?, ?)";
		$stmt->bind_param("iii", $userId, $foodId, $foodVoteId);
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
        	$num_rows = $stmt->num_rows;
        	$stmt->close();
        	return $num_rows > 0;

        } else 	// something is wrong
        	return false;
	}

	/**
	 * Creates a vote >> an entry that matches all necessary chunks of data.
	 * @param $mac, $venueName, $voteName
	 * @return true if successful, otherwise false
	 */
	function createVenueVote($mac, $foodName, $foodVoteName) {
		createIfNotExists(TBL_USER_VENUE_VOTES);

		$user = getUser($mac);
		$food = getFood($foodName);
		$foodVoteType = getFoodVoteType($foodVoteName);

		$success = createFoodVote($user[FIELD_ID], $food[FIELD_ID], $foodVoteType[FIELD_ID]);
		return $success;
	}

	/* ------ cRud ------ */
	/**
	 * Gets all votes for a given food
	 * @param $foodName
	 * @return assoc. array of votes
	 */
	function getVotes($foodName) {
		createIfNotExists(TBL_USER_FOOD_VOTES);

		$food = getFood($foodName);

		$mysql = "SELECT * FROM ". TBL_USER_FOOD_VOTES ." WHERE ". FIELD_FOOD_ID ." = ?";
		$stmt->bind_param("i", $food[FIELD_ID]);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			
			// $votes has keys: {id, user_id, food_id, food_vote_id, date_added}
        	$votes = array();
        	while ($row = $stmt->fetch_assoc()) {
        		array_push($votes, $row)
        	}

        	$stmt->close();
        	return $votes;
        
        } else 	// something is wrong
        	return SENTINEL;
	}

	/**
	 * Gets all votes by a given user
	 * @param $mac - MAC address of user
	 * @return assoc. array of votes
	 */
	function getVotes($mac) {
		createIfNotExists(TBL_USER_FOOD_VOTES);

		$user = getUser($mac);

		$mysql = "SELECT * FROM ". TBL_USER_FOOD_VOTES ." WHERE ". FIELD_USER_ID ." = ?";
		$stmt->bind_param("i", $user[FIELD_ID]);
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();

			// $votes has keys: {id, user_id, food_id, food_vote_id, date_added}
        	$votes = array();
        	while ($row = $stmt->fetch_assoc()) {
        		array_push($votes, $row)
        	}
        	
        	$stmt->close();
        	return $votes;
        
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ crUd ------ */
	// TODO will fill this out as needed

	/* ------ cruD ------ */
	// TODO will fill this out as needed

/* -------------------------- `rating` table methods -------------------------- */												// now obsolete!

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



	// make human readable mac address into an int
	function mac2int($mac) {
		$mac = str_replace(":", "", $mac);
    	return base_convert($mac, 16, 10);
	}

	// make mac address human readable
	function int2mac($int) {
	    $hex = base_convert($int, 10, 16);
	    while (strlen($hex) < 12)
	        $hex = '0'.$hex;
	    return strtoupper(implode(':', str_split($hex,2)));
	}
}

?>