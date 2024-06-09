# create a release zip file
# minifies js
# create installation zip
# adds checksum to update xml
# to be installed on your windows: 7-zip, node.js, npm install uglifyjs

# Set the path to the directory containing the .js files
$jsPath = "js"

# Define the destination ZIP file
$zipFile = "mod_eventchart.zip"

# Define update xml
$updateXml = "eventchart_update.xml"


# Minify
# Remove all existing .min.js files in the directory
Get-ChildItem -Path $jsPath -Filter *.min.js | ForEach-Object {
    Remove-Item $_.FullName
}

# Loop through each .js file in the directory
Get-ChildItem -Path $jsPath -Filter *.js | ForEach-Object {
    # Get the full path of the current .js file
    $inputFile = $_.FullName

    # Create the output file path by replacing .js with .min.js
    $outputFile = [System.IO.Path]::ChangeExtension($inputFile, ".min.js")

    # Execute uglifyjs to compress the file
    uglifyjs $inputFile -o $outputFile
}

# Zip
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
Copy-Item -Path "js" -Destination "$tempFolder\js" -Recurse

# Copy individual files to the temporary folder
Copy-Item -Path "LICENSE" -Destination $tempFolder
Copy-Item -Path "*.php" -Destination $tempFolder
Copy-Item -Path "*mod_eventchart.xml" -Destination $tempFolder

# Create the ZIP file from the temporary folder
# Path to 7z.exe
$sevenZipPath = "C:\Program Files\7-Zip\7z.exe"

# Windows zip does not work with standard PHP unzip
# Compress-Archive -Path "$tempFolder\*" -DestinationPath $zipFile

# Create the ZIP file using 7-Zip
& "$sevenZipPath" a -tzip "$zipFile" ".\$tempFolder\*"

# Check if the ZIP file was created successfully
if (Test-Path $zipFile) {
    Write-Output "ZIP file created successfully: $zipFile"
} else {
    Write-Output "Failed to create ZIP file"
}

# Clean up the temporary folder
Remove-Item -Recurse -Force $tempFolder


# Create checksum

# Function to calculate SHA256 checksum
function Get-FileChecksum($filePath) {
    $sha256 = [System.Security.Cryptography.SHA256]::Create()
    $fileStream = [System.IO.File]::Open($filePath, [System.IO.FileMode]::Open, [System.IO.FileAccess]::Read)
    $checksumBytes = $sha256.ComputeHash($fileStream)
    $fileStream.Close()
    $checksum = [BitConverter]::ToString($checksumBytes) -replace '-', ''
    return $checksum
}

# Get the checksum of the module file
$checksum = Get-FileChecksum $zipFile
Write-Output "Checksum: $checksum"

# Load the XML file
[xml]$xml = Get-Content $updateXml

# Navigate to the updates/update node
$updateNode = $xml.updates.update

# Update or add the sha256 element in the XML file
if ($updateNode.sha256) {
    $updateNode.sha256 = $checksum
} else {
    $newElement = $xml.CreateElement("sha256")
    $newElement.InnerText = $checksum
    $updateNode.AppendChild($newElement) | Out-Null
}

# Save the updated XML file
$xml.Save($updateXml)

Write-Output "Updated XML file with checksum."
