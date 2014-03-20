<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2014 Erik Frister <typo3@marketing-factory.de>
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
 * Implements the View for Articles
 */
class Tx_Commerce_Tree_Leaf_ArticleView extends Tx_Commerce_Tree_Leaf_View {
	/**
	 * DB Table
	 *
	 * @var string
	 */
	protected $table = 'tx_commerce_articles';

	/**
	 * @var string
	 */
	protected $domIdPrefix = 'txcommerceArticle';

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param string $title string
	 * @param string &$row record
	 * @param integer $bank pointer (which mount point number)
	 * @return string
	 * @access private
	 */
	public function wrapTitle($title, &$row, $bank = 0) {
		if (!is_array($row) || !is_numeric($bank)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('wrapTitle (articleview) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}

			return '';
		}

			// Max. size for Title of 255
		$title = ('' != trim($title)) ? t3lib_div::fixed_lgd_cs($title, 255) : $this->getLL('leaf.noTitle');

		$aOnClick = 'if(top.content.list_frame){top.content.list_frame.location.href=top.TS.PATH_typo3+\'alt_doc.php?returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search)+\'&' . $this->getJumpToParam($row) . '\';}';

		$res = ($this->noOnclick) ?
			$title :
			'<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . htmlspecialchars(strip_tags($title)) . '</a>';

		return $res;
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
				t3lib_div::devLog('getJumpToParam gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}

			return '';
		}

		$value = 'edit';

		if ($this->realValues) {
			$value = $this->table . '_' . $row['uid'];
		}

		$res = 'edit[' . $this->table . '][' . $row['uid'] . ']=' . $value;

		return $res;
	}
}

class_alias('Tx_Commerce_Tree_Leaf_ArticleView', 'tx_commerce_leaf_articleview');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/ArticleView.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/ArticleView.php']);
}

?>