<?php
/**
 * Extended Functionality for the Clickmenu when commerce-tables are hit
 * Basically does the same as the alt_clickmenu.php, only that for Categories the output needs to be overridden depending on the rights
 *
 * @author Marketing Factory <typo3@marketing-factory.de>
 */
class tx_commerce_clickmenu {

	protected $rec;
	protected $pObj;
	
	/**
	 * Changes the clickmenu Items for the Commerce Records
	 * 
	 * @return {array} Menu Items Array 
	 * @param $pObj {object} clickenu object
	 * @param $menuItems {array} current menu Items
	 * @param $table {string} db table 
	 * @param $uid {int} uid of the record
	 */
	function main(&$pObj ,$menuItems, $table, $uid) {
		global $TCA, $BE_USER;
		
		//Only modify the menu Items if we have the correct table
		if($table != 'tx_commerce_categories' && $table != 'tx_commerce_products' && $table != 'tx_commerce_articles') {
			return $menuItems;	
		}	
		
		//Check for List allow
		if(!$GLOBALS['BE_USER']->check('tables_select', $table)) {
			if (TYPO3_DLOG) t3lib_div::devLog('Clickmenu not allowed for user.', COMMERCE_EXTkey, 1);		
			return '';
		}
		
		//Configure the parent clickmenu
		$pObj->backPath 		= '../../../../typo3/';
		$pObj->disabledItems[]  = 'history';	//do not allow the entry 'history' in the clickmenu
		
		
		// Get record:
		$this->rec  = t3lib_BEfunc::getRecordWSOL($table, $uid);
		$this->pObj = & $pObj;
		
		$categoryUid = 0;	//uid of category that is used to check against permissions
		$parentUid	 = 0;	//uid of parent(could be ANY record) that will be the new parent when we insert a record with a paste
		
		//Initialize the rights-variables
		$delete   = false;
		$edit     = false;
		$new      = false;
		$editLock = false;
		$root 	  = 0;
		$DBmount  = FALSE;
		$copy	  = FALSE;
		$paste 	  = FALSE;
		$version  = FALSE;
		$review	  = FALSE;
		
		//get category uid depending on where the clickmenu is called
		switch($table) {
			case 'tx_commerce_products':
				//get all parent categories
				$item = t3lib_div::makeInstance('tx_commerce_product');
				$item->init($uid);
				
				$parentCategories = $item->getParentCategories();
				
				//store the rights in the flags
				$delete  = tx_commerce_belib::checkPermissionsOnCategoryContent($parentCategories, array('editcontent'));	
				$edit    = $delete;
				$new     = $delete;
				$copy 	 = ($this->rec['t3ver_state'] == 0 && $this->rec['sys_language_uid'] == 0);
				$paste   = (($this->rec['t3ver_state'] == 0) && $delete);
				
				//make sure we do not allowed to overwrite a product with itself
				if(count($pObj->clipObj->elFromTable('tx_commerce_products'))) {
					$clipRecord = $pObj->clipObj->getSelectedRecord();
					$paste = ($uid == $clipRecord['uid']) ? false : $paste;
				}
				
				$version = ($BE_USER->check('modules','web_txversionM1'));
				$review  = ($version && ($this->rec['t3ver_oid'] != 0) && (($this->rec['t3ver_stage'] == 0) || ($this->rec['t3ver_stage'] == 1)));
				break;
				
			case 'tx_commerce_articles':
				//get all parent categories for the parent product
				$item = t3lib_div::makeInstance('tx_commerce_article');
				$item->init($uid);
			
				$productUid = $item->getParentProductUid();
			
				//get the parent categories of the product
				$item = t3lib_div::makeInstance('tx_commerce_product');
				$item->init($productUid);
				
				$parentCategories = $item->getParentCategories();
				
				//store the rights in the flags
				$delete = tx_commerce_belib::checkPermissionsOnCategoryContent($parentCategories, array('editcontent'));	
				$edit   = $delete;
				$new    = $delete;
				break;
				
			case 'tx_commerce_categories':
				//get the rights for this category
				$delete = tx_commerce_belib::checkPermissionsOnCategoryContent(array($uid), array('delete'));
				$edit   = tx_commerce_belib::checkPermissionsOnCategoryContent(array($uid), array('edit'));
				$new    = tx_commerce_belib::checkPermissionsOnCategoryContent(array($uid), array('new'));
				
				//check if we may paste into this category
				if(count($pObj->clipObj->elFromTable('tx_commerce_categories'))) {
					//if category is in clipboard, check new-right
					$paste = $new;
					
					//make sure we dont offer pasting one category into itself. that would lead to endless recursion
					$clipRecord = $pObj->clipObj->getSelectedRecord();
					$paste = ($uid == $clipRecord['uid']) ? false : $paste;
					
				} else if(count($pObj->clipObj->elFromTable('tx_commerce_products'))){
					//if product is in clipboard, check editcontent right
					$paste = tx_commerce_belib::checkPermissionsOnCategoryContent(array($uid), array('editcontent'));
				}
				
				$editLock = ($GLOBALS['BE_USER']->isAdmin()) ? false : $this->rec['editlock'];
				
				//check if the current item is a db mount
				$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
				$mounts->init($GLOBALS['BE_USER']->user['uid']);
			
				$DBmount = (in_array($uid, $mounts->getMountData()));
				$copy	 = ($this->rec['sys_language_uid'] == 0);
			
				//check if current item is root
				$root = (int)(0 == $uid);
				break;
		}
		
		//get the UID of the Products SysFolder
		$prodPid = tx_commerce_belib::getProductFolderUid();
		
		$menuItems 	= array();

			// If record found (or root), go ahead and fill the $menuItems array which will contain data for the elements to render.
		if (is_array($this->rec) || $root)	{

				// Edit:
			if(!$root && !$editLock && $edit)	{
				if (!in_array('edit',$pObj->disabledItems))		$menuItems['edit'] = $pObj->DB_edit($table,$uid);
				$pObj->editOK = 1;
			}

				// New: fix: always give the UID of the products page to create any commerce object
			if (!in_array('new',$pObj->disabledItems) && $new)	$menuItems['new'] = $pObj->DB_new($table, $prodPid);
			
			
			
				// Info:
			if(!in_array('info', $pObj->disabledItems) && !$root)	$menuItems['info'] = $pObj->DB_info($table,$uid);

			$menuItems['spacer1'] = 'spacer';
			
				//Cut not included

				// Copy:
			if(!in_array('copy',$pObj->disabledItems) && $copy && !$root && !$DBmount)	$menuItems['copy'] = $pObj->DB_copycut($table,$uid,'copy');
				
				//Paste
			$elFromAllTables = count($pObj->clipObj->elFromTable(''));
			if (!in_array('paste',$pObj->disabledItems) && $elFromAllTables)	{
				$selItem = $pObj->clipObj->getSelectedRecord();
				$elInfo=array(
					t3lib_div::fixed_lgd_cs($selItem['_RECORD_TITLE'],$BE_USER->uc['titleLen']),
					($root?$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']:t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table,$pObj->rec),$BE_USER->uc['titleLen'])),
					$pObj->clipObj->currentMode()
				);

				$elFromTable = count($pObj->clipObj->elFromTable($table));
				if (!$root && !$DBmount && $elFromTable  && $TCA[$table]['ctrl']['sortby'] && 'tx_commerce_categories' == $table && $paste) { 
					//paste into - for categories
					$menuItems['pasteafter'] = $this->DB_paste($table, $uid, $elInfo);
				} else if(!$root && $paste && !$DBmount && $elFromTable && $TCA[$table]['ctrl']['sortby'] && 'tx_commerce_products' == $table) {
					//overwrite product	with product
					$menuItems['overwrite'] =  $this->DB_overwrite($table, $uid, $elInfo);
				} else if(!$root && $paste && !$DBmount && $TCA[$table]['ctrl']['sortby'] && 'tx_commerce_categories' == $table && count($pObj->clipObj->elFromTable('tx_commerce_products'))) {
					//paste product into category
					$menuItems['pasteafter'] = $this->DB_paste($table, $uid, $elInfo);
				}
			}
			
				//versioning
			if(!in_array('versioning', $pObj->disabledItems) && $version) {
				$menuItems['versioning'] = $this->DB_versioning($table, $uid, $elInfo);
			}
			
				//send to review
			if(!in_array('review', $pObj->disabledItems) && $review) {
				$menuItems['review'] = $this->DB_review($table, $uid, $elInfo);
			}
			
				// Delete:
			$elInfo = array(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table,$this->rec),$BE_USER->uc['titleLen']));
			
			if(!$editLock && !in_array('delete',$pObj->disabledItems) && !$root && !$DBmount && $delete)	{
				$menuItems['spacer2']	= 'spacer';
				$menuItems['delete']	= $pObj->DB_delete($table,$uid,$elInfo);
			}

			if(!in_array('history',$pObj->disabledItems))	{
				$menuItems['history'] = $pObj->DB_history($table,$uid,$elInfo);
			}
		}
	
		return $menuItems;
	}
	
	/**
	 * Displays the paste option
	 * @return 
	 * @param $table Object
	 * @param $uid Object
	 * @param $elInfo Object
	 */
	protected function DB_paste($table, $uid, $elInfo) {
		$editOnClick = '';
		$loc = 'top.content'.($this->oBj->listFrame && !$this->pObj->alwaysContentFrame ?'.list_frame':'');
		
		if($GLOBALS['BE_USER']->jsConfirmation(2))	{
			$conf = $loc.' && confirm('.$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_treelib.php:clickmenu.pasteConfirm'),$elInfo[0],$elInfo[1])).')';
		} else {
			$conf = $loc;
		}
		$editOnClick = 'if('.$conf.'){'.$loc.'.location.href=top.TS.PATH_typo3+\''.$this->pasteUrl($table,$uid,0).'&redirect=\'+top.rawurlencode('.$this->pObj->frameLocation($loc.'.document').'); hideCM();}';

		return $this->pObj->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_treelib.php:clickmenu.paste', 1)),
			$this->pObj->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->pObj->PH_backPath,'gfx/clip_pasteinto.gif','width="12" height="12"').' alt="" />'),
			$editOnClick.'return false;'
		);
	}
	
	/**
	 * Displays the overwrite option
	 * 
	 * @return {string}
	 * @param $table {string}	Table that is to be host of the overwrite
	 * @param $uid {int}		uid of the item that is to be overwritten
	 * @param $elInfo {array}	Info Array 
	 */
	protected function DB_overwrite($table, $uid, $elInfo) {
		$editOnClick = '';
		$loc = 'top.content'.($this->oBj->listFrame && !$this->pObj->alwaysContentFrame ?'.list_frame':'');
		
		if($GLOBALS['BE_USER']->jsConfirmation(2))	{
			$conf = $loc.' && confirm('.$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_treelib.php:clickmenu.overwriteConfirm'),$elInfo[0],$elInfo[1])).')';
		} else {
			$conf = $loc;
		}
		$editOnClick = 'if('.$conf.'){'.$loc.'.location.href=top.TS.PATH_typo3+\''.$this->overwriteUrl($table,$uid,0).'&redirect=\'+top.rawurlencode('.$this->pObj->frameLocation($loc.'.document').'); hideCM();}';

		return $this->pObj->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_treelib.php:clickmenu.overwrite', 1)),
			$this->pObj->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->pObj->PH_backPath,'gfx/clip_pasteinto.gif','width="12" height="12"').' alt="" />'),
			$editOnClick.'return false;'
		);
	}
	
	/**
	 * Displays the versioning option
	 * 
	 * @return {string}
	 * @param $table {string}	Table that is to be host of the versioning
	 * @param $uid {int}		uid of the item that is to be versionized
	 * @param $elInfo {array}	Info Array 
	 */
	protected function DB_versioning($table, $uid, $elInfo) {		
		$url = t3lib_extMgm::extRelPath('version').'cm1/index.php?table='.rawurlencode($table).'&uid='.$uid;
		
		return $this->pObj->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:version/locallang.xml:title', 1)),
			$this->pObj->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->pObj->PH_backPath,t3lib_extMgm::extRelPath('version').'cm1/cm_icon.gif','width="15" height="12"').' alt="" />'),
			$this->pObj->urlRefForCM($url),
			1
		);
	}
	
	/**
	 * Displays the 'Send to review/public' option
	 * 
	 * @return {string}
	 * @param $table {string}	Table that is to be host of the sending
	 * @param $uid {int}		uid of the item that is to be send
	 * @param $elInfo {array}	Info Array 
	 */
	protected function DB_review($table, $uid, $elInfo) {		
		$url = t3lib_extMgm::extRelPath('version').'cm1/index.php?id='.($table=='pages'?$uid:$this->pObj->rec['pid']).'&table='.rawurlencode($table).'&uid='.$uid.'&sendToReview=1';
		
		return $this->pObj->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:version/locallang.xml:title_review', 1)),
			$this->pObj->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->pObj->PH_backPath,t3lib_extMgm::extRelPath('version').'cm1/cm_icon.gif','width="15" height="12"').' alt="" />'),
			$this->pObj->urlRefForCM($url),
			1
		);
	}
	
	
	/**
	 * overwriteUrl of the element (database)
	 * 
	 * @return {string}
	 * @param $table {string}				Tablename
	 * @param $uid {int}					uid of the record that should be overwritten
	 * @param $redirect {boolean}[optional] If set, then the redirect URL will point back to the current script, but with CB reset.
	 */
	protected function overwriteUrl($table, $uid, $redirect = 1) {
		$rU = $this->pObj->clipObj->backPath.'../typo3conf/ext/commerce/mod_cce/tx_commerce_cce_db.php?'.
			($setRedirect ? 'redirect='.rawurlencode(t3lib_div::linkThisScript(array('CB'=>''))) : '').
			'&vC='.$GLOBALS['BE_USER']->veriCode().
			'&prErr=1&uPT=1'.
			'&CB[overwrite]='.rawurlencode($table.'|'.$uid).
			'&CB[pad]='.$this->pObj->current;
		return $rU;
	}

	
	/**
	 * pasteUrl of the element (database)
	 * For the meaning of $table and $uid, please read from ->makePasteCmdArray!!!
	 *
	 * @param	string		Tablename
	 * @param	int			uid that should be paste into
	 * @param	boolean		If set, then the redirect URL will point back to the current script, but with CB reset.
	 * @return	string
	 */
	protected function pasteUrl($table, $uid, $redirect = 1) {
		$rU = $this->pObj->clipObj->backPath.'../typo3conf/ext/commerce/mod_cce/tx_commerce_cce_db.php?'.
			($setRedirect ? 'redirect='.rawurlencode(t3lib_div::linkThisScript(array('CB'=>''))) : '').
			'&vC='.$GLOBALS['BE_USER']->veriCode().
			'&prErr=1&uPT=1'.
			'&CB[paste]='.rawurlencode($table.'|'.$uid).
			'&CB[pad]='.$this->pObj->current;
		return $rU;
	}
}
?>