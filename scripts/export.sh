#!/bin/bash

cd "$(dirname $(dirname $(realpath $0)))"

line="$(grep -o 'title":\s*"[A-Za-z0-9/]*' addon/addon.json)"
addonId="${line##*\"}"
addonDir="target/src/addons/$addonId"
root="$(realpath $(dirname $(dirname $0)))"
cmd="php $(realpath $root/target/cmd.php)"

cd "$root"
$cmd xf-addon:export "$addonId" || exit
/bin/cp -frv "$addonDir/_data" "addon/"
