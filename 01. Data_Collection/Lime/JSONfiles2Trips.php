<?php
/* ===================================================================================
        File: mm2trips.php
        Author: BLINDED FOR REVIEW
        Description: Read RAW micromobility JSON files and convert to a TRIPS csv file
        Run Script:
            php mm2trips.php /data/ lime lime_dc_trips.csv
====================================================================================== */

// PARAMETERS
$threshold_duration_min = 121; // 121 seconds in order to be identified as a trip
$threshold_distance_min = 100; // 100 meters in order to be identified as a trip
$threshold_duration_max = 7200; // 2 hours

// Three parameters are necessary for this script.
if (count($argv) < 3) {
        echo "\n\tThree parameters are required to run this script:\n\t1. City name (full or relative path to folder with data).\n\t2. Operator name (file prefix).\n\t3. Full path of output file.\n\n";
        exit;
}

// Arguments
$city = $argv[1];
$operator = $argv[2];
$outputfilename = $argv[3];

// Create the output file handler
$fout = fopen($outputfilename,'w');

// Set things up for data loop
$bikes = array();

// Start time for running the script
$start = mktime();

// Get the timestamps in the directory specified in argument 1
$files = scandir($city);
$total = count($files);
$cnt = 0;
$step = 0;

// Keep track of files with errors
$errorfiles = array();

// Sort the files alphabetically (timestamps contain timestamps)
natcasesort($files);

// Tell me how we are progressing
echo "Percentage of files processed:\n";

// Loop through JSON files and get contents
foreach($files as $filei) {
    
    if (strpos($filei, ".json") !== false) {
        getContents($filei);
    }
    // Only show me every 1%
    $cnt++;
    $progress = $cnt/$total * 100;
    if (floor($progress) == $step) {
        echo "\t".$step . "%\n";
        $step += 5;
    }
}

// Close the file
fclose($fout);

// Get time again to calculate amount of time the script took to run.
$end = mktime();
echo "Script took ".(round((($end - $start)/60)*10)/10)." minutes to process ".$total." files.\n";
echo count($errorfiles) . " of these were either empty of corrupt.\n";

// Do the heavy lifting.
function getContents($filei) {
    // This is terrible.  Never use global variables.  Do as I say, not as I do.
    global $city;
    global $operator;
    global $bikes;
    global $fout;
    global $threshold_duration_min;
    global $threshold_duration_max;
    global $threshold_distance_min;
    global $errorfiles;

    // Read the contents of each JSON file
    $contents = json_decode(file_get_contents($city."/".$filei), false);
    
    // Convert the filename to a timestamp
    $timestamp = str_replace($operator."_","",str_replace(".json","",$filei));

    // Make sure there is actually some data in there
    if ($contents && property_exists($contents, 'data')) {

        // Write out the filename/timestamp (for debugging)
    	// echo $timestamp . "\n";

        // Loop through vehicles
        // Not that this changes depending on API.  For example, skip doesn't have a data object, just a bikes object.
        foreach($contents->data->bikes as $bike) {

            // Get LATITUDE, LONGITUDE, BIKE_ID for each vehicle
            $lat = $bike->lat;
            $lon = $bike->lon;
            $bikeid = $bike->BICYCLE_ID;
            $type = $bike->vehicle_type;

            // Check to see if a vehicle with that BIKE_ID already exists in the global bikes dictionary.
            if(isset($bikes[$bikeid])) {

                // If it does, calculate the time difference and distance difference from the last identified occurence in the dictionary.
                $diff_time = intval($timestamp) - $bikes[$bikeid]->ts;
                $diff_dist = haversine($bikes[$bikeid]->lat,$bikes[$bikeid]->lon, $lat, $lon);

                // Check the differences against the thresholds (top of file).  If they meet this criteria, they are a trip.
                if ($diff_dist > $threshold_distance_min and $diff_time > $threshold_duration_min and $diff_time < $threshold_duration_max) {

                    // Build a string containing origin, destination, start time, duration, euclidean distance and write to the output file.
                    $content = $bikeid . "," . $bikes[$bikeid]->lat . "," . $bikes[$bikeid]->lon  . "," . $lat . "," . $lon . "," . $bikes[$bikeid]->ts . "," . $diff_time . "," . $diff_dist . ",".$type."\n";
                    fwrite($fout, $content);
                }
            }

            // Update the bikes dictionary with the latest coordinates and time for the BIKE_ID
            $bikes[$bikeid] = (Object)array('bikeid'=>$bikeid,'lat'=>$lat, 'lon'=>$lon,'ts'=>intval($timestamp), 'type'=>$type);
        }

    } else {
        // If there is no data (or corrupt) file, tell me which one.
    	//echo "No Data\t" . $timestamp . "\n";
        $errorfiles[] = $timestamp;
    }
}


// Simple function to caculate distance in meters.  Not great, but works for our purposes.
function haversine($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}


?>