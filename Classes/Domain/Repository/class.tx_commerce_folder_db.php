<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Misc commerce db functions
 */
class tx_commerce_folder_db {
	/***************************************
	 * Commerce sysfolder
	 ***************************************/

	/**
	 * Returns pidList of extension Folders
	 *
	 * @param string $module
	 * @return string commalist of PIDs
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, this wont get replaced as it was removed from the api
	 */
	public function getFolderPidList($module = 'commerce') {
		t3lib_div::logDeprecatedFunction();

		return implode(',', array_keys(self::getFolders($module)));
	}

	/**
	 * Find the extension folders or create one.
	 *
	 * @param string $title Folder Title as named in pages table
	 * @param string $module Extension Moduke
	 * @param integer $pid Parent Page id
	 * @param string $parentTitle Parent Folder Title
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
	 * @param string $module
	 * @param integer $pid
	 * @param string $title
	 * @return array rows of found extension folders
	 */
	public static function getFolders($module = 'commerce', $pid = 0, $title = '') {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$rows = array();
		$res = $database->exec_SELECTquery(
			'uid,pid,title',
			'pages',
			'doktype=254 and tx_graytree_foldername = \'' . strtolower($title) . '\' AND pid = ' . (int) $pid . ' AND module=\'' .
				$module . '\' ' . t3lib_BEfunc::deleteClause('pages')
		);

		if ($row = $database->sql_fetch_assoc($res)) {
			$rows[$row['uid']] = $row;
		}
		return $rows;
	}

	/**
	 * Create your database table folder
	 * overwrite this if wanted
	 *
	 * @param string $title
	 * @param string $module
	 * @param integer $pid: ...
	 * @return integer
	 * @TODO title aus extkey ziehen
	 * @TODO sorting
	 */
	protected function createFolder($title = 'Commerce', $module = 'commerce', $pid = 0) {
		$fields_values = array();
		$fields_values['pid'] = $pid;
		$fields_values['sorting'] = 10111;
		$fields_values['perms_user'] = 31;
		$fields_values['perms_group'] = 31;
		$fields_values['perms_everybody'] = 31;
		$fields_values['title'] = $title;
			// MAKE IT tx_commerce_foldername
		$fields_values['tx_graytree_foldername'] =  strtolower($title);
		$fields_values['doktype'] = 254;
		$fields_values['module'] = $module;
		$fields_values['crdate'] = time();
		$fields_values['tstamp'] = time();

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		$database->exec_INSERTquery('pages', $fields_values);

		return $database->sql_insert_id();
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_folder_db.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_folder_db.php']);
}

?>