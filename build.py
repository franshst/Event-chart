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
zip_name = f"mod_eventchart_v{version}.zip"
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

def copy_update_and_add_checksum(zip_file):
    shutil.copy(source_update_xml, target_update_xml)
    tree = ET.parse(target_update_xml)
    root = tree.getroot()
    version_tag = root.find("version")
    if version_tag is None:
        version_tag = ET.SubElement(root, "version")
    version_tag.text = version

    sha256 = hashlib.sha256()
    with open(zip_file, "rb") as f:
        for block in iter(lambda: f.read(4096), b""):
            sha256.update(block)
    checksum = sha256.hexdigest()

    update_node = root.find("update")
    sha256_node = update_node.find("sha256") if update_node is not None else None
    if sha256_node is not None:
        sha256_node.text = checksum
    elif update_node is not None:
        ET.SubElement(update_node, "sha256").text = checksum

    tree.write(target_update_xml)

def create_zip():
    print("Creating ZIP archive...")
    if os.path.exists(zip_name):
        os.remove(zip_name)
    if os.path.exists(temp_folder):
        shutil.rmtree(temp_folder)

    os.makedirs(temp_folder)

    # Copy required folders
    for folder in ["tmpl", "language", "Helper", "js"]:
        shutil.copytree(folder, os.path.join(temp_folder, folder))

    # Copy necessary files
    shutil.copy("LICENSE", temp_folder)
    shutil.copy(target_mod_xml, os.path.join(temp_folder, "mod_eventchart.xml"))
    for file in os.listdir("."):
        if file.endswith(".php"):
            shutil.copy(file, temp_folder)

    shutil.make_archive(zip_name.replace(".zip", ""), 'zip', temp_folder)
    shutil.rmtree(temp_folder)
    print(f"✅ ZIP file created: {zip_name}")

if __name__ == "__main__":
    minify_js()
    copy_and_update_version(source_mod_xml, target_mod_xml)
    create_zip()
    copy_update_and_add_checksum(zip_name)
    print(f"\nDone!\n  - Created: {zip_name}\n  - Updated: {target_mod_xml}, {target_update_xml}")
