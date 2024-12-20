<?php

/*
	@author BLINDED FOR REVIEW
	@email BLINDED FOR REVIEW
	@date December 2024

	@description
	This script loops through a CSV file containing a list of Strava Running Activity IDs and downloads the JSON trajectory data for those activities.
	A folder called "acts" needs to exist in the same directory as this script and needs to be writable.
	A file called "strava_activity_ids.csv" needs to exist in the same directory as this script.

	Before doing anything, create a strava account, login, and get the "cookie" used to track your logged in session.  Put this cookie in a file called "cookie.inc" (just the string of characters).  Make sure it is in the same directory as the script.
*/

	// You NEED a cookie to ensure that you are a logged in user.
	$cookie = file_get_contents('cookie.inc');
	
	$activities = array();

	// This is the CSV file containing all the activity IDs.
	$file = fopen("strava_activity_ids.csv","r");

	// Loop through and get the contents of the CSV file in an array
	while(!feof($file))  {
		$activities[] = trim(fgets($file));
	}
	fclose($file);

	// Look through each activity ID, make a request to get the JSON content of the activity, and save it to a file if it does not already exist.
	foreach($activities as $actid) {
		$json = getActivity($actid);
		$actname = "acts/".$actid.".json";

		# If the activity file does not exist, write it.
		if (!file_exists($actname)) {
			$fout = fopen($actname,"w");
			fwrite($fout, json_encode($json));
			fclose($fout);
			echo "\t\tNew Activity file created.\n";
		} 
	}

	// This does the heavy lifting of getting the Activity JSON contents (GPS fixes and Time)
	function getActivity($id) {

		// Note that you need the cookie
		global $cookie;
	
		// Base URL with activity ID appended.
		$url = "https://www.strava.com/activities/".$id."/streams?stream_types[]=time&stream_types[]=latlng";


		// The CURL command to go and get the actual data.
		$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($ch, CURLOPT_URL,$url);
		$json=curl_exec($ch);
		$json = json_decode($json);

		// Send the JSON data back to be saved.
		return $json;
	}

?>