<?php
/**
 * Used for rendering a list of records for a tree item
 * The original file (typo3/db_list.php) could not be used only because of the object intantiation at the bottom of the file.
 * @author Marketing Factory
 * @maintainer Erik Frister
 */

$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_mod_web_list.xml');
require_once(PATH_typo3 . 'class.db_list.inc');
require_once(PATH_typo3 . 'class.db_list_extra.inc');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_list_extra.inc');

class tx_commerce_db_list {
		// Internal, GPvars:
	var $id;					// Treeitem Id for which to make the listing
	var $pointer;				// Pointer - for browsing list of records.
	var $imagemode;				// Thumbnails or not
	var $table;					// Which table to make extended listing for
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

	/**
	 * Document template object
	 *
	 * @var template
	 */
	var $doc;

	var $MCONF=array();			// Module configuration
	var $MOD_MENU=array();		// Menu configuration
	var $MOD_SETTINGS=array();	// Module settings (session variable)

		// Internal, dynamic:
	var $content;				// Module output accumulation
	var $control;				//Array of tree leaf names and leaf data classes

	var $script;				// the script calling this class
	var $scriptNewWizard;		// the script for the wizard of the command 'new'
	var $params;				// the paramters for the script


	/**
	 * Initializing the module
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER;

			// Setting module configuration / page select clause
		$this->MCONF = $GLOBALS['MCONF'];
		$this->perms_clause = $BE_USER->getPagePermsClause(1);

			// GPvars:
		$this->id 			= t3lib_div::_GP('id');
		$this->pointer 		= t3lib_div::_GP('pointer');
		$this->imagemode 	= t3lib_div::_GP('imagemode');
		$this->table 		= t3lib_div::_GP('table');

		//Get Tabpe and controlArray in a different way
		$controlParams = t3lib_div::_GP('control');
		if ($controlParams) {
			$this->table = key ($controlParams);
			//$this->id = $controlParams[$this->table]['uid'];
			$this->controlArray = current($controlParams);
		}

		$this->search_field = t3lib_div::_GP('search_field');
		$this->search_levels = t3lib_div::_GP('search_levels');
		$this->showLimit = t3lib_div::_GP('showLimit');
		$this->returnUrl = t3lib_div::_GP('returnUrl');

		$this->clear_cache = t3lib_div::_GP('clear_cache');
		$this->cmd = t3lib_div::_GP('cmd');
		$this->cmd_table = t3lib_div::_GP('cmd_table');

			// Initialize menu
		$this->menuConfig();

		$this->params = t3lib_div::getIndpEnv('QUERY_STRING'); // Current script name with parameters
		$this->script = t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT'); // Current script name
	}

	/**
	 * Initialize function menu array
	 *
	 * @return	void
	 */
	function menuConfig()	{

			// MENU-ITEMS:
		$this->MOD_MENU = array(
			'bigControlPanel' => '',
			'clipBoard' => '',
			'localization' => ''
		);

			// Loading module configuration:
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,'mod.'.$this->MCONF['name']);

			// Clean up settings:
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * Clears page cache for the current id, $this->id
	 *
	 * @return	void
	 */
	function clearCache()	{
		if ($this->clear_cache)	{
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values=0;
			$tce->start(Array(),Array());
			$tce->clear_cacheCmd($this->id);
		}
	}

	/**
	 * Main function, starting the rendering of the list.
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$CLIENT,$TYPO3_DB;

		$defVals='';				// default values for the new command

		$listingProduced 	= false;
		$formProduced 		= false;
		$linkparam			= array();
		$parent_uid 		= (is_null($this->controlArray['uid'])) ? 0 : $this->controlArray['uid'];
			// Start document template object:
		$this->doc = t3lib_div::makeInstance('template');

		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType='xhtml_trans';

			// Loading current page record and checking access:
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

			// Initialize the dblist object:
		$dblist = t3lib_div::makeInstance('commerceRecordList');
		$dblist->backPath 					= $BACK_PATH;
		$dblist->calcPerms 					= $BE_USER->calcPerms($this->pageinfo);
		$dblist->thumbs 					= $BE_USER->uc['thumbnailsByDefault'];
		$dblist->returnUrl					= $this->returnUrl;
		$dblist->allFields 					= ($this->MOD_SETTINGS['bigControlPanel'] || $this->table) ? 1 : 0;
		$dblist->localizationView 			= $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard 				= 1;
		$dblist->disableSingleTableView 	= $this->modTSconfig['properties']['disableSingleTableView'];
		$dblist->listOnlyInSingleTableMode 	= $this->modTSconfig['properties']['listOnlyInSingleTableView'];
		$dblist->clickTitleMode 			= $this->modTSconfig['properties']['clickTitleMode'];
		$dblist->alternateBgColors			= $this->modTSconfig['properties']['alternateBgColors']?1:0;
		$dblist->allowedNewTables 			= t3lib_div::trimExplode(',',$this->modTSconfig['properties']['allowedNewTables'],1);
		$dblist->newWizards 				= $this->modTSconfig['properties']['newWizards']?1:0;

		// Clipboard is initialized:
		$dblist->clipObj = t3lib_div::makeInstance('t3lib_clipboard');		// Start clipboard
		$dblist->clipObj->initializeClipboard();	// Initialize - reads the clipboard content from the user session

			// Clipboard actions are handled:
		$CB = t3lib_div::_GET('CB');	// CB is the clipboard command array
		if ($this->cmd=='setCB') {
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
				// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
			$CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge((array)t3lib_div::_POST('CBH'),(array)t3lib_div::_POST('CBC')),$this->cmd_table);
		}
		if (!$this->MOD_SETTINGS['clipBoard'])	$CB['setP']='normal';	// If the clipboard is NOT shown, set the pad to 'normal'.
		$dblist->clipObj->setCmd($CB);		// Execute commands.
		$dblist->clipObj->cleanCurrent();	// Clean up pad
		$dblist->clipObj->endClipboard();	// Save the clipboard content

			// This flag will prevent the clipboard panel in being shown.
			// It is set, if the clickmenu-layer is active AND the extended view is not enabled.
		$dblist->dontShowClipControlPanels = $CLIENT['FORMSTYLE'] && !$this->MOD_SETTINGS['bigControlPanel'] && $dblist->clipObj->current=='normal' && !$BE_USER->uc['disableCMlayers'] && !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers'];

		foreach ($this->control as $type => $controldat) {
			$dblist->HTMLcode = '';
			$treedb = t3lib_div::makeInstance($controldat['dataClass']);
			$treedb->init();

			$records = $treedb->getRecordsDbList($parent_uid);

			$this->resCountAll = count($records['pid'][$parent_uid]);

			if ($treedb->getTable())	{
				$linkparam[] = '&edit['.$treedb->getTable().'][-'.$parent_uid.']=new';
				$tmpDefVals = '&defVals['.$treedb->getTable().']['.$controldat['parent'].']=' .$parent_uid;
				$defVals .= $tmpDefVals;
			}

				// If there is access to the page, then render the list contents and set up the document template object:
			if ($access && $this->resCountAll)	{
				$this->table = ($treedb->getTable() ? $treedb->getTable() : $this->table);
				$dblist->allFields = ($this->MOD_SETTINGS['bigControlPanel'] || $this->table) ? 1 : 0;

					// Deleting records...:
					// Has not to do with the clipboard but is simply the delete action. The clipboard object is used to clean up the submitted entries to only the selected table.
				if ($this->cmd == 'delete')	{
					$items = $dblist->clipObj->cleanUpCBC(t3lib_div::_POST('CBC'),$this->cmd_table,1);
					if (count($items))	{
						$cmd=array();
						reset($items);
						while(list($iK)=each($items))	{
							$iKParts = explode('|',$iK);
							$cmd[$iKParts[0]][$iKParts[1]]['delete']=1;
						}
						$tce = t3lib_div::makeInstance('t3lib_TCEmain');
						$tce->stripslashes_values=0;
						$tce->start(array(),$cmd);
						$tce->process_cmdmap();

						if (isset($cmd['pages']))	{
							t3lib_BEfunc::setUpdateSignal('updatePageTree');
						}

						$tce->printLogErrorMessages(t3lib_div::getIndpEnv('REQUEST_URI'));
					}
				}

					// Initialize the listing object, dblist, for rendering the list:
				$this->pointer = t3lib_div::intInRange($this->pointer,0,100000);

				if ($dblist->sortField)	{
					if (in_array($BACK_PATH.$dblist->sortField,$dblist->makeFieldList($dblist->table,1)))	{
						$orderBy = $dblist->table.'.'.$dblist->sortField;
						if ($dblist->sortRev)	$orderBy.=' DESC';
					}
				}

				//Get the list of uids
				$uids = array();

				for($i = 0; $i < $this->resCountAll; $i++) {
					$uids[] = $records['pid'][$parent_uid][$i]['uid'];
				}

				//get potential new versions
				//@explain: not implemented because the pid is -1 and it then doesn't show in the list
				//list shows only LIVE values
				/*for($i = 0; $i < $this->resCountAll; $i++) {
					$latestV = $treedb->getLatestVersion($uids[$i]);

					if($latestV['uid'] != $uids[$i]){
						 $uids[$i] = $latestV['uid'];
					}
				}*/

				$dblist->uid 		= implode(',', $uids);
				$dblist->parent_uid = $parent_uid;				// uid of the parent category

				$dblist->start($this->id,$this->table,$this->pointer,$this->search_field,$this->search_levels,$this->showLimit);
				$dblist->setDispFields();

				// $defVal for Tableheader;

				$dblist->defVals = $defVals;
				if (!$formProduced) {

						// Render the page header:
					$dblist->writeTop($this->pageinfo);
				}
					// Render versioning selector:
				$dblist->HTMLcode.= $this->doc->getVersionSelector($this->id);
					// Render the list of tables:

				$dblist->generateList();

					// Write the bottom of the page:
				$dblist->writeBottom();

			}

				// If there is access to the page, then render the JavaScript for the clickmenu
			if ($access) {

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
						document.location="'.$this->doc->backPath.'alt_doc.php?returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')).
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
				$this->doc->JScode.= $CMparts[0];
				$this->doc->postCode.= $CMparts[2];

			} // access

			if (!$formProduced) {
				$formProduced = true;

					// Begin to compile the whole page, starting out with page header:
				$this->content='';
				$this->content.=$this->doc->startPage('DB list');
				$this->content.= '<form action="'.htmlspecialchars($dblist->listURL()).'" method="post" name="dblistForm">';

					// List Module CSH:
				if (!strlen($this->id))	{
					$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module_noId', $GLOBALS['BACK_PATH'],'<br/>|');
				} elseif (!$this->id)	{	// zero...:
					$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module_root', $GLOBALS['BACK_PATH'],'<br/>|');
				}
			}
			if ($dblist->HTMLcode) {
				$listingProduced = true;

					// Add listing HTML code:
				$this->content.= $dblist->HTMLcode;

				// Making field select box (when extended view for a single table is enabled):
				$this->content.=$dblist->fieldSelectBox($dblist->table);
			}

		} // foreach
		if ($formProduced) {
			$this->content.= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';

				// List Module CSH:
			if ($this->id)	{
				$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module', $GLOBALS['BACK_PATH'],'<br/>|');
			}
		} else {
			// This is always needed to get the correct page layout
							// Begin to compile the whole page, starting out with page header:
			$this->content='';
			$this->content.=$this->doc->startPage('DB list');
		}

			// If a listing was produced, create the page footer with search form etc:
		if ($listingProduced)	{

				// Adding checkbox options for extended listing and clipboard display:
			$this->content.='

					<!--
						Listing options for clipboard and thumbnails
					-->
					<div id="typo3-listOptions">
						<form action="" method="post">';

			$this->content.=t3lib_BEfunc::getFuncCheck($this->id,'SET[bigControlPanel]',$this->MOD_SETTINGS['bigControlPanel'],$this->script,$this->params).' '.$LANG->getLL('largeControl',1).'<br />';

//			if ($dblist->showClipboard)	{
//				$this->content.=t3lib_BEfunc::getFuncCheck($this->id,'SET[clipBoard]',$this->MOD_SETTINGS['clipBoard'],$this->script,$this->params).' '.$LANG->getLL('showClipBoard',1).'<br />';
//			}
			$this->content.=t3lib_BEfunc::getFuncCheck($this->id,'SET[localization]',$this->MOD_SETTINGS['localization'],$this->script,$this->params).' '.$LANG->getLL('localization',1).'<br />';
			$this->content.='
						</form>
					</div>';
			$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_options', $GLOBALS['BACK_PATH']);

				// Printing clipboard if enabled:
//			if ($this->MOD_SETTINGS['clipBoard'] && $dblist->showClipboard)	{
//				$this->content.= $dblist->clipObj->printClipboard();
//				$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_clipboard', $GLOBALS['BACK_PATH']);
//			}

				// Link for creating new records:
			if (!$this->modTSconfig['properties']['noCreateRecordsLink']) 	{
				$sumlink = $this->scriptNewWizard.'?id='.intval($this->id);
				foreach ($linkparam as $k=>$v) {
				$sumlink .= $v;
				}
				$sumlink .= $defVals;

				$linkNewRecord = '
					<!--
						Link for creating a new record:
					-->
					<div id="typo3-newRecordLink">
					<a href="'.htmlspecialchars($this->scriptNewWizard.'?id='.$this->id.$sumlink.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.
								'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','width="11" height="12"').' alt="" />'.
								$LANG->getLL('newRecordGeneral',1).
								'</a>
					</div>';
				$this->content.=$linkNewRecord;
			}

				// Search box:
			$this->content.=$dblist->getSearchBox();

				// Display sys-notes, if any are found:
			$this->content.=$dblist->showSysNotesForPage();

				// ShortCut:
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.='<br/>'.$this->doc->makeShortcutIcon('id,imagemode,pointer,table,search_field,search_levels,showLimit,sortField,sortRev',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']);
			}
		} else {
			$sumlink = $this->scriptNewWizard.'?id='.intval($this->id);
			foreach ($linkparam as $k=>$v) {
				$sumlink .= $v;
			}
			$sumlink .= $defVals;

			$linkNewRecord = '
					<!--
						Link for creating a new record:
					-->
					<div id="typo3-newRecordLink">
					<a href="'.htmlspecialchars($this->scriptNewWizard.'?id='.$this->id.$sumlink.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.
								'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','width="11" height="12"').' alt="" />'.
								$LANG->getLL('newRecordGeneral',1).
								'</a>
					</div>';
				$this->content.=$linkNewRecord;
		}


			// Finally, close off the page:
		$this->content.= $this->doc->endPage();
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}
}


//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_list.php']);
}
?>