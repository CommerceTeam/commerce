<?php
/**
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
 * Misc commerce db functions
 *
 * @author Eric Frister <ef@marketing-factory.de>
 */
class Tx_Commerce_Domain_Repository_FolderRepository {
	/**
	 * Returns pidList of extension Folders
	 *
	 * @param string $module Module name
	 *
	 * @return string commalist of PIDs
	 *
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, this wont get replaced as it was removed from the api
	 */
	public function getFolderPidList($module = 'commerce') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return implode(',', array_keys(self::getFolders($module)));
	}

	/**
	 * Find the extension folders or create one.
	 *
	 * @param string $title Folder Title as named in pages table
	 * @param string $module Extension Moduke
	 * @param int $pid Parent Page id
	 * @param string $parentTitle Parent Folder Title
	 *
	 * @return array
	 */
	public static function initFolders($title = 'Commerce', $module = 'commerce', $pid = 0, $parentTitle = '') {
		// creates a Commerce folder on the fly
		// not really a clean way ...
		if ($parentTitle) {
			$parentFolders = self::getFolders($module, $pid, $parentTitle);
			$currentParentFolders = current($parentFolders);
			$pid = $currentParentFolders['uid'];
		}

		$folders = self::getFolders($module, $pid, $title);
		if (!count($folders)) {
			self::createFolder($title, $module, $pid);
			$folders = self::getFolders($module, $pid, $title);
		}

		$currentFolder = current($folders);

		return array($currentFolder['uid'], implode(',', array_keys($folders)));
	}

	/**
	 * Find the extension folders
	 *
	 * @param string $module Module name
	 * @param int $pid Page id
	 * @param string $title Title
	 *
	 * @return array rows of found extension folders
	 */
	public static function getFolders($module = 'commerce', $pid = 0, $title = '') {
		$row = self::getDatabaseConnection()->exec_SELECTgetSingleRow(
			'uid,pid,title',
			'pages',
			'doktype = 254 AND tx_graytree_foldername = \'' . strtolower($title) . '\' AND pid = ' . (int) $pid . ' AND module=\'' .
				$module . '\' ' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages')
		);

		return isset($row['uid']) ? array($row['uid'] => $row) : array();
	}

	/**
	 * Create your database table folder
	 * overwrite this if wanted
	 *
	 * @param string $title Title
	 * @param string $module Name of the module
	 * @param int $pid Page id
	 *
	 * @return int
	 *
	 * @todo title aus extkey ziehen
	 * @todo sorting
	 */
	protected function createFolder($title = 'Commerce', $module = 'commerce', $pid = 0) {
		$fieldValues = array(
			'sorting' => 10111,
			'perms_user' => 31,
			'perms_group' => 31,
			'perms_everybody' => 31,
			'doktype' => 254,
			'crdate' => $GLOBALS['EXEC_TIME'],
			'tstamp' => $GLOBALS['EXEC_TIME'],

			'pid' => $pid,
			'title' => $title,
			'module' => $module,

			// @todo MAKE IT tx_commerce_foldername
			'tx_graytree_foldername' => strtolower($title),
		);

		self::getDatabaseConnection()->exec_INSERTquery('pages', $fieldValues);
		return self::getDatabaseConnection()->sql_insert_id();
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection
	 */
	protected static function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
