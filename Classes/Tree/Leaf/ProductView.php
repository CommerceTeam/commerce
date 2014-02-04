<?php
/**
 * Implements the Tx_Commerce_Tree_Leaf_View for Product
 */
class Tx_Commerce_Tree_Leaf_ProductView extends Tx_Commerce_Tree_Leaf_View {
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
	 * Wrapping $title in a-tags.
	 *
	 * @param string $title Title string
	 * @param array $row Item record
	 * @param integer $bank Bank pointer (which mount point number)
	 * @return string
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

		$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($row) . '\',this,\'' . $this->domIdPrefix . $row['uid'] . '_' . $bank . '\',\'alt_doc.php\');';

		$res = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';

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
				t3lib_div::devLog('getJumpToParam (productview) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
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

class_alias('Tx_Commerce_Tree_Leaf_ProductView', 'tx_commerce_leaf_productview');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/ProductView.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/ProductView.php']);
}

?>