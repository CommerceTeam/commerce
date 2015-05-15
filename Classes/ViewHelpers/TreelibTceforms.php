<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2003-2012 Rene Fritz <r.fritz@colorcube.de>
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TCEforms functions for handling and rendering of trees for group/select
 * elements If we want to display a browseable tree, we need to run the tree
 * in an iframe element. In consequence this means that the display of the
 * browseable tree needs to be generated from an extra script.
 * This is the base class for such a script.
 * The class itself do not render the tree but call tceforms to render the field.
 * In beforehand the TCA config value of treeViewBrowseable will be set to
 * 'iframeContent' to force the right rendering. That means the script do not
 * know anything about trees. It just set parameters and render the field with
 * TCEforms.
 */
class Tx_Commerce_ViewHelpers_TreelibTceforms {
	/**
	 * count rendered tree items - just for frame height calculation
	 *
	 * @var integer
	 */
	public $treeItemC = 0;

	/**
	 * count rendered trees
	 *
	 * @var integer
	 */
	public $treesC = 0;

	/**
	 * Rendered trees as HTML
	 *
	 * @var string
	 * @access private
	 */
	protected $treeContent = '';

	/**
	 * itemArray for usage in TCEforms
	 * This holds the original values
	 *
	 * @var array
	 * @access private
	 */
	protected $itemArray = array();

	/**
	 * itemArray for usage in TCEforms
	 * This holds the processed values with titles/labels
	 *
	 * @var array
	 * @access private
	 */
	protected $itemArrayProcessed = array();

	/**
	 * Defines if the content of the iframe should be
	 * rendered instead of the iframe itself.
	 * This is for iframe mode.
	 *
	 * @var boolean
	 * @access private
	 */
	protected $iframeContentRendering = FALSE;

	/**
	 * Defines the prefix used for JS code to call the parent window.
	 * This is for iframe mode.
	 *
	 * @var string
	 * @access private
	 */
	protected $jsParent = '';

	/**
	 * @var t3lib_TCEforms
	 */
	public $tceforms;

	/**
	 * @var array
	 */
	public $PA;

	/**
	 * @var string
	 */
	public $table;

	/**
	 * @var string
	 */
	public $field;

	/**
	 * @var array
	 */
	public $row;

	/**
	 * @var array
	 */
	public $config;

	/**
	 * @var string
	 */
	public $treeBrowserScript;

	/**
	 * @var language
	 */
	public $language;

	/**
	 * Getter / Setter
	 */

	/**
	 * Init
	 *
	 * @param array $parameter An array with additional configuration options.
	 *
	 * @return void
	 */
	public function init($parameter) {
		$this->tceforms = & $parameter['pObj'];
		$this->PA = & $parameter;

		$this->table = $parameter['table'];
		$this->field = $parameter['field'];
		$this->row = $parameter['row'];
		$this->config = $parameter['fieldConf']['config'];

		$this->language = $GLOBALS['LANG'];

		// set currently selected items
		$itemArray = GeneralUtility::trimExplode(',', $this->PA['itemFormElValue'], TRUE);
		$this->setItemArray($itemArray);

		$this->setIframeContentRendering($this->config['treeViewBrowseable'] === 'iframeContent');
	}

	/**
	 * Enable the iframe content rendering mode
	 *
	 * @param boolean $iFrameContentRendering
	 * @param string $jsParent
	 * @return void
	 */
	public function setIframeContentRendering($iFrameContentRendering = TRUE, $jsParent = 'parent.') {
		if (($this->iframeContentRendering = $iFrameContentRendering)) {
			$this->jsParent = $jsParent;
		} else {
			$this->jsParent = '';
		}
	}

	/**
	 * Returns true if iframe content rendering mode is enabled
	 *
	 * @return boolean
	 */
	public function isIframeContentRendering() {
		return $this->iframeContentRendering;
	}

	/**
	 * Returns true if iframe content rendering mode is enabled
	 *
	 * @return boolean
	 */
	public function isIframeRendering() {
		return ($this->config['treeViewBrowseable'] && !$this->iframeContentRendering);
	}

	/**
	 * Set the selected items
	 *
	 * @param array $itemArray
	 * @return void
	 */
	public function setItemArray($itemArray) {
		$this->itemArray = $itemArray;
	}

	/**
	 * Return the processed aray of selected items
	 *
	 * @return array
	 */
	public function getItemArrayProcessed() {
		return $this->itemArrayProcessed;
	}

	/**
	 * Return the count value of selectable items
	 *
	 * @return integer
	 */
	public function getItemCountSelectable() {
		return $this->treeItemC;
	}

	/**
	 * Return the count value of rendered trees
	 *
	 * @return integer
	 */
	public function getItemCountTrees() {
		return $this->treesC;
	}

	/**
	 * Returns the rendered trees (HTML)
	 *
	 * @return string
	 */
	public function getTreeContent() {
		return $this->treeContent;
	}

	/**********************************************************
	 * Rendering
	 ************************************************************/

	/**
	 * Renders the category tree for mounts
	 *
	 * @param object $browseTree Category Tree
	 * @return string the rendered trees (HTML)
	 */
	public function renderBrowsableMountTrees($browseTree) {
		$this->treeContent = $browseTree->getBrowseableTree();

		return $this->treeContent;
	}

	/**********************************************************
	 * Div-Frame specific stuff
	 ************************************************************/

	/**
	 * Returns div HTML code which includes the rendered tree(s).
	 *
	 * @param string $width CSS width definition
	 * @param string $height CSS height definition
	 * @return string HTML content
	 */
	public function renderDivBox($width = NULL, $height = NULL) {
		if ($width == NULL) {
			list($width, $height) = $this->calcFrameSizeCss();
		}
		$divStyle = 'position:relative; left:0px; top:0px; height:' . $height . '; width:' . $width .
			';border:solid 1px;overflow:auto;background:#fff;';
		$divFrame = '<div  name="' . $this->PA['itemFormElName'] . '_selTree" style="' . htmlspecialchars($divStyle) . '">';

		$divFrame .= $this->treeContent;
		$divFrame .= '</div>';

			// include function
		$divFrame .= '<script type="text/javascript">';
		$divFrame .= '
			function jumpTo(id,linkObj,highLightID,script)	{
				var catUid = id.substr(id.lastIndexOf("=") + 1); //We can leave out the "="
				var text   = (linkObj.firstChild) ? linkObj.firstChild.nodeValue : "Unknown";
				//Params (field, value, caption)
				setFormValueFromBrowseWin("' . $this->PA['itemFormElName'] . '", catUid, text);
			}';
		$divFrame .= '</script>';
		$divFrame .= '<script src="' . $this->tceforms->backPath . 'js/tree.js"></script>
			<script type="text/javascript">
			Tree.ajaxID = "Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper::ajaxExpandCollapse";
			</script>
		';

		return $divFrame;
	}

	/*************************************************************
	 * IFrame specific stuff
	 ************************************************************/

	/**
	 * Set the script to be called for the iframe tree browser.
	 *
	 * @param string $script Path to the script
	 * @return void
	 */
	public function setIframeTreeBrowserScript($script) {
		$this->treeBrowserScript = $script;
	}

	/**
	 * Returns iframe HTML code to call the tree browser script.
	 *
	 * @param string $width CSS width definition
	 * @param string $height CSS height definition
	 * @return string HTML content
	 */
	public function renderIframe($width = NULL, $height = NULL) {
		if (!$this->treeBrowserScript) {
			die ('Tx_Commerce_ViewHelpers_TreelibTceforms: treeBrowserScript is not set!');
		}

		if ($width == NULL) {
			list($width, $height) = $this->calcFrameSizeCss();
		}

		$table = $GLOBALS['TCA'][$this->table]['orig_table'] ?
			$GLOBALS['TCA'][$this->table]['orig_table'] :
			$this->table;

		$iFrameParameter = $this->getIframeParameter($table, $this->field, $this->row['uid']);

		$divStyle = 'height:' . $height . '; width:' . $width . '; border:solid 1px #000; background:#fff;';
		$iFrame = '<iframe src="' . htmlspecialchars(
				$this->treeBrowserScript . '?' . $iFrameParameter
			) . '" name="' . $this->PA['itemFormElName'] . '_selTree" border="1" style="' . htmlspecialchars($divStyle) . '">';
		$iFrame .= '</iframe>';

		return $iFrame;
	}

	/**
	 * Returns GET parameter string to be passed to the tree browser script.
	 *
	 * @param    string $table
	 * @param    string $field
	 * @param    string $uid
	 * @return    string
	 * @see tx_dam_treelib_browser
	 */
	public function getIframeParameter($table, $field, $uid) {
		$params = array();

		$config = '';
		if ($GLOBALS['TCA'][$table]['columns'][$field]['config']['type'] == 'flex') {
			$config = base64_encode(serialize($this->PA['fieldConf']));
		}

		$allowProducts = 0;

		if (1 == $this->config['allowProducts']) {
			$allowProducts = 1;
		}

		$params['table'] = $table;
		$params['field'] = $field;
		$params['uid'] = $uid;
		$params['elname'] = $this->PA['itemFormElName'];
		$params['config'] = $config;
		$params['allowProducts'] = $allowProducts;
		$params['seckey'] = GeneralUtility::shortMD5(
			implode('|', $params) . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
		);

		return GeneralUtility::implodeArrayForUrl('', $params);
	}

	/**********************************************************
	 * Rendering tools
	 ************************************************************/

	/**
	 * calculate size of the tree frame
	 *
	 * @param integer $itemCountSelectable
	 * @return array array($width, $height)
	 */
	public function calcFrameSizeCss($itemCountSelectable = NULL) {
		if ($itemCountSelectable === NULL) {
			$itemCountSelectable = max(1, $this->treeItemC + $this->treesC + 1);
		}

		$width = '240px';

		$this->config['autoSizeMax'] = max($this->config['autoSizeMax'], 0);
		$height = $this->config['autoSizeMax'] ?
			\TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(
				$itemCountSelectable,
				\TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->config['size'], 1), $this->config['autoSizeMax']
			) :
			$this->config['size'];

			// hardcoded: 16 is the height of the icons
		$height = ($height * 16) . 'px';

		return array(
			$width,
			$height
		);
	}

	/**********************************************************
	 * Data tools
	 ************************************************************/

	/**
	 * In effect this function returns an array with the preselected item
	 * (aka Mountpoints that are already assigned) to the user
	 *    [0] => 5|Fernseher
	 *  Meta: [0] => $key|$caption
	 *
	 * @return array
	 * @param object $tree Browsetree Object
	 * @param integer $userid User UID (this is not NECESSARILY
	 * the UID of the currently logged-in user
	 */
	public function processItemArrayForBrowseableTree(&$tree, $userid) {
		/** @var Tx_Commerce_Tree_CategoryMounts $mounts */
		$mounts = GeneralUtility::makeInstance('Tx_Commerce_Tree_CategoryMounts');
		$mounts->init($userid);

		$preselected = $mounts->getMountDataLabeled();

			// Modify the Array - separate the uid and label with a '|'
		$l = count($preselected);

		for ($i = 0; $i < $l; $i++) {
			$preselected[$i] = implode('|', $preselected[$i]);
		}

		$this->itemArrayProcessed = $preselected;

		return $preselected;
	}

	/**
	 * In effect this function returns an array with the preselected item
	 * (aka Mountpoints that are already assigned) to the Group
	 *    [0] => 5|Fernseher
	 *  Meta: [0] => $key|$caption
	 *
	 * @return array
	 * @param object $tree Browsetree Object
	 * @param integer $groupuid User UID (this is not NECESSARILY
	 * the UID of the currently logged-in user
	 */
	public function processItemArrayForBrowseableTreeGroups(&$tree, $groupuid) {
		/** @var Tx_Commerce_Tree_CategoryMounts $mounts */
		$mounts = GeneralUtility::makeInstance('Tx_Commerce_Tree_CategoryMounts');
		$mounts->initByGroup($groupuid);

		$preselected = $mounts->getMountDataLabeled();

			// Modify the Array - separate the uid and label with a '|'
		$l = count($preselected);

		for ($i = 0; $i < $l; $i++) {
			$preselected[$i] = implode('|', $preselected[$i]);
		}

		$this->itemArrayProcessed = $preselected;

		return $preselected;
	}

	/**
	 * In effect this function returns an array with the preselected item
	 * (aka Parent Categories that are already assigned)
	 *    [0] => 5|Fernseher
	 *  Meta: [0] => $key|$caption
	 *
	 * @return array
	 * @param object $tree Browsetree Object
	 * @param integer $catUid Cat UID
	 */
	public function processItemArrayForBrowseableTreePCategory(&$tree, $catUid) {
		if (!is_numeric($catUid)) {
			return array();
		}

			// Get the parent Categories for the cat uid
		/** @var Tx_Commerce_Domain_Model_Category $cat */
		$cat = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $catUid);
		$cat->loadData();
		$parent = $cat->getParentCategories();

		$this->itemArrayProcessed = array();

		/** @var Tx_Commerce_Tree_CategoryMounts $mounts */
		$mounts = GeneralUtility::makeInstance('Tx_Commerce_Tree_CategoryMounts');
		$mounts->init($GLOBALS['BE_USER']->user['uid']);

		if (is_array($parent)) {
			for ($i = 0, $l = count($parent); $i < $l; $i++) {
				/** @var Tx_Commerce_Domain_Model_Category $parentObject */
				$parentObject = & $parent[$i];
				$parentObject->loadData();

					// Separate Key and Title with a |
				$title = ($parentObject->isPermissionSet('show') && $mounts->isInCommerceMounts($parentObject->getUid())) ?
					$parentObject->getTitle() :
					$this->language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xml:leaf.restrictedAccess', 1);
				$this->itemArrayProcessed[] = $parentObject->getUid() . '|' . $title;
			}
		}

		return $this->itemArrayProcessed;
	}

	/**
	 * In effect this function returns an array with the preselected item
	 * (aka Categories that are already assigned to the plugin)
	 *    [0] => 5|Fernseher
	 *  Meta: [0] => $key|$caption
	 *
	 * @return array
	 * @param object $tree Browsetree Object
	 * @param integer $catUid Cat UID
	 */
	public function processItemArrayForBrowseableTreeCategory(&$tree, $catUid) {
		if (!is_numeric($catUid)) {
			return array();
		}

		/** @var Tx_Commerce_Domain_Model_Category $category */
		$category = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $catUid);
		$category->loadData();

		$this->itemArrayProcessed = array();

		/** @var Tx_Commerce_Tree_CategoryMounts $mounts */
		$mounts = GeneralUtility::makeInstance('Tx_Commerce_Tree_CategoryMounts');
		$mounts->init($GLOBALS['BE_USER']->user['uid']);

			// Separate Key and Title with a |
		$title = ($category->isPermissionSet('show') && $mounts->isInCommerceMounts($category->getUid())) ?
			$category->getTitle() :
			$this->language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xml:leaf.restrictedAccess', 1);
		$this->itemArrayProcessed = array($category->getUid() . '|' . $title);

		return $this->itemArrayProcessed;
	}

	/**
	 * In effect this function returns an array with the preselected item
	 * (aka Parent Categories that are already assigned to the product!)
	 *    [0] => 5|Fernseher
	 *  Meta: [0] => $key|$caption
	 *
	 * @return array
	 * @param object $tree Browsetree Object
	 * @param integer $uid Product UID
	 */
	public function processItemArrayForBrowseableTreeProduct(&$tree, $uid) {
		if (!is_numeric($uid)) {
			return array();
		}

			// Get the parent Categories for the cat uid
		/** @var Tx_Commerce_Domain_Model_Product $prod */
		$prod = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $uid);
		$prod->loadData();

			// read parent categories from the live product
		if ($prod->getT3verOid() != 0) {
			$prod->init($prod->getT3verOid());
			$prod->loadData();
		}

		$parent = $prod->getParentCategories();

			// Load each category and push into the array
		$cat = NULL;
		$itemArray = array();

		for ($i = 0, $l = count($parent); $i < $l; $i++) {
			/** @var Tx_Commerce_Domain_Model_Category $category */
			$category = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $parent[$i]);
			$category->loadData();

			$title = ($category->isPermissionSet('show')) ?
				$category->getTitle() :
				$this->language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xml:leaf.restrictedAccess', 1);
				// Separate Key and Title with a |
			$itemArray[] = $category->getUid() . '|' . $title;
		}

		$this->itemArrayProcessed = $itemArray;

		return $this->itemArrayProcessed;
	}

	/**
	 * Extracts the ID and the Title from which every item we have
	 *
	 * @param string $itemFormElValue tx_commerce_products_512,tx_commerce_article_42
	 * @return array
	 */
	public function processItemArrayForBrowseableTreeDefault($itemFormElValue) {
		$items = GeneralUtility::trimExplode(',', $itemFormElValue, TRUE);

		$itemArray = array();

			// Walk the records we have.
		foreach ($items as $value) {
				// Get parts.
			$parts = GeneralUtility::trimExplode('_', $value, TRUE);

			$uid = array_pop($parts);
			$table = implode('_', $parts);

				// Product
			if ('tx_commerce_products' == $table) {
				/** @var Tx_Commerce_Domain_Model_Product $prod */
				$prod = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $uid);
				$prod->loadData();

				$itemArray[] = $value . '|' . $prod->getTitle();
			} elseif ('tx_commerce_articles' == $table) {
				/** @var Tx_Commerce_Domain_Model_Article $article */
				$article = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Article', $uid);
				$article->loadData();

				$itemArray[] = $value . '|' . $article->getTitle();
			} elseif ('tx_commerce_categories' == $table) {
				/** @var Tx_Commerce_Domain_Model_Category $category */
				$category = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $uid);
				$category->loadData();

				$itemArray[] = $value . '|' . $category->getTitle();
			} else {
					// Hook:
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/treelib/class.tx_commerce_treelib_tceforms.php']['processItemArrayForBrowseableTreeDefault'])) {
					GeneralUtility::deprecationLog('
						hook
						$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/treelib/class.tx_commerce_treelib_tceforms.php\'][\'processItemArrayForBrowseableTreeDefault\']
						is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
						$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/ViewHelpers/TreelibTceforms.php\'][\'processItemArrayForBrowseableTreeDefault\']
					');
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/treelib/class.tx_commerce_treelib_tceforms.php']['processItemArrayForBrowseableTreeDefault'] as $classRef) {
						$hookObj = & GeneralUtility::getUserObj($classRef);
						if (method_exists($hookObj, 'processDefault')) {
							$itemArray[] = $hookObj->processDefault($itemFormElValue, $table, $uid);
						}
					}
				}
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/ViewHelpers/TreelibTceforms.php']['processItemArrayForBrowseableTreeDefault'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/ViewHelpers/TreelibTceforms.php']['processItemArrayForBrowseableTreeDefault'] as $classRef) {
						$hookObj = & GeneralUtility::getUserObj($classRef);
						if (method_exists($hookObj, 'processDefault')) {
							$itemArray[] = $hookObj->processDefault($itemFormElValue, $table, $uid);
						}
					}
				}
			}
		}

		return $itemArray;
	}

	/**
	 * Extracts the id's from $PA['itemFormElValue'] in standard TCE format.
	 *
	 * @param string $itemFormElValue
	 * @return array
	 */
	public function getItemFormElValueIdArr($itemFormElValue) {
		$out = array();
		$items = GeneralUtility::trimExplode(',', $itemFormElValue, TRUE);
		foreach ($items as $value) {
			$values = GeneralUtility::trimExplode('|', $value, TRUE);
			$out[] = $values[0];
		}

		return $out;
	}
}
