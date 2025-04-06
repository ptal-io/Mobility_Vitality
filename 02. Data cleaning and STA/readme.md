After downloading the dataset, extract all data files into the same folder as your code to ensure proper execution. Put the 'Roadway_Block.shp' inside as well. For example, place all 12 Capital Bikeshare CSV files and 'Roadway_Block.shp' in the folder containing Jupyter notebook '2.1.Capital Bikeshare_data cleaning and STA.ipynb'

Here are the scripts for cleaning and doing STA analysis (Figure 5) for these four datasets. Please run the codes in order, as you will need the former file in the latter code, especially for LIME data.

For each type of data cleaning script, it may take more than 3 days to run. 

To make sure the parallel processing works:
If you're running the Jupyter Notebook on your own laptop instead of using Colab, make sure to import the functions defined in the "generate shortest path as trajectory" cell. To ensure that parallel processing works correctly, add the following line at the top of that cell in the '2.1.Capital Bikeshare_data cleaning and STA.ipynb' notebook: from utils import *
