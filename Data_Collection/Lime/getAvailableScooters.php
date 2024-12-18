<?php

    // URL to get the Lime data for Washington DC
    $url = "https://data.lime.bike/api/partners/v1/gbfs/washington_dc/free_bike_status";

    // Where to store the data (data directory) and prepend file with name
    $path = "data/lime_";
    requestData($url, $path);

    function requestData($url, $path) {

        $ch = curl_init($url);
        $fp = fopen($path.time().".json", "w");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

?>