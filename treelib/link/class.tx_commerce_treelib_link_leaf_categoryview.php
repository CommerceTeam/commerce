<?php
/**
 * Implements the leafview for the Category
 */
class tx_commerce_treelib_link_leaf_categoryview extends leafView {
	/**
	 * DB Table ##isnt this read automatically?###
	 *
	 * @var string
	 */
	protected $table = 'tx_commerce_categories';

	/**
	 * @var string
	 */
	protected $domIdPrefix = 'txcommerceCategory';

	/**
	 * the linked category
	 *
	 * @var integer
	 */
	protected $openCat = 0;

	/**
	 * returns the link from the tree used to jump to a destination
	 *
	 * @param array $row - Array with the ID Information
	 * @return string
	 */
	public function getJumpToParam($row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getJumpToParam (leafview) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		return 'commerce:tx_commerce_categories:' . $row['uid'];
	}

	/**
	 * @param $uid
	 * @return void
	 */
	public function setOpenCategory($uid) {
		$this->openCat = $uid;
	}

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param string $title string
	 * @param string $row record
	 * @param integer $bank pointer (which mount point number)
	 * @return	string
	 * @access private
	 */
	public function wrapTitle($title, $row, $bank = 0) {
		if (!is_array($row) || !is_numeric($bank)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('wrapTitle (leafview) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// Max. size for Title of 30
		$title = ('' != $title) ? t3lib_div::fixed_lgd_cs($title, 30) : $this->getLL('leaf.noTitle');

		$aOnClick = 'return link_folder(\'' . $this->getJumpToParam($row) . '\');';
		$style = ($row['uid'] == $this->openCat && 0 != $this->openCat) ? 'style="color: red; font-weight: bold"' : '';
		$res = (($this->noRootOnclick && 0 == $row['uid']) || $this->noOnclick) ?
			$title :
			'<a href="#" onclick="' . htmlspecialchars($aOnClick) . '" ' . $style . '>' . $title . '</a>';

		return $res;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/link/class.tx_commerce_treelib_link_leaf_categoryview.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/link/class.tx_commerce_treelib_link_leaf_categoryview.php']);
}

?>