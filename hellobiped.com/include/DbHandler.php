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
*	limit frequency of votes only for venue votes, not recipe votes
*																														TODOs
*																															CHECK -	create if statements for every $stmt
*																															ONGOING	clean up PHPDoc
*																															CHECK -	complete createIfNotExists()
*																															look for methods that aren't needed or don't make sense
*																															change $name to $venueName or $recipeName
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
	 * Creates appropriate table if it doesn't exist.
	 * @param String $tableName
	 * @return true if successful, false on failure
	 */
	function createIfNotExists($tableName) {
		$mysql = NULL;

		if ($tableName = TBL_USERS) {
			$mysql = "CREATE TABLE IF NOT EXISTS `users` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
				 `mac_addr` bigint(6) unsigned NOT NULL COMMENT 'MAC address',
				 `ipv4_addr` int(10) unsigned NOT NULL COMMENT 'IPv4 address',
				 `ipv6_addr` binary(16) NOT NULL COMMENT 'IPv6 address',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of entry',
				 `last_updated` timestamp NOT NULL COMMENT  'TIMESTAMP of last update',
				 PRIMARY KEY (`id`),
				 UNIQUE KEY `mac_addr` (`mac_addr`,`date_added`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A table of unique users'";
		
		} elseif ($tableName = TBL_VENUES) {
			$mysql = "CREATE TABLE IF NOT EXISTS `venues` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
				 `name` text NOT NULL COMMENT 'Name',
				 `class` text NOT NULL COMMENT 'Class, or type',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of entry',
				 PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A table of unique venues'";
		
		} elseif ($tableName = TBL_RECIPES) {
			$mysql = "CREATE TABLE IF NOT EXISTS `recipes` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
				 `name` text NOT NULL COMMENT 'Name',
				 `class` text NOT NULL COMMENT 'Class, or type',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of entry',
				 PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A table of unique recipes'";
		
		} elseif ($tableName = TBL_VENUE_VOTE_TYPES) {
			$mysql = "CREATE TABLE IF NOT EXISTS `venue_vote_types` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
				 `name` text NOT NULL COMMENT 'Name of vote type',
				 `value` int(11) NOT NULL DEFAULT '0' COMMENT 'Value of vote type',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of entry',
				 PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A table of all possible values of a vote for a venue'";
		
		} elseif ($tableName = TBL_RECIPE_VOTE_TYPES) {
			$mysql = "CREATE TABLE IF NOT EXISTS `recipe_vote_types` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
				 `name` text NOT NULL COMMENT 'Name of vote type',
				 `value` int(11) NOT NULL DEFAULT '0' COMMENT 'Value of vote type',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of entry',
				 PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A table of all possible values of a vote for a recipe'";
		
		} elseif ($tableName = TBL_USER_VENUE_VOTES) {
			$mysql = "CREATE TABLE IF NOT EXISTS `user_venue_votes` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID of entry',
				 `user_id` int(20) unsigned NOT NULL COMMENT 'ID of user who voted',
				 `venue_id` int(20) unsigned NOT NULL COMMENT 'ID of venue the user voted for',
				 `venue_vote_id` int(20) unsigned NOT NULL COMMENT 'ID of vote belonging to user',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of when the user voted',
				 PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='This user voted for this venue with this type of vote'";
		
		} elseif ($tableName = TBL_USER_RECIPE_VOTES) {
			$mysql = "CREATE TABLE IF NOT EXISTS `user_recipe_votes` (
				 `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID of entry',
				 `user_id` int(20) unsigned NOT NULL COMMENT 'ID of user who voted',
				 `recipe_id` int(20) unsigned NOT NULL COMMENT 'ID of recipe the user voted for',
				 `recipe_vote_id` int(20) unsigned NOT NULL COMMENT 'ID of vote belonging to user',
				 `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'TIMESTAMP of when the user voted',
				 PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='This user voted for this recipe with this type of vote'";
		}

		$stmt = $this->conn->prepare($mysql);
		
		if (!$stmt)
			return SENTINEL;
		else {
			$success = $stmt->execute();
			$stmt->close();
			return $success;
		}
	}

	/**
	 * Creates a user
	 * @param String $mac, String $ipv4, String $ipv6
	 * @return true if successful, false on failure
	 */
	function createUser($mac, $ipv4, $ipv6) {
		createIfNotExists(TBL_USERS);
		// remaining fields of id and date_added will be automatically filled in
		$mysql = "INSERT INTO ". TBL_USERS ." (". FIELD_MAC .", ". FIELD_IPV4 .", ". FIELD_IPV6 .") VALUES (?, ?, ?)";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("sss", mac2int($mac), ip2long($ipv4), inet_pton($ipv6));
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$rows = $stmt->num_rows;
			$stmt->close();
			return $rows > 0;

        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ cRud ------ */
	/**
	 * Gets users with mac address
	 * @param String $mac, in human-readable form
	 * @return user assoc array, false on failure
	 */
	function getUser($mac) {
		createIfNotExists(TBL_USERS);
		
		$mysql = "SELECT * FROM ". TBL_USERS ." WHERE ". FIELD_MAC ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", mac2int($mac));
		
		if ($stmt != false) {
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

        } else 	// something is wrong
        	return SENTINEL;
	}

	/**
	 * Gets users with date_added field before $timestamp
	 * @param String $timestamp, in form 'Y-m-d H:i:s'
	 * @return array of assoc user arrays, false on failure
	 */
	function getUsersBefore($timestamp) {
		createIfNotExists(TBL_USERS);
		
		$mysql = "SELECT * FROM ". TBL_USERS ." WHERE ". FIELD_DATE_ADDED ." < ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $timestamp);
		
		if ($stmt != false) {
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
        	
        } else 	// something is wrong
        	return SENTINEL;
	}

	/**
	 * Gets users with date_added after $timestamp 
	 * @param String $timestamp, in from 'Y-m-d H:i:s'
	 * @return array of assoc user arrays, false on failure
	 */
	function getUsersAfter($timestamp) {
		createIfNotExists(TBL_USERS);
		
		$mysql = "SELECT * FROM ". TBL_USERS ." WHERE ". FIELD_DATE_ADDED ." > ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $timestamp);
		
		if ($stmt != false) {
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
        
        } else 	// something is wrong
        	return SENTINEL;
	}

	/**
	 * Gets number of users in database
	 * @return int number of users, false on failure
	 */
	function getNumOfUsers() {
		createIfNotExists(TBL_USERS);
		
		$mysql = "SELECT * FROM ". TBL_USERS;
		$stmt->$this->conn->prepare($mysql);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$numRows = $stmt->num_rows;
			$stmt->close();

			return $numRows;
        
        } else 	// something is wrong
        	return SENTINEL;
	}

	/**
	 * Gets all users in database
	 * @return array of user arrays false on failure
	 */
	function getAllUsers() {
		createIfNotExists(TBL_USERS);
		
		$mysql = "SELECT * FROM ". TBL_USERS;
		$stmt->$this->conn->prepare($mysql);
		
		if ($stmt != false) {
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
        
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ crUd ------ */
	/**
	 * Updates a specific field of a user in db
	 * @param String $mac, human-readable mac address
	 * @param String $field, the database field to update
	 * @param String $value, the replacement value
	 * @return number of rows affected false on failure
	 */
	function updateUser($mac, $field, $value) {
		createIfNotExists(TBL_USERS);

		date_default_timezone_set('UTC');
		$now = date('Y-m-d H:i:s');

		$mysql = "UPDATE ". TBL_USERS ." SET ". $field ." = ?, ". FIELD_LAST_UPDATED ." = ? WHERE ". FIELD_MAC ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("sss", $value, $now, $mac);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$numRows = $stmt->num_rows;
			$stmt->close();

			return $numRows;
        
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ cruD ------ */
	/**
	 * Deletes user from database
	 * @param String $mac, human-readable mac addres
	 * @return int number of rows affected, false on failure
	 */
	function deleteUser($mac) {
		createIfNotExists(TBL_USERS);

		$mysql = "DELETE FROM ". TBL_USERS ." WHERE ". FIELD_MAC ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $mac);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$numRows = $stmt->num_rows;
			$stmt->close();

			return $numRows;
        } else 	// something is wrong
        	return SENTINEL;
	}
	/**
	 * Deletes users with date_added field BEFORE $timestamp
	 * @param String $timestamp, in form 'Y-m-d H:i:s'
	 * @return true if successful, false on failure
	 */
	function deleteUsersBefore($timestamp) {
		// TODO...
	}

	/**
	 * Deletes users with date_added field AFTER $timestamp
	 * @param String $timestamp, in form 'Y-m-d H:i:s'
	 * @return true if successful, false on failure
	 */
	function deleteUserAfter($timestamp) {
		// TODO...
	}

/* -------------------------- `venues` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * Creates a venue
	 * @param String $venueName
	 * @param String $class
	 * @return true if successful, false on failure
	 */
	function createVenue($venueName, $class) {
		createIfNotExists(TBL_VENUES);

		// remaining fields of id and date_added will be automatically filled in
		$mysql = "INSERT INTO ". TBL_VENUES ." (". FIELD_NAME .", ". FIELD_CLASS .") VALUES (?, ?)";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("ss", $venueName, $class);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$rows = $stmt->num_rows;
			$stmt->close();

			return $rows > 0;
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ cRud ------ */
	/**
	 * Gets venues with name
	 * @param String $venueName
	 * @return venue assoc array, false on failure
	 */
	function getVenue($venueName) {
		createIfNotExists(TBL_VENUES);
		
		$mysql = "SELECT * FROM ". TBL_VENUES ." WHERE ". FIELD_NAME ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $venueName);
		
		if ($stmt != false) {
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
        } else 	// something is wrong
        	return SENTINEL;
	}

	/**
	 * Gets venues of a given class
	 * @param String $class
	 * @return array of assoc venue arrays, false on failure
	 */
	function getVenues($class) {
		createIfNotExists(TBL_VENUES);
		
		$mysql = "SELECT * FROM ". TBL_VENUES ." WHERE ". FIELD_CLASS ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $class);
		
		if ($stmt != false) {
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
        } else 	// something is wrong
        	return SENTINEL;														// probably not needed
	}

	/* ------ crUd ------ */
	/**
	 * Update a certain field of a venue
	 * @param String $field - db field
	 * @param String $value - updated value
	 * @return int number of rows affected, false on failure
	 */
	function updateVenue($venueName, $field, $value) {
		createIfNotExists(TBL_VENUES);

		$mysql = "UPDATE ". TBL_VENUES ." SET ". $field ." = ? WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("ss", $value, $venueName);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$numRows = $stmt->num_rows;
			$stmt->close();

			return $numRows;
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ cruD ------ */
	/**
	 * Deletes a venue from db
	 * @param String $venueName
	 * @return int number of rows affected, false on failure
	 */
	function deleteVenue($venueName) {		// TODO I wonder if the $field, $value would work for a lot more functions like this one...
		createIfNotExists(TBL_VENUES);	// answer: yes, it would. but we really don't need flexibility like that.

		$mysql = "DELETE FROM ". TBL_VENUES ." WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $venueName);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$numRows = $stmt->num_rows;
			$stmt->close();

			return $numRows;
        } else 	// something is wrong
        	return SENTINEL;
	}

/* -------------------------- `recipes` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * Inserts a recipe into db
	 * @param String $recipeName
	 * @param String $class
	 * @return true if successful, false on failure
	 */
	function createRecipe($recipeName, $class) {
		createIfNotExists(TBL_RECIPES);
		// remaining fields of id and date_added will be automatically filled in
		$mysql = "INSERT INTO ". TBL_RECIPES ." (". FIELD_NAME .", ". FIELD_CLASS .") VALUES (?, ?)";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("ss", $recipeName, $class);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$rows = $stmt->num_rows;
			$stmt->close();

			return $rows > 0;
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ cRud ------ */
	/**
	 * Selects a recipe from db
	 * @param String $recipeName
	 * @return recipe assoc array, false on failure
	 */
	function getRecipe($recipeName) {
		createIfNotExists(TBL_RECIPES);
		
		$mysql = "SELECT * FROM ". TBL_RECIPES ." WHERE ". FIELD_NAME ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $recipeName);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$row = $stmt->fetch_assoc()
			$stmt->close();

			$recipe = [
				FIELD_ID => $row[FIELD_ID],
				FIELD_NAME => $row[FIELD_NAME],
				FIELD_CLASS => $row[FIELD_CLASS],
				FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
			];

			return $recipe;
        } else 	// something is wrong
        	return SENTINEL;
	}

	/**
	 * @return array of assoc recipe arrays
	 */
	function getRecipes($class) {
		createIfNotExists(TBL_RECIPES);
		
		$mysql = "SELECT * FROM ". TBL_RECIPES ." WHERE ". FIELD_CLASS ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $class);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			
			$recipes = array();
			while ($row = $stmt->fetch_assoc()) {
				$recipe = [
					FIELD_ID => $row[FIELD_ID],
					FIELD_NAME => $row[FIELD_NAME],
					FIELD_CLASS => $row[FIELD_CLASS],
					FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
			];
				array_push($recipes, $recipe);
			}
			$stmt->close();

			return $recipes;
        } else 	// something is wrong
        	return SENTINEL;														// probably not needed
	}

	/* ------ crUd ------ */
	/**
	 * Updates a field of a given recipe in db
	 * @param String $field - db field
	 * @param String $value - updated value
	 * @return int number of rows affected, false on failure
	 */
	function updateRecipe($recipeName, $field, $value) {
		createIfNotExists(TBL_RECIPES);

		$mysql = "UPDATE ". TBL_RECIPES ." SET ". $field ." = ? WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("ss", $value, $recipeName);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$numRows = $stmt->num_rows;
			$stmt->close();

			return $numRows;
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ cruD ------ */
	/**
	 * Deletes a recipe from db
	 * @param String $recipeName
	 * @return int number of rows affected, false on failure
	 */
	function deleteRecipe($recipeName) {
		createIfNotExists(TBL_RECIPES);

		$mysql = "DELETE FROM ". TBL_RECIPES ." WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $recipeName);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$numRows = $stmt->num_rows;
			$stmt->close();

			return $numRows;
        } else 	// something is wrong
        	return SENTINEL;
	}

/* -------------------------- `venue_vote_types` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * Creates a venue vote type in db
	 * @param String $venueName
	 * @param String $value
	 * @return true if successful, false on failure
	 */
	function createVenueVoteType($venueName, $value) {
		createIfNotExists(TBL_VENUE_VOTE_TYPES);
		// remaining fields of id and date_added will be automatically filled in
		$mysql = "INSERT INTO ". TBL_VENUE_VOTE_TYPES ." (". FIELD_NAME .", ". FIELD_VALUE .") VALUES (?, ?)";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("si", $venueName, $value);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$rows = $stmt->num_rows;
			$stmt->close();

			return $rows > 0;
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ cRud ------ */
	/**
	 * Gets a venue vote type from db
	 * @param String $venueName
	 * @return venue vote type assoc array, false on failure
	 */
	function getVenueVoteType($venueName) {
		createIfNotExists(TBL_VENUE_VOTE_TYPES);
		
		$mysql = "SELECT * FROM ". TBL_VENUE_VOTE_TYPES ." WHERE ". FIELD_NAME ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $venueName);
		
		if ($stmt != false) {
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
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ crUd ------ */
	// TODO as needed...

	/* ------ cruD ------ */
	/**
	 * Deletes a venue from the db
	 * @param String $venueName
	 * @return int number of rows affected, false on failure
	 */
	function deleteVenueVoteType($venueName) {
		createIfNotExists(TBL_VENUE_VOTE_TYPES);

		$mysql = "DELETE FROM ". TBL_VENUE_VOTE_TYPES ." WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $venueName);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$numRows = $stmt->num_rows;
			$stmt->close();

			return $numRows;
        } else 	// something is wrong
        	return SENTINEL;
	}

/* -------------------------- `recipe_vote_types` table methods -------------------------- */

	/* ------ Crud ------ */
	/**
	 * Creates a recipe vote type in db
	 * @param String $typeName
	 * @param String $value
	 * @return true if successful, false on failure
	 */
	function createRecipeVoteType($typeName, $value) {
		createIfNotExists(TBL_RECIPE_VOTE_TYPES);
		// remaining fields of id and date_added will be automatically filled in
		$mysql = "INSERT INTO ". TBL_RECIPE_VOTE_TYPES ." (". FIELD_NAME .", ". FIELD_VALUE .") VALUES (?, ?)";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("si", $typeName, $value);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$rows = $stmt->num_rows;
			$stmt->close();

			return $rows > 0;
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ cRud ------ */
	/**
	 * Gets a recipe vote type from db
	 * @param String $recipeName
	 * @return recipe vote type assoc array, false on failure
	 */
	function getRecipeVoteType($recipeName) {
		createIfNotExists(TBL_RECIPE_VOTE_TYPES);
		
		$mysql = "SELECT * FROM ". TBL_RECIPE_VOTE_TYPES ." WHERE ". FIELD_NAME ." = ?");
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $recipeName);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$row = $stmt->fetch_assoc()
			$stmt->close();

			$recipeVoteType = [
				FIELD_ID => $row[FIELD_ID],
				FIELD_NAME => $row[FIELD_NAME],
				FIELD_VALUE => $row[FIELD_VALUE],
				FIELD_DATE_ADDED => $row[FIELD_DATE_ADDED]
			];

			return $recipeVoteType;
        } else 	// something is wrong
        	return SENTINEL;
	}

	/* ------ crUd ------ */
	// TODO as needed...

	/* ------ cruD ------ */
	/**
	 * Deletes a recipe vote type from db
	 * @param String $recipeName
	 * @return int number of rows affected, false on failure
	 */
	function deleteRecipeVoteType($recipeName) {
		createIfNotExists(TBL_RECIPE_VOTE_TYPES);

		$mysql = "DELETE FROM ". TBL_RECIPE_VOTE_TYPES ." WHERE ". FIELD_NAME ." = ?";
		$stmt->$this->conn->prepare($mysql);
		$stmt->bind_param("s", $recipeName);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			$numRows = $stmt->num_rows;
			$stmt->close();

			return $numRows;
        } else 	// something is wrong
        	return SENTINEL;
	}

/* -------------------------- `user_venue_votes` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * Creates a vote >> an entry that matches all necessary chunks of data.
	 * Without all parts, the vote (or entry) is not meaningful.
	 * @param int $userId
	 * @param int $venueId
	 * @param int $venueVoteId
	 * @return true if successful, false on failure
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

        } else 	// something is wrong
        	return SENTINEL;
	}

	/**
	 * Creates a vote >> an entry that matches all necessary chunks of data.
	 * @param String $mac, human-readable mac address
	 * @param String $venueName
	 * @param String $voteName
	 * @return true if successful, false on failure
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
	 * $timestamp is the latest time for this user to have voted last
	 * @param String $mac, human-readable mac address
	 * @param String $timestamp, in form 'Y-m-d H:i:s'
	 * @return false if not recent, true otherwise
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
	 * Gets all votes for a given recipe with date_added after $timestmp
	 * @param String $recipeName
	 * @param String $timestamp
	 * @return assoc array of votes
	 */
	function getRecentVenueVotes($venueName, $timestamp) {
		// TODO...also change function name
	}

	/**
	 * Gets all votes for a given venue
	 * @param String $venueName
	 * @return assoc array of votes
	 */
	function getVotesByVenue($venueName) {
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
	 * @param String $mac - MAC address of user
	 * @return assoc array of votes
	 */
	function getVenueVotesByUser($mac) {
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

/* -------------------------- `user_recipe_votes` table methods -------------------------- */
	/* ------ Crud ------ */
	/**
	 * Creates a vote >> an entry that matches all necessary chunks of data.
	 * Without all parts, the vote (or entry) is not meaningful.
	 * @param int $userId
	 * @param int $recipeId
	 * @param int $recipeVoteId
	 * @return true if successful, false on failure
	 */
	function createRecipeVote($userId, $recipeId, $recipeVoteId) {
		createIfNotExists(TBL_USER_RECIPE_VOTES);

		$mysql = "INSERT INTO ". TBL_USER_RECIPE_VOTES ." ( ". FIELD_USER_ID .", "
			. FIELD_RECIPE_ID .", ". FIELD_RECIPE_VOTE_ID .") VALUES (?, ?, ?)";
		$stmt->bind_param("iii", $userId, $recipeId, $recipeVoteId);
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
        	$num_rows = $stmt->num_rows;
        	$stmt->close();
        	return $num_rows > 0;

        } else 	// something is wrong
        	return SENTINEL;
	}

	/**
	 * Creates a vote >> an entry that matches all necessary chunks of data.
	 * @param String $mac
	 * @param String $venueName
	 * @param String $voteName
	 * @return true if successful, false on failure
	 */
	function createRecipeVote($mac, $recipeName, $recipeVoteName) {
		createIfNotExists(TBL_USER_RECIPE_VOTES);

		$user = getUser($mac);
		$recipe = getRecipe($recipeName);
		$recipeVoteType = getRecipeVoteType($recipeVoteName);

		$success = createRecipeVote($user[FIELD_ID], $recipe[FIELD_ID], $recipeVoteType[FIELD_ID]);
		return $success;
	}

	/* ------ cRud ------ */
	/**
	 * Gets all votes for a given recipe with date_added after $timestmp
	 * @param String $recipeName
	 * @param String $timestamp
	 * @return assoc array of votes
	 */
	function getRecentRecipeVotes($recipeName, $timestamp) {
		// TODO...also change function name, check venue name
	}

	/**
	 * Gets all votes for a given recipe
	 * @param $recipeName
	 * @return assoc array of votes
	 */
	function getVotesByRecipe($recipeName) {
		createIfNotExists(TBL_USER_RECIPE_VOTES);

		$recipe = getRecipe($recipeName);

		$mysql = "SELECT * FROM ". TBL_USER_RECIPE_VOTES ." WHERE ". FIELD_RECIPE_ID ." = ?";
		$stmt->bind_param("i", $recipe[FIELD_ID]);
		
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();
			
			// $votes has keys: {id, user_id, recipe_id, recipe_vote_id, date_added}
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
	 * @return assoc array of votes
	 */
	function getRecipeVotesByUser($mac) {
		createIfNotExists(TBL_USER_RECIPE_VOTES);

		$user = getUser($mac);

		$mysql = "SELECT * FROM ". TBL_USER_RECIPE_VOTES ." WHERE ". FIELD_USER_ID ." = ?";
		$stmt->bind_param("i", $user[FIELD_ID]);
		if ($stmt != false) {
			$stmt->execute();
			$stmt->store_result();

			// $votes has keys: {id, user_id, recipe_id, recipe_vote_id, date_added}
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
}

?>