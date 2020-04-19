#!/bin/php
<?php

print PHP_EOL.'Checking ...'.PHP_EOL;

if (count($_SERVER['argv']) < 2) {
    print 'composer create-project gelysis/joomla-installer <project folder> <semantic version>'.PHP_EOL;
} else {
    $references = $versions = [];
    $exactMatch = false;

    $repository = 'https://github.com/joomla/joomla-cms.git';
    $version = ($_SERVER['argv'][2] ?? NULL);

    $command = 'git ls-remote --heads --refs --tags --sort "v:refname" '.$repository.' | sed "s/.*\///"';
    exec($command, $references);

    $stableVersion = '#^([0-9]+\.){2}[0-9]+$#';
    $semanticVersioning = '#^([0-9]+\.){1,2}([0-9]+|-[a-z]){2}\w*$#';

    if ($version === NULL) {
        foreach ($references as $key => $ref) {
            if (!preg_match($stableVersion, $ref)) {
                unset($references[$key]);
            }
        }

        $exactMatch = count($references) > 0;
        if ($exactMatch) {
            $version = array_pop($references);
        }
    } elseif (in_array($version, $references)) {
        $exactMatch = true;
    } else {
        foreach ($references as $key => $ref) {
            if (preg_match($semanticVersioning, $ref) && strpos($ref, $version) === 0) {
                $versions[] = $ref;
            }
        }
    }

    if ($exactMatch) {
        print 'Klone und installiere Joomla CMS '.$version
            .' / Cloning and installing Joomla CMS '.$version.' ...'.PHP_EOL;
        exec(__DIR__.'/setup-application.sh '.$repository.' '.$version);
    } else {
        print 'Diese Version existiert nicht. / Version does not exists.'.PHP_EOL;
        if (!empty($versions)) {
            print 'Meinten Sie: / Did you mean: '.implode(', ', $versions).PHP_EOL;
        }
    }
}

print PHP_EOL;
