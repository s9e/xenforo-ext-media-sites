#!/bin/bash
cd "$(dirname $0)/.."
php scripts/build.php
rm -rf addon/_output addon/hashes.json
echo "y" | php cmd.php xf-addon:upgrade s9e/MediaSites
php cmd.php xf-addon:build-release s9e/MediaSites

cd addon/_releases
file="$(ls -1t *.zip | head -n1)"
rm -rf upload
unzip -q "$file"          && \
kzip -r -y "$file" upload && \
advzip -z4 "$file"        && \
rm -rf upload
