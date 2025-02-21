<?php

include (LIB.'admin.lib.php');

function dropoff_loc($id) {
	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);

	$query_dropoffs_user = sprintf("SELECT uid FROM %s WHERE brewerDropOff='%s'",$prefix."brewer",$id);
	$dropoffs_user = mysqli_query($connection,$query_dropoffs_user) or die (mysqli_error($connection));
	$dropoffs_user = mysqli_query($connection,$query_dropoffs_user) or die (mysqli_error($connection));
	$row_dropoffs_user = mysqli_fetch_assoc($dropoffs_user);
	$totalRows_dropoffs_user = mysqli_num_rows($dropoffs_user);

	$return = $totalRows_dropoffs_user."^".$row_dropoffs_user['uid'];

	return $return;
}

function location_count($location_id) {

	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);

	$query_dropoff = sprintf("SELECT uid FROM %s WHERE brewerDropOff='%s'",$prefix."brewer",$location_id);
	$dropoff = mysqli_query($connection,$query_dropoff) or die (mysqli_error($connection));
	$row_dropoff = mysqli_fetch_assoc($dropoff);
	$totalRows_dropoff = mysqli_num_rows($dropoff);

	do { $uid[] = $row_dropoff['uid']; } while ($row_dropoff = mysqli_fetch_assoc($dropoff));

	foreach ($uid as $brewBrewerID) {

		$query_dropoffs = sprintf("SELECT COUNT(*) as 'count' FROM %s WHERE brewBrewerID='%s'",$prefix."brewing",$brewBrewerID);
		$dropoffs = mysqli_query($connection,$query_dropoffs) or die (mysqli_error($connection));
		$row_dropoffs = mysqli_fetch_assoc($dropoffs);

		$location_count[] = $row_dropoffs['count'];

	}

	return array_sum($location_count);
}

function dropoff_location_info($location_id) {

	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);

	$query_location_info = sprintf("SELECT id,dropLocation,dropLocationName FROM %s WHERE id='%s'",$prefix."drop_off",$location_id);
	$location_info = mysqli_query($connection,$query_location_info) or die (mysqli_error($connection));
	$row_location_info = mysqli_fetch_assoc($location_info);

	$return =
	$row_location_info['id']."^".
	$row_location_info['dropLocation']."^".
	$row_location_info['dropLocationName'];

	return $return;

}

function entries_by_dropoff_loc($id) {

	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);

	$query_dropoffs = sprintf("SELECT uid FROM %s WHERE brewerDropOff='%s'",$prefix."brewer",$id);
	$dropoffs = mysqli_query($connection,$query_dropoffs) or die (mysqli_error($connection));
	$row_dropoffs = mysqli_fetch_assoc($dropoffs);
	$totalRows_dropoffs = mysqli_num_rows($dropoffs);

	$build_rows = "";

	if ($totalRows_dropoffs > 0) {

		do {

			$query_dropoff_count = sprintf("SELECT * FROM %s WHERE brewBrewerID='%s'",$prefix."brewing",$row_dropoffs['uid']);
			$dropoff_count = mysqli_query($connection,$query_dropoff_count) or die (mysqli_error($connection));
			$row_dropoff_count = mysqli_fetch_assoc($dropoff_count);
			$totalRows_dropoff_count = mysqli_num_rows($dropoff_count);

			if ($totalRows_dropoff_count > 0) {
				do {
					$entry_name = html_entity_decode($row_dropoff_count['brewName'],ENT_QUOTES|ENT_XML1,"UTF-8");
					$entry_name = htmlentities($entry_name,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,"UTF-8");
					$build_rows .= "
						<tr>
							<td>".sprintf("%06s",$row_dropoff_count['id'])."</td>
							<td>".$entry_name."</td>
							<td>".$row_dropoff_count['brewBrewerLastName'].", ".$row_dropoff_count['brewBrewerFirstName']."</td>
							<td><p class=\"box_small\"></p></td>
						</tr>
				";

				} while ($row_dropoff_count = mysqli_fetch_assoc($dropoff_count));

			} // end if ($totalRows_dropoff_count > 0)

		} while ($row_dropoffs = mysqli_fetch_assoc($dropoffs));

	} // end if ($totalRows_dropoffs > 0)

	return $build_rows;
}

// --------------------------------------------------------
// The following apply to:	/output/email_export.php
//							/output/entries_export.php
// --------------------------------------------------------

function parseCSVComments($comments) {

	// First, escape all " and make them ""
	$comments = str_replace('"', '""', $comments);
	$comments = preg_replace("/[\n\r]/","",$comments);

	// Check if any commas or new lines
	if(eregi(",", $comments) or eregi("\n", $comments) or eregi("\t", $comments) or eregi("\r", $comments) or eregi("\v", $comments)) {

		// If new lines or commas and escape them
		return '"'.$comments.'"';

	}

	// If no new lines or commas just return the value
	else return $comments;
}

function filename($input) {

	if ($input == "default") $return = "";
	else {
		$return = str_replace('_', ' ',$input);
		$return = ucwords($return);
		$return = "_".str_replace(' ','_',$return);
	}
	return $return;
}

// --------------------------------------------------------
// The following applies to /output/entry.output.php
// --------------------------------------------------------

function pay_to_print($prefs_pay,$entry_paid) {
	if (($prefs_pay == "Y") && ($entry_paid == "1")) return TRUE;
	elseif (($prefs_pay == "Y") && ($entry_paid == "0")) return FALSE;
	elseif ($prefs_pay == "N") return TRUE;
}



// --------------------------------------------------------
// The following applies to /output/labels.php
// --------------------------------------------------------

function truncate($string, $your_desired_width, $append="") {

  $parts = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
  $parts_count = count($parts);

  $length = 0;
  $last_part = 0;

  for (; $last_part < $parts_count; ++$last_part) {
    $length += strlen($parts[$last_part]);
    if ($length > $your_desired_width) { 
    	break; 
    }
  }

  $r = implode(array_slice($parts, 0, $last_part));
  
  if (strlen($string) > $your_desired_width) {
  	$r = rtrim($r);
  	$r .= $append;
  }

  return $r;
}

function user_entry_count($uid,$view) {

	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);

	$judging_numbers = array();
	$entry_numbers = array();
	$user_entry_numbers = "";
	$user_judging_numbers = "";

	if ($view == "entry") $sort = "id";
	else $sort = "brewJudgingNumber";

	$query_with_entries_count = sprintf("SELECT DISTINCT id,brewJudgingNumber FROM %s WHERE brewBrewerID='%s' AND brewReceived='1' ORDER BY %s ASC", $prefix."brewing",$uid,$sort);
	$with_entries_count = mysqli_query($connection,$query_with_entries_count) or die (mysqli_error($connection));
	$row_with_entries_count = mysqli_fetch_assoc($with_entries_count);
	$totalRows_with_entries_count = mysqli_num_rows($with_entries_count);

	if ($totalRows_with_entries_count > 0) {
		do {
			$judging_numbers[] = sprintf("%06d",$row_with_entries_count['brewJudgingNumber']);
			$entry_numbers[] = sprintf("%06d",$row_with_entries_count['id']);
		} while($row_with_entries_count = mysqli_fetch_assoc($with_entries_count));

		$user_judging_numbers = implode(", ",array_unique($judging_numbers));
		$user_entry_numbers = implode(", ",array_unique($entry_numbers));
	}

	return $totalRows_with_entries_count."^".$user_entry_numbers."^".$user_judging_numbers;

}

// --------------------------------------------------------
// The following applies to /output/staff_points.php
// --------------------------------------------------------

function round_down_to_hundred($number) {
    if (strlen($number)<3) { $number = $number;	}
	else { $number = substr($number, 0, strlen($number)-2) . "00";	}
    return $number;
}

function total_days() {
	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);

	$query_sessions = sprintf("SELECT judgingDate FROM %s", $prefix."judging_locations");
	$sessions = mysqli_query($connection,$query_sessions) or die (mysqli_error($connection));
	$row_sessions = mysqli_fetch_assoc($sessions);

	do {
		$a[] = getTimeZoneDateTime($_SESSION['prefsTimeZone'], $row_sessions['judgingDate'], $_SESSION['prefsDateFormat'],  $_SESSION['prefsTimeFormat'], "system", "date-no-gmt");
	} while ($row_sessions = mysqli_fetch_assoc($sessions));

	$output = array_unique($a);
	$output = count($output);
	return $output;

}

function total_sessions() {
	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);

	$query_sessions = sprintf("SELECT COUNT(*) as 'count' FROM %s", $prefix."judging_locations");
	$sessions = mysqli_query($connection,$query_sessions) or die (mysqli_error($connection));
	$row_sessions = mysqli_fetch_assoc($sessions);

	/*
	do {
		$a[] = $row_sessions['judgingRounds'];
	} while ($row_sessions = mysqli_fetch_assoc($sessions));
	*/

	return $row_sessions['count'];

}

function total_flights() {
	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);
	$query_tables = sprintf("SELECT id FROM %s", $prefix."judging_tables");
	$tables = mysqli_query($connection,$query_tables) or die (mysqli_error($connection));
	$row_tables = mysqli_fetch_assoc($tables);

	do {
		$a[] = $row_tables['id'];
	} while ($row_tables = mysqli_fetch_assoc($tables));

	foreach ($a as $table_id) {
		$query_table_flights = sprintf("SELECT flightNumber FROM %s WHERE flightTable='%s' ORDER BY flightNumber DESC LIMIT 1", $prefix."judging_flights", $table_id);
		$table_flights = mysqli_query($connection,$query_table_flights) or die (mysqli_error($connection));
		$row_table_flights = mysqli_fetch_assoc($table_flights);
		$b[] = $row_table_flights['flightNumber'];
	}

	$query_style_types = sprintf("SELECT COUNT(*) AS 'count' FROM %s WHERE styleTypeBOS='Y'",$prefix."style_types");
	$style_types = mysqli_query($connection,$query_style_types) or die (mysqli_error($connection));
	$row_style_types = mysqli_fetch_assoc($style_types);
	$b[] = $row_style_types['count'];
	$output = array_sum($b);
	return $output;

}

function validate_bjcp_id($input) {
	$length = strlen($input);
	if ($length != 5) return FALSE;
	elseif (!preg_match('([a-zA-Z])',$input)) return FALSE;
	else return TRUE;
}

function total_points($total_entries,$method) {

	// Get the maximum allowable points for all roles
	// According to the Maximum Points Earned (Table 1) table - https://dev.bjcp.org/about/reference/experience-point-award-schedule/

	$points = 0;

	switch ($method) {

		case "Organizer":
			if (($total_entries >= 1) && ($total_entries <= 49)) $points = 2.0;
			elseif (($total_entries >= 50) && ($total_entries <= 99)) $points = 2.5;
			elseif (($total_entries >= 100) && ($total_entries <= 149)) $points = 3.0;
			elseif (($total_entries >= 150) && ($total_entries <= 199)) $points = 3.5;
			elseif (($total_entries >= 200) && ($total_entries <= 299)) $points = 4.0;
			elseif (($total_entries >= 300) && ($total_entries <= 399)) $points = 4.5;
			elseif (($total_entries >= 400) && ($total_entries <= 499)) $points = 5.0;
			elseif ($total_entries >= 500) $points = 6.0;
			else $points = 0;
		break;

		case "Staff":
			if (($total_entries >= 1) && ($total_entries <= 49)) $points = 1.0;
			if (($total_entries >= 50) && ($total_entries <= 99)) $points = 2.0;
			if (($total_entries >= 100) && ($total_entries <= 149)) $points = 3.0;
			if (($total_entries >= 150) && ($total_entries <= 199)) $points = 4.0;
			if (($total_entries >= 200) && ($total_entries <= 299)) $points = 5.0;
			if (($total_entries >= 300) && ($total_entries <= 399)) $points = 6.0;
			if (($total_entries >= 400) && ($total_entries <= 499)) $points = 7.0;
			if (($total_entries >= 500) && ($total_entries <= 599)) $points = 8.0;
			if ($total_entries > 599) {
				$total = round_down_to_hundred($total_entries)/100;
				//$points = $total;
				if ($total >= 2) {
					for($i=1; $i<$total+1; $i++) {
						$points = $i+3;
					}
				}
			}
		break;

		case "Judge":
			if (($total_entries >= 1) && ($total_entries <= 49)) $points = 1.5;
			elseif (($total_entries >= 50) && ($total_entries <= 99)) $points = 2.0;
			elseif (($total_entries >= 100) && ($total_entries <= 149)) $points = 2.5;
			elseif (($total_entries >= 150) && ($total_entries <= 199)) $points = 3.0;
			elseif (($total_entries >= 200) && ($total_entries <= 299)) $points = 3.5;
			elseif (($total_entries >= 300) && ($total_entries <= 399)) $points = 4.0;
			elseif (($total_entries >= 400) && ($total_entries <= 499)) $points = 4.5;
			elseif ($total_entries >= 500) $points = 5.5;
			else $points = 0;
		break;

	}

	return number_format($points,1);

}

function judge_points($user_id,$judge_max_points) {

	/*
	 * To figure out judge points, need to assess:
	 *  - Which sessions the judge was assigned to
	 *  - Which day those sessions were on
	 *  - For each day:
	 *    - Determine how many sessions the judge was assigned to and award 0.5 points for each
	 *    - Make sure that number is a minimum of 1.0 and a maximum of 1.5
	 *  - Sum up the daily points
	 *  - Compare that sum to the maximum judge points based upon the table; if more use the max, if less, use the sum
	 */
	
	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);
	require (INCLUDES.'url_variables.inc.php');
	require (INCLUDES.'db_tables.inc.php');
	require (DB.'judging_locations.db.php');

	$possible_judging_days = array();
	$days_judged = array();

	$points = 0;

	$query_judging = sprintf("SELECT * FROM %s", $prefix."judging_locations");
	$judging = mysqli_query($connection,$query_judging) or die (mysqli_error($connection));
	$row_judging = mysqli_fetch_assoc($judging);
	$totalRows_judging = mysqli_num_rows($judging);

	do {

		// Get date and determine 24 hour window where it falls based upon the time zone
		$timestamp_curr_day_midnight = strtotime(date("Y-m-d", $row_judging['judgingDate']));
		$timestamp_next_day_midnight = $timestamp_curr_day_midnight + (60 * 60 * 24);
		$possible_judging_days[] = $timestamp_curr_day_midnight;

		$query_assignments = sprintf("SELECT COUNT(*) as 'count' FROM %s WHERE bid='%s' AND assignLocation='%s' AND assignment='J'", $prefix."judging_assignments", $user_id, $row_judging['id']);
    $assignments = mysqli_query($connection,$query_assignments) or die (mysqli_error($connection));
    $row_assignments = mysqli_fetch_assoc($assignments);

    if ($row_assignments['count'] > 0) {
			$days_judged[] = array (
				"day_midnight" => $timestamp_curr_day_midnight,
				"points" => $row_assignments['count'] * 0.5,
			);
		}

	} while ($row_judging = mysqli_fetch_assoc($judging));

	$possible_judging_days = array_unique($possible_judging_days);

	if (!empty($days_judged)) {
		foreach ($possible_judging_days as $judging_day) {
			foreach ($days_judged as $day) {		
				$point_day = 0;
				if ($day['day_midnight'] == $judging_day) {
					$point_day += $day['points'];
				}
				if ($point_day > 1.5) $points += 1.5;
				else $points += $point_day;
			}
		}
	}

	// Cannot exceed the maximum allowable points for judges for the competition
	if ($points > $judge_max_points) $points = $judge_max_points;
	else $points = $points;

	// If points are below the 1.0 minimum, award minimum
	if ($points < 1) $points = 1;
	else $points = $points;

	return number_format($points,1);

}

function steward_points($user_id) {

	/*
	 * To figure out steward points, need to assess:
	 *  - Which sessions the steward was assigned to
	 *  - Which day those sessions were on
	 *  - For each day:
	 *    - Determine how many sessions the steward was assigned to and award 0.5 points for each
	 *    - Make sure that number is a minimum of 0.5 and a maximum of 1.0 for the entire competition
	 *  - Sum up the daily points
	 */

	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);
	require (INCLUDES.'url_variables.inc.php');
	require (INCLUDES.'db_tables.inc.php');
	require (DB.'judging_locations.db.php');

	$possible_judging_days = array();
	$days_stewarded = array();

	$points = 0;

	$query_judging = sprintf("SELECT * FROM %s", $prefix."judging_locations");
	$judging = mysqli_query($connection,$query_judging) or die (mysqli_error($connection));
	$row_judging = mysqli_fetch_assoc($judging);
	$totalRows_judging = mysqli_num_rows($judging);

	$queries = "";

	do {

		// Get date and determine 24 hour window where it falls based upon the time zone
		$timestamp_curr_day_midnight = strtotime(date("Y-m-d", $row_judging['judgingDate']));
		$timestamp_next_day_midnight = $timestamp_curr_day_midnight + (60 * 60 * 24);
		$possible_judging_days[] = $timestamp_curr_day_midnight;

		$query_assignments = sprintf("SELECT COUNT(*) as 'count' FROM %s WHERE bid='%s' AND assignLocation='%s' AND assignment='S';", $prefix."judging_assignments", $user_id, $row_judging['id']);
    $assignments = mysqli_query($connection,$query_assignments) or die (mysqli_error($connection));
    $row_assignments = mysqli_fetch_assoc($assignments);

    $queries .= $query_assignments." ";

    if ($row_assignments['count'] > 0) {
			$days_stewarded[] = array (
				"day_midnight" => $timestamp_curr_day_midnight,
				"points" => $row_assignments['count'] * 0.5,
			);
		}

	} while ($row_judging = mysqli_fetch_assoc($judging));

	$possible_judging_days = array_unique($possible_judging_days);

	if (!empty($days_stewarded)) {

		foreach ($possible_judging_days as $judging_day) {
			foreach ($days_stewarded as $day) {		
				$point_day = 0;
				if ($day['day_midnight'] == $judging_day) {
					$point_day += $day['points'];
				}
				if ($point_day > 0.5) $points += 0.5;
				else $points += $point_day;
			}
		}

		// Cannot exceed more than 1.0 points per competition
		if ($points > 1.0) $points = 1.0; 
		else $points = $points;

	}

	//return $user_id;

	return number_format($points,1);

}

function bos_points($uid) {
	include (CONFIG.'config.php');
	mysqli_select_db($connection,$database);
	require(INCLUDES.'db_tables.inc.php');
	$query_bos_judges = sprintf("SELECT staff_judge_bos FROM %s WHERE uid='%s'",$prefix."staff",$uid);
	$bos_judges = mysqli_query($connection,$query_bos_judges) or die (mysqli_error($connection));
	$row_bos_judges = mysqli_fetch_assoc($bos_judges);

	if ($row_bos_judges['staff_judge_bos'] == 1) return TRUE;
	else return FALSE;
}


// --------------------------------------------------------
// The following applies to /output/pullsheets.php
// --------------------------------------------------------

function number_of_flights($table_id) {
    require(CONFIG.'config.php');
    mysqli_select_db($connection,$database);

	$query_flights = sprintf("SELECT flightNumber FROM %s WHERE flightTable='%s' ORDER BY flightNumber DESC LIMIT 1", $prefix."judging_flights", $table_id);
    $flights = mysqli_query($connection,$query_flights) or die (mysqli_error($connection));
    $row_flights = mysqli_fetch_assoc($flights);

	$r = $row_flights['flightNumber'];
	return $r;
}

function check_flight_number($entry_id,$flight) {
	require(CONFIG.'config.php');
    mysqli_select_db($connection,$database);

	$query_flights = sprintf("SELECT flightNumber,flightRound FROM %s WHERE flightEntryID='%s'", $prefix."judging_flights", $entry_id);
    $flights = mysqli_query($connection,$query_flights) or die (mysqli_error($connection));
    $row_flights = mysqli_fetch_assoc($flights);

	if ($row_flights['flightNumber'] == $flight) $r = $row_flights['flightRound'];
	else $r = "";
	return $r;

}

function check_flight_round($flight_round,$round) {

	if ($round == "default") {
		if ($flight_round != "") return TRUE;
		else return FALSE;
	}

	if ($round != "default") {
		if (($flight_round != "") && ($flight_round == $round)) return TRUE;
		else return FALSE;
	}

}

/*
function style_type_info($id) {
	require(CONFIG.'config.php');
    mysqli_select_db($connection,$database);

	$query_style_type = sprintf("SELECT * FROM %s WHERE id='%s'",$prefix."style_types",$id);
	$style_type = mysqli_query($connection,$query_style_type) or die (mysqli_error($connection));
	$row_style_type = mysqli_fetch_assoc($style_type);

	$return =
	$row_style_type['styleTypeBOS']."^".  // 0
	$row_style_type['styleTypeBOSMethod']."^".  // 1
	$row_style_type['styleTypeName'];  // 2

	return $return;
}
*/

function results_count($style) {
	require(CONFIG.'config.php');
    mysqli_select_db($connection,$database);

	$query_entry_count = sprintf("SELECT COUNT(*) as 'count' FROM %s WHERE brewCategorySort='%s' AND brewReceived='1'", $prefix."brewing",  $style);
	$entry_count = mysqli_query($connection,$query_entry_count) or die (mysqli_error($connection));
	$row_entry_count = mysqli_fetch_assoc($entry_count);

	$query_score_count = sprintf("SELECT  COUNT(*) as 'count' FROM %s a, %s b, %s c WHERE b.brewCategorySort='%s' AND a.eid = b.id AND a.scorePlace IS NOT NULL AND c.uid = b.brewBrewerID", $prefix."judging_scores", $prefix."brewing", $prefix."brewer", $style);
	$score_count = mysqli_query($connection,$query_score_count) or die (mysqli_error($connection));
	$row_score_count = mysqli_fetch_assoc($score_count);

	return $row_entry_count['count']."^".$row_score_count['count'];

}

function get_flight_info($id) {
	require(CONFIG.'config.php');
    mysqli_select_db($connection,$database);

    $query_flights = sprintf("SELECT * FROM %s WHERE flightEntryID='%s'", $prefix."judging_flights", $id);
    $flights = mysqli_query($connection,$query_flights) or die (mysqli_error($connection));
    $row_flights = mysqli_fetch_assoc($flights);
    $totalRows_flights = mysqli_num_rows($flights);

    if ($totalRows_flights > 0) {
	    $query_tables = sprintf("SELECT id,tableName,tableNumber FROM %s WHERE id='%s'", $prefix."judging_tables", $row_flights['flightTable']);
		$tables = mysqli_query($connection,$query_tables) or die (mysqli_error($connection));
		$row_tables = mysqli_fetch_assoc($tables);

		$return = array(
			"response" => "Assigned",
			"id" => $row_tables['id'],
			"tableName" => $row_tables['tableName'],
			"tableNumber" => $row_tables['tableNumber'],
			"flightNumber" => $row_flights['flightNumber'],
			"flightRound" => $row_flights['flightRound']
		);
	}

	else $return = array("response" => "Not assigned to a table.");

	return $return;
}

?>