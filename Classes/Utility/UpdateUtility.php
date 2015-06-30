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

/**
 * Update Class for DB Updates of version 0.11.0
 *
 * Basically checks for the new Tree, if all records have a MM
 * relation to Record UID 0 if not, these records are created
 *
 * Class \CommerceTeam\Commerce\Utility\UpdateUtility
 *
 * @author 2008-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class UpdateUtility {
	/**
	 * Performes the Updates
	 * Outputs HTML Content
	 *
	 * @return string
	 */
	public function main() {
		$createdRelations = $this->createParentMmRecords();
		$createDefaultRights = $this->createDefaultRights();
		$createBackendUser = $this->createBackendUser();

		$htmlCode = array();

		$htmlCode[] = 'This updates were performed successfully:
			<ul>';

		if ($createdRelations > 0) {
			$htmlCode[] = '<li>' . $createdRelations .
				' updated mm-Relations for the Category Records. <b>Please Check you Category Tree!</b></li>';
		}
		if ($createDefaultRights > 0) {
			$htmlCode[] = '<li>' . $createDefaultRights .
				' updated User-rights on categories. Set to rights on the commerce products folder</li>';
		}
		if ($createBackendUser) {
			$htmlCode[] = '<li>Default user created</li>';
		}
		$htmlCode[] = '</ul>';

		return implode(chr(10), $htmlCode);
	}

	/**
	 * Sets the default user rights, based on the
	 * <User-Rights in the commerce-products folder
	 *
	 * @return int
	 */
	public function createDefaultRights() {
		$database = $this->getDatabaseConnection();
		$countRecords = 0;

		/**
		 * Get data from folder
		 */
		list($modPid) = \CommerceTeam\Commerce\Domain\Repository\FolderRepository::initFolders('Commerce', 'commerce');
		list($prodPid) = \CommerceTeam\Commerce\Domain\Repository\FolderRepository::initFolders('Products', 'commerce', $modPid);
		$resrights = $database->exec_SELECTquery(
			'perms_userid, perms_groupid, perms_user, perms_group, perms_everybody',
			'pages',
			'uid = ' . $prodPid
		);
		$data = $database->sql_fetch_assoc($resrights);

		$result = $database->exec_SELECTquery(
			'uid',
			'tx_commerce_categories',
			'perms_user = 0 OR perms_group = 0 OR perms_everybody = 0'
		);
		while (($row = $database->sql_fetch_assoc($result))) {
			$database->exec_UPDATEquery('tx_commerce_categories', 'uid = ' . $row['uid'], $data);
			$countRecords++;
		}
		return ++$countRecords;
	}

	/**
	 * Creates the missing MM records for categories below the root (UID=0) element
	 *
	 * @return int Num Records Changed
	 */
	public function createParentMmRecords() {
		$database = $this->getDatabaseConnection();
		$countRecords = 0;

		$result = $database->exec_SELECTquery(
			'uid',
			'tx_commerce_categories',
			'sys_language_uid = 0 AND l18n_parent = 0 AND uid NOT IN (
				SELECT uid_local FROM tx_commerce_categories_parent_category_mm
			) AND tx_commerce_categories.deleted = 0'
		);
		while (($row = $database->sql_fetch_assoc($result))) {
			$data = array(
				'uid_local' => $row['uid'],
				'uid_foreign' => 0,
				'tablenames' => '',
				'sorting' => 99,
			);

			$database->exec_INSERTquery('tx_commerce_categories_parent_category_mm', $data);
			$countRecords++;
		}
		return $countRecords;
	}

	/**
	 * Creates the missing MM records for categories below the root (UID=0) element
	 *
	 * @return int
	 */
	public function createBackendUser() {
		$userId = 0;
		$database = $this->getDatabaseConnection();

		$result = $database->exec_SELECTquery('uid', 'be_users', 'username = \'_fe_commerce\'');
		if ($result && $database->sql_num_rows($result) == 0) {
			$data = array(
				'pid' => 0,
				'username' => '_fe_commerce',
				'password' => 'MD5(RAND())',
				'tstamp' => $GLOBALS['EXEC_TIME'],
				'crdate' => $GLOBALS['EXEC_TIME'],
			);

			$database->exec_INSERTquery(
				'be_users',
				$data,
				array(
					'password',
					'tstamp',
					'crdate'
				)
			);
			$userId = $this->getDatabaseConnection()->sql_insert_id();
		}
		return $userId;
	}

	/**
	 * Check if the Ipdate is necessary
	 *
	 * @return bool True if update should be perfomed
	 */
	public function access() {
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('commerce')) {
			return FALSE;
		}

		$database = $this->getDatabaseConnection();

		$result = $database->exec_SELECTquery(
			'uid',
			'tx_commerce_categories',
			'uid NOT IN (
				SELECT uid_local FROM tx_commerce_categories_parent_category_mm
			) AND tx_commerce_categories.deleted = 0 AND sys_language_uid = 0 AND l18n_parent = 0'
		);

		if ($result && $database->sql_num_rows($result) > 0) {
			return TRUE;
		}

		/**
		 * No userrights set at all, must be an update.
		 */
		$result = $database->exec_SELECTquery(
			'uid',
			'tx_commerce_categories',
			'perms_user = 0 AND perms_group = 0 AND perms_everybody = 0'
		);
		if ($result && $database->sql_num_rows($result) > 0) {
			return TRUE;
		}

		$result = $database->exec_SELECTquery('uid', 'be_users', 'username = \'_fe_commerce\'');
		if ($result && $database->sql_num_rows($result) == 0) {
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
