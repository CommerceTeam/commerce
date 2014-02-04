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
 * Implements the Tx_Commerce_Tree_Leaf_View for the Category
 */
class Tx_Commerce_Tree_Leaf_CategoryView extends Tx_Commerce_Tree_Leaf_View {
	/**
	 * DB Table ##isnt this read automatically?
	 *
	 * @var string
	 */
	protected $table = 'tx_commerce_categories';

	/**
	 * @var string
	 */
	protected $domIdPrefix = 'txcommerceCategory';

	/**
	 * returns the link from the tree used to jump to a destination
	 *
	 * @param array $row - Array with the ID Information
	 * @return string
	 */
	public function getJumpToParam($row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getJumpToParam (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// get the UID of the Products SysFolder
		$prodPid = Tx_Commerce_Utility_BackendUtility::getProductFolderUid();

		$res = 'id=' . $prodPid . '&control[' . $this->table . '][uid]=' . $row['uid'];

		if ($this->realValues) {
			$res = $this->table . '_' . $row['uid'];
		}

		return $res;
	}
}

class_alias('Tx_Commerce_Tree_Leaf_CategoryView', 'tx_commerce_leaf_categoryview');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/CategoryView.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/CategoryView.php']);
}

?>