#!/bin/bash
cd "$(dirname $0)/.."
php scripts/build.php
rm -rf addon/_output addon/hashes.json
echo "y" | php cmd.php xf-addon:upgrade s9e/MediaSites
php cmd.php xf-addon:build-release s9e/MediaSites
advzip -z4 "$(ls -1t addon/_releases/*.zip | head -n1)"