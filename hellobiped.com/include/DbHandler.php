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
	 *
	 * @param $mac MAC address of the user
	 * @param $timestamp latest time for this ip to have voted last
	 *
	 * @return whther or not $mac has made an entry since $timestamp
	 *
	 *
	 * if I ever need a getUsersVotes($ipv4), this is pretty much that
	 */
	function isRecent($mac, $timestamp, $table) {

		// echo("votes after ".$timestamp."?");
		// base_convert($int, 10, 16);

		if ($table == TBL_COMPARISON) {
			$stmt = $this->conn->prepare("SELECT * FROM comparison c WHERE c.time_added >= ? AND HEX(c.mac_addr) = ?");
			$stmt->bind_param("ss", $timestamp, $mac);
		
		} elseif ($table == TBL_RATING) {
			$stmt = $this->conn->prepare("SELECT * FROM rating c WHERE c.time_added >= ? AND HEX(c.mac_addr) = ?");
			$stmt->bind_param("ss", $timestamp, $mac);
		}

		// if preparation and binding were successful...
		if ($stmt != false) {

			$stmt->execute();
			$stmt->store_result();
        	$num_rows = $stmt->num_rows;
        	$stmt->close();
        	return $num_rows > 0;

        // something is wrong, so don't allow anything to be posted.
        } else
        	return true;
	}



	/* --------------------- `comparison` table methods --------------------- */

	/**
	 * Creating a comparison
	 * @param $mac MAC address of device
	 * @param $dewick vote for dewick
	 * @param $carm vote for carm
	 *
	 * @return mysql table id number
	 */
	public function postVote ($mac, $dewick, $carm) {
		date_default_timezone_set('UTC');
		$now = date('Y-m-d H:i:s');

		// convert to int
		$mac = base_convert($mac, 16, 10);

		// Create the table if it doesn't exist
		// not sure when this would be needed. I guess I just feel cool being "ROBUST!"
		// hard code table name
		$create = "CREATE TABLE IF NOT EXISTS `comparison` (
 						`id` int(20) unsigned NOT NULL AUTO_INCREMENT,
 						`dewick` int(1) unsigned NOT NULL DEFAULT '0',
						`carmichael` int(1) unsigned NOT NULL DEFAULT '0',
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
		$insert = "INSERT INTO `comparison` (dewick, carmichael, time_added, mac_addr) 
						VALUES (?, ?, ?, ?)";
		$stmt = $this->conn->prepare($insert);
		$stmt->bind_param("sssi", $dewick, $carm, $now, $mac);

		if (!$stmt)
			return false;

		if ($stmt->execute()) {
			
			// vote now entered
			$stmt->close();
			return true;

		} else {

			// some error happened
			$stmt->close();
			return false;
		}
	}

	/**
	 * Gets a total number of votes for dewick or carm
	 * @param ealiest MySql TIMESTAMP to accept
	 * @return an array of votes for 'dewick' and 'carm'
	 */

	public function totalVotes ($timestamp) {

		$stmt = $this->conn->prepare("SELECT dewick, carmichael FROM comparison c WHERE c.time_added >= ?");
		$stmt->bind_param("s", $timestamp);

		if (!$stmt)
			return NULL;
		
		$stmt->execute();

		// bind result variables
		$stmt->bind_result($dewick, $carm);

		// inititate counters
		$sum_d = 0;
		$sum_c = 0;

		// fetch variables
		while ($row = $stmt->fetch()) {
			
			if ($dewick != 0) {
				
				$sum_d++;		// each entry only represents 1 vote

			} else if ($carm != 0) {
				
				$sum_c++;
			}
		}

		$stmt->close();
		
		return array("dewick" => $sum_d, "carm" => $sum_c); 
	}



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

		$stmt = $this->conn->prepare("SELECT dewick, carmichael FROM TBL_RATING r WHERE r.time_added >= ?");
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