#!/bin/bash

line="$(grep -o 'title":\s*"[A-Za-z0-9/]*' addon/addon.json)"
addonId="${line##*\"}"
addonDir="target/src/addons/$addonId"
root="$(realpath $(dirname $(dirname $0)))"
cmd="php $(realpath $root/target/cmd.php)"

cd "$root"
php "$root/scripts/build.php"                              || exit
rm -rf "$addonDir/_output" "$addonDir/hashes.json"         || exit
cp -rf "$root/addon/"[^_]* "$root/addon/_data" "$addonDir" || exit
echo "y" | $cmd xf-addon:upgrade "$addonId"
$cmd xf-addon:build-release "$addonId"                     || exit

cd "$addonDir/_releases"
file="$(ls -1t *.zip | head -n1)"
rm -rf upload
unzip -q "$file"                && \
advzip -a4 -i100 "$file" upload && \
rm -rf upload
mv "$file" "$root/addon/_releases"
