<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2006 Rene Fritz (r.fritz@colorcube.de)
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
 * TCEforms functions for handling and rendering of trees for group/select elements
 *
 * If we want to display a browseable tree, we need to run the tree in an iframe element.
 * In consequence this means that the display of the browseable tree needs to be generated from an extra script.
 * This is the base class for such a script.
 *
 * The class itself do not render the tree but call tceforms to render the field.
 * In beforehand the TCA config value of treeViewBrowseable will be set to 'iframeContent' to force the right rendering.
 *
 * That means the script do not know anything about trees. It just set parameters and render the field with TCEforms.
 *
 * @author	Rene Fritz <r.fritz@colorcube.de>
 * @maintainer Marketing Factory
 * @package Commerce
 */

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_category.php'); 
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_product.php'); 


class tx_commerce_treelib_tceforms {

	/**
	 * count rendered tree items - just for frame height calculation
	 * @var integer
	 */
	var $treeItemC = 0;

	/**
	 * count rendered trees
	 * @var integer
	 */
	var $treesC = 0;

	/**
	 * Rendered trees as HTML
	 * @var string
	 * @access private
	 */
	var $treeContent = '';

	/**
	 * itemArray for usage in TCEforms
	 * This holds the original values
	 * @var array
	 * @access private
	 */
	var $itemArray = array();

	/**
	 * itemArray for usage in TCEforms
	 * This holds the processed values with titles/labels
	 * @var array
	 * @access private
	 */
	var $itemArrayProcessed = array();

	/**
	 * Defines if the content of the iframe should be rendered instead of the iframe itself.
	 * This is for iframe mode.
	 * @var boolean
	 * @access private
	 */
	var $iframeContentRendering = false;

	/**
	 * Defines the prefix used for JS code to call the parent window.
	 * This is for iframe mode.
	 * @var string
	 * @access private
	 */
	var $jsParent = '';



	var $tceforms;
	var $PA;
	var $table;
	var $field;
	var $row;
	var $config;


	/**********************************************************
	 *
	 * Getter / Setter
	 *
	 ************************************************************/


	/**
	 * Init
	 *
	 * @param	array		$PA An array with additional configuration options.
	 * @param	object		$fobj TCEForms object reference
	 * @return	void
	 */
	function init($PA, &$fObj)	{
		$this->tceforms = &$PA['pObj'];
		$this->PA = &$PA;

		$this->table = $PA['table'];
		$this->field = $PA['field'];
		$this->row = $PA['row'];
		$this->config = $PA['fieldConf']['config'];
		
			// set currently selected items
		$itemArray = t3lib_div::trimExplode(',', $this->PA['itemFormElValue'], true);
		$this->setItemArray($itemArray);
		
		
		$this->setIFrameContentRendering($this->config['treeViewBrowseable']==='iframeContent');
	}


	/**
	 * Enable the iframe content rendering mode
	 *
	 * @return void
	 */
	function setIFrameContentRendering ($IFrameContentRendering=true, $jsParent='parent.') {
		if ($this->iframeContentRendering = $IFrameContentRendering) {
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
	function isIFrameContentRendering () {
		return $this->iframeContentRendering;
	}



	/**
	 * Returns true if iframe content rendering mode is enabled
	 *
	 * @return boolean
	 */
	function isIFrameRendering () {
		return ($this->config['treeViewBrowseable'] && !$this->iframeContentRendering);
	}


	/**
	 * Set the selected items
	 *
	 * @param array $itemArray
	 * @return void
	 */
	function setItemArray ($itemArray) {
		$this->itemArray = $itemArray;
	}


	/**
	 * Return the processed aray of selected items
	 *
	 * @return array
	 */
	function getItemArrayProcessed () {
		return $this->itemArrayProcessed;
	}


	/**
	 * Return the count value of selectable items
	 *
	 * @return integer
	 */
	function getItemCountSelectable() {
		return $this->treeItemC;
	}


	/**
	 * Return the count value of rendered trees
	 *
	 * @return integer
	 */
	function getItemCountTrees() {
		return $this->treesC;
	}

	/**
	 * Returns the rendered trees (HTML)
	 *
	 * @return string
	 */
	function getTreeContent() {
		return $this->treeContent;
	}





	/**********************************************************
	 *
	 * Rendering
	 *
	 ************************************************************/

	/**
	 * Renders the category tree for mounts
	 *
	 * @param 	object 	$browseTree Category Tree
	 * @param 	string 	$divTreeAttribute Each tree is wrapped in a div-tag. This defines attributes for the tag.
	 * @return 	string the rendered trees (HTML)
	 */
	function renderBrowsableMountTrees (&$browseTree, $divTreeAttribute=' style="margin:5px"' ) {
		
		/*$this->treeItemC = 0;
		$this->treesC = 0;
		$this->treeContent = '';
		$this->itemArrayProcessed = array();
		
		if (is_array($browseTrees)) {
			foreach($browseTrees as $treeName => $treeViewObj)	{

				if ($treeViewObj->isTCEFormsSelectClass AND $treeViewObj->supportMounts) {
					$treeViewObj->backPath = $this->tceforms->backPath;
					$treeViewObj->mode = 'tceformsSelect';
					$treeViewObj->TCEforms_itemFormElName = $this->PA['itemFormElName'];
					$treeViewObj->setRecs = true;
					$treeViewObj->jsParent = $this->jsParent;
					$treeViewObj->ext_IconMode = true; // no context menu on icons

						// this needs to be true for multiple trees but then TCE can't handle it as "select" or "group" - so "passthrough" is needed
					$treeViewObj->TCEFormsSelect_prefixTreeName = true;

					$tree = '';

					if ((string)$treeViewObj->supportMounts=='rootOnly') {
						$tree = $treeViewObj->printRootOnly();
						$this->treeItemC += 1;

					} else {
						if ($this->isIFrameContentRendering()) {

							$treeViewObj->expandAll = false;
							$treeViewObj->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');
							$treeViewObj->PM_addParam = $this->getIFrameParameter($this->table, $this->field, $this->row['uid']);

						} else {
							$treeViewObj->expandAll = true;
							$treeViewObj->expandFirst = true;
						}

						$tree = $treeViewObj->getBrowsableTree();
						$this->treeItemC += count($treeViewObj->ids)+1;
					}

					if ($tree) {
						$this->treeContent .= '<div'.$divTreeAttribute.'>'.$tree.'</div>';
						$this->treesC += 1;
					}


						// process selected items - get names
					$this->processItemArray($treeViewObj);
				}
			}
		}*/
		
		$this->treeContent = $browseTree->getBrowseableTree();

		return $this->treeContent;
	}





	/**********************************************************
	 *
	 * Div-Frame specific stuff
	 *
	 ************************************************************/


	/**
	 * Returns div HTML code which includes the rendered tree(s).
	 *
	 * @param	string $width CSS width definition
	 * @param	string $height CSS height definition
	 * @return	string HTML content
	 */
	function renderDivBox ($width=NULL, $height=NULL) {
		if ($width==NULL) {
			list($width, $height) = $this->calcFrameSizeCSS();
		}
		global $BACK_PATH;
		$BACK_PATH='../typo3/';
		$divStyle = 'position:relative; left:0px; top:0px; height:'.$height.'; width:'.$width.';border:solid 1px;overflow:auto;background:#fff;';
		$divFrame = '<div  name="'.$this->PA['itemFormElName'].'_selTree" style="'.htmlspecialchars($divStyle).'">';
		
		$divFrame .= $this->treeContent;
		$divFrame .= '</div>';
		
		//include function
		//$sOnChange = 'setFormValueFromBrowseWin(\''.$PA['itemFormElName'].'\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text); '.implode('',$PA['fieldChangeFunc']);
		$divFrame .= '<script type="text/javascript">';
		$divFrame .= '
		function jumpTo(id,linkObj,highLightID,script)	{
				var catUid = id.substr(id.lastIndexOf("=") + 1); //We can leave out the "="
				var text   = (linkObj.firstChild) ? linkObj.firstChild.nodeValue : "Unknown";
				//Params (field, value, caption)
				setFormValueFromBrowseWin("'.$this->PA['itemFormElName'].'", catUid, text);
			}';
		$divFrame .= '</script>';
		$divFrame .= '<script src="'.$this->tceforms->backPath.'../typo3conf/ext/commerce/mod_access/tree.js" type=""></script>'; //tree js
		
		return $divFrame;
	}






	/**********************************************************
	 *
	 * IFrame specific stuff
	 *
	 ************************************************************/


	/**
	 * Set the script to be called for the iframe tree browser.
	 *
	 * @param 	string 	$script Path to the script
	 * @return	void
	 * @see tx_dam_treelib_browser
	 */
	function setIFrameTreeBrowserScript ($script) {
		$this->treeBrowserScript = $script;
	}


	/**
	 * Returns iframe HTML code to call the tree browser script.
	 *
	 * @param	string $width CSS width definition
	 * @param	string $height CSS height definition
	 * @return	string HTML content
	 * @see tx_dam_treelib_browser
	 */
	function renderIFrame ($width=NULL, $height=NULL) {

		if(!$this->treeBrowserScript) {
			die ('tx_commerce_treelib_tceforms: treeBrowserScript is not set!');
		}

		if ($width==NULL) {
			list($width, $height) = $this->calcFrameSizeCSS();
		}


		$table = $GLOBALS['TCA'][$this->table]['orig_table'] ? $GLOBALS['TCA'][$this->table]['orig_table'] : $this->table;

		$iFrameParameter = $this->getIFrameParameter($table, $this->field, $this->row['uid']);

		$divStyle = 'height:'.$height.'; width:'.$width.'; border:solid 1px #000; background:#fff;';
		$iFrame = '<iframe src="'.htmlspecialchars($this->treeBrowserScript.'?'.$iFrameParameter).'" name="'.$this->PA['itemFormElName'].'_selTree" border="1" style="'.htmlspecialchars($divStyle).'">';
		$iFrame .= '</iframe>';
		return $iFrame;
	}


	/**
	 * Returns GET parameter string to be passed to the tree browser script.
	 *
	 * @param 	string $table
	 * @param 	string $field
	 * @param 	string $uid
	 * @return 	string
	 * @see tx_dam_treelib_browser
	 */
	function getIFrameParameter ($table, $field, $uid) {
		$params = array();
			
		$config = '';
		if ($GLOBALS['TCA'][$table]['columns'][$field]['config']['type'] == 'flex') {
			$config = base64_encode(serialize($this->PA['fieldConf']));			
		}
		
		$params['table'] = $table;
		$params['field'] = $field;
		$params['uid'] = $uid;
		$params['elname'] = $this->PA['itemFormElName'];
		$params['config'] = $config;
		$params['seckey'] = t3lib_div::shortMD5(implode('|', $params).'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
		return t3lib_div::implodeArrayForUrl('', $params);
	}





	/**********************************************************
	 *
	 * Rendering tools
	 *
	 ************************************************************/



	/**
	 * calculate size of the tree frame
	 *
	 * @return array array($width, $height)
	 */
	function calcFrameSizeCSS($itemCountSelectable=NULL) {

		if ($itemCountSelectable===NULL) {
			$itemCountSelectable = max (1, $this->treeItemC + $this->treesC + 1);
		}

		$width = '240px';

		$this->config['autoSizeMax'] = t3lib_div::intInRange($this->config['autoSizeMax'], 0);
		$height = $this->config['autoSizeMax'] ? t3lib_div::intInRange($itemCountSelectable, t3lib_div::intInRange($this->config['size'], 1), $this->config['autoSizeMax']) : $this->config['size'];

			// hardcoded: 16 is the height of the icons
		$height = ($height*16).'px';
			// em height needs a factor - don't know why
		#$height = intval($height*1.5).'em';

		return array($width, $height);
	}




	/**********************************************************
	 *
	 * Data tools
	 *
	 ************************************************************/


	/**
	 * Returns the mounts for the selection classes stored in user and/or group fields.
	 * The storage format is different from TCE select fields. For each item the treeName is prefixed with ':'.
	 *
	 * @param	string		$treeName: ...
	 * @param	string		$userMountField: ...
	 * @param	string		$groupMountField: ...
	 * @return	array		Mount data array for usage with treeview class
	 * @see renderBrowsableMountTrees()
	 */
	/*function getMountsForTree($treeName, $userMountField='tx_dam_mountpoints', $groupMountField='tx_dam_mountpoints') {
		global $BE_USER;

		$mounts = array();

		if($GLOBALS['BE_USER']->user['admin']){
			$mounts = array(0 => 0);
			return $mounts;
		}

		if ($GLOBALS['BE_USER']->user[$userMountField]) {
			 $values = explode(',',$GLOBALS['BE_USER']->user[$userMountField]);
			 foreach($values as $mount) {
			 	list($k,$id) = explode(':', $mount);
			 	if ($k == $treeName) {
					$mounts[$id] = $id;
			 	}
			 }
		}

		if(is_array($GLOBALS['BE_USER']->userGroups)){
			foreach($GLOBALS['BE_USER']->userGroups as $group){

				if ($group[$groupMountField]) {
					$values = explode(',',$group[$groupMountField]);
					 foreach($values as $mount) {
					 	list($k,$id) = explode(':', $mount);
					 	if ($k == $treeName) {
							$mounts[$id] = $id;
					 	}
					 }
				}
			}
		}

			// if root is mount just set it and remove all other mounts
		if(isset($mounts[0])) {
			$mounts = array(0 => 0);
		}

		return $mounts;
	}*/


	/**
	 * Process selected items in $this->itemArray
	 * Get names for items, return and store the result in $this->itemArrayProcessed
	 *
	 * @param 	array 	$browseTrees Array of browse trees: array($treeName => $treeViewObj)
	 * @return 	array 
	 */
	/*function processItemArrayForBrowseableTrees ($browseTrees) {

		$this->itemArrayProcessed = array();

		if (is_array($browseTrees)) {
			foreach($browseTrees as $treeName => $treeViewObj)	{

				if ($treeViewObj->isTCEFormsSelectClass) {
					$treeViewObj->mode = 'tceformsSelect';

						// process selected items - get names
					$this->processItemArray($treeViewObj);
				}
			}
		}
		return $this->itemArrayProcessed;
	}*/
	
	/**
	 * In effect this function returns an array with the preselected item (aka Mountpoints that are already assigned) to the user
	 * 	[0] => 5|Fernseher
	 *  Meta: [0] => $key|$caption
	 *  
	 * @return array
	 * @param $tree {object} Browsetree Object
	 * @param $userid {int}	User UID (this is not NECESSARILY the UID of the currently logged-in user
	 */
	function processItemArrayForBrowseableTree(&$tree, $userid) {
		
		$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
		$mounts->init($userid);
		
		$preselected = $mounts->getMountDataLabeled();
		
		//Modify the Array - separate the uid and label with a '|'
		$l = count($preselected);
		
		for($i = 0; $i < $l; $i ++) {
			$preselected[$i] = implode('|', $preselected[$i]);	
		}
		
		$this->itemArrayProcessed = $preselected;
		
		return $preselected;
	}
	
	/**
	 * In effect this function returns an array with the preselected item (aka Mountpoints that are already assigned) to the Group
	 * 	[0] => 5|Fernseher
	 *  Meta: [0] => $key|$caption
	 *  
	 * @return array
	 * @param $tree {object} Browsetree Object
	 * @param $userid {int}	User UID (this is not NECESSARILY the UID of the currently logged-in user
	 */
	function processItemArrayForBrowseableTreeGroups(&$tree, $groupuid) {
		
		$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
		$mounts->initByGroup($groupuid);
		
		$preselected = $mounts->getMountDataLabeled();
		
		//Modify the Array - separate the uid and label with a '|'
		$l = count($preselected);
		
		for($i = 0; $i < $l; $i ++) {
			$preselected[$i] = implode('|', $preselected[$i]);	
		}
		
		$this->itemArrayProcessed = $preselected;
		
		return $preselected;
	}
	
	/**
	 * In effect this function returns an array with the preselected item (aka Parent Categories that are already assigned)
	 * 	[0] => 5|Fernseher
	 *  Meta: [0] => $key|$caption
	 *  
	 * @return array
	 * @param $tree {object} Browsetree Object
	 * @param $cat {int}	Cat UID
	 */
	function processItemArrayForBrowseableTreePCategory(&$tree, $catUid) {
		
		if(!is_numeric($catUid)) {
			return array();
		}
		
		//Get the parent Categories for the cat uid
		$cat = t3lib_div::makeInstance('tx_commerce_category');
		$cat->init($catUid);
		$cat->load_data();
		$parent = $cat->getParentCategories();
		
		$this->itemArrayProcessed = array();
		
		$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
		$mounts->init($GLOBALS['BE_USER']->user['uid']);
		
		if(is_array($parent)) {	
			for($i = 0, $l = count($parent); $i < $l; $i ++) {
				$parent[$i]->load_data();
				
				//Separate Key and Title with a |
				$title = ($parent[$i]->isPSet('show') && $mounts->isInCommerceMounts($parent[$i]->getUid())) ? $parent[$i]->getTitle() : $GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_treelib.php:leaf.restrictedAccess',1); 
				$this->itemArrayProcessed = array($parent[$i]->getUid().'|'.$title); 
			}
		}
		return $this->itemArrayProcessed;
	}
	
	/**
	 * In effect this function returns an array with the preselected item (aka Categories that are already assigned to the plugin)
	 * 	[0] => 5|Fernseher
	 *  Meta: [0] => $key|$caption
	 *  
	 * @return array
	 * @param $tree {object} Browsetree Object
	 * @param $cat {int}	Cat UID
	 */
	function processItemArrayForBrowseableTreeCategory(&$tree, $catUid) {
		
		if(!is_numeric($catUid)) {
			return array();
		}
		
		//Get the parent Categories for the cat uid
		$cat = t3lib_div::makeInstance('tx_commerce_category');
		$cat->init($catUid);
		$cat->load_data();
		
		$this->itemArrayProcessed = array();
		
		$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
		$mounts->init($GLOBALS['BE_USER']->user['uid']);
		
		
		//Separate Key and Title with a |
		$title = ($cat->isPSet('show') && $mounts->isInCommerceMounts($cat->getUid())) ? $cat->getTitle() : $GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_treelib.php:leaf.restrictedAccess',1); 
		$this->itemArrayProcessed = array($cat->getUid().'|'.$title); 
	
		return $this->itemArrayProcessed;
	}
	
	/**
	 * In effect this function returns an array with the preselected item (aka Parent Categories that are already assigned to the product!)
	 * 	[0] => 5|Fernseher
	 *  Meta: [0] => $key|$caption
	 *  
	 * @return array
	 * @param $tree {object} Browsetree Object
	 * @param $uid {int}	 Product UID
	 */
	function processItemArrayForBrowseableTreeProduct(&$tree, $uid) {
		if(!is_numeric($uid)) {
			return array();
		}
		
		//Get the parent Categories for the cat uid
		$prod = t3lib_div::makeInstance('tx_commerce_product');
		$prod->init($uid);
		$prod->load_data();
		
		//read parent categories from the live product
		if($prod->get_t3ver_oid() != 0) {
			$prod->init($prod->get_t3ver_oid());
			$prod->load_data();
		}
		
		$parent = $prod->getParentCategories();
		
		//Load each category and push into the array
		$cat = null;
		$itemArray = array();
		
		for($i = 0, $l = count($parent); $i < $l; $i ++) {
			$cat = 	t3lib_div::makeInstance('tx_commerce_category');
			$cat->init($parent[$i]);
			$cat->load_data();
			
			$title = ($cat->isPSet('show')) ? $cat->getTitle() : $GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_treelib.php:leaf.restrictedAccess',1);
			//Separate Key and Title with a |
			$itemArray[] = $cat->getUid().'|'.$title;
		}
	
		
		$this->itemArrayProcessed = $itemArray;
		
		return $this->itemArrayProcessed;
	}

	/**
	 * Extracts the id's from $PA['itemFormElValue'] in standard TCE format.
	 *
	 * @return array
	 */
	function getItemFormElValueIdArr ($itemFormElValue) {
		$out = array();
		$tmp1 = t3lib_div::trimExplode(',', $itemFormElValue, true);
		foreach ($tmp1 as $value) {
			$tmp2 = t3lib_div::trimExplode('|', $value, true);
			$out[] = $tmp2[0];
		}
		return $out;
	}

}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_treelib_tceforms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_treelib_tceforms.php']);
}
?>
