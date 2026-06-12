#!/usr/bin/env bash
# Build the Adobe Commerce Marketplace package ZIP for Ecomail_Ecomail.
# Usage: ./tools/build-marketplace-package.sh [version]
set -euo pipefail

VERSION="${1:-2.3.0}"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MODULE_ROOT="$REPO_ROOT/app/code/Ecomail/Ecomail"
DIST_ROOT="$REPO_ROOT/dist"
PACKAGE="$DIST_ROOT/ecomail-magento2-ecomail-$VERSION.zip"

[ -d "$MODULE_ROOT" ] || { echo "Module root not found: $MODULE_ROOT" >&2; exit 1; }

mkdir -p "$DIST_ROOT"
rm -f "$PACKAGE"

cd "$MODULE_ROOT"
if command -v zip >/dev/null 2>&1; then
    zip -r -X "$PACKAGE" . \
        -x ".git/*" -x ".github/*" -x "var/*" -x "generated/*" -x "pub/*" -x "dist/*"
else
    python3 - "$PACKAGE" <<'PY'
import os, sys, zipfile
package = sys.argv[1]
excluded = {'.git', '.github', 'var', 'generated', 'pub', 'dist'}
with zipfile.ZipFile(package, 'w', zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk('.'):
        parts = os.path.normpath(root).split(os.sep)
        if parts and parts[0] in excluded:
            continue
        dirs[:] = [d for d in dirs if not (root == '.' and d in excluded)]
        for name in sorted(files):
            path = os.path.join(root, name)
            zf.write(path, os.path.relpath(path, '.'))
PY
fi

echo "Created $PACKAGE"
