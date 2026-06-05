#!/bin/bash
# Run once on SiteGround via SSH after GitHub repo exists.
# Clones repo and symlinks theme/plugin into WordPress dev site.

set -euo pipefail

REPO_URL="${1:-https://github.com/jasoncstyle/mbss-website.git}"
REPO_DIR="$HOME/repos/mbss-website"
WP_CONTENT="$HOME/www/dev.myrtlebeachsailingschool.com/public_html/wp-content"

mkdir -p "$HOME/repos"
if [ ! -d "$REPO_DIR/.git" ]; then
  git clone "$REPO_URL" "$REPO_DIR"
fi

ln -sfn "$REPO_DIR/theme/mbss-theme" "$WP_CONTENT/themes/mbss-theme"
ln -sfn "$REPO_DIR/plugin/mbss-operations" "$WP_CONTENT/plugins/mbss-operations"

echo "Symlinks created. Future updates: cd $REPO_DIR && git pull"
