import os
import shutil
import subprocess
import hashlib
import xml.etree.ElementTree as ET
import sys

# Get version from CLI
version = sys.argv[1] if len(sys.argv) > 1 else None
if not version:
    print("Usage: python build.py <version>")
    exit(1)

# Configuration
zip_name = f"mod_eventchart.zip"
temp_folder = "mod_eventchart_temp"

source_mod_xml = os.path.join("update", "mod_eventchart.xml")
source_update_xml = os.path.join("update", "eventchart_update.xml")

target_mod_xml = "mod_eventchart.xml"
target_update_xml = "eventchart_update.xml"

def minify_js():
    js_path = "js"
    print("Minifying JavaScript files...")
    for filename in os.listdir(js_path):
        if filename.endswith(".min.js"):
            os.remove(os.path.join(js_path, filename))

    for filename in os.listdir(js_path):
        if filename.endswith(".js") and not filename.endswith(".min.js"):
            input_file = os.path.join(js_path, filename)
            output_file = os.path.join(js_path, filename.replace(".js", ".min.js"))
            try:
                subprocess.run(["uglifyjs", input_file, "-o", output_file], check=True)
            except Exception as e:
                print(f"Error minifying {filename}: {e}")
                return

def copy_and_update_version(source, destination):
    shutil.copy(source, destination)
    tree = ET.parse(destination)
    root = tree.getroot()
    version_tag = root.find("version")
    if version_tag is None:
        version_tag = ET.SubElement(root, "version")
    version_tag.text = version
    tree.write(destination)
    print(f"✔ Version updated in {destination}")

def prepare_update_xml():
    print("Preparing update XML...")
    with open(source_update_xml, "r", encoding="utf-8") as f:
        content = f.read()

    content = content.replace("{VERSION}", version)

    with open(target_update_xml, "w", encoding="utf-8") as f:
        f.write(content)

    print("✔ eventchart_update.xml prepared with version:", version)

def update_checksum(zip_file):
    print("Updating checksum in eventchart_update.xml...")
    sha256 = hashlib.sha256()
    with open(zip_file, "rb") as f:
        for block in iter(lambda: f.read(4096), b""):
            sha256.update(block)
    checksum = sha256.hexdigest()

    tree = ET.parse(target_update_xml)
    root = tree.getroot()

    update_node = root.find("update")
    if update_node is None:
        update_node = ET.SubElement(root, "update")

    sha256_node = update_node.find("sha256")
    if sha256_node is not None:_
