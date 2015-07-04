<?php
namespace CommerceTeam\Commerce\Tree\Leaf;
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
 * Implements the mounts for \CommerceTeam\Commerce\Tree\Leaf\Master
 *
 * Class \CommerceTeam\Commerce\Tree\Leaf\Mounts
 *
 * @author 2008-2011 Erik Frister <typo3@marketing-factory.de>
 */
class Mounts extends Base {
	/**
	 * Uid of the User
	 *
	 * @var int
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
	 * @var int
	 */
	protected $pointer;

	/**
	 * User for this mount
	 *
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected $user;

	/**
	 * Group for this mount
	 *
	 * @var int
	 */
	protected $group;

	/**
	 * Flag if we want to read the mounts by group
	 *
	 * @var bool
	 */
	protected $byGroup;

	/**
	 * Table
	 *
	 * @var string
	 */
	protected $table = 'be_users';

	/**
	 * Group table
	 *
	 * @var string
	 */
	protected $grouptable = 'be_groups';

	/**
	 * Field
	 *
	 * @var string
	 */
	protected $field = NULL;

	/**
	 * Usergroup field
	 *
	 * @var string
	 */
	protected $usergroupField = 'usergroup';

	/**
	 * Where
	 *
	 * @var string
	 */
	protected $where = '';

	/**
	 * Constructor - initializes the values
	 *
	 * @return self
	 */
	public function __construct() {
		parent::__construct();

		$this->user_uid  = 0;
		$this->mountlist = '';
		$this->mountdata = array();
		$this->pointer = 0;
		$this->user = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication'
		);
		$this->group = 0;
		$this->byGroup = FALSE;
	}

	/**
	 * Initializes the Mounts for a user
	 * Overwrite this function if you plan to not
	 * read Mountpoints from the be_users table
	 *
	 * @param int $uid User UID
	 *
	 * @return void
	 */
	public function init($uid) {
		// Return if the UID is not numeric - could also be because we have a new user
		if (!is_numeric($uid) || $this->field == NULL) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'init (CommerceTeam\\Commerce\\Tree\\Leaf\\Mounts) gets passed invalid parameters. Script is aborted.',
					COMMERCE_EXTKEY,
					2
				);
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
		$this->mountlist = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($mounts);
		// Clean duplicates
		$this->mountdata = explode(',', $this->mountlist);
	}

	/**
	 * Initializes the Mounts for a group
	 * Overwrite this function if you plan to not
	 * read Mountpoints from the be_groups table
	 *
	 * @param int $uid Group UID
	 *
	 * @return void
	 */
	public function initByGroup($uid) {
		// Return if the UID is not numeric - could also be because we have a new user
		if (!is_numeric($uid) || $this->field == NULL) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'initByGroup (mounts) gets passed invalid parameters. Script is aborted.',
					COMMERCE_EXTKEY,
					2
				);
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
		$this->mountlist = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($mounts);
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
			$database = $this->getDatabaseConnection();
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
				$groups = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($row[$this->usergroupField]);
			} else {
				$groups = $this->group;
			}

			if (trim($groups)) {
				$result = $database->exec_SELECTquery($this->field, $this->grouptable, 'uid IN (' . $groups . ')');

				// Walk the groups and add the mounts
				while (($row = $database->sql_fetch_assoc($result))) {
					$mounts .= ',' . $row[$this->field];
				}

				// Make nicely formated list
				$mounts = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($mounts);
			}
		}

		return $mounts;
	}

	/**
	 * Checks whether the User has mounts
	 *
	 * @return bool
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
	 * @return int
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
