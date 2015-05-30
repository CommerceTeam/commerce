<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Ingo Schmitt <is@marketing-factory.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Database Class for tx_commerce_article_prices. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_article_price to get informations for articles.
 * Inherited from Tx_Commerce_Domain_Repository_Repository
 *
 * Basic abtract Class for Database Query for
 * Database retrival class fro product
 * inherited from Tx_Commerce_Domain_Repository_Repository
 */
class Tx_Commerce_Domain_Repository_ArticlePriceRepository extends Tx_Commerce_Domain_Repository_Repository {
	/**
	 * @var string table concerning the data
	 */
	protected $databaseTable = 'tx_commerce_article_prices';

	/**
	 * Get data
	 *
	 * @param integer $uid UID for Data
	 * @return array assoc Array with data
	 * @todo implement access_check concering category tree
	 * Special Implementation for prices, as they don't have a localisation'
	 */
	public function getData($uid) {
		$uid = (int) $uid;

		$proofSql = '';
		$database = $this->getDatabaseConnection();

		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$proofSql = $this->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);
		}

		$result = $database->exec_SELECTquery('*', $this->databaseTable, 'uid = ' . $uid . $proofSql);

		// Result should contain only one Dataset
		if ($database->sql_num_rows($result) == 1) {
			$returnData = $database->sql_fetch_assoc($result);
			$database->sql_free_result($result);

			return $returnData;
		}

		$this->error('exec_SELECTquery(\'*\',' . $this->databaseTable . ',\'uid = ' . $uid . '\'); returns no or more than one Result');
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
