<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 - 2011 Christian Ehret
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
 * hook to adjust linkwizard (linkbrowser)
 */

class Tx_Commerce_Hook_BrowselinksHooks implements t3lib_browseLinksHook {
	/**
	 * Sauvegarde locale du cObj parent
	 *
	 * @var tx_rtehtmlarea_browse_links
	 */
	protected $pObj;

	/**
	 * @var tx_commerce_treelib_link_categorytree
	 */
	protected $treeObj;

	/**
	 * @var string
	 */
	protected $script;

	/**
	 * Initialisation (additionalParameters est un tableau vide)
	 *
	 * @param tx_rtehtmlarea_browse_links $parentObject
	 * @param array $additionalParameters
	 */
	public function init($parentObject, $additionalParameters) {
		$this->pObj = $parentObject;
		if ($this->isRTE()) {
				// for 4.3
			$this->pObj->anchorTypes[] = 'commerce_tab';
		}

			// initialize the tree
		$this->initTree();

			// add js
			// has to be added as script tags to the body since parentObject is not passed by reference
			// first we go from rhtml path to typo3 path
		$linkToTreeJs = '../../../' . t3lib_extMgm::extRelPath('commerce') . 'mod_access/tree.js';

		$this->script = '<script src="' . $linkToTreeJs . '" type="text/javascript"></script>';
		$this->script .= t3lib_div::wrapJS('Tree.ajaxID = "tx_commerce_browselinkshooks::ajaxExpandCollapse";');
	}

	/**
	 * @return void
	 */
	protected function initTree() {
			// initialiize the tree
		$this->treeObj = t3lib_div::makeInstance('tx_commerce_treelib_link_categorytree');
		$this->treeObj->init();
	}

	/**
	 * @param array $currentlyAllowedItems
	 * @return array
	 */
	public function addAllowedItems($currentlyAllowedItems) {
		$currentlyAllowedItems[] = 'commerce_tab';

		return $currentlyAllowedItems;
	}

	/**
	 * @param array $menuDefinition
	 * @return array
	 */
	public function modifyMenuDefinition($menuDefinition) {
		$key = 'commerce_tab';

		$menuDefinition[$key]['isActive'] = $this->pObj->act == $key;
		$menuDefinition[$key]['label'] = 'Commerce';
		$menuDefinition[$key]['url'] = '#';
		$menuDefinition[$key]['addParams'] = 'onclick="jumpToUrl(\'?act=' . $key . '&editorNo=' . $this->pObj->editorNo . '&contentTypo3Language=' . $this->pObj->contentTypo3Language . '&contentTypo3Charset=' . $this->pObj->contentTypo3Charset . '\');return false;"';

		return $menuDefinition;
	}

	/**
	 * Contenu du nouvel onglet
	 *
	 * @param string $act
	 * @return string
	 */
	public function getTab($act) {
		$content = '';
		if ($act == 'commerce_tab') {
				// strip http://commerce: in front of url
			$url = $this->pObj->curUrlInfo['value'];
			$url = substr($url, stripos($url, 'commerce:') + strlen('commerce:'));

			$product_uid = 0;
			$cat_uid = 0;

			$linkHandlerData = t3lib_div::trimExplode('|', $url);

			foreach ($linkHandlerData as $linkData) {
				$params = t3lib_div::trimExplode(':', $linkData);
				if (isset($params[0])) {
					if ($params[0] == 'tx_commerce_products') {
						$product_uid = (int) $params[1];
					} elseif ($params[0] == 'tx_commerce_categories') {
						$cat_uid = (int) $params[1];
					}
				}
				if (isset($params[2])) {
					if ($params[2] == 'tx_commerce_products') {
						$product_uid = (int) $params[3];
					} elseif ($params[2] == 'tx_commerce_categories') {
						$cat_uid = (int) $params[3];
					}
				}
			}

			if ($this->isRTE()) {
				if (isset($this->pObj->classesAnchorJSOptions)) {
						// works for 4.1.x patch, in 4.2 they make this property protected! -> to enable classselector in 4.2 easoiest is to path rte.
					$this->pObj->classesAnchorJSOptions[$act] = @$this->pObj->classesAnchorJSOptions['page'];
				}
			}

				// set product/category of current link for the tree to expand it there
			if ($product_uid > 0) {
				$this->treeObj->setOpenProduct($product_uid);
			}

			if ($cat_uid > 0) {
				$this->treeObj->setOpenCategory($cat_uid);
			}

				// get the tree
			$tree = $this->treeObj->getBrowseableTree();

			$cattable = '<h3 class="bgColor5">Category Tree:</h3><div id="PageTreeDiv">' . $tree . '</div>';

			$content = $this->script;
			$content .= $cattable;

			if ($this->isRTE()) {
				$content .= $this->pObj->addAttributesForm();
			}

		}
		return $content;
	}

	/**
	 * @param string $href
	 * @param string $siteUrl
	 * @param array $info
	 * @return array
	 */
	public function parseCurrentUrl($href, $siteUrl, $info) {
			// depending on link and setup the href string can contain complete absolute link
		if (substr($href, 0, 7) == 'http://') {
			if ($_href = strstr($href, '?id=')) {
				$href = substr($_href, 4);
			} else {
				$href = substr(strrchr($href, '/'), 1);
			}
		}

		if (strtolower(substr($href, 0, 20)) == 'commerce:tx_commerce') {
			$info['act'] = 'commerce_tab';
		}

		return $info;
	}

	/**
	 * @param string $type
	 * @param $pObj
	 * @return boolean
	 */
	public function isValid($type, &$pObj) {
		$isValid = FALSE;

		if ($type === 'rte') {
			$isValid = TRUE;
		}

		return $isValid;
	}

	/**
	 * returns additional addonparamaters - required to keep several informations for the RTE linkwizard
	 * @return string
	 */
	public function getaddPassOnParams() {
		$result = '';
		if (!$this->isRTE()) {
			$P2 = t3lib_div::_GP('P');

			$result = t3lib_div::implodeArrayForUrl('P', $P2);
		}
		return $result;
	}

	/**
	 * @return boolean
	 */
	protected function isRTE() {
		if ($this->pObj->mode == 'rte') {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Makes the AJAX call to expand or collapse the categorytree.
	 * Called by typo3/ajax.php
	 *
	 * @param array $params : additional parameters (not used here)
	 * @param TYPO3AJAX &$ajaxObj : reference of the TYPO3AJAX object of this request
	 * @return void
	 */
	public function ajaxExpandCollapse($params, &$ajaxObj) {
		$PM = t3lib_div::_GP('PM');
			// IE takes anchor as parameter
		if (($PMpos = strpos($PM, '#')) !== FALSE) {
			$PM = substr($PM, 0, $PMpos);
		}
		$PM = t3lib_div::trimExplode('_', $PM);

			// Load the tree
		$this->initTree();
		$tree = $this->treeObj->getBrowseableAjaxTree($PM);

		$ajaxObj->addContent('tree', $tree);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Hook/BrowselinksHooks.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Hook/BrowselinksHooks.php']);
}

?>