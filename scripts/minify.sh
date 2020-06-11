#!/bin/bash

cd "$(dirname $(dirname $0))"

BIN=./vendor/node_modules/google-closure-compiler-linux/compiler
if [[ ! -f "$BIN" ]];
then
	cd vendor
	npm i google-closure-compiler-linux
	cd ..
fi

"$BIN" --js src/LazyLoad.js -O ADVANCED --js_output_file src/LazyLoad.min.js