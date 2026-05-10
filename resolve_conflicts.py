import sys
import re

def resolve_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    pattern = re.compile(r'<<<<<<< HEAD\n.*?\n=======\n(.*?)\n>>>>>>> [a-f0-9]+\n', re.DOTALL)
    
    resolved = pattern.sub(r'\1\n', content)
    
    with open(filepath, 'w') as f:
        f.write(resolved)

files = [
    'includes/admin/class-notice-popup.php',
    'includes/admin/class-settings-page.php',
    'includes/core/class-plugin.php',
    'includes/toolbar/class-admin-toolbar.php',
    'uninstall.php'
]

for file in files:
    resolve_file(file)

