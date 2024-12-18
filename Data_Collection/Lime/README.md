### Lime Data Collection

We are unable to share the raw Lime e-scooter and e-bike trip data used in this paper.  We are able to share the scripts used to collect the data as well as the process to recreate trips.

The URL provided in the file `getAvailableScooters.php` provides a JSON response with the IDs and geographic coordinates of all available vehicles in Washington, D.C., at the current time.  We created a cronjob that ran this script every minute over the course of 2023.

The script `JSONfiles2Trips.php` is as PHP script that loops through all the JSON files of 'available vehicles' and reconstructs trips based on when a vehicle disappears in the data and then when it reappears in the data.  The output is a CSV file containing the trip origins and destinations and well as timestamps.