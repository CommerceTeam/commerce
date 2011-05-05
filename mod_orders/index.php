<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
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
 * Module 'Orders' for the 'commerce' extension.
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @author Daniel Schöttgen <ds@marketing-factory.de>
 */

unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$LANG->includeLLFile("EXT:commerce/mod_orders/locallang.php");
require_once (PATH_t3lib."class.t3lib_scbase.php");
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.

require_once (PATH_t3lib.'class.t3lib_page.php');
require_once (PATH_t3lib.'class.t3lib_pagetree.php');
require_once (PATH_t3lib.'class.t3lib_recordlist.php');
require_once (PATH_t3lib.'class.t3lib_clipboard.php');
require_once (PATH_t3lib.'class.t3lib_tcemain.php');

require_once (t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_order_localrecordlist.php');

/**
 * Load Locallang
 */

$LANG->includeLLFile('EXT:lang/locallang_mod_web_list.php');


class tx_commerce_orders extends t3lib_SCbase {

	var $id;					// Page Id for which to make the listing
	var $pointer;				// Pointer - for browsing list of records.
	var $imagemode;				// Thumbnails or not
	var $table ='tx_commerce_orders';	// Which table to make extended listing for
	var $table_user ='fe_users';	// Which table to make extended listing for
	
	var $search_field;			// Search-fields
	var $search_levels;			// Search-levels
	var $showLimit;				// Show-limit
	var $returnUrl;				// Return URL

	var $clear_cache;			// Clear-cache flag - if set, clears page cache for current id.
	var $cmd;					// Command: Eg. "delete" or "setCB" (for TCEmain / clipboard operations)
	var $cmd_table;				// Table on which the cmd-action is performed.

		// Internal, static:
	var $perms_clause;			// Page select perms clause
	var $modTSconfig;			// Module TSconfig
	var $pageinfo;				// Current ids page record
	var $doc;					// Document template object

	var $MCONF=array();			// Module configuration
	var $MOD_MENU=array();		// Menu configuration
	var $MOD_SETTINGS=array();	// Module settings (session variable)
	var $include_once=array();	// Array, where files to include is accumulated in the init() function

	/**
	 *
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();
		$this->table='tx_commerce_orders';
		$this->clickMenuEnabled=1;
		require_once (t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_create_folder.php');
		tx_commerce_create_folder::init_folders();

		$order_pid = array_unique(tx_commerce_folder_db::initFolders('Orders','Commerce',0,'Commerce'));

		/**
		 * If we get an id via GP use this, else use the default id
		 */
		if (t3lib_div::_GP('id')) {
			$this->id=t3lib_div::_GP('id');
		} else {
			$this->id = $order_pid[0];
		}

			// Initialize page browser
		$this->pointer = 0;
		if (t3lib_div::_GP('pointer')) {
			$this->pointer = intval(t3lib_div::_GP('pointer'));
		}
	}

	/**
	 * Hanlde post request
	 */
	function doaction(){
		$orderuids = t3lib_div::_GP('orderUid');
		$destPid = t3lib_div::_GP('modeDestUid');

			// Only if we have a list of orders
		if ((is_array($orderuids)) and ($destPid)) {
			foreach ($orderuids as $oneUid) {
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values=0;
				
				$data['tx_commerce_orders'][$oneUid] = t3lib_befunc::getRecordRaw('tx_commerce_orders','uid = '.$oneUid,'cust_deliveryaddress,cust_fe_user,cust_invoice');
				$data['tx_commerce_orders'][$oneUid]['newpid'] =$destPid;
				$tce->start($data,array());
				$tce->process_datamap();
			}
		}
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user["admin"] && !$this->id))	{

				// Fist check if we should move some orders
			$this->doaction();

				// Draw the header.
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
				</script>
			';

			$headerSection = $this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"])."<br>".$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.path").": ".t3lib_div::fixed_lgd_cs($this->pageinfo["_thePath"],-50);

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));
			$this->content.=$this->doc->divider(5);

			// Render content:
			$this->moduleContent();

			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section("",$this->doc->makeShortcutIcon("id",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
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
	function moduleContent() {
		global $BE_USER,$LANG,$BACK_PATH,$TCA,$TYPO3_CONF_VARS,$id,$table;
		$this->content = '';
		$this->orderList($this->content);
	}

	/**
	 * generates the orderlist for the module orders
	 * HTML Output will be put to $this->content;
	 */
	function orderList($content = '') {
		global $BE_USER,$LANG,$BACK_PATH,$TCA,$TYPO3_CONF_VARS,$CLIENT;	
		$this->table='tx_commerce_orders';
		
		$this->content=$content;
			// Start document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType='xhtml_trans';
		$this->dontShowClipControlPanels = 1;
			// Loading current page record and checking access:
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

			// Initialize the dblist object:
		$dblist = t3lib_div::makeInstance('tx_commerce_order_localRecordlist');
		$dblist->additionalOutTop = $this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));
		$dblist->backPath = $BACK_PATH;

			// Since TYPO3 core revision 5135 (Followup for bug #2859) BACK_PATH is added to recordList->listURL()
			// This made the headerRow "Orders" link to the wrong URL
			// So we now ask for the current TYPO3 version and add a proper path to this script
		if (t3lib_div::int_from_ver(TYPO3_version) >= 4003000) {
			$dblist->script = t3lib_extMgM::extRelPath('commerce') . 'mod_orders/index.php';
		} else {
				// @TODO: Remove this when commerce requires 4.3
			$dblist->script = 'index.php';
		}

		$dblist->calcPerms = $BE_USER->calcPerms($this->pageinfo);
		$dblist->thumbs = $BE_USER->uc['thumbnailsByDefault'];
		$dblist->returnUrl=$this->returnUrl;

		$dblist->allFields = 1;
		if($this->userID){
		    $dblist->onlyUser = $this->userID;
		}
		
		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 0;

			// Clipboard is initialized:
		$dblist->clipObj = t3lib_div::makeInstance('t3lib_clipboard');		// Start clipboard
		$dblist->clipObj->initializeClipboard();	// Initialize - reads the clipboard content from the user session

			// Clipboard actions are handled:
		$CB = t3lib_div::_GET('CB');	// CB is the clipboard command array
		if ($this->cmd=='setCB') {
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
				// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
			$CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge(t3lib_div::_POST('CBH'),t3lib_div::_POST('CBC')),$this->cmd_table);
		}

		$this->doc->JScode=$this->doc->wrapScriptTags('
			function jumpToUrl(URL)	{	//
				document.location = URL;
				return false;
			}
			function jumpExt(URL,anchor)	{	//
				var anc = anchor?anchor:"";
				document.location = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
				return false;
			}
			function jumpSelf(URL)	{	//
				document.location = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
				return false;
			}
			'.$this->doc->redirectUrls(
			$dblist->listURL()).'
			'.$dblist->CBfunctions().'
			function editRecords(table,idList,addParams,CBflag)	{	//
				document.location="'.$backPath.'alt_doc.php?returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')).
					'&edit["+table+"]["+idList+"]=edit"+addParams;
			}
			function editList(table,idList)	{	//
				var list="";

					// Checking how many is checked, how many is not
				var pointer=0;
				var pos = idList.indexOf(",");
				while (pos!=-1)	{
					if (cbValue(table+"|"+idList.substr(pointer,pos-pointer))) {
						list+=idList.substr(pointer,pos-pointer)+",";
					}
					pointer=pos+1;
					pos = idList.indexOf(",",pointer);
				}
				if (cbValue(table+"|"+idList.substr(pointer))) {
					list+=idList.substr(pointer)+",";
				}

				return list ? list : idList;
			}

			if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
		');

		// Setting up the context sensitive menu:
		$CMparts=$this->doc->getContextMenuCode();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->JScode.=$CMparts[0];
		$this->doc->postCode.= $CMparts[2];

			// If there is access to the page, then render the list contents and set up the document template object:
		if ($access)	{
				// Initialize the listing object, dblist, for rendering the list:
			$this->pointer = t3lib_div::intInRange($this->pointer,0,PHP_INT_MAX);
			
			$dblist->start($this->id,$this->table,$this->pointer,$this->search_field,$this->search_levels,$this->showLimit);

				// Render the page header:
			if(!$this->noTopView) {
				$dblist->writeTop($this->pageinfo);
			}
				// Render versioning selector:
			$dblist->HTMLcode.= $this->doc->getVersionSelector($this->id);

				// Render the list of tables:
			$dblist->generateList($this->id,$this->table);

				// Write the bottom of the page:
			$dblist->writeBottom();

				// Add JavaScript functions to the page:
			$this->doc->JScode=$this->doc->wrapScriptTags('
				function jumpToUrl(URL)	{	//
					document.location = URL;
					return false;
				}
				function jumpExt(URL,anchor)	{	//
					var anc = anchor?anchor:"";
					document.location = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
					return false;
				}
				function jumpSelf(URL)	{	//
					document.location = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
					return false;
				}
				'.$this->doc->redirectUrls($dblist->listURL()).'
				'.$dblist->CBfunctions().'
				function editRecords(table,idList,addParams,CBflag)	{	//
					document.location="'.$backPath.'alt_doc.php?returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')).
						'&edit["+table+"]["+idList+"]=edit"+addParams;
				}
				function editList(table,idList)	{	//
					var list="";

						// Checking how many is checked, how many is not
					var pointer=0;
					var pos = idList.indexOf(",");
					while (pos!=-1)	{
						if (cbValue(table+"|"+idList.substr(pointer,pos-pointer))) {
							list+=idList.substr(pointer,pos-pointer)+",";
						}
						pointer=pos+1;
						pos = idList.indexOf(",",pointer);
					}
					if (cbValue(table+"|"+idList.substr(pointer))) {
						list+=idList.substr(pointer)+",";
					}

					return list ? list : idList;
				}

				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
			');

				// Setting up the context sensitive menu:
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.=$CMparts[0];
			$this->doc->postCode.= $CMparts[2];
		} // access

			// Begin to compile the whole page, starting out with page header:
		$this->content.=$this->doc->startPage('DB list');
		$dblist->additionalOutTop .= $this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));

		$this->content.= '<form action="'.htmlspecialchars($dblist->listURL()).'" method="post" name="dblistForm">';

			// List Module CSH:
		if (!strlen($this->id)) {
			$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module_noId', $GLOBALS['BACK_PATH'],'<br/>|');
		} elseif (!$this->id) {	// zero...
			$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module_root', $GLOBALS['BACK_PATH'],'<br/>|');
		}

			// Add listing HTML code:
		$this->content.= $dblist->HTMLcode;
		$this->content.= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_orders/index.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_orders/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_commerce_orders');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>