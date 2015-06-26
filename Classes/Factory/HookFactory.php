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

/**
 * Class HookFactory
 *
 * @package CommerceTeam\Commerce\Factory
 * @author Sebastian Fischer <typo3@marketing-factory.de>
 */
class HookFactory {
	/**
	 * Class name map
	 *
	 * @var array
	 */
	protected static $classNameMap = array(
		'commerce/Controller/ListController' => 'commerce/Classes/Controller/ListController.php',
	);

	/**
	 * Hook name map
	 *
	 * @var array
	 */
	protected static $hookNameMap = array(
		'preMakeListView' => 'preRenderListView',
	);

	/**
	 * Get hook objects
	 *
	 * @param string $className Class name
	 * @param string $hookName Hook name
	 *
	 * @return array
	 */
	public static function getHookObjects($className, $hookName) {
		$result = array();

		static::mapClassName($className);
		static::mapHookName($className, $hookName);

		$extConf = & $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'];
		if (is_array($extConf[$className][$hookName])) {
			foreach ($extConf[$className][$hookName] as $classRef) {
				$result[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}

		return $result;
	}

	/**
	 * Map old class name hooks
	 *
	 * @param string $className Class name
	 *
	 * @return void
	 */
	protected static function mapClassName($className) {
		if (isset(static::$classNameMap[$className])) {
			$oldClassName = static::$classNameMap[$className];

			$extConf = & $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'];
			if (isset($extConf[$oldClassName])) {
				if (isset($extConf[$className])) {
					$extConf[$className] = array_merge($extConf[$oldClassName], $extConf[$className]);
				} else {
					$extConf[$className] = $extConf[$oldClassName];
				}

				unset($extConf[$oldClassName]);
			}
		}
	}

	/**
	 * Map old hook names
	 *
	 * @param string $className Class name
	 * @param string $hookName Hook name
	 *
	 * @return void
	 */
	protected static function mapHookName($className, $hookName) {
		if (isset(static::$hookNameMap[$hookName])) {
			$oldHookName = static::$hookNameMap[$hookName];

			$extConf = & $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'];
			if (
				isset($extConf[$className])
				&& isset($extConf[$className][$oldHookName])
			) {
				if (
					is_array($extConf[$className][$oldHookName])
					&& isset($extConf[$className][$hookName])
					&& !is_string(isset($extConf[$className][$hookName]))
				) {
					$extConf[$hookName] = array_merge($extConf[$hookName][$oldHookName], $extConf[$hookName][$hookName]);
				} elseif (!isset($extConf[$className][$hookName])) {
					$extConf[$className][$hookName] = $extConf[$className][$oldHookName];
				}

				unset($extConf[$className][$oldHookName]);
			}
		}
	}
}