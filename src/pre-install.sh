#!/bin/sh
cd "$(dirname "$0")"

read -p "Joomla version: " VERSION
php ./pre-install.php $VERSION
