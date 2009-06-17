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
 * 
 * @author	Rene Fritz <r.fritz@colorcube.de>
 * @package DAM-Treelib
 */
require_once (PATH_t3lib.'class.t3lib_scbase.php');


/**
 * Base class for the (iframe) treeview in TCEforms elements
 *
 * Can display
 * - non-browseable trees (all expanded)
 * - and browsable trees that runs inside an iframe which is needed not to reload the whole page all the time
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
 * Might be possible with AJAX ...
 *
 * @author	Rene Fritz <r.fritz@colorcube.de>
 * @package DAM-Treelib
 */

class tx_commerce_treelib_browser extends t3lib_SCbase {


	/**
	 * Constructor function for script class.
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER, $BACK_PATH, $TYPO3_CONF_VARS;
		

		parent::init();

			// Setting GPvars:
		$this->table = t3lib_div::_GP('table');
		$this->field = t3lib_div::_GP('field');
		$this->uid = t3lib_div::_GP('uid');
		$this->itemFormElName = t3lib_div::_GP('elname');		
		$this->flex_config = t3lib_div::_GP('config');
		$seckey = t3lib_div::_GP('seckey');


			// since we are worried about someone forging parameters (XSS security hole) we will check with sent md5 hash:
		if (!($seckey===t3lib_div::shortMD5($this->table.'|'.$this->field.'|'.$this->uid.'|'.$this->itemFormElName.'|'.$this->flex_config.'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']))) {
			die('access denied');
		}

		if ($this->flex_config) {
			$this->flex_config = unserialize(base64_decode($this->flex_config));
		}
		
		$this->backPath = $BACK_PATH;

			// Initialize template object
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType='xhtml_trans';
		$this->doc->backPath = $this->backPath;


			// from tx_dam_SCbase
		$this->doc->buttonColor = '#e3dfdb';
		$this->doc->buttonColorHover = t3lib_div::modifyHTMLcolor($this->doc->buttonColor,-20,-20,-20);

			// in typo3/stylesheets.css css is defined with id instead of a class: TABLE#typo3-tree
			// that's why we need TABLE.typo3-browsetree
		$this->doc->inDocStylesArray['typo3-browsetree'] = '
					/* Trees */
			TABLE.typo3-browsetree A { text-decoration: none;  }
			TABLE.typo3-browsetree TR TD { white-space: nowrap; vertical-align: middle; }
			TABLE.typo3-browsetree TR TD IMG { vertical-align: middle; }
			TABLE.typo3-browsetree TR TD IMG.c-recIcon { margin-right: 1px;}
			TABLE.typo3-browsetree { margin-bottom: 10px; width: 95%; }

			TABLE.typo3-browsetree TR TD.typo3-browsetree-control {
				padding: 0px;
			}
			TABLE.typo3-browsetree TR TD.typo3-browsetree-control a {
				padding: 0px 3px 0px 3px;
				background-color: '.$this->doc->buttonColor.';
			}
			TABLE.typo3-browsetree TR TD.typo3-browsetree-control > a:hover {
				background-color:'.$this->doc->buttonColorHover.';
			}';


		$this->doc->inDocStylesArray['background-color'] = '
			#ext-dam-mod-treebrowser-index-php { background-color:#fff; }
			#ext-treelib-browser { background-color:#fff; }
		';
		
		$this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
		$this->doc->loadJavascriptLib('../typo3conf/ext/commerce/mod_access/tree.js'); ###MAKE PATH BE CALCULATED, NOT FIXED###
		
		// Setting JavaScript for menu
		// in this context, the function jumpTo is different
		// it adds the Category to the mountpoints
		// DAM actually calls the function parent.setFormValueFromBrowseWin directly - we sneak around it ;)
		###MAYBE WE SHOULD just use a different leaf_categoryview and give it a different JS function - DEPENDS how often a manipulation like this is needed###
		$this->doc->JScode=$this->doc->wrapScriptTags(
			($this->currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($this->currentSubScript).'");':'').'

			function jumpTo(id,linkObj,highLightID,script)	{
				var catUid = id.substr(id.lastIndexOf("=") + 1); //We can leave out the "="
				var text   = (linkObj.firstChild) ? linkObj.firstChild.nodeValue : "Unknown";
				//Params (field, value, caption)
				parent.setFormValueFromBrowseWin("data['.$this->table.']['.$this->uid.']['.$this->field.']", catUid, text);
			}
		');
	}

	/**
	 * Main function - generating the click menu in whatever form it has.
	 *
	 * @return	void
	 */
	function main()	{
		global $TCA, $BACK_PATH, $TYPO3_CONF_VARS;

			// get the data of the field - the currently selected items
		$row = $this->getRecordProcessed();

		$this->content .= $this->doc->startPage('Treeview Browser');

		require_once (PATH_t3lib.'class.t3lib_tceforms.php');
		$form = t3lib_div::makeInstance('t3lib_tceforms');
		$form->initDefaultBEmode();
		$form->backPath = $this->backPath;

			// modifying TCA to force the right rendering - not nice but works
		t3lib_div::loadTCA($this->table);

		$row['uid'] = $this->uid;	
		
		$fakePA = array();
		
		if (is_array($this->flex_config)) {
			$fakePA['fieldConf'] = array(
				'label' => $form->sL($this->flex_config['label']),
				'config' => $this->flex_config['config'],			
				'defaultExtras' => $this->flex_config['defaultExtras']
			);	
		} else {
			$fakePA['fieldConf'] = array(
				'label' => $form->sL($TCA[$this->table]['columns'][$this->field]['label']),
				'config' => $TCA[$this->table]['columns'][$this->field]['config']
			);
		}
					
		$fakePA['fieldConf']['config']['treeViewBrowseable'] = 'iframeContent';
		$fakePA['fieldConf']['config']['noTableWrapping'] = true;			
		$fakePA['itemFormElName'] = $this->itemFormElName;
		$fakePA['itemFormElName_file'] = $this->itemFormElName;

		$this->content .= $form->getSingleField_SW($this->table,$this->field,$row,$fakePA);	
	}


	/**
	 * End page and output content.
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}


	/**
	 * Fetch the record data and return processed data for TCEforms
	 *
	 * @return array Record
	 */
	function getRecordProcessed () {
		global $TYPO3_CONF_VARS;

			// This will render MM relation fields in the correct way.
			// Read the whole record, which is not needed, but there's no other way.
		require_once (PATH_t3lib.'class.t3lib_transferdata.php');
		$trData = t3lib_div::makeInstance('t3lib_transferData');
		$trData->addRawData = true;
		$trData->lockRecords = true;
		$trData->fetchRecord($this->table, $this->uid, '');
		reset($trData->regTableItems_data);
		$row = current($trData->regTableItems_data);

		return $row;
	}
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_treelib_browser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_treelib_browser.php']);
}
?>
