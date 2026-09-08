#!/usr/bin/env bash
set -euo pipefail

# Laravel Moat audits GitHub org/repo security posture.
# Without a GitHub remote or token, we verify the binary + moat.toml are present.

if ! command -v moat >/dev/null 2>&1; then
  echo "moat binary not found on PATH. Install from https://github.com/laravel/moat/releases"
  exit 1
fi

if [[ ! -f moat.toml ]]; then
  echo "moat.toml is missing from the repository root."
  exit 1
fi

ACCOUNT="${MOAT_ACCOUNT:-}"
if [[ -z "$ACCOUNT" ]]; then
  REMOTE="$(git remote get-url origin 2>/dev/null || true)"
  if [[ "$REMOTE" =~ github.com[:/](.+/[^/.]+)(\.git)?$ ]]; then
    ACCOUNT="${BASH_REMATCH[1]}"
  fi
fi

if [[ -z "$ACCOUNT" ]]; then
  echo "Moat ready: binary OK, moat.toml present."
  echo "No GitHub remote/account detected — skipping live audit."
  echo "Set MOAT_ACCOUNT=owner/repo (or add an origin remote) to audit."
  exit 0
fi

TOKEN="$(gh auth token 2>/dev/null || true)"
if [[ -z "${GITHUB_TOKEN:-}" && -n "$TOKEN" ]]; then
  export GITHUB_TOKEN="$TOKEN"
fi

exec moat "$ACCOUNT" --format markdown
