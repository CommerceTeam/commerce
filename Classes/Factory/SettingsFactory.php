<?php
namespace CommerceTeam\Commerce\Factory;
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
 * Class \CommerceTeam\Commerce\Factory\SettingsFactory
 *
 * @author Sebastian Fischer <typo3@marketing-factory.de>
 */
class SettingsFactory implements SingletonInterface {
	/**
	 * Extension key
	 *
	 * @var string
	 */
	protected $extensionKey = COMMERCE_EXTKEY;

	/**
	 * Settings
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Values of path cache
	 *
	 * @var array
	 */
	protected $extConfValueCache = array();

	/**
	 * Values of path cache
	 *
	 * @var array
	 */
	protected $configurationValueCache = array();

	/**
	 * Values of path cache
	 *
	 * @var array
	 */
	protected $tcaValueCache = array();


	/**
	 * Instance
	 *
	 * @var SettingsFactory
	 */
	protected static $instance = NULL;

	/**
	 * Clone
	 *
	 * Block cloning of instance
	 *
	 * @return void
	 */
	protected function __clone() {
	}

	/**
	 * Constructor
	 *
	 * Block external instanciation
	 */
	protected function __construct() {
	}

	/**
	 * Get instance
	 *
	 * @return SettingsFactory
	 */
	public static function getInstance() {
		if (self::$instance === NULL) {
			self::$instance = new self;
			self::$instance->getSettings();
		}

		return self::$instance;
	}


	/**
	 * Get settings if they are not set fill
	 * them with values of TYPO3_CONF_VARS EXTCONF
	 *
	 * @return void
	 */
	protected function getSettings() {
		if (!is_array($this->settings)) {
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extensionKey])) {
				$this->settings = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extensionKey];
			} else {
				$this->settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extensionKey]);
			}
		}
	}

	/**
	 * Get extension configuration
	 *
	 * @param string $path Path to get value of
	 *
	 * @return array|string|int|bool
	 */
	public function getExtConf($path) {
		if (!isset($this->extConfValueCache[$path])) {
			$pathParts = GeneralUtility::trimExplode('.', $path);

			$configuration = $this->settings['extConf'];

			foreach ($pathParts as $pathPart) {
				if (isset($configuration[$pathPart])) {
					$configuration = $configuration[$pathPart];
				} else {
					$configuration = '';
					break;
				}
			}

			$this->extConfValueCache[$path] = $configuration;
		}

		return $this->extConfValueCache[$path];
	}

	/**
	 * Return all extension configuration
	 *
	 * @return array
	 */
	public function getExtConfComplete() {
		return $this->settings['extConf'];
	}

	/**
	 * Get configuration located in [COMMERCE_EXTKEY]
	 *
	 * @return array|string|int|bool
	 */
	public function getConfiguration() {
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
	 * Get TCA configuration
	 *
	 * @param string $path Path to get value of
	 *
	 * @return array|string|int|bool
	 */
	public function getTcaValue($path) {
		if (!isset($this->tcaValueCache[$path])) {
			$pathParts = GeneralUtility::trimExplode('.', $path);

			$configuration = $GLOBALS['TCA'];

			foreach ($pathParts as $pathPart) {
				if (isset($configuration[$pathPart])) {
					$configuration = $configuration[$pathPart];
				} else {
					$configuration = '';
					break;
				}
			}

			$this->tcaValueCache[$path] = $configuration;
		}

		return $this->tcaValueCache[$path];
	}
}