<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2008 - 2012 Ingo Schmitt <typo3@marketing-factory.de>
 * (c) 2013 Sebastian Fischer <typo3@marketing-factory.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Implements the mounts for Tx_Commerce_Tree_Leaf_Master
 */
class Tx_Commerce_Tree_Leaf_Mounts extends Tx_Commerce_Tree_Leaf_Base {
	/**
	 * Uid of the User
	 *
	 * @var integer
	 */
	protected $user_uid;

	/**
	 * List with all mounts
	 *
	 * @var string
	 */
	protected $mountlist;

	/**
	 * Array with all mounts
	 *
	 * @var array
	 */
	protected $mountdata;

	/**
	 * Walk-Pointer
	 *
	 * @var integer
	 */
	protected $pointer;

	/**
	 * User for this mount
	 *
	 * @var t3lib_beUserAuth
	 */
	protected $user;

	/**
	 * Group for this mount
	 *
	 * @var integer
	 */
	protected $group;

	/**
	 * Flag if we want to read the mounts by group
	 *
	 * @var boolean
	 */
	protected $byGroup;

	/**
	 * @var string
	 */
	protected $table = 'be_users';

	/**
	 * @var string
	 */
	protected $grouptable = 'be_groups';

	/**
	 * @var string
	 */
	protected $field = NULL;

	/**
	 * @var string
	 */
	protected $usergroupField = 'usergroup';

	/**
	 * @var string
	 */
	protected $where = '';

	/**
	 * Constructor - initializes the values
	 *
	 * @return self
	 */
	public function __construct() {
		$this->user_uid  = 0;
		$this->mountlist = '';
		$this->mountdata = array();
		$this->pointer = 0;
		$this->user = t3lib_div::makeInstance('t3lib_beUserAuth');
		$this->group = 0;
		$this->byGroup = FALSE;

		parent::__construct();
	}

	/**
	 * Initializes the Mounts for a user
	 * Overwrite this function if you plan to not read Mountpoints from the be_users table
	 *
	 * @param $uid {int}	User UID
	 * @return void
	 */
	public function init($uid) {
			// Return if the UID is not numeric - could also be because we have a new user
		if (!is_numeric($uid) || $this->field == NULL) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('init (Tx_Commerce_Tree_Leaf_Mounts) gets passed invalid parameters. Script is aborted.', COMMERCE_EXTKEY, 2);
			}
			return;
		}

		$this->user_uid = $uid;
		$this->user->setBeUserByUid($uid);

		$mounts = $this->getMounts();

			// If neither User nor Group have mounts, return
		if ($mounts == NULL) {
			return;
		}

			// Store the results
		$this->mountlist = t3lib_div::uniqueList($mounts);
			// Clean duplicates
		$this->mountdata = explode(',', $this->mountlist);
	}

	/**
	 * Initializes the Mounts for a group
	 * Overwrite this function if you plan to not read Mountpoints from the be_groups table
	 *
	 * @param $uid {int}	Group UID
	 * @return void
	 */
	public function initByGroup($uid) {
			// Return if the UID is not numeric - could also be because we have a new user
		if (!is_numeric($uid) || $this->field == NULL) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('initByGroup (mounts) gets passed invalid parameters. Script is aborted.', COMMERCE_EXTKEY, 2);
			}
			return;
		}

		$this->byGroup = TRUE;
		$this->group = $uid;
		$this->user_uid = 0;

		$mounts = $this->getMounts();

			// If the Group has no mounts, return
		if ($mounts == NULL) {
			return;
		}

			// Store the results
		$this->mountlist = t3lib_div::uniqueList($mounts);
			// Clean duplicates
		$this->mountdata = explode(',', $this->mountlist);
	}

	/**
	 * Returns a comma-separeted list of mounts
	 *
	 * @return string item1, item2, ..., itemN
	 */
	protected function getMounts() {
		$mounts = '';

			// Set mount to 0 if the User is a admin
		if (!$this->byGroup && $this->user->isAdmin()) {
			$mounts = '0';
		} else {
			/** @var t3lib_db $database */
			$database = & $GLOBALS['TYPO3_DB'];
				// Read usermounts - if none are set, mounts are set to NULL
			if (!$this->byGroup) {
				$result = $database->exec_SELECTquery(
					$this->field . ',' . $this->usergroupField,
					$this->table,
					'uid = ' . $this->user_uid,
					$this->where
				);

				$row = $database->sql_fetch_assoc($result);
				$mounts = $row[$this->field];

					// Read Usergroup mounts
				$groups = t3lib_div::uniqueList($row[$this->usergroupField]);
			} else {
				$groups = $this->group;
			}

			if (trim($groups)) {
				$result = $database->exec_SELECTquery($this->field, $this->grouptable, 'uid IN (' . $groups . ')');

					// Walk the groups and add the mounts
				while ($row = $database->sql_fetch_assoc($result)) {
					$mounts .= ',' . $row[$this->field];
				}

					// Make nicely formated list
				$mounts = t3lib_div::uniqueList($mounts);
			}
		}

		return $mounts;
	}

	/**
	 * Checks whether the User has mounts
	 *
	 * @return boolean
	 */
	public function hasMounts() {
		return ($this->mountlist != '');
	}

	/**
	 * Returns the mountlist of the current BE User
	 *
	 * @return string
	 */
	public function getMountList() {
		return $this->mountlist;
	}

	/**
	 * Returns the array with the mounts of the current BE User
	 *
	 * @return array
	 */
	public function getMountData() {
		return $this->mountdata;
	}

	/**
	 * Walks the category mounts
	 * Returns the mount-id or FALSE
	 *
	 * @return integer
	 */
	public function walk() {
			// Abort if we reached the end of this collection
		if (!isset($this->mountdata[$this->pointer])) {
			$this->resetPointer();
			return FALSE;
		}

		return $this->mountdata[$this->pointer++];
	}

	/**
	 * Sets the internal pointer to 0
	 *
	 * @return void
	 */
	public function resetPointer() {
		$this->pointer = 0;
	}
}

class_alias('Tx_Commerce_Tree_Leaf_Mounts', 'mounts');

?>