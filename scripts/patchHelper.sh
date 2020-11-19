#!/bin/bash

cd "$(dirname $(dirname $0))"

scripts/minify.sh
php scripts/patchHelper.php