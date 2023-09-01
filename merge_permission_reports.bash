#!/bin/bash

# Check if a directory is provided as an argument
if [ $# -ne 1 ]; then
    echo "Usage: $0 <directory>"
    exit 1
fi

# Directory containing the CSV files
directory="$1"

# Create a temporary directory to store the unique CSV files
temp_dir=$(mktemp -d)

# Check if the temporary directory was created successfully
if [ $? -ne 0 ]; then
    echo "Error: Failed to create a temporary directory."
    exit 1
fi

# Initialize a flag to track whether the header has been added to the merged file
header_added=false

# Loop through all CSV files in the directory
for file in "$directory"/*.csv; do
    # Calculate the MD5 hash of the file's content
    file_hash=$(md5sum "$file" | cut -d ' ' -f 1)

    # Check if a file with the same hash already exists in the temporary directory
    if [ ! -f "$temp_dir/$file_hash.csv" ]; then
        # If not, copy the file to the temporary directory
        cp "$file" "$temp_dir/$file_hash.csv"
        
        # Append the content of the CSV file (excluding the header) to the merged file
        if [ "$header_added" = false ]; then
            head -n 1 "$file" > "$directory/merged.csv"
            header_added=true
        fi
        tail -n +2 "$file" >> "$directory/merged.csv"
    fi
done

# Clean up the temporary directory
rm -rf "$temp_dir"

echo "Processing complete. Merged CSV file is saved as $directory/merged.csv."
