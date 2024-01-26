#!/bin/bash

cd "$(dirname $(dirname $(realpath $0)))"

while [ 1 ];
do
	cp -r addon/* target/src/addons/s9e/MediaSites;
	sleep 1;
done