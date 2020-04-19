#!/bin/sh
cd "$(dirname "$0")"

REPOSITORY=$1
VERSION=$2

cd ..
git config --global advice.detachedHead false
git clone --quiet $REPOSITORY --branch $VERSION --depth 1 --single-branch > /dev/null
composer install
git config --global advice.detachedHead true

cd ./public
ln -s ../joomla-cms/index.php index.php
ln -s ../joomla-cms/media media
ln -s ../joomla-cms/templates templates

cd ./administrator
ln -s ../../joomla-cms/administrator/index.php index.php
ln -s ../../joomla-cms/administrator/templates templates
