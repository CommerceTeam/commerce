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
 * Extension class for the BrowseTreeView class,
 * specially made for browsing pages in the Web module
 *
 * Class Tx_Commerce_Tree_OrderTree
 *
 * @author 2010-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Tree_OrderTree extends \TYPO3\CMS\Backend\Tree\View\BrowseTreeView {
	/**
	 * Extension page id
	 *
	 * @var int
	 */
	public $ext_showPageId;

	/**
	 * Extension icon mode
	 *
	 * @var bool
	 */
	public $ext_IconMode;

	/**
	 * @var string
	 */
	public $thisScript = 'navigation.php';

	/**
	 * Calls init functions
	 *
	 * @return self
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialization
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->treeName = 'orders';
	}

	/**
	 * Wrapping icon in browse tree
	 *
	 * @param string $icon IMG code
	 * @param array $row Data row for element.
	 *
	 * @return string Page icon
	 */
	public function wrapIcon($icon, array &$row) {
		$language = $this->getLanguageService();

		// If the record is locked, present a warning sign.
		if (($lockInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::isRecordLocked('pages', $row['uid']))) {
			$aOnClick = 'alert(' . $language->JScharCode($lockInfo['msg']) . ');return false;';
			$lockIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' .
				\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(
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
			/**
			 * Template
			 *
			 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate $tbeTemplate
			 */
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
	 * Adds a red "+" to the input string, $str, if the field
	 * "php_tree_stop" in the $row (pages) is set
	 *
	 * @param string $str Input string, like a page title for the tree
	 * @param array $row Record row with "php_tree_stop" field
	 *
	 * @return string Modified string
	 * @access private
	 */
	public function wrapStop($str, array $row) {
		if ($row['php_tree_stop']) {
			$str .= '<a href="' .
				htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('setTempDBmount' => $row['uid']))) .
				'" class="typo3-red">+</a> ';
		}
		return $str;
	}


	/**
	 * Get language service
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}
