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
 * Implements the Tx_Commerce_Tree_Leaf_View for Product
 *
 * Class Tx_Commerce_ViewHelpers_Browselinks_ProductView
 *
 * @author 2008-2011 Erik Frister <typo3@marketing-factory.de>
 */
class Tx_Commerce_ViewHelpers_Browselinks_ProductView extends Tx_Commerce_Tree_Leaf_View {
	/**
	 * DB Table
	 *
	 * @var string
	 */
	protected $table = 'tx_commerce_products';

	/**
	 * @var string
	 */
	protected $domIdPrefix = 'txcommerceProduct';

	/**
	 * uid of the open product
	 *
	 * @var integer
	 */
	protected $openProd = 0;

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param string $title Title string
	 * @param string $row Item record
	 * @param integer $bank Bank pointer (which mount point number)
	 * @return string
	 * @access private
	 */
	public function wrapTitle($title, &$row, $bank = 0) {
		if (!is_array($row) || !is_numeric($bank)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('wrapTitle (productview) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		// Max. size for Title of 30
		$title = ('' != trim($title)) ? \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($title, 30) : $this->getLL('leaf.noTitle');

		$aOnClick = 'return link_commerce(\'' . $this->getJumpToParam($row) . '\');';

		$style = ($row['uid'] == $this->openProd) ? 'style="color: red; font-weight: bold"' : '';
		$res = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '" ' . $style . '>' . $title . '</a>';

		return $res;
	}

	/**
	 * Setter
	 *
	 * @param integer $uid
	 * @return void
	 */
	public function setOpenProduct($uid) {
		$this->openProd = $uid;
	}

	/**
	 * returns the link from the tree used to jump to a destination
	 *
	 * @param array $row - Array with the ID Information
	 * @return string
	 */
	public function getJumpToParam(&$row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('getJumpToParam (productview) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}
		$res = 'commerce:tx_commerce_products:' . $row['uid'] . '|tx_commerce_categories:' . $row['item_parent'];
		return $res;
	}
}
