

import os
import shutil
import subprocess
import hashlib
import xml.etree.ElementTree as ET

# Configuration
js_path = "js"
zip_file = "mod_eventchart.zip"
update_xml = "eventchart_update.xml"
temp_folder = "mod_eventchart_temp"
seven_zip_path = "7z"  # Assuming 7z is in the system PATH

def minify_js():
    """
    Minifies JavaScript files in the specified directory.
    """
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
            except (subprocess.CalledProcessError, FileNotFoundError) as e:
                print(f"Error minifying {input_file}: {e}")
                print("Please ensure uglify-js is installed and in your PATH (npm install -g uglify-js)")
                return

def create_zip():
    """
    Creates a ZIP archive of the module.
    """
    print("Creating ZIP archive...")
    if os.path.exists(zip_file):
        os.remove(zip_file)

    if os.path.exists(temp_folder):
        shutil.rmtree(temp_folder)
    os.makedirs(temp_folder)

    # Copy directories
    shutil.copytree("tmpl", os.path.join(temp_folder, "tmpl"))
    shutil.copytree("language", os.path.join(temp_folder, "language"))
    shutil.copytree("Helper", os.path.join(temp_folder, "Helper"))
    shutil.copytree("js", os.path.join(temp_folder, "js"))

    # Copy files
    shutil.copy("LICENSE", temp_folder)
    for filename in os.listdir("."):
        if filename.endswith(".php") or filename.endswith("mod_eventchart.xml"):
            shutil.copy(filename, temp_folder)

    try:
        shutil.make_archive(zip_file.replace(".zip", ""), 'zip', temp_folder)
        print(f"ZIP file created successfully: {zip_file}")
    except Exception as e:
        print(f"Error creating ZIP file: {e}")
    finally:
        shutil.rmtree(temp_folder)


def update_checksum():
    """
    Updates the checksum in the update XML file.
    """
    print("Updating checksum in XML file...")
    sha256 = hashlib.sha256()
    with open(zip_file, "rb") as f:
        for byte_block in iter(lambda: f.read(4096), b""):
            sha256.update(byte_block)
    checksum = sha256.hexdigest()
    print(f"Checksum: {checksum}")

    tree = ET.parse(update_xml)
    root = tree.getroot()
    update_node = root.find("update")
    sha256_node = update_node.find("sha256")
    if sha256_node is not None:
        sha256_node.text = checksum
    else:
        sha256_node = ET.SubElement(update_node, "sha256")
        sha256_node.text = checksum

    tree.write(update_xml)
    print("Updated XML file with checksum.")

if __name__ == "__main__":
    minify_js()
    create_zip()
    update_checksum()
    input("Press any key to continue...")

