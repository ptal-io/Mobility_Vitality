Please download all 12 months of 2023 trip data for Capital Bikeshare on this website: https://s3.amazonaws.com/capitalbikeshare-data/index.html

The 'Roadway_Block.shp' is here: https://opendata.dc.gov/datasets/6fcba8618ae744949630da3ea12d90eb_163/explore

Lime bikes and Lime e-scooters are collected using 'php' in the 'Lime' file.

Strava data collection and cleaning scripts are also in the 'Strava' file.

While we are unable to share the RAW data for Lime and Strava, we do provide the data collection scripts. For the Lime data, trips are collected from the Lime API in real time.  In order to reproduce what we did, you would need to run the script we provided, every minute, over the course of months to a year, then reconstruct the trips following the documentation outlined in the paper.  For the Strava data, it is possible to get historical data, which is why we provided the list of "activity IDs" (runs).  The challenge here is that there is a rate limit on API requests to Strava (300 requests per hour).  In order to collect the ~10000 activities that were used in our analysis, it would take roughly a few days of requesting from the API (using the provided script).
