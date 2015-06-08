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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Implements the view of the leaf
 *
 * Class Tx_Commerce_Tree_Leaf_View
 *
 * @author 2008-2014 Erik Frister <typo3@marketing-factory.de>
 */
class Tx_Commerce_Tree_Leaf_View extends Tx_Commerce_Tree_Leaf_Base {
	/**
	 * @var bool
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
	 * @var int
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
	 * @var bool
	 */
	protected $noClickmenu;

	/**
	 * Should the root item have a title-onclick?
	 *
	 * @var bool
	 */
	protected $noRootOnclick = FALSE;

	/**
	 * Should the otem in general have a title-onclick?
	 *
	 * @var bool
	 */
	protected $noOnclick = FALSE;

	/**
	 * Use real values for leafs that otherwise just have "edit"
	 * this is needed for the parent category tree in records
	 *
	 * @var bool
	 */
	protected $realValues = FALSE;

	/**
	 * Internal
	 *
	 * @var string
	 */
	protected $icon;

	/**
	 * @var bool
	 */
	protected $iconGenerated = FALSE;

	/**
	 * @var bool
	 */
	public $showDefaultTitleAttribute = FALSE;

	/**
	 * @var int
	 */
	protected $parentCategory = 0;

	/**
	 * Initialises the variables iconPath and backPath
	 *
	 * @return self
	 */
	public function __construct() {
		parent::__construct();

		$rootPathT3 = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');

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
	 * @param int $index Leaf Index
	 *
	 * @return void
	 */
	public function setLeafIndex($index) {
		if (!is_numeric($index)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('setLeafIndex (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
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
	 * @param int $bank Category UID of the Mount (aka Bank)
	 *
	 * @return void
	 */
	public function setBank($bank) {
		if (!is_numeric($bank)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('setBank (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->bank = $bank;
	}

	/**
	 * Sets the Tree Name of the Parent Tree
	 *
	 * @param string $name Name of the tree
	 *
	 * @return void
	 */
	public function setTreeName($name) {
		if (!is_string($name)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog(
					'setTreeName (Tx_Commerce_Tree_Leaf_View) gets passed wrong-cast parameters. Should be string but is not.',
					COMMERCE_EXTKEY,
					2
				);
			}
		}
		$this->treeName = $name;
	}

	/**
	 * Sets if the clickmenu should be enabled for this Tx_Commerce_Tree_Leaf_View
	 *
	 * @param bool $flag Flag
	 *
	 * @return void
	 */
	public function noClickmenu($flag = TRUE) {
		$this->noClickmenu = (bool) $flag;
	}

	/**
	 * Sets if the root onlick should be enabled for this Tx_Commerce_Tree_Leaf_View
	 *
	 * @param bool $flag Flag
	 *
	 * @return void
	 */
	public function noRootOnclick($flag = TRUE) {
		$this->noRootOnclick = (bool)$flag;
	}

	/**
	 * Sets the noClick for the title
	 *
	 * @param bool $flag
	 *
	 * @return void
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
	 * If $this->iconPath and $this->iconName is set,
	 * 	try to get icon based on those values.
	 *
	 * @param array $row Item row.
	 *
	 * @return string Image tag.
	 */
	public function getIcon($row) {
		if ($this->iconPath && $this->iconName) {
			$icon = '<img' . IconUtility::skinImg('', $this->iconPath . $this->iconName, 'width="18" height="16"') . ' alt=""' .
				($this->showDefaultTitleAttribute ? ' title="UID: ' . $row['uid'] . '"' : '') . ' />';
		} else {
			$icon = IconUtility::getSpriteIconForRecord($this->table, $row, array(
				'title' => ($this->showDefaultTitleAttribute ? 'UID: ' . $row['uid'] : $this->getTitleAttrib($row)),
				'class' => 'c-recIcon'
			));
		}

		return $this->wrapIcon($icon, $row);
	}

	/**
	 * Get the icon for the root
	 * $this->iconPath and $this->rootIconName have to be set
	 *
	 * @param array $row
	 *
	 * @return string Image tag
	 */
	public function getRootIcon($row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('getRootIcon (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		$icon = '<img' . IconUtility::skinImg($this->iconPath, $this->rootIconName, 'width="18" height="16"') .
			' title="Root" alt="" />';

		return $this->wrapIcon($icon, $row);
	}

	/**
	 * Wraps the Icon in a <span>
	 *
	 * @param string $icon
	 * @param array $row
	 * @param string $additionalParams
	 *
	 * @return string HTML Code
	 */
	public function wrapIcon($icon, $row, $additionalParams = '') {
		if (!is_array($row) || !is_string($additionalParams)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('wrapIcon (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		if ($additionalParams == '' && $row['uid']) {
			$additionalParams = '&control[' . $this->table . '][uid]=' . $row['uid'];

			switch (get_class($this)) {
				case 'Tx_Commerce_Tree_Leaf_CategoryView':
					$additionalParams .= '&parentCategory=' . $row['uid'];
					break;

				case 'Tx_Commerce_Tree_Leaf_ProductView':
					$additionalParams .= '&parentCategory=' . $row['item_parent'];
					break;

				default:
			}

			$additionalParams = urlencode($additionalParams);
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
	 * @param string $row Item record
	 * @param int $bank Pointer (which mount point number)
	 *
	 * @return string
	 * @access private
	 */
	public function wrapTitle($title, $row, $bank = 0) {
		if (!is_array($row) || !is_numeric($bank)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('wrapTitle (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// Max. size for Title of 255
		$title = ('' != $title) ? GeneralUtility::fixed_lgd_cs($title, 255) : $this->getLL('leaf.noTitle');

		$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($row) . '\',this,\'' . $this->domIdPrefix .
			$row['uid'] . '_' . $bank . '\',\'\');';

		$res = (($this->noRootOnclick && 0 == $row['uid']) || $this->noOnclick) ?
			htmlspecialchars(strip_tags($title)) :
			'<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . htmlspecialchars(strip_tags($title)) . '</a>';

		return $res;
	}

	/**
	 * Returns the value for the image "title" attribute
	 *
	 * @param array $row The input row array (where the key "title"
	 * 	is used for the title)
	 *
	 * @return string The attribute value (is htmlspecialchared() already)
	 */
	public function getTitleAttrib($row) {
		return htmlspecialchars('[' . $row['uid'] . '] ' . $row['title']);
	}

	/**
	 * Link from the tree used to jump to a destination
	 *
	 * @param array $row Array with the ID Information
	 *
	 * @return string
	 */
	public function getJumpToParam($row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('getJumpToParam (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		$res = 'id=' . $row['uid'];
		return $res;
	}

	/**
	 * Generate the plus/minus icon for the browsable tree.
	 *
	 * @param array $row Record for the entry
	 * @param int $isLast The current entry number
	 * @param int $isExpanded The total number of entries.
	 * 	If equal to $a, a "bottom" element is returned.
	 * @param bool $isBank The element was expanded to render
	 * 	subelements if this flag is set.
	 * @param bool $hasChildren The Element is a Bank if this flag is set.
	 *
	 * @return string Image tag with the plus/minus icon.
	 */
	public function PMicon($row, $isLast, $isExpanded, $isBank = FALSE, $hasChildren = FALSE) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('PMicon (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		$plusMinus = $isExpanded ? '-minus' : '-plus';
		$plusMinus = $hasChildren ? $plusMinus : '';
		$bottom = ($isLast) ? '-end' : '';
		$bottom  = ($isBank) ? '' : $bottom;
		$icon = '<img alt="" src="/' . TYPO3_mainDir . 'clear.gif" class="x-tree-ec-icon x-tree-elbow' . $bottom . $plusMinus . '">';

		if ($hasChildren) {
				// Calculate the command
			$indexFirst = (0 >= count($this->parentIndices)) ? $this->leafIndex : $this->parentIndices[0];

			$cmd = array($this->treeName, $indexFirst, $this->bank, ($isExpanded ? 0 : 1));

			// Add the parentIndices to the Command (also its own index since
			// it has not been added if we HAVE parent indices
			if (0 < count($this->parentIndices)) {
				$l = count($this->parentIndices);

					// Add parent indices - first parent Index is already in the command
				for ($i = 1; $i < $l; $i++) {
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
	 * @param bool $isExpand
	 *
	 * @return string Link-wrapped input string
	 */
	protected function PMiconATagWrap($icon, $cmd, $isExpand = TRUE) {
		if (!is_string($icon) || !is_string($cmd)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('PMiconATagWrap (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// activate dynamic ajax-based tree
		$js = htmlspecialchars('Tree.load(' . GeneralUtility::quoteJSvalue($cmd) . ', ' . (int)$isExpand . ', this);');
		return '<a class="pm" onclick="' . $js . '">' . $icon . '</a>';
	}
}
