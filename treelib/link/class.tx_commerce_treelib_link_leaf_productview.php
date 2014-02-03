<?php
/**
 * Implements the leafview for Product
 */
class tx_commerce_treelib_link_leaf_productview extends leafView {
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
				t3lib_div::devLog('wrapTitle (productview) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// Max. size for Title of 30
		$title = ('' != trim($title)) ? t3lib_div::fixed_lgd_cs($title, 30) : $this->getLL('leaf.noTitle');

		$aOnClick = 'return link_folder(\'' . $this->getJumpToParam($row) . '\');';

		$style = ($row['uid'] == $this->openProd) ? 'style="color: red; font-weight: bold"' : '';
		$res = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '" ' . $style . '>' . $title . '</a>';

		return $res;
	}

	/**
	 * @param integer $uid
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
				t3lib_div::devLog('getJumpToParam (productview) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}
		$res = 'commerce:tx_commerce_products:' . $row['uid'] . '|tx_commerce_categories:' . $row['item_parent'];
		return $res;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/link/class.tx_commerce_leaf_productview.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/link/class.tx_commerce_leaf_productview.php']);
}

?>