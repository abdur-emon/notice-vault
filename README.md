# Notice Vault

Capture WordPress admin notices into a centralized popup, so the dashboard stays clean and nothing gets lost.

[![Requires at least](https://img.shields.io/badge/Requires_at_least-5.0-blue.svg)]()
[![Requires PHP](https://img.shields.io/badge/Requires_PHP-7.2-blue.svg)]()
[![License](https://img.shields.io/badge/License-GPLv2_or_later-success.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

The full plugin description, feature list, installation steps, FAQ, screenshots, changelog, and upgrade notes live in [`readme.txt`](readme.txt) — that's the file WordPress.org renders on the plugin's listing page. Please edit that file when updating user-facing copy; this `README.md` only exists for GitHub browsers.

## Building for WordPress.org

The dev tree contains files that should not ship to the wp.org plugin directory: `.git`, `.gitignore`, `.wordpress-org` (uploaded separately to the SVN `/assets/` folder, not the plugin zip), `README.md`, `CHANGELOG.md`, etc. Run the following from the plugin root to produce a clean `./build/notice-vault/` copy that contains only the files that should ship:

```sh
rm -rf build && mkdir -p build/notice-vault && \
rsync -a \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='.gitignore' \
    --exclude='.gitattributes' \
    --exclude='.wordpress-org' \
    --exclude='.claude' \
    --exclude='.claude.local' \
    --exclude='phpcs.xml.dist' \
    --exclude='phpcs.xml' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='.idea' \
    --exclude='.vscode' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='build' \
    --exclude='dist' \
    --exclude='README.md' \
    --exclude='CHANGELOG.md' \
    --exclude='*.zip' \
    --exclude='*.tar.gz' \
    ./ build/notice-vault/
```

Point Plugin Check (and the wp.org SVN/zip submission) at `build/notice-vault/`, not at the dev folder. The `build/` directory is git-ignored.

## Author

Developed and maintained by Abdur Rahman Emon and Md. Ismail. Issues and pull requests welcome.

## License

GPL v2 or later. See [`license-uri`](https://www.gnu.org/licenses/gpl-2.0.html).
