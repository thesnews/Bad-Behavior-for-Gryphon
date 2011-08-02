<?php
/*
Bad Behavior - detects and blocks unwanted Web accesses
Copyright (C) 2005-2006 Michael Hampton

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

As a special exemption, you may link this program with any of the
programs listed below, regardless of the license terms of those
programs, and distribute the resulting program, without including the
source code for such programs: ExpressionEngine; Simple Machines Forum

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

Please report any problems to badbots AT ioerror DOT us
*/

###############################################################################
###############################################################################

define('BB2_CWD', dirname(__FILE__));

// Bad Behavior callback functions.

// Return current time in the format preferred by your database.
function bb2_db_date() {
	return gmdate('Y-m-d H:i:s');	// Example is MySQL format
}

// Return affected rows from most recent query.
function bb2_db_affected_rows() {
	$s = \foundry\config::get('bb2LastStatement');
	
	if( $s ) {
		return $s->rowCount();
	}

	return false;
}

// I very, very much dislike this method of database handling
// Everything should be bound, but I don't have much choice here...
function bb2_db_escape($string) {

	$dbh = \foundry\db::get('default');
	$quoted = $dbh->quote($string);
	
	if( $quoted === sprintf("'%s'", $string)
		&& strpos($string, ' ') === false ) {
		return $string;
	}
	
	return sprintf('"%s"', substr($quoted, 1, -1));
}

// Return the number of rows in a particular query.
function bb2_db_num_rows($result) {
	if ($result !== FALSE)
		return count($result);
	return 0;
}

// Run a query and return the results, if any.
// Should return FALSE if an error occurred.
// Bad Behavior will use the return value here in other callbacks.
function bb2_db_query($query) {
	$dbh = \foundry\db::get('default');
	$s = $dbh->prepare($query);

	if( !$s->execute(null, false, false) ) {
		return false;
	}	
	\foundry\config::set('bb2LastStatement', $s);
	
	return $s->fetchAll();	
}

// Return all rows in a particular query.
// Should contain an array of all rows generated by calling mysql_fetch_assoc()
// or equivalent and appending the result of each call to an array.
function bb2_db_rows($result) {
	return $result;
}

// Return emergency contact email address.
function bb2_email() {
	return \foundry\config::get('mail:admin');
}

// retrieve settings from database
// Settings are hard-coded for non-database use
function bb2_read_settings() {
	return array(
		'log_table' => 'dsw_bbLogs',
		'display_stats' => \foundry\config::get('dsw:badbehavior:stats'),
		'strict' => false,
		'verbose' => false,
		'logging' => \foundry\config::get('dsw:badbehavior:log'),
		'httpbl_key' => '',
		'httpbl_threat' => '25',
		'httpbl_maxage' => '30',
		'offsite_forms' => false,
	);
}

// write settings to database
function bb2_write_settings($settings) {
//	return false;
}

// installation
function bb2_install() {
	$settings = bb2_read_settings();
	
	if (!$settings['logging']) return;
	bb2_db_query(bb2_table_structure($settings['log_table']));
}

// Screener
// Insert this into the <head> section of your HTML through a template call
// or whatever is appropriate. This is optional we'll fall back to cookies
// if you don't use it.
function bb2_insert_head() {
	global $bb2_javascript;
	return $bb2_javascript;
}

// Display stats? This is optional.
function bb2_insert_stats($force = false) {
	$settings = bb2_read_settings();

	if ($force || $settings['display_stats']) {
		$blocked = bb2_db_query("SELECT COUNT(*) FROM " . $settings['log_table'] . " WHERE `key` NOT LIKE '00000000'");
		if ($blocked !== FALSE) {
			return sprintf('<p><a href="http://www.bad-behavior.ioerror.us/">Bad Behavior</a> has blocked <strong>%s</strong> access attempts in the last 7 days</p>', $blocked[0]["COUNT(*)"]);
		}
	}
}

// Return the top-level relative path of wherever we are (for cookies)
// You should provide in $url the top-level URL for your site.
function bb2_relative_path() {
	//$url = parse_url(get_bloginfo('url'));
	//return $url['path'] . '/';
	return \foundry\proc::getRelativeWebRoot();
}

?>
