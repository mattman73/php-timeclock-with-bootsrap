#!/usr/bin/env bash
# update-dashboard.sh - pull the latest Jondo dashboard code from
# GitHub into an existing folder on the Pi, preserving node_modules
# and your local .env file.
#
# Usage:
#   ./update-dashboard.sh [/path/to/your/dashboard]
#
# If you don't pass a path it uses $HOME/Jondotimeclock-dashboard.
# After the first run with the right path you can just type:
#   ./update-dashboard.sh
#
# How it works:
#   - turns the folder into a git repo (if it isn't already) and
#     points it at the GitHub remote;
#   - fetches the latest commit;
#   - "git reset --hard origin/<default-branch>" overwrites tracked
#     files (your old source) with the GitHub version;
#   - it does NOT run "git clean", so untracked files - including
#     node_modules and .env - are left exactly where they were.
#
# A defensive .env backup is taken first, just in case .env was ever
# accidentally committed to the repo (it shouldn't be).

set -euo pipefail

REPO_URL="https://github.com/mattman73/Jondotimeclock-dashboard.git"
DASHBOARD_DIR="${1:-$HOME/Jondotimeclock-dashboard}"

if [ ! -d "$DASHBOARD_DIR" ]; then
    echo "ERROR: folder not found: $DASHBOARD_DIR" >&2
    echo "Pass the real path:" >&2
    echo "  $0 /home/jondo/your-dashboard-folder" >&2
    exit 1
fi

cd "$DASHBOARD_DIR"
echo "Updating dashboard at: $(pwd)"

# 1. Defensive .env backup (only meaningful if .env was ever tracked
#    in the repo; untracked files survive reset --hard anyway).
BACKUP=""
if [ -f .env ]; then
    BACKUP=$(mktemp /tmp/jondo-env.XXXXXX)
    cp -p .env "$BACKUP"
    trap 'rm -f "$BACKUP"' EXIT
fi

# 2. Remember the current package-lock hash so we can tell whether
#    dependencies changed in this update.
OLD_LOCK=""
[ -f package-lock.json ] && OLD_LOCK=$(sha256sum package-lock.json | awk '{print $1}')

# 3. Make the folder a git repo if it isn't one yet, and make sure
#    origin points at the GitHub URL.
if [ ! -d .git ]; then
    echo "Initialising git in this folder..."
    git init -q
fi
if git remote get-url origin >/dev/null 2>&1; then
    git remote set-url origin "$REPO_URL"
else
    git remote add origin "$REPO_URL"
fi

# 4. Fetch the latest from GitHub.
echo "Fetching from $REPO_URL ..."
git fetch --prune origin

# 5. Find the remote's default branch (usually main, sometimes master).
BRANCH=$(git ls-remote --symref origin HEAD 2>/dev/null \
    | awk '/^ref:/ {print $2}' | sed 's@^refs/heads/@@')
if [ -z "$BRANCH" ]; then
    if git rev-parse --verify origin/main >/dev/null 2>&1; then
        BRANCH=main
    else
        BRANCH=master
    fi
fi
echo "Updating to origin/$BRANCH ..."

# 6. Hard-reset tracked files to match the remote.
#    Untracked files (node_modules, .env, local notes) are NOT
#    touched - we deliberately do NOT run "git clean".
git reset --hard "origin/$BRANCH"

# 7. Restore .env if the reset somehow removed it (would only happen
#    if .env was tracked, which would be a security bug in the repo).
if [ -n "$BACKUP" ] && [ ! -f .env ]; then
    cp -p "$BACKUP" .env
    echo ".env restored from backup."
fi

echo
echo "Now at: $(git rev-parse --short HEAD)  ($(git log -1 --format=%s))"

# 8. Tell the user whether dependencies need reinstalling.
NEED_INSTALL=0
if [ ! -d node_modules ]; then
    NEED_INSTALL=1
elif [ -n "$OLD_LOCK" ] && [ -f package-lock.json ]; then
    NEW_LOCK=$(sha256sum package-lock.json | awk '{print $1}')
    [ "$OLD_LOCK" != "$NEW_LOCK" ] && NEED_INSTALL=1
fi
echo
if [ "$NEED_INSTALL" = 1 ]; then
    echo "package-lock.json changed (or node_modules is missing)."
    echo "Run:  cd \"$DASHBOARD_DIR\" && npm install --omit=dev"
else
    echo "package-lock.json unchanged - no npm install needed."
fi

echo
echo "Reload the service to pick up the new code:"
echo "  sudo systemctl restart jondo-dashboard.service"
