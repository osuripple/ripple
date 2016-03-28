#!/usr/bin/env bash

# Run composer in various directories.
originaldir=$(pwd)
for i in 'osu.ppy.sh/blog'; do
	cd $i
	composer install
	cd $originaldir
done
