<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Erik Frister <typo3@marketing-factory.de>
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
 * Implements the Data for the Article Leaf
 */
class Tx_Commerce_Tree_Leaf_ArticleData extends Tx_Commerce_Tree_Leaf_SlaveData {
	/**
	 * Fields that should be read from the products
	 *
	 * @var string
	 */
	protected $extendedFields = 'title, hidden';

	/**
	 * @var string
	 */
	protected $table = 'tx_commerce_articles';

	/**
	 * table to read the leafitems from
	 *
	 * @var string
	 */
	protected $itemTable = 'tx_commerce_articles';

	/**
	 * Flag if mm table is to be used or the parent field
	 *
	 * @var boolean
	 */
	protected $useMMTable = FALSE;

	/**
	 * @var string
	 */
	protected $itemParentField = 'uid_product';

	/**
	 * @var string
	 */
	protected $item_parent = 'uid_product';

	/**
	 * Initializes the ProductData Object
	 *
	 * @return void
	 */
	public function init() {
		$this->whereClause = 'deleted = 0';
		$this->order = 'tx_commerce_articles.sorting ASC';
	}

	/**
	 * @todo If we implement the positions (see above), we should also implement this and any function related to making this leaf not ultimate
	 * Returns true if this Article is currently expanded
	 *
	 * @param array $row - Current Row
	 * @return boolean
	 */
	public function isExpanded(&$row) {
			// Article is the ultimate leaf, so to speak - it currently has no subleafs
		return FALSE;
	}
}

class_alias('Tx_Commerce_Tree_Leaf_ArticleData', 'tx_commerce_leaf_articledata');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/ArticleData.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/ArticleData.php']);
}

?>