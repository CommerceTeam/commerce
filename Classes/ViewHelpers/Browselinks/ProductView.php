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
	 * Dom id prefix
	 *
	 * @var string
	 */
	protected $domIdPrefix = 'txcommerceProduct';

	/**
	 * Uid of the open product
	 *
	 * @var int
	 */
	protected $openProd = 0;

	/**
	 * Returns the link from the tree used to jump to a destination
	 *
	 * @param array $row Array with the ID Information
	 *
	 * @return string
	 */
	public function getJumpToParam(array $row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'getJumpToParam (Tx_Commerce_ViewHelpers_Browselinks_ProductView) gets passed invalid parameters.',
					COMMERCE_EXTKEY,
					3
				);
			}
			return '';
		}

		return 'commerce:tx_commerce_products:' . $row['uid'] . '|tx_commerce_categories:' . $row['item_parent'];
	}

	/**
	 * Set open product
	 *
	 * @param int $uid Uid
	 *
	 * @return void
	 */
	public function setOpenProduct($uid) {
		$this->openProd = $uid;
	}

	/**
	 * Wrapping title in a-tags.
	 *
	 * @param string $title Title string
	 * @param array $row Item record
	 * @param int $bank Bank pointer (which mount point number)
	 *
	 * @return string
	 */
	public function wrapTitle($title, array &$row, $bank = 0) {
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
}
