import matplotlib.pyplot as plt
import seaborn as sns
import pandas as pd
import geopandas as gpd
import networkx as nx
from shapely.geometry import Point, LineString
from scipy.spatial import KDTree
import numpy as np
import os
from concurrent.futures import ProcessPoolExecutor, as_completed
import pytz
import glob

from utils import *
# Read road shapefile, its CRS is EPSG:4326
road_network = gpd.read_file('Roadway_Block.shp')

# Build a road network diagram
G = nx.Graph()
nodes = []

for _, row in road_network.iterrows():
    geom = row.geometry
    if geom and geom.geom_type == 'LineString':
        coords = list(geom.coords)
        nodes.extend(coords)
        for i in range(len(coords) - 1):
            G.add_edge(coords[i], coords[i + 1], weight=LineString([coords[i], coords[i + 1]]).length)

# Remove duplicate nodes and create KDTree
unique_nodes = list(set(nodes))
node_coords = np.array(unique_nodes)
kdtree = KDTree(node_coords[:, :2])

# Function to find the nearest node
def get_nearest_node(point, kdtree, node_coords):
    dist, idx = kdtree.query([point.x, point.y])
    return tuple(node_coords[idx])

# Function to process a batch
def process_batch(batch_df):
    paths = []
    for _, row in batch_df.iterrows():
        start_point = Point(row['start_lng'], row['start_lat'])
        end_point = Point(row['end_lng'], row['end_lat'])

        if start_point.is_empty or end_point.is_empty:
            paths.append(None)
            continue

        start_node = get_nearest_node(start_point, kdtree, node_coords)
        end_node = get_nearest_node(end_point, kdtree, node_coords)

        if start_node and end_node:
            try:
                shortest_path_nodes = nx.shortest_path(G, source=start_node, target=end_node, weight='weight')
    # Read road shapefile, its CRS is EPSG:4326
                if len(shortest_path_nodes) > 1:
                    shortest_path = LineString(shortest_path_nodes)
                    paths.append(shortest_path)
                else:
                    paths.append(None)
            except nx.NetworkXNoPath:
                paths.append(None)
        else:
            paths.append(None)
    return batch_df.assign(geometry=paths)

# Functions for processing batches in parallel
def process_batches_in_parallel(df, batch_size, num_workers, month):
    num_batches = len(df) // batch_size + 1
    futures = []
    with ProcessPoolExecutor(max_workers=num_workers) as executor:
        for i in range(num_batches):
            batch_df = df.iloc[i * batch_size: (i + 1) * batch_size]
            print(f"Submitting batch {i + 1} of {num_batches}...")
            futures.append(executor.submit(process_batch, batch_df))

        for i, future in enumerate(as_completed(futures)):
            batch_result = future.result()
            # Save each batch as GeoJSON
            batch_output_file = f'capitalbike_shortest_paths_{month}_{i + 1}.geojson'
            gdf_batch = gpd.GeoDataFrame(batch_result, geometry='geometry')
            gdf_batch.crs = "EPSG:4326"
            gdf_batch.to_file(batch_output_file, driver='GeoJSON')
            print(f"Batch {i + 1} processed and saved to {batch_output_file}")

# Merge GeoJSON files
def merge_geojson(file_paths, output_file_path):
    gdf_list = [gpd.read_file(file) for file in file_paths]
    combined_gdf = gpd.GeoDataFrame(pd.concat(gdf_list, ignore_index=True))
    combined_gdf.to_file(output_file_path, driver='GeoJSON')
    print(f"GeoJSON files have been successfully merged into {output_file_path}")

# Function to clean and calculate new columns, and then save as GeoJSON
def clean_and_calculate_columns(combined_gdf, road_block_gdf, month):
    combined_gdf = combined_gdf.to_crs(epsg=3559)
    combined_gdf['length'] = combined_gdf.geometry.length
    combined_gdf['speed'] = combined_gdf['length'] / combined_gdf['duration']
    cleaned_gdf = combined_gdf[(combined_gdf['speed'] >= 1) & (combined_gdf['speed'] <= 9)].copy()
    cleaned_gdf['started_at'] = pd.to_datetime(cleaned_gdf['started_at'])
    cleaned_gdf['Y_or_D'] = cleaned_gdf['started_at'].dt.weekday.apply(lambda x: 'Y' if x < 5 else 'D')

    def determine_period(hour):
        if 6 <= hour <= 9:
            return 1
        elif 10 <= hour <= 15:
            return 2
        elif 16 <= hour <= 19:
            return 3
        else:
            return 4

    cleaned_gdf['period'] = cleaned_gdf['started_at'].dt.hour.apply(determine_period)
    cleaned_gdf['hour'] = cleaned_gdf['started_at'].dt.hour
    cleaned_gdf['started_at'] = cleaned_gdf['started_at'].astype(str)
    cleaned_gdf['ended_at'] = cleaned_gdf['ended_at'].astype(str)

    output_file_path_clean = f'capitalbike_tra{month}_clean.geojson'
    cleaned_gdf.to_file(output_file_path_clean, driver='GeoJSON')
    print(f"Cleaned GeoJSON saved to {output_file_path_clean}")

