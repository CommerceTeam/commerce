<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Ingo Schmitt <is@marketing-factory.de>
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
 * Extension class for the BrowseTreeView class,
 * specially made for browsing pages in the Web module
 */
class Tx_Commerce_Tree_OrderTree extends \TYPO3\CMS\Backend\Tree\View\BrowseTreeView {
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
		$this->treeName = 'orders';
	}

	/**
	 * Wrapping icon in browse tree
	 *
	 * @param string $icon IMG code
	 * @param array $row Data row for element.
	 * @return string Page icon
	 */
	public function wrapIcon($icon, &$row) {
		/** @var \TYPO3\CMS\Lang\LanguageService $language */
		$language = $GLOBALS['LANG'];

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
			/** @var \TYPO3\CMS\Backend\Template\DocumentTemplate $tbeTemplate */
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
	 * @param array $row record row with "php_tree_stop" field
	 * @return string Modified string
	 * @access private
	 */
	public function wrapStop($str, $row) {
		if ($row['php_tree_stop']) {
			$str .= '<a href="' .
				htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('setTempDBmount' => $row['uid']))) .
				'" class="typo3-red">+</a> ';
		}
		return $str;
	}
}
