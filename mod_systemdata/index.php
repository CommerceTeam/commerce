<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2008 Ingo Schmitt <is@marketing-factory.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Module 'Systemdata' for the 'commerce' extension.
 *
 * @author	Thomas Hempel <thomas@work.de>
 * 
 * $Id: index.php 455 2006-12-13 10:11:30Z ingo $
 */
	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
$LANG->includeLLFile("EXT:commerce/mod_systemdata/locallang.xml");
#include ("locallang.php");
require_once (PATH_t3lib."class.t3lib_scbase.php");
require_once (PATH_t3lib."class.t3lib_recordlist.php");
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

class tx_commerce_systemdata extends t3lib_SCbase {
	var $pageinfo;
	
	/**
	 * Containing the Root-Folder-Pid of Commerce
	 * 
	 * @var integer
	 */
	var $modPid;

	/**
	 *
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();
			require_once (t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_create_folder.php');
			tx_commerce_create_folder::init_folders();
			list($modPid,$defaultFolder,$folderList) = tx_commerce_folder_db::initFolders('Commerce', 'commerce');
			list($this->attrUid,$defaultFolder,$folderList) = tx_commerce_folder_db::initFolders('Attributes', 'commerce', $modPid);
			$this->modPid = $modPid;
		/*
		if (t3lib_div::_GP("clear_all_cache"))	{
			$this->include_once[]=PATH_t3lib."class.t3lib_tcemain.php";
		}
		*/
	}
	
	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			"function" => Array (
				"1" => $LANG->getLL("title_attributes"),
				"2" => $LANG->getLL("title_manufacturer"),
				"3" => $LANG->getLL("title_supplier"),
			)
		);
		parent::menuConfig();
	}

		// If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		/*
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		
		if (($this->id && $access) || ($BE_USER->user["admin"] && !$this->id))	{
		*/	

				// Draw the header.
			$this->doc = t3lib_div::makeInstance("noDoc");
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
					function deleteRecord(table,id,url,warning)	{	//
						if (
							confirm(eval(warning))
						)	{
							window.location.href = "'.$this->doc->backPath.'tce_db.php?cmd["+table+"]["+id+"][delete]=1&redirect="+escape(url);
						}
						return false;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
				</script>
			';
			
			$headerSection = ''; // $this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"])."<br>".$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.path").": ".t3lib_div::fixed_lgd_pre($this->pageinfo["_thePath"],50);

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));
			
				// Render content:
			$this->moduleContent();

				// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section("",$this->doc->makeShortcutIcon("id",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]));
			}

			$this->content.=$this->doc->spacer(10);
		/*
		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance("noDoc");
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
		*/
	}

	/**
	 * Prints out the module HTML
	 */
	function printContent()	{
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 */
	function moduleContent()	{
		 
		switch((string)$this->MOD_SETTINGS["function"])	{
				// attributes
			
			case 1:
				$content = $this->getAttributeListing();
				$this->content.=$this->doc->section('',$content,0,1);
			break;
			case 2:
				$content = $this->getManufacturerListing();
				$this->content.=$this->doc->section('',$content,0,1);
			break;
			case 3:
				$content = $this->getSupplierListing();
				$this->content.=$this->doc->section('',$content,0,1);
			break;
		}
	}
	
	function getAttributeListing()	{
		global $LANG;
		
		$recordList = t3lib_div::makeInstance('t3lib_recordList');
		$recordList->backPath = $this->doc->backPath;
		$recordList->initializeLanguages();
	
		$comPath = t3lib_extMgm::extRelPath('commerce');
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_attributes', 'pid=' .$this->attrUid .' AND hidden=0 AND deleted=0 and sys_language_uid =0', '', 'internal_title, title');
		$result = '<table>';
		$result .= '<tr><td class="bgColor6" colspan="3"><strong>'.$LANG->getLL('title_attributes').'</strong></td><td class="bgColor6"><strong>'.$LANG->getLL('title_values').'</strong></td></tr>';
		while ($attribute = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$result .= '<tr>';
			$result .= '<td class="bgColor4" align="center" valign="top"> '.t3lib_befunc::thumbCode($attribute,'tx_commerce_attributes','icon',$this->doc->backPath).'</td>';
			if ($attribute['internal_title']) {
				$result .= '<td valign="top" class="bgColor4"><strong>' .$attribute['internal_title'] .'</strong> ('.$attribute['title'].')';
			}else{
				$result .= '<td valign="top" class="bgColor4"><strong>' .$attribute['title'] .'</strong>';
			}
			
			$catCount = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(*) as catCount', 'tx_commerce_categories_attributes_mm', 'uid_foreign=' .$attribute['uid']);
			$catCount = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($catCount);
			$catCount = $catCount['catCount'];
			
			$proCount = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(*) as proCount', 'tx_commerce_products_attributes_mm', 'uid_foreign=' .$attribute['uid']);
			$proCount = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($proCount);
			$proCount = $proCount['proCount'];
			
			
			// Select language versions
			$resLocalVersion = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_attributes', 'pid=' .$this->attrUid .' AND hidden=0 AND deleted=0 and sys_language_uid <>0 and l18n_parent ='.$attribute['uid'], '', 'sys_language_uid');
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($resLocalVersion)>0) {
				$result .= '<table >';
				while ($localAttributes =$GLOBALS['TYPO3_DB']->sql_fetch_assoc($resLocalVersion)) {
					$result .= '<tr><td>&nbsp;';
					$result .= '</td><td>';
					if ($localAttributes['internal_title']) {
						$result .= $localAttributes['internal_title'].' ('.$localAttributes['title'].')';
					}else{
						$result .= $localAttributes['title'];
					}
					$result .= '</td><td>';
					$result .= $recordList->languageFlag($localAttributes['sys_language_uid']);
					$result .= '</td></tr>';
					
				}
				$result .= '</table>';
			}	
			$result .= '<br />' .$LANG->getLL('usage');
			$result .= ' <strong>' .$LANG->getLL('categories') .'</strong>: ' .$catCount;
			$result .= ' <strong>' .$LANG->getLL('products') .'</strong>: ' .$proCount;
			
			$result .= '</td>';
			$result .= '<td valign="top" class="bgColor5"><a href="#" onclick="document.location=\''.$this->doc->backPath.'alt_doc.php?returnUrl='.$comPath.'mod_systemdata/index.php&amp;edit[tx_commerce_attributes]['.$attribute['uid'].']=edit\'; return false;">';
			$result .= '<img src="'.$this->doc->backPath.'gfx/edit2.gif" border="0" /></a>';
			$result .= '<a href="#" onclick="deleteRecord(\'tx_commerce_attributes\', ' .$attribute['uid'] .', \''.$comPath.'mod_systemdata/index.php\',\''.$LANG->JScharCode($LANG->getLL('deleteWarning')).'\');"><img src="'.$this->doc->backPath.'gfx/garbage.gif" border="0" /></a>';
			$result .= '</td>';
			
			$result .= '<td valign="top">';
			
			if ($attribute['has_valuelist'] == 1)	{
				$valueRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_attribute_values', 'attributes_uid='.$attribute['uid'].' AND hidden=0 AND deleted=0','','sorting');
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($valueRes) > 0)	{
					$result .= '<table border="0" cellspacing="0" cellpadding="0">';
					while ($value = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($valueRes))	{
						$result .= '<tr><td>'.$value['value'].'</td></tr>';
					}
					$result .= '</table>';
				} else {
					$result .= $LANG->getLL('no_values');
				}
			} else {
				$result .= $LANG->getLL('no_valuelist');
			}
			
			$result .= '</td></tr>';
		}
		
			// create new attribute link
			// typo3conf/ext/commerce/mod_systemdata/index.php
		$result .= '<tr><td colspan="4">';
		$result .= '<a href="#" onclick="document.location=\''.$this->doc->backPath.'alt_doc.php?returnUrl='.$comPath.'mod_systemdata/index.php&amp;edit[tx_commerce_attributes]['.$this->attrUid.']=new\'; return false;">'.$LANG->getLL('create_attribute').'</a>';
		$result .= '</td></tr>';
		
		$result .= '</table>';
		
		return $result;
		// debug($this->attrUid);
	}
	
	/**
	 * generates a list of all saved Manufacturers
	 */	
	function getManufacturerListing()	{
		global $LANG;
		
		$extConf = unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"]['commerce']);		
		
		$fields = explode(',', $extConf['coSuppliers']);
		$table = 'tx_commerce_manufacturer';
		
		$comPath = t3lib_extMgm::extRelPath('commerce');
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'pid=' .$this->modPid .' AND hidden=0 AND deleted=0', '', 'title');
		$result = '<table>';
		
		$result .= '<tr><td></td>';
		for ($i = 0; $i < count($fields); $i++) {			
			$fieldname = $LANG->sL(t3lib_BEfunc::getItemLabel($table, $fields[$i]));
			$result .= '<td class="bgColor6"><strong>'.$fieldname.'</strong></td>';
		}
		$result .= '</tr>';
		
		while ($res AND $manufacturer = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$result .= '<tr><td valign="top" class="bgColor5"><a href="#" onclick="document.location=\''.$this->doc->backPath.'alt_doc.php?returnUrl='.$comPath.'mod_systemdata/index.php&amp;edit[tx_commerce_manufacturer]['.$manufacturer['uid'].']=edit\'; return false;">';
			$result .= '<img src="'.$this->doc->backPath.'gfx/edit2.gif" border="0" /></a>';
			$result .= '<a href="#" onclick="deleteRecord(\'tx_commerce_manufacturer\', ' .$manufacturer['uid'] .', \''.$comPath.'mod_systemdata/index.php\',\''.$LANG->JScharCode($LANG->getLL('deleteWarningManufacturer')).'\');"><img src="'.$this->doc->backPath.'gfx/garbage.gif" border="0" /></a>';
			$result .= '</td>';
			for ($i = 0; $i < count($fields); $i++) {
				$result .= '<td valign="top" class="bgColor4"><strong>' .$manufacturer[$fields[$i]] .'</strong>';
			}			
			$result .= '</td></tr>';
		}
		
			// create new attribute link
			// typo3conf/ext/commerce/mod_systemdata/index.php
		$result .= '<tr><td colspan="2">';
		$result .= '<a href="#" onclick="document.location=\''.$this->doc->backPath.'alt_doc.php?returnUrl='.$comPath.'mod_systemdata/index.php&amp;edit[tx_commerce_manufacturer]['.$this->modPid.']=new\'; return false;">'.$LANG->getLL('create_manufacturer').'</a>';
		$result .= '</td></tr>';
		
		$result .= '</table>';
		
		return $result;
		// debug($this->attrUid);
	}
	
	
	/**
	 * generates a list of all saved Suppliers
	 */	
	function getSupplierListing()	{
		global $LANG;
		$comPath = t3lib_extMgm::extRelPath('commerce');
		
		$extConf = unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"]['commerce']);		
		$fields = explode(',', $extConf['coSuppliers']);
		$table = 'tx_commerce_supplier';
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'pid=' .$this->modPid .' AND hidden=0 AND deleted=0', '', 'title');
		
		$result = '<table>';
		$result .= '<tr><td></td>';
		for ($i = 0; $i < count($fields); $i++) {			
			$fieldname = $LANG->sL(t3lib_BEfunc::getItemLabel($table, $fields[$i]));
			$result .= '<td class="bgColor6"><strong>'.$fieldname.'</strong></td>';
		}
		$result .= '</tr>';
		
		while ($res AND $supplier = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$result .= '<tr><td valign="top" class="bgColor5"><a href="#" onclick="document.location=\''.$this->doc->backPath.'alt_doc.php?returnUrl='.$comPath.'mod_systemdata/index.php&amp;edit[tx_commerce_supplier]['.$supplier['uid'].']=edit\'; return false;">';
			$result .= '<img src="'.$this->doc->backPath.'gfx/edit2.gif" border="0" /></a>';
			$result .= '<a href="#" onclick="deleteRecord(\'tx_commerce_supplier\', ' .$supplier['uid'] .', \''.$comPath.'mod_systemdata/index.php\',\''.$LANG->JScharCode($LANG->getLL('deleteWarningsupplier')).'\');"><img src="'.$this->doc->backPath.'gfx/garbage.gif" border="0" /></a>';
			$result .= '</td>';
			
			for ($i = 0; $i < count($fields); $i++) {
				$result .= '<td valign="top" class="bgColor4"><strong>' .$supplier[$fields[$i]] .'</strong>';
			}
						
			$result .= '</td></tr>';
		}
		
			// create new attribute link
			// typo3conf/ext/commerce/mod_systemdata/index.php
		$result .= '<tr><td colspan="2">';
		$result .= '<a href="#" onclick="document.location=\''.$this->doc->backPath.'alt_doc.php?returnUrl='.$comPath.'mod_systemdata/index.php&amp;edit[tx_commerce_supplier]['.$this->modPid.']=new\'; return false;">'.$LANG->getLL('create_supplier').'</a>';
		$result .= '</td></tr>';
		
		$result .= '</table>';
		
		
		return $result;
		// debug($this->attrUid);
	}
	
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_systemdata/index.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_systemdata/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_commerce_systemdata');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>