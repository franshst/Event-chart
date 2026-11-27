#!/usr/bin/env bash
set -euo pipefail

if [ $# -ne 1 ]; then
    echo "Usage: $0 <version> (e.g. 0.1.4)" >&2
    exit 1
fi

VERSION="${1#v}"
TAG="v${VERSION}"

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "Commit your changes before deploying" >&2
    exit 1
fi

git fetch --tags --quiet
if git rev-parse -q --verify "refs/tags/$TAG" >/dev/null; then
    echo "Tag $TAG already exists" >&2
    exit 1
fi

python3 build.py "$VERSION"

LAST=$(gh release list --limit 1 --json tagName --jq '.[0].tagName')
if [ -n "$LAST" ]; then
    NOTES=$(git log "$LAST..HEAD" --pretty=format:'- %s')
else
    NOTES=$(git log HEAD --pretty=format:'- %s')
fi

git tag "$TAG"
git push origin "$TAG"

gh release create "$TAG" --title "$TAG" --notes "$NOTES" --prerelease mod_eventchart.zip eventchart_update.xml
