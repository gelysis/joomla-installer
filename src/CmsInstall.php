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

final class CmsInstall
{

    /** @var string self::DEFAULT_LANGUAGE */
    private const DEFAULT_LANGUAGE = 'en';
    /** @var string self::ASK_VERSION */
    private const ASK_VERSION = 'version';
    /** @var string self::INSTALL */
    private const INSTALL = 'install';
    /** @var string self::SUCCESS */
    private const SUCCESS = 'success';
    /** @var string self::NONEXISTS */
    private const NONEXISTS = 'nonexists';
    /** @var string self::SUGGESTIONS */
    private const SUGGESTIONS = 'suggest';
    /** @var string self::FAIL */
    private const FAIL = 'fail';
    /** @var string[][] self::OUTPUT */
    private const OUTPUT = [
        'de' => [
            self::ASK_VERSION => 'Welche Joomla Version möchten Sie installieren',
            self::INSTALL => 'Klone und installiere Joomla CMS',
            self::SUCCESS => 'Installiere nun die Programmbibliotheken.'
                .' Folgen Sie nach Abschluss bitte den Installationshinweisen in README.md.',
            self::NONEXISTS => 'Diese Version existiert nicht.',
            self::SUGGESTIONS => 'Meinten Sie: ',
            self::FAIL => 'Installation ist fehlgeschlagen.'
        ],
        self::DEFAULT_LANGUAGE => [
            self::ASK_VERSION => 'Joomla version',
            self::INSTALL => 'Cloning and installing Joomla CMS',
            self::SUCCESS => 'Installing libraries now.'
                .' Please follow afterwards the installation instructions in README.md.',
            self::NONEXISTS => 'Version does not exists.',
            self::SUGGESTIONS => 'Do you mean: ',
            self::FAIL => 'Installation failed.'
        ]
    ];
    /** @var string self::CMS_FOLDER */
    private const CMS_FOLDER = 'cms';

    /** @var \Composer\IO\ConsoleIO self::$io */
    private static $io;
    /** @var string[] self::$output */
    private static $output;

    /**
     * @param \Composer\Script\Event $event
     * @return void
     */
    public static function execute(Event $event): void
    {
        self::$io = $event->getIO();

        $languagePart = strstr(\Locale::getDefault(), '_', true);
        $language = (array_key_exists($languagePart, self::OUTPUT) ? $languagePart : self::DEFAULT_LANGUAGE);
        self::$output = self::OUTPUT[$language];

        $repository = 'https://github.com/joomla/joomla-cms.git';
        $allVersions = self::getAllVersions($repository);

        $versions = self::getFilteredVersions($allVersions);

        if (count($versions) === 1) {
            $version = current($versions);

            self::$io->write(PHP_EOL.rtrim(self::$output[self::INSTALL]).' '.$version.' ...');
            exec(__DIR__.'/install-cms.sh '.$repository.' '.$version.' '.self::CMS_FOLDER);
            self::adjustComposerJson();

            self::$io->write(self::$output[self::SUCCESS]);
        } else {
            $message = self::$output[self::NONEXISTS];
            if (!empty($versions)) {
                $message .= ' '.self::$output[self::SUGGESTIONS].implode(', ', $versions).'?';
            }
            self::$io->write([$message, self::$output[self::FAIL]]);
        }

        self::$io->write('');
    }

    /**
     * @param string $repository
     * @return string[]
     */
    private static function getAllVersions(string $repository): array
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
    private static function getFilteredVersions(array $allVersions): array
    {
        $versions = $stableVersions = [];

        $stableVersion = '#^([0-9]+\.){2}[0-9]+$#';
        foreach ($allVersions as $version) {
            if (preg_match($stableVersion, $version)) {
                $stableVersions[] = $version;
            }
        }

        $latestStable = end($stableVersions);
        $desiredVersion = self::$io->ask(rtrim(self::$output[self::ASK_VERSION], ':?').' ('.$latestStable.')?');

        if (empty($desiredVersion)) {
            $versions = [$latestStable];

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
    private static function adjustComposerJson(): void
    {
        $file = dirname(__DIR__).'/composer.json';

        if (file_exists($file) && is_writable($file)) {
            $composerJson = file_get_contents($file);
            $composerJson = str_replace('"libraries/vendor"', '"'.self::CMS_FOLDER.'/libraries/vendor"', $composerJson);
            file_put_contents($file, $composerJson);
        }
    }

}
