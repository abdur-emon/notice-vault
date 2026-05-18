#!/usr/bin/env bash
#
# build.sh — produce a clean wp.org-ready copy of Notice Tracker.
#
# Run this before invoking Plugin Check or before uploading to wp.org
# SVN. The resulting directory at ./build/notice-tracker/ contains only
# the files that should ship: no .gitignore, no .git, no .wordpress-org
# (that one is uploaded separately to /assets/), no dev docs, no editor
# cruft.
#
# Usage:
#   ./build.sh                     # produces ./build/notice-tracker/
#   wp plugin check ./build/notice-tracker --severity=5  # clean check
#
# Idempotent — safe to re-run.

set -euo pipefail

PLUGIN_SLUG="notice-tracker"
SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEST_DIR="${SRC_DIR}/build/${PLUGIN_SLUG}"

# Wipe any prior build output.
rm -rf "${SRC_DIR}/build"
mkdir -p "${DEST_DIR}"

# rsync everything except the items below. Trailing slash on the src is
# intentional — copies contents, not the directory itself.
rsync -a \
	--exclude='.git' \
	--exclude='.github' \
	--exclude='.gitignore' \
	--exclude='.gitattributes' \
	--exclude='.wordpress-org' \
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
	--exclude='build.sh' \
	--exclude='*.zip' \
	--exclude='*.tar.gz' \
	"${SRC_DIR}/" "${DEST_DIR}/"

echo "Built clean copy at: ${DEST_DIR}"
echo
echo "Next steps:"
echo "  wp plugin check ${DEST_DIR}"
echo "  zip -r notice-tracker.zip ${DEST_DIR}"
