#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:-$ROOT/tests/artifacts}"
python3 "$ROOT/tests/run_browser_smoke.py" "$OUT"
