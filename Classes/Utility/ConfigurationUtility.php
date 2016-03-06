<?php
namespace CommerceTeam\Commerce\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class \CommerceTeam\Commerce\Factory\SettingsFactory.
 *
 * @author Sebastian Fischer <typo3@marketing-factory.de>
 */
class ConfigurationUtility implements SingletonInterface
{
    /**
     * Extension key.
     *
     * @var string
     */
    protected $extensionKey = 'commerce';

    /**
     * Settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * Values of path cache.
     *
     * @var array
     */
    protected $extConfValueCache = [];

    /**
     * Values of path cache.
     *
     * @var array
     */
    protected $configurationValueCache = [];

    /**
     * Values of path cache.
     *
     * @var array
     */
    protected $tcaValueCache = [];

    /**
     * Instance.

     *
*@var ConfigurationUtility
     */
    protected static $instance = null;

    /**
     * Clone.
     *
     * Block cloning of instance
     *
     * @return void
     */
    protected function __clone()
    {
    }

    /**
     * Constructor.

     * Block external instantiation

     *
*@return ConfigurationUtility
     */
    protected function __construct()
    {
    }

    /**
     * Get instance.

     *
*@return ConfigurationUtility
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->getSettings();
        }

        return self::$instance;
    }

    /**
     * Get settings if they are not set fill
     * them with values of TYPO3_CONF_VARS EXTCONF.
     *
     * @return void
     */
    protected function getSettings()
    {
        if (!is_array($this->settings)) {
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey])) {
                $this->settings = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey];
            } else {
                $this->settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey]);
            }
        }
    }

    /**
     * Get extension configuration.
     *
     * @param string $path Path to get value of
     *
     * @return array|string|int|bool
     */
    public function getExtConf($path)
    {
        if (!isset($this->extConfValueCache[$path])) {
            $pathParts = GeneralUtility::trimExplode('.', $path);

            $configuration = $this->settings;

            foreach ($pathParts as $pathPart) {
                if (isset($configuration[$pathPart])) {
                    $configuration = $configuration[$pathPart];
                } else {
                    $configuration = false;
                    break;
                }
            }

            $this->extConfValueCache[$path] = $configuration;
        }

        return $this->extConfValueCache[$path];
    }

    /**
     * Get configuration located in commerce.
     *
     * @param string $path Path to get value of
     *
     * @return array|string|int|bool
     */
    public function getConfiguration($path)
    {
        if (!isset($this->configurationValueCache[$path])) {
            $pathParts = GeneralUtility::trimExplode('.', $path);

            $configuration = $this->settings;

            foreach ($pathParts as $pathPart) {
                if (isset($configuration[$pathPart])) {
                    $configuration = $configuration[$pathPart];
                } else {
                    $configuration = '';
                    break;
                }
            }

            $this->configurationValueCache[$path] = $configuration;
        }

        return $this->configurationValueCache[$path];
    }

    /**
     * Get TCA configuration.
     *
     * @param string $path Path to get value of
     *
     * @return array|string|int|bool
     */
    public function getTcaValue($path)
    {
        if (!isset($this->tcaValueCache[$path])) {
            $pathParts = GeneralUtility::trimExplode('.', $path);

            $configuration = $GLOBALS['TCA'];

            foreach ($pathParts as $pathPart) {
                if (isset($configuration[$pathPart])) {
                    $configuration = $configuration[$pathPart];
                } else {
                    $configuration = false;
                    break;
                }
            }

            $this->tcaValueCache[$path] = $configuration;
        }

        return $this->tcaValueCache[$path];
    }
}
