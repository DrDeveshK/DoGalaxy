#!/usr/bin/env bash
# Copy core + one child theme onto a cPanel WordPress docroot.
# Usage (on server):  ./bin/deploy.sh doswagat
# Usage (dry-run):    ./bin/deploy.sh doswagat --dry-run
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="${1:-}"
DRY="${2:-}"
if [[ -z "$SLUG" || "$SLUG" == "-h" ]]; then
  echo "usage: $0 <slug> [--dry-run]"
  python3 -c "import json; p=json.load(open('$ROOT/products.json'));
print('\n'.join(f'  {k:16} {v[\"domain\"]}' for k,v in p.items()))"
  exit 1
fi
python3 - "$ROOT" "$SLUG" "$DRY" <<'PY'
import json, shutil, sys
from pathlib import Path
root, slug, dry = Path(sys.argv[1]), sys.argv[2], sys.argv[3]
products = json.loads((root / "products.json").read_text())
if slug not in products:
    sys.exit(f"unknown slug: {slug}")
p = products[slug]
src_core = root / "themes" / "dogalaxy-core"
src_child = root / "themes" / slug
dest = Path(p["docroot"]) / "wp-content" / "themes"
print(f"{slug} → {dest}")
if dry == "--dry-run":
    print("dry-run: would copy", src_core, "and", src_child)
    raise SystemExit(0)
if not dest.parent.exists():
    sys.exit(f"WP not found at {p['docroot']} — install WordPress via Softaculous first")
dest.mkdir(parents=True, exist_ok=True)
shutil.copytree(src_core, dest / "dogalaxy-core", dirs_exist_ok=True)
shutil.copytree(src_child, dest / slug, dirs_exist_ok=True)
print("copied dogalaxy-core +", slug)
print("WP Admin → Appearance → activate", p["name"])
PY
