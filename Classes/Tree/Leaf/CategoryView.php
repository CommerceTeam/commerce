<?php
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
 * Implements the Tx_Commerce_Tree_Leaf_View for the Category
 *
 * Class Tx_Commerce_Tree_Leaf_CategoryView
 *
 * @author 2008-2011 Erik Frister <typo3@marketing-factory.de>
 */
class Tx_Commerce_Tree_Leaf_CategoryView extends Tx_Commerce_Tree_Leaf_View {
	/**
	 * DB Table isn't this read automatically?
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
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'getJumpToParam (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.',
					COMMERCE_EXTKEY,
					3
				);
			}
			return '';
		}

		// get the UID of the Products SysFolder
		$productPid = Tx_Commerce_Utility_BackendUtility::getProductFolderUid();

		$res = '&id=' . $productPid . '&control[' . $this->table . '][uid]=' . $row['uid'];

		if ($this->realValues) {
			$res = $this->table . '_' . $row['uid'];
		}

		return $res;
	}
}
