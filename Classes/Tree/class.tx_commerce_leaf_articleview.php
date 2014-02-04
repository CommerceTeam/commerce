<?php
/**
 * Implements the View for Articles
 */
class tx_commerce_leaf_articleview extends leafView {
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
	 * @param string $row record
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

			// Max. size for Title of 30
		$title = ('' != trim($title)) ? t3lib_div::fixed_lgd_cs($title, 30) : $this->getLL('leaf.noTitle');

		$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($row) . '\',this,\'' . $this->domIdPrefix . $row['uid'] . '_' .
			$bank . '\',\'alt_doc.php\');';

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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_articleview.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_articleview.php']);
}

?>