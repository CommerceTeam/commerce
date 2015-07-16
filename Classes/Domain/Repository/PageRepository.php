<?php
namespace CommerceTeam\Commerce\Domain\Repository;
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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Class \CommerceTeam\Commerce\Domain\Repository\PageRepository
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
class PageRepository extends Repository {
	/**
	 * Database table concerning the data
	 *
	 * @var string
	 */
	protected $databaseTable = 'pages';

	/**
	 * Find by uid
	 *
	 * @param int $uid Page id
	 *
	 * @return array
	 */
	public function findByUid($uid) {
		return (array) $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
			'pid ',
			$this->databaseTable,
			'uid = ' . $uid . BackendUtility::deleteClause($this->databaseTable),
			'',
			'sorting'
		);
	}

	/**
	 * Find folder by uid that is editable
	 *
	 * @param int $uid Page uid
	 *
	 * @return array
	 */
	public function findEditFolderByUid($uid) {
		return (array) $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
			'tx_commerce_foldereditorder',
			$this->databaseTable,
			'tx_commerce_foldereditorder = 1 AND uid = ' . $uid
		);
	}
}