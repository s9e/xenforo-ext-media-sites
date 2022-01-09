#!/bin/bash

line="$(grep -o 'title":\s*"[A-Za-z0-9/]*' addon/addon.json)"
addonId="${line##*\"}"

cd "$(dirname $0)/.."
php scripts/build.php                                  || exit
rm -rf addon/_output addon/hashes.json                 || exit
echo "y" | php cmd.php xf-addon:upgrade "$addonId"
php cmd.php xf-addon:build-release "$addonId"

cd addon/_releases
file="$(ls -1t *.zip | head -n1)"
rm -rf upload
unzip -q "$file"                && \
advzip -a4 -i100 "$file" upload && \
rm -rf upload
