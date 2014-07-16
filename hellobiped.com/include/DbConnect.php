<?php

/**
 * Handling database connections
 *
 * @author: Aaron Bowen
 * Date 6/24/14
 */

class DbConnect {

	private $conn;

	// Constructor
	function __construct () { }

	/**
	 * Establishing database connections
	 * @return database connection handler
	 */
	function connect () {

		// Congif.php holds database names and such
		include_once dirname(__FILE__) . '/./Config.php';

		// Connecting to mysql database
		$this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

		// Check for database connection error
		if (mysqli_connect_errno()) {
				echo "Failed to connect to MySQL: " . mysqli_connect_errno();
		}

		// Returning connection resource
		return $this->conn;
	}
}

?>