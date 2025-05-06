#!/bin/bash

line="$(grep -o 'title":\s*"[A-Za-z0-9/]*' addon/addon.json)"
addonId="${line##*\"}"
addonDir="target/src/addons/$addonId"
root="$(realpath $(dirname $(dirname $0)))"
cmd="php $(realpath $root/target/cmd.php)"

cd "$root"
sed -i "s/\(version_string.*\)\",/\\1-php74\",/" addon/addon.json
php "$root/scripts/build.php"                              || exit
./vendor/bin/rector process addon/                         || exit
rm -rf "$addonDir/_output" "$addonDir/hashes.json"         || exit
cp -rf "$root/addon/"[^_]* "$root/addon/_data" "$addonDir" || exit
echo "y" | $cmd xf-addon:upgrade "$addonId"
if [ -z $(grep "mastodon\\\\.social/\\." "$root/addon/_data/bb_code_media_sites.xml") ];
then
	echo "Bad Mastodon";
	exit;
fi
$cmd xf-addon:build-release "$addonId"                     || exit

cd "$addonDir/_releases"
file="$(ls -1t *.zip | head -n1)"
rm -rf upload
unzip -q "$file"                && \
advzip -a4 -i100 "$file" upload && \
rm -rf upload
mv "$file" "$root/addon/_releases"
