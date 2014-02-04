<?php

class Tx_Commerce_Tree_StatisticTree extends t3lib_browseTree {
	/**
	 * @var integer
	 */
	public $ext_showPageId;

	/**
	 * @var boolean
	 */
	public $ext_IconMode;

	/**
	 * Calls init functions
	 *
	 * @return self
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->treeName = 'statistics';
	}

	/**
	 * Wrapping icon in browse tree
	 *
	 * @param string $icon Icon IMG code
	 * @param array $row Data row for element.
	 * @return string Page icon
	 */
	public function wrapIcon($icon, &$row) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// If the record is locked, present a warning sign.
		if ($lockInfo = t3lib_BEfunc::isRecordLocked('pages', $row['uid'])) {
			$aOnClick = 'alert(' . $language->JScharCode($lockInfo['msg']) . ');return false;';
			$lockIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'status-warning-in-use',
					array('title' => htmlspecialchars($lockInfo['msg']))
				) .
				'</a>';
		} else {
			$lockIcon = '';
		}

			// Add title attribute to input icon tag
		$thePageIcon = $this->addTagAttributes($icon, $this->titleAttrib . '="' . $this->getTitleAttrib($row) . '"');

			// Wrap icon in click-menu link.
		if (!$this->ext_IconMode) {
			/** @var template $tbeTemplate */
			$tbeTemplate = $GLOBALS['TBE_TEMPLATE'];

			$thePageIcon = $tbeTemplate->wrapClickMenuOnIcon($thePageIcon, 'pages', $row['uid'], 0, '&bank=' . $this->bank);
		} elseif (!strcmp($this->ext_IconMode, 'titlelink')) {
			$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($row) . '\',this,\'' . $this->treeName . '\');';
			$thePageIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $thePageIcon . '</a>';
		}

			// Add Page ID:
		if ($this->ext_showPageId) {
			$pageIdStr = '[' . $row['uid'] . ']&nbsp;';
		} else {
			$pageIdStr = '';
		}

		return $thePageIcon . $lockIcon . $pageIdStr;
	}

	/**
	 * Adds a red "+" to the input string, $str, if the field "php_tree_stop" in the $row (pages) is set
	 *
	 * @param string $str Input string, like a page title for the tree
	 * @param array $row record row with "php_tree_stop" field
	 * @return string Modified string
	 */
	public function wrapStop($str, $row) {
		if ($row['php_tree_stop']) {
			$str .= '<a href="' .
				htmlspecialchars(t3lib_div::linkThisScript(array('setTempDBmount' => $row['uid']))) . '" class="typo3-red">+</a> ';
		}
		return $str;
	}
}

class_alias('Tx_Commerce_Tree_StatisticTree', 'tx_commerce_statistic_pagetree');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_statistic/class.tx_commerce_statistic_pagetree.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_statistic/class.tx_commerce_statistic_pagetree.php']);
}

?>