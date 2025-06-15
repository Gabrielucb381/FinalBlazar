#!/bin/bash

for file in /seeders/*.json; do
  collection=$(basename "$file" .json)

  if [ -f "$file" ]; then
    echo "Importing $file into collection $collection..."
    mongoimport --db=blazar --authenticationDatabase=admin --username=mongodb --password=blazar --collection="$collection" --jsonArray --file="$file"
  else
    echo "File $file not found. Skipping $collection."
  fi
done