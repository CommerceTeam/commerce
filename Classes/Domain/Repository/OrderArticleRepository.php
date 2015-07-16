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
 * Class \CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
class OrderArticleRepository extends Repository {
	/**
	 * Database table concerning the data
	 *
	 * @var string
	 */
	protected $databaseTable = 'tx_commerce_order_articles';

	/**
	 * Find order articles by order id in page
	 *
	 * @param string $orderId Order Id
	 * @param int $pageId Page id
	 *
	 * @return array
	 */
	public function findByOrderIdInPage($orderId, $pageId) {
		return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
			'*',
			$this->databaseTable,
			'pid = ' . $pageId . BackendUtility::deleteClause($this->databaseTable) .
				' AND order_id = \'' . $this->getDatabaseConnection()->quoteStr($orderId, $this->databaseTable) . '\''
		);
	}
}