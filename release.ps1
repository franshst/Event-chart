# Define the destination ZIP file
$zipFile = "mod_eventchart.zip"

# Remove the existing ZIP file if it exists
if (Test-Path $zipFile) {
    Remove-Item $zipFile
}

# Create a temporary folder to gather all files to be zipped
$tempFolder = "mod_eventchart_temp"
if (Test-Path $tempFolder) {
    Remove-Item -Recurse -Force $tempFolder
}
New-Item -ItemType Directory -Path $tempFolder

# Copy directories to the temporary folder
Copy-Item -Path "tmpl" -Destination "$tempFolder\tmpl" -Recurse
Copy-Item -Path "language" -Destination "$tempFolder\language" -Recurse
Copy-Item -Path "Helper" -Destination "$tempFolder\Helper" -Recurse

# Copy individual files to the temporary folder
Copy-Item -Path "LICENSE" -Destination $tempFolder
Copy-Item -Path "*.html" -Destination $tempFolder
Copy-Item -Path "*.php" -Destination $tempFolder
Copy-Item -Path "mod_eventchart.xml" -Destination $tempFolder

# Create the ZIP file from the temporary folder
Compress-Archive -Path "$tempFolder\*" -DestinationPath $zipFile

# Clean up the temporary folder
Remove-Item -Recurse -Force $tempFolder

Write-Output "ZIP file created successfully: $zipFile"
