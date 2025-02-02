import os

# Boilerplate placeholders for plugin details
plugin_details = {
    "name": "Plugin Name",  # Placeholder for Plugin Name
    "namespace": "Namespace",  # Placeholder for Namespace
    "textdomain": "textdomain",  # Placeholder for Text Domain
    "author": "Author Name",  # Placeholder for Author
    "website": "https://example.com",  # Placeholder for Plugin Website
    "support_url": "https://buymeacoffee.com/example",  # Placeholder for Support URL
    "license": "GNU General Public License v2.0 or later"
}

# Internal structure definition
structure = {
    "src": {
        "Admin": ["AdminMenu.php"],
        "Core": ["Plugin.php", "SEOHandler.php", "Autoloader.php"],
        "Integration": ["YoastSEO.php", "AIOSEO.php", "RankMath.php"],
        "Frontend": ["FrontendDisplay.php"]
    },
    "assets": {
        "css": ["style.css", "admin.css"],
        "js": ["main.js"]
    },
    "templates": {
        "admin": ["dashboard.php"],
        "frontend": []
    },
    "vendor": [],
    "": ["metafiller.php", "composer.json", "README.md", "LICENSE.txt"]
}

# Base content for files
file_contents = {
    "php": """<?php

// {filename} file.

namespace {namespace}\\{namespace_subfolder};

// Class definition here.

""",
    "composer.json": """{
    "name": "{author}/{textdomain}",
    "description": "A powerful plugin for automated SEO meta field management.",
    "type": "wordpress-plugin",
    "license": "{license}",
    "autoload": {
        "psr-4": {
            "{namespace}\\\\": "src/"
        }
    }
}""",
    "README.md": """# {name}

{name} was created with love by {author} to help you automate SEO meta field management effortlessly.

## License

This plugin is licensed under the {license}.

## Support {name}
If you enjoy using {name}, consider supporting its development:
- **Buy me a coffee:** [{support_url}]({support_url})
- **Visit Plugin Website:** [{website}]({website})

Your contributions help keep this plugin maintained and improved!""",
    "LICENSE.txt": """GNU GENERAL PUBLIC LICENSE
Version 2, June 1991

Copyright (C) 2025 {author}

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

---

## About This Plugin
{name} was created with love by {author}. If you enjoy using this plugin, consider supporting its development!

Visit: [{website}]({website})
Support: [{support_url}]({support_url})
"""
}

# Helper function to write files with boilerplate
def write_file(file_path, content):
    with open(file_path, "w") as f:
        f.write(content)

# Create the internal structure
def create_structure(base, structure, plugin_details):
    for folder, contents in structure.items():
        folder_path = os.path.join(base, folder)
        os.makedirs(folder_path, exist_ok=True)
        if isinstance(contents, dict):  # Nested structure
            create_structure(folder_path, contents, plugin_details)
        elif isinstance(contents, list):  # List of files
            for file in contents:
                file_path = os.path.join(folder_path, file)
                ext = file.split(".")[-1]
                if ext == "php":
                    namespace = folder.replace("/", "\\")
                    content = file_contents.get("php", "").format(
                        filename=file,
                        namespace=plugin_details["namespace"],
                        namespace_subfolder=namespace
                    )
                else:
                    content = file_contents.get(file, "").format(**plugin_details)
                write_file(file_path, content)

# Gather plugin details
def gather_plugin_details():
    print("Fill in the plugin details (press Enter to use defaults):")
    for key, default_value in plugin_details.items():
        value = input(f"{key.capitalize()} [{default_value}]: ").strip()
        plugin_details[key] = value if value else default_value

# Main execution
if __name__ == "__main__":
    print("Welcome to the Plugin Boilerplate Generator!")
    gather_plugin_details()
    create_structure(".", structure, plugin_details)
    print(f"\nBoilerplate for {plugin_details['name']} has been successfully created!")
