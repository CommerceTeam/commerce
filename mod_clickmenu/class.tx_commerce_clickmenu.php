<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * Context menu adapted for Commerce
 * Class for configuration of the click menu
 *
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Thomas Hempel <thomas@work.de>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id$
 */


require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_clickmenu.php');

define('COMMERCE_CLICKMENU_DLOG', '0'); // Switch for debugging error messages


class tx_commerce_clickMenu extends tx_graytree_clickMenu {
	var $languageFile = 'LLL:EXT:commerce/locallang_cm.xml';
	var $extKey = COMMERCE_EXTkey;					// extension key is commerce


	/**
	 * Initialize click menu
	 *
	 * @param	string		Input "item" GET var.
	 * @return	string		The clickmenu HTML content
	 */
	function init(&$item)	{
		$this->defValsMask['tx_commerce_categories']['leaf'] = '&defVals[###TABLE###][parent_category]=###UID###&defVals[###TABLE###][pid]=###PID###'; 
	#	categories
	#	$this->defValsMask['tx_commerce_products']['tree'] = '&defVals[tx_commerce_categories][parent_category]=###UID###&defVals[###TABLE###][parent_id]=###UID###&defVals[###TABLE###][pid]=###PID###'; 
	#	$this->defValsMask['tx_commerce_products']['leaf'] = '&defVals[tx_commerce_categories][parent_category]=###UID###&defVals[###TABLE###][parent_id]=###UID###&defVals[###TABLE###][pid]=###PID###'; 
// to change
		$this->defValsMask['tx_commerce_products']['tree'] = '&defVals[tx_commerce_categories][parent_category]=###UID###&defVals[###TABLE###][categories]=###UID###&defVals[###TABLE###][pid]=###PID###'; 
		$this->defValsMask['tx_commerce_products']['leaf'] = '&defVals[tx_commerce_categories][parent_category]=###UID###&defVals[###TABLE###][categories]=###UID###&defVals[###TABLE###][pid]=###PID###'; 

		$this->rootTableArray[] = 'tx_commerce_categories';
		$this->leafTableArray['tx_commerce_categories'] = array('tx_commerce_products');
		$this->newContentWizScriptPath = t3lib_extMgm::extRelPath($this->extKey).'mod_category/index.php';
		return (parent::init($item));
	}

	/**
	 * Adding CM element for Editing of the access related fields of a table (disable, starttime, endtime, fe_groups)
	 *
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 * 
	 * @author	Franz Holzinger <kontakt@fholzinger.com>
	 * @author	Thomas Hempel <thomas@work.de>
	 */
	function DB_editRecord($table,$uid)	{
			// get some configs
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']);
		if ($extConf['simpleMode'] && $table == 'tx_commerce_products')	{
			$editProduct = '?edit[tx_commerce_products]['.$uid.']=edit';
				// get the article uid that is assigned to this product
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_articles', 'uid_product='.$uid);
			$aUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			
			$editArticle = '&edit[tx_commerce_articles]['.$aUid['uid'].']=edit';

				//the columnsOnly parameter for product and article
			$columnsOnlyProduct = '&columnsOnly[tx_commerce_products]=' .$extConf['coProducts'];
			$columnsOnlyArticle = '&columnsOnly[tx_commerce_articles]=' .$extConf['coArticles'];
			
			$url = 'alt_doc.php'.$editProduct.$editArticle.$columnsOnlyProduct.$columnsOnlyArticle;
		} else {	
			$url = 'alt_doc.php?edit['.$table.']['.$uid.']=edit';
		}

		return $this->linkItem(
			$this->label('edit'),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->PH_backPath,'gfx/edit2.gif','width="12" height="12"').' alt="" />'),
			$this->urlRefForCM($url,'returnUrl'),
			1	// no top frame CM!
		);
	}

	/**
	 * Adding CM element for regular Create new element
	 *
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @param	integer		PID for the current record.
	 * @param	string		Table name of the root
	 * @return	array		Item array, element in $menuItems
	 */
	function DB_new($table, $uid, $pid, $rootTable = '')	{
		if ($table == 'tx_commerce_categories' || $table == 'tx_commerce_products')	{ 

			$editOnClick='';
			$loc='top.content'.(!$this->alwaysContentFrame?'.list_frame':'');
	
			if (TYPO3_DLOG && COMMERCE_CLICKMENU_DLOG) t3lib_div::devLog('tx_commerce_clickMenu::DB_new  $table = '. $table.' $uid ='.$uid.' $pid ='.$rootTable.' $rootTable ='.$pid, COMMERCE_EXTkey);
			
			if (is_array($this->defValsMask) && is_array($this->defValsMask[$table]))	{
				if ($rootTable)	{
					$defVals = str_replace (array('###UID###', '###TABLE###', '###PID###'), array($uid, $table, $pid), $this->defValsMask[$table]['tree']);
				} else {
					$defVals = str_replace (array('###UID###', '###TABLE###', '###PID###'), array($uid, $table, $pid), $this->defValsMask[$table]['leaf']);
				}
			}
	
				// get some configs
			$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']);
			if ($extConf['simpleMode'] && $table == 'tx_commerce_products')	{
				$editProduct = '?edit[tx_commerce_products]['.$uid.']=edit';
					// get the article uid that is assigned to this product
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_articles', 'uid_product='.$uid);
				$aUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				
				$editArticle = '&edit[tx_commerce_articles]['.$aUid['uid'].']=edit';
	
					//the columnsOnly parameter for product and article
				$columnsOnlyProduct = '&columnsOnly[tx_commerce_products]=' .$extConf['coProducts'];
				$columnsOnlyArticle = '&columnsOnly[tx_commerce_articles]=' .$extConf['coArticles'];
	
				$simpleMode = 'alt_doc.php'.$editProduct.$editArticle.$columnsOnlyProduct.$columnsOnlyArticle;
			} else {	
				$simpleMode = '';
			}
	
				$editOnClick='if('.$loc.'){'.$loc.'.document.location=top.TS.PATH_typo3+\''.
				($this->listFrame?
					'alt_doc.php?returnUrl=\'+top.rawurlencode('.$this->frameLocation($loc.'.document').')+\'&edit['.$table.'][-'.$uid.']=new' .$defVals .$simpleMode .'\'':	 
					PATH_txgraytree_rel.'mod_cmd/index.php?CMD=tx_graytree_cmd_new&id='.intval($pid).'&edit['.$table.'][-'.$uid.']=new' .$defVals .$simpleMode ."'").
				';}';
	
				$linkText = trim($GLOBALS['LANG']->sL($this->languageFile.':tx_graytree_cm1.new_'.$table,1));
				if (!$linkText)	{
					$linkText = trim($GLOBALS['LANG']->sL( 'LLL:EXT:graytree/locallang_cm.php:tx_graytree_cm1.newSubCat',1));
				}
	
			$rc = $this->linkItem(
				$GLOBALS['LANG']->makeEntities($linkText),
				$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->PH_backPath,'gfx/new_'.($table=='pages'&&$this->listFrame?'page':'el').'.gif','width="'.($table=='pages'?'13':'11').'" height="12"').' alt="" />'),
				$editOnClick.'return hideCM();');
		}

		if (TYPO3_DLOG && COMMERCE_CLICKMENU_DLOG) t3lib_div::devLog('tx_commerce_clickMenu::DB_new  $rc = '. $rc, COMMERCE_EXTkey);

		return $rc;
	}


}




// Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_clickmenu/class.tx_commerce_clickmenu.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_clickmenu/class.tx_commerce_clickmenu.php']);
}



?>