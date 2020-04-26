#!/bin/sh
cd "$(dirname "$0")"

REPOSITORY=$1
VERSION=$2
FOLDER=$3

cd ..
git config --global advice.detachedHead false
git clone --quiet $REPOSITORY --branch $VERSION --depth 1 --single-branch $FOLDER > /dev/null
git config --global advice.detachedHead true

rm -rf $FOLDER/.git composer.*
mv $FOLDER/composer.* ./

cd ./public
ln -s ../joomla-cms/index.php index.php
ln -s ../joomla-cms/installation installation
ln -s ../joomla-cms/media media
ln -s ../joomla-cms/templates templates

cd ./administrator
ln -s ../../joomla-cms/administrator/index.php index.php
ln -s ../../joomla-cms/administrator/templates templates
