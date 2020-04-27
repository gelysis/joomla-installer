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

    /** @var string self::DEFAULT_LANGUAGE */
    protected const DEFAULT_LANGUAGE = 'en';
    /** @var string self::INSTALL */
    protected const INSTALL = 'install';
    /** @var string self::SUCCESS */
    protected const SUCCESS = 'success';
    /** @var string self::NONEXISTS */
    protected const NONEXISTS = 'nonexists';
    /** @var string self::SUGGESTIONS */
    protected const SUGGESTIONS = 'suggest';
    /** @var string self::FAIL */
    protected const FAIL = 'fail';
    /** @var string[][] self::OUTPUT */
    protected const OUTPUT = [
        'de' => [
            self::INSTALL => 'Klone und installiere Joomla CMS ',
            self::SUCCESS => 'Joomla wurde erfolgreich installiert.'
                .' Folgen Sie bitte nun den Installationshinweisen in README.md.',
            self::NONEXISTS => 'Diese Version existiert nicht.',
            self::SUGGESTIONS => 'Meinten Sie: ',
            self::FAIL => 'Installation ist fehlgeschlagen.'
        ],
        self::DEFAULT_LANGUAGE => [
            self::INSTALL => 'Cloning and installing Joomla CMS ',
            self::SUCCESS => 'Joomla has been installed successfully.'
            .' Please follow the installation instructions in README.md.',
            self::NONEXISTS => 'Version does not exists.',
            self::SUGGESTIONS => 'Do you mean: ',
            self::FAIL => 'Installation failed.'
        ]
    ];
    /** @var \Composer\IO\ConsoleIO self::$io */
    protected static $io;

    /**
     * @param \Composer\Script\Event $event
     * @return void
     */
    public static function execute(Event $event): void
    {
        self::$io = $event->getIO();

        $languagePart = strstr(\Locale::getDefault(), '_', true);
        $language = (array_key_exists($languagePart, self::OUTPUT) ? $languagePart : self::DEFAULT_LANGUAGE);
        $output = self::OUTPUT[$language];

        $repository = 'https://github.com/joomla/joomla-cms.git';
        $folder = strstr(substr(strrchr($repository, '/'), 1), '.', true);
        $allVersions = self::getAllVersions($repository);

        $versions = self::getFilteredVersions($allVersions);

        if (count($versions) === 1) {
            $version = current($versions);
            self::$io->write($output[self::INSTALL].$version.' ...');
            exec(__DIR__.'/install-cms.sh '.$repository.' '.$version.' '.$folder);

            self::adjustComposerJson();

            self::$io->write($output[self::SUCCESS]);
        } else {
            self::$io->write($output[self::NONEXISTS]);
            if (!empty($versions)) {
                self::$io->write($output[self::SUGGESTIONS].implode(', ', $versions));
            }

            self::$io->write($output[self::FAIL]);
        }
    }

    /**
     * @param string $repository
     * @return string[]
     */
    protected static function getAllVersions(string $repository): array
    {
        $versions = $references = [];
        $semanticVersioning = '#^([0-9]+\.){1,2}([0-9]+|-[a-z]){2}\w*$#';

        $command = 'git ls-remote --heads --refs --tags --sort "v:refname" '.$repository.' | sed "s/.*\///"';
        exec($command, $references);

        foreach ($references as $key => $ref) {
            if (preg_match($semanticVersioning, $ref)) {
                $versions[] = $references[$key];
            }
        }

        return $versions;
    }

    /**
     * @param array $allVersions
     * @return string[]
     */
    protected static function getFilteredVersions(array $allVersions): array
    {
        $desiredVersion = self::$io->ask('Joomla version (latest by default): ');

        $versions = [];
        $stableVersion = '#^([0-9]+\.){2}[0-9]+$#';

        if (empty($desiredVersion)) {
            foreach ($allVersions as $version) {
                if (preg_match($stableVersion, $version)) {
                    $versions[] = $version;
                }
            }
            $versions = array_slice($versions, -1);

        } elseif (in_array($desiredVersion, $allVersions)) {
            $versions = [$desiredVersion];

        } else {
            foreach ($allVersions as $version) {
                if (strpos($version, $desiredVersion) === 0) {
                    $versions[] = $version;
                }
            }
        }

        return $versions;
    }

    /**
     * @return void
     */
    protected static function adjustComposerJson(): void
    {
        self::$io->write(PHP_EOL.'Beende die Installation ...'.' / Finalising set up ...');
        $file = dirname(__DIR__).'/composer.json';

        if (file_exists($file) && is_writable($file)) {
            $composerJson = file_get_contents($file);
            $composerJson = str_replace('"libraries/vendor"', '"joomla-cms/libraries/vendor"', $composerJson);
            file_put_contents($file, $composerJson);
        }

        self::$io->write(PHP_EOL);
    }

}
