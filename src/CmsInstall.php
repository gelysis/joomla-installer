<?php
/**
 * @package GjInstaller
 * @author gelysis <age@gelysis.org>
 * @license http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause - Please check LICENSE.md for more information
 * @copyright Copyright ©2020 Andreas Gerhards
 */

declare(strict_types = 1);
namespace GjInstaller;

use Composer\Script\Event;

class CmsInstall
{

    public function execute(Event $event)
    {
        /** @var \Composer\IO\ConsoleIO $io */
        $io = $event->getIO();

        $references = $versions = [];
        $exactMatch = false;

        $repository = 'https://github.com/joomla/joomla-cms.git';
        $version = $io->ask('Joomla version: ');

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
            $io->write('Klone und installiere Joomla CMS '.$version
                .' / Cloning and installing Joomla CMS '.$version.' ...');
            exec(__DIR__.'/install-cms.sh '.$repository.' '.$version);
        } else {
            $io->write('Diese Version existiert nicht. / Version does not exists.');
            if (!empty($versions)) {
                $io->write('Meinten Sie: / Did you mean: '.implode(', ', $versions));
            }
        }
    }

}
