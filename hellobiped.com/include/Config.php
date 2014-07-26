<?php

/**
 * Database configuration
 *
 * @author Aaron Bowen
 * Date: 6/24/14
 */

// Credentials
define('DB_USERNAME', 'areano');
define('DB_PASSWORD', 'foosphere');
define('DB_HOST', 'mysql.hellobiped.com');
define('DB_NAME', 'dining');

// DB constants - tables and table fields
define('TBL_COMPARISON', 'comparison');
define('TBL_RATING', 'rating');	

define('TBL_USERS', 'users');
	define('FIELD_ID', 'id');
	define('FIELD_MAC', 'mac_addr');
	define('FIELD_IPV4', 'ipv4_addr');
	define('FIELD_IPV6', 'ipv6_addr');
	define('FIELD_DATE_ADDED', 'date_added');
	define('FIELD_LAST_UPDATED', 'last_updated');

define('TBL_VENUES', 'venues');
	define('FIELD_NAME', 'name');
	define('FIELD_CLASS', 'class');
	define('CLASS_DINING_HALL', 'dining_hall');
	define('CLASS_DINING_HALL', 'take-out');

define('TBL_RECIPES', 'recipes');
	define('CLASS_ENTREE', 'entree');


define('TBL_VENUE_VOTE_TYPES', 'venue_vote_types');
define('TBL_RECIPE_VOTE_TYPES', 'recipe_vote_types');
	define('FIELD_VALUE', 'value');
	define('VOTE_NAME', 'vote_name');
	define('POS_VOTE', 'positive');		// CHANGED FROM NAME_VENUE... WASN'T CHANGED IN DBHANDLER
	define('NEG_VOTE', 'negative');

define('TBL_USER_VENUE_VOTES', 'user_venue_votes');
	define('FIELD_USER_ID', 'user_id');
	define('FIELD_VENUE_ID', 'venue_id');
	define('FIELD_VENUE_VOTE_ID', 'venue_vote_id');

define('TBL_USER_RECIPE_VOTES', 'user_recipe_votes');	
	define('FIELD_RECIPE_ID', 'recipe_id');
	define('FIELD_RECIPE_VOTE_ID', 'recipe_vote_id');


// Response constants
define('ERROR_TAG', 'error');
define('URL_TAG', 'url');
define('MESSAGE_TAG', 'message');

// Boolean constants
define('SENTINEL', false);

// Numeric constants
define('USER_CREATED_SUCCESSFULLY', 0);
define('USER_CREATE_FAILED', 1);
define('USER_ALREADY_EXISTED', 2);

?>