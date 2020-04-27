#!/bin/sh
cd "$(dirname "$0")"

REPOSITORY=$1
VERSION=$2
FOLDER=$3

cd ..
git config --global advice.detachedHead false
git clone --quiet $REPOSITORY --branch $VERSION --depth 1 --single-branch $FOLDER > /dev/null
git config --global advice.detachedHead true

rm -rf composer.* src vendor $FOLDER/.git
mv $FOLDER/composer.* ./

cd ./public
ln -s ../$FOLDER/index.php index.php
ln -s ../$FOLDER/installation installation
ln -s ../$FOLDER/media media
ln -s ../$FOLDER/templates templates

cd ./administrator
ln -s ../../$FOLDER/administrator/index.php index.php
ln -s ../../$FOLDER/administrator/templates templates
