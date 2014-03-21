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
 * Implements the view of the leaf
 */
class Tx_Commerce_Tree_Leaf_View extends Tx_Commerce_Tree_Leaf_Base {
	/**
	 * @var boolean
	 */
	protected $leafIndex = FALSE;

	/**
	 * @var array
	 */
	protected $parentIndices;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * Iconpath and Iconname
	 *
	 * @var string
	 */
	protected $iconPath = '../typo3conf/ext/commerce/Resources/Public/Icons/Table/';

	/**
	 * @var string
	 */
	protected $iconName;

	/**
	 * Back Path
	 *
	 * @var string
	 */
	protected $backPath = '../../../../typo3/';

	/**
	 * Prefix for DOM Id
	 *
	 * @var string
	 */
	protected $domIdPrefix = 'txcommerceLeaf';

	/**
	 * HTML title attribute
	 *
	 * @var string
	 */
	protected $titleAttrib = 'title';

	/**
	 * Item UID of the Mount for this View
	 *
	 * @var integer
	 */
	protected $bank;

	/**
	 * Name of the Tree
	 *
	 * @var string
	 */
	protected $treeName;

	/**
	 * @var string
	 */
	protected $rootIconName = 'commerce_globus.gif';

	/**
	 * @var string
	 */
	protected $cmd;

	/**
	 * Should clickmenu be enabled
	 *
	 * @var boolean
	 */
	protected $noClickmenu;

	/**
	 * Should the root item have a title-onclick?
	 *
	 * @var boolean
	 */
	protected $noRootOnclick = FALSE;

	/**
	 * should the otem in general have a title-onclick?
	 *
	 * @var boolean
	 */
	protected $noOnclick = FALSE;

	/**
	 * use real values for leafs that otherwise just have "edit"
	 *
	 * @var boolean
	 */
	protected $realValues = FALSE;

	/**
	 * Internal
	 *
	 * @var string
	 */
	protected $icon;

	/**
	 * @var boolean
	 */
	protected $iconGenerated = FALSE;

	/**
	 * @var boolean
	 */
	public $showDefaultTitleAttribute = FALSE;

	/**
	 * Initialises the variables iconPath and backPath
	 *
	 * @return self
	 */
	public function __construct() {
		parent::__construct();

		if (t3lib_div::int_from_ver(TYPO3_version) >= '4002007') {
			$rootPathT3 = t3lib_div::getIndpEnv('TYPO3_SITE_PATH');
		} else {
				// Code TYPO3 Site Path manually, backport from TYPO3 4.2.7 svn
			$rootPathT3 = substr(t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST')));
		}

			// If we don't have any data, set /
		if (empty($rootPathT3)) {
			$rootPathT3 = '/';
		}

		if ($GLOBALS['BACK_PATH']) {
			$this->backPath = $GLOBALS['BACK_PATH'];
		} else {
			$this->backPath = $rootPathT3 . TYPO3_mainDir;
		}
		$this->iconPath = $this->backPath . PATH_TXCOMMERCE_ICON_TREE_REL;
	}

	/**
	 * Sets the Leaf Index
	 *
	 * @param integer $index Leaf Index
	 * @return void
	 */
	public function setLeafIndex($index) {
		if (!is_numeric($index)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setLeafIndex (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->leafIndex = $index;
	}

	/**
	 * Sets the parent indices
	 *
	 * @return void
	 * @param array $indices Array with the Parent Indices
	 */
	public function setParentIndices(array $indices) {
		$this->parentIndices = $indices;
	}

	/**
	 * Sets the bank
	 *
	 * @param integer $bank - Category UID of the Mount (aka Bank)
	 * @return void
	 */
	public function setBank($bank) {
		if (!is_numeric($bank)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setBank (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->bank = $bank;
	}

	/**
	 * Sets the Tree Name of the Parent Tree
	 *
	 * @return void
	 * @param string $name Name of the tree
	 */
	public function setTreeName($name) {
		if (!is_string($name)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setTreeName (Tx_Commerce_Tree_Leaf_View) gets passed wrong-cast parameters. Should be string but is not.', COMMERCE_EXTKEY, 2);
			}
		}
		$this->treeName = $name;
	}

	/**
	 * Sets if the clickmenu should be enabled for this Tx_Commerce_Tree_Leaf_View
	 *
	 * @param boolean $flag [optional] Flag
	 * @return void
	 */
	public function noClickmenu($flag = TRUE) {
		$this->noClickmenu = (bool) $flag;
	}

	/**
	 * Sets if the root onlick should be enabled for this Tx_Commerce_Tree_Leaf_View
	 *
	 * @return void
	 * @param boolean $flag [optional]	Flag
	 */
	public function noRootOnclick($flag = TRUE) {
		$this->noRootOnclick = (bool)$flag;
	}

	/**
	 * Sets the noClick for the title
	 *
	 * @return void
	 * @param boolean $flag
	 */
	public function noOnclick($flag = TRUE) {
		$this->noOnclick = $flag;
	}

	/**
	 * Will set the real values to the views
	 * for products and articles, instead of "edit"
	 *
	 * @return void
	 */
	public function substituteRealValues() {
		$this->realValues = TRUE;
	}

	/**
	 * Get icon for the row.
	 * If $this->iconPath and $this->iconName is set, try to get icon based on those values.
	 *
	 * @param array $row Item row.
	 * @param integer $categoryUid
	 * @return string Image tag.
	 */
	public function getIcon($row, $categoryUid = 0) {
		if ($this->iconPath && $this->iconName) {
			$icon = '<img' . t3lib_iconWorks::skinImg('', $this->iconPath . $this->iconName, 'width="18" height="16"') . ' alt=""' .
				($this->showDefaultTitleAttribute ? ' title="UID: ' . $row['uid'] . '"' : '') . ' />';
		} else {
			$icon = t3lib_iconWorks::getSpriteIconForRecord($this->table, $row, array(
				'title' => ($this->showDefaultTitleAttribute ? 'UID: ' . $row['uid'] : $this->getTitleAttrib($row)),
				'class' => 'c-recIcon'
			));
		}

		$additionalParams = '';
		if ($categoryUid) {
			$additionalParams .= '&category=' . $categoryUid;
		}
		trim($additionalParams, ',');

		return $this->wrapIcon($icon, $row, urlencode($additionalParams));
	}

	/**
	 * Get the icon for the root
	 * $this->iconPath and $this->rootIconName have to be set
	 *
	 * @param array $row
	 * @return string Image tag
	 */
	public function getRootIcon($row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getRootIcon (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		$icon = '<img' . t3lib_iconWorks::skinImg($this->iconPath, $this->rootIconName, 'width="18" height="16"') . ' title="Root" alt="" />';

		return $this->wrapIcon($icon, $row);
	}

	/**
	 * Wraps the Icon in a <span>
	 *
	 * @param string $icon
	 * @param array $row
	 * @param string $additionalParams
	 * @return string HTML Code
	 */
	public function wrapIcon($icon, $row, $additionalParams = '') {
		if (!is_array($row) || !is_string($additionalParams)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('wrapIcon (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// Wrap the Context Menu on the Icon if it is allowed
		if (isset($GLOBALS['TBE_TEMPLATE']) && !$this->noClickmenu) {
			/** @var template $template */
			$template = $GLOBALS['TBE_TEMPLATE'];
			$template->backPath = $this->backPath;
			$icon = $template->wrapClickMenuOnIcon($icon, $this->table, $row['uid'], 0, $additionalParams);
		}
		return $icon;
	}

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param string $title string
	 * @param string $row item record
	 * @param integer $bank pointer (which mount point number)
	 * @return string
	 * @access private
	 */
	public function wrapTitle($title, $row, $bank = 0) {
		if (!is_array($row) || !is_numeric($bank)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('wrapTitle (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// Max. size for Title of 255
		$title = ('' != $title) ? t3lib_div::fixed_lgd_cs($title, 255) : $this->getLL('leaf.noTitle');

		$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($row) . '\',this,\'' . $this->domIdPrefix . $row['uid'] . '_' . $bank . '\',\'\');';

		$res = (($this->noRootOnclick && 0 == $row['uid']) || $this->noOnclick) ?
			htmlspecialchars(strip_tags($title)) :
			'<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . htmlspecialchars(strip_tags($title)) . '</a>';

		return $res;
	}

	/**
	 * Returns the value for the image "title" attribute
	 *
	 * @param array $row The input row array (where the key "title" is used for the title)
	 * @return string The attribute value (is htmlspecialchared() already)
	 * @see wrapIcon()
	 */
	public function getTitleAttrib($row) {
		return htmlspecialchars('[' . $row['uid'] . '] ' . $row['title']);
	}

	/**
	 * returns the link from the tree used to jump to a destination
	 *
	 * @param array $row - Array with the ID Information
	 * @return string
	 */
	public function getJumpToParam($row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getJumpToParam (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		$res = 'id=' . $row['uid'];
		return $res;
	}

	/**
	 * Generate the plus/minus icon for the browsable tree.
	 *
	 * @param array $row record for the entry
	 * @param integer $isLast The current entry number
	 * @param integer $isExpanded The total number of entries. If equal to $a, a "bottom" element is returned.
	 * @param boolean $isBank The element was expanded to render subelements if this flag is set.
	 * @param boolean $hasChildren The Element is a Bank if this flag is set.
	 * @return string Image tag with the plus/minus icon.
	 * @see t3lib_pageTree::PMicon()
	 */
	public function PMicon(&$row, $isLast, $isExpanded, $isBank = FALSE, $hasChildren = FALSE) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('PMicon (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		$PM = $hasChildren ? ($isExpanded ? 'minus' : 'plus') : 'join';
		$BTM = ($isLast) ? 'bottom' : '';
			// If the current row is a bank, display only the plus/minus
		$BTM  = ($isBank) ? '' : $BTM;
		$icon = '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/' . $PM . $BTM . '.gif', 'width="18" height="16"') . ' alt="" />';

		if ($hasChildren) {
				// Calculate the command
			$indexFirst = (0 >= count($this->parentIndices)) ? $this->leafIndex : $this->parentIndices[0];

			$cmd = array($this->treeName, $indexFirst, $this->bank, ($isExpanded ? 0 : 1));

				// Add the parentIndices to the Command (also its own index since it has not been added if we HAVE parent indices
			if (0 < count($this->parentIndices)) {
				$l = count($this->parentIndices);

					// Add parent indices - first parent Index is already in the command
				for ($i = 1; $i < $l; $i ++) {
					$cmd[] = $this->parentIndices[$i];
				}

					// Add its own index at the very end
				$cmd[] = $this->leafIndex;
			}

				// Append the row UID | Parent Item under which this row stands
			$cmd[] = $row['uid'] . '|' . $row['item_parent'];
				// Overwrite the Flag for expanded
			$cmd[3] = ($isExpanded ? 0 : 1);

				// Make the string-command
			$cmd = implode('_', $cmd);

			$icon = $this->PMiconATagWrap($icon, $cmd, !$isExpanded);
		}
		return $icon;
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @param boolean $isExpand
	 * @return string Link-wrapped input string
	 * @access private
	 */
	protected function PMiconATagWrap($icon, $cmd, $isExpand = TRUE) {
		if (!is_string($icon) || !is_string($cmd)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('PMiconATagWrap (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// activate dynamic ajax-based tree
		$js = htmlspecialchars('Tree.load(\'' . $cmd . '\', ' . (int) $isExpand . ', this);');
		return '<a class="pm" onclick="' . $js . '">' . $icon . '</a>';
	}
}

class_alias('Tx_Commerce_Tree_Leaf_View', 'leafView');

?>