<?php
/**
 * Implements the Commerce Engine

 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 **/

unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH . 'init.php');
require_once($BACK_PATH . 'template.php');

$LANG->includeLLFile('EXT:commerce/mod_cce/locallang_mod.xml');

class SC_tx_commerce_cce_db {

	// Internal, static: GPvar
	var $redirect;		// Redirect URL. Script will redirect to this location after performing operations (unless errors has occured)
	var $prErr;			// Boolean. If set, errors will be printed on screen instead of redirection. Should always be used, otherwise you will see no errors if they happen.

	var $CB;			// Clipboard command array. May trigger changes in "cmd"
	var $vC;			// Verification code
	var $uPT;			// Boolean. Update Page Tree Trigger. If set and the manipulated records are pages then the update page tree signal will be set.
	var $cmd;			//Command
	var $clipObj;
	var $BACK_PATH;
	var $content;
	var $pageinfo;
	var $sorting;		//holds the sorting of copied record
	var $locales;

	/**
	 * Document Template Object
	 * @var template
	 */
	public $doc;

	/**
	 * Commerce Core Engine
	 *
	 * @var tx_commerce_cce
	 */
	var $cce;


	/**
	 * Initialization of the class
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER;

			// GPvars:
		$this->BACK_PATH 	= $GLOBALS['BACK_PATH'];;
		$this->redirect 	= t3lib_div::_GP('redirect');
		$this->prErr		= t3lib_div::_GP('prErr');
		$this->CB 			= t3lib_div::_GP('CB');
		$this->vC 			= t3lib_div::_GP('vC');
		$this->uPT 			= t3lib_div::_GP('uPT');
		$this->clipObj		= null;
		$this->content 		= '';
		$this->sorting 		= t3lib_div::_GP('sorting');
		$this->locales		= t3lib_div::_GP('locale');
		$this->cmd			= t3lib_div::_GP('cmd');

		if(null == $this->sorting) $this->sorting = 0;

		$cbString = (isset($this->CB['overwrite'])) ? 'CB[overwrite]='.rawurlencode($this->CB['overwrite']).'&CB[pad]='.$this->CB['pad'] : 'CB[paste]='.rawurlencode($this->CB['paste']).'&CB[pad]='.$this->CB['pad'];

		// Initializing document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath 	= $GLOBALS['BACK_PATH'];
		$this->doc->docType 	= 'xhtml_trans';
		$this->doc->setModuleTemplate('../typo3conf/ext/commerce/mod_cce/templates/copy.html');
		$this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
		$this->doc->loadJavascriptLib('../typo3conf/ext/commerce/mod_cce/copyPaste.js');
		$this->doc->form = '<form action="tx_commerce_cce_db.php?'.$cbString.'&vC='.$this->vC.'&uPT='.$this->uPT.'&redirect='.rawurlencode($this->redirect).'&prErr='.$this->prErr.'&cmd=commit" method="post" name="localeform" id="localeform">';
	}

	/**
	 * Clipboard pasting and deleting.
	 *
	 * @return	void
	 */
	function initClipboard()	{
		if (is_array($this->CB))	{
			$clipObj = t3lib_div::makeInstance('t3lib_clipboard');
			$clipObj->initializeClipboard();
			$clipObj->setCurrentPad($this->CB['pad']);
			$this->clipObj = $clipObj;
		}
	}

	/**
	 * Executing the posted actions ...
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER;

			// Checking referer / executing
		$refInfo  = parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');

		if ($httpHost != $refInfo['host'] && $this->vC!=$BE_USER->veriCode() && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer'])	{
			//$this->tce->log('',0,0,0,1,'Referer host "%s" and server host "%s" did not match and veriCode was not valid either!',1,array($refInfo['host'],$httpHost));
			//@todo: log correctly
		} else {

			//get current item in clipboard
			$item 		= $this->clipObj->getSelectedRecord();
			$uidClip  	= $item['uid'];
			$uidTarget  = 0;
			$table 		= '';

			//check which command we actually want to execute
			$command = '';

			if(isset($this->CB['overwrite'])) {
				//overwrite a product
				$command = 'overwrite';

				list($table, $uidTarget) = explode('|', $this->CB['overwrite']);
			} else if(isset($this->CB['paste'])) {
				//paste either a product into a category or a category into a category
				$command = (null == $this->clipObj->getSelectedRecord('tx_commerce_categories', $uidClip)) ? 'pasteProduct' : 'pasteCategory';

				list($table, $uidTarget) = explode('|', $this->CB['paste']);
			}

			if(null == $this->cmd) {
				//locale and sorting position haven't been chosen yet
				$this->showCopyWizard($uidClip, $uidTarget, $command);
			} else {
				$this->commitCommand($uidClip, $uidTarget, $command);
			}
		}
	}

	/**
	 * Shows the copy wizard
	 *
	 * @return {void}
	 * @param $uidClip {int}	uid of the clipped item
	 * @param $uidTarget {int}
	 */
	protected function showCopyWizard($uidClip, $uidTarget, $command) {
		global $LANG;

		$str = '';

		$this->pageinfo = tx_commerce_belib::readCategoryAccess($uidTarget, tx_commerce_belib::getCategoryPermsClause(1));

		$str .= $this->doc->header($LANG->getLL('Copy'));
		$str .= $this->doc->spacer(5);

		$noActionReq = false;	//flag if neither sorting nor localizations are existing and we can immediately copy

		// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/mod_cce/class.tx_commerce_cce_db.php']['copyWizardClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/mod_cce/class.tx_commerce_cce_db.php']['copyWizardClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

		switch ($command) {
				case 'overwrite':
				case 'pasteProduct':
					//chose local to copy from product
					$product = t3lib_div::makeInstance('tx_commerce_product');
					$product->init($uidClip);
					$product->loadData();
					$prods = $product->get_l18n_products();

					if(0 != count($prods)) {

						$str .= '<h1>'.$LANG->getLL('copy.head.l18n').'</h1>';
						$str .= '<h2>'.$LANG->getLL('copy.product').': '.$product->get_title().'</h2>';

						$str .= '<ul>';

						//walk the l18n and get the selector box
						$l = count($prods);

						for($i = 0; $i < $l; $i ++) {
							$tmpProd = $prods[$i];

							$flag = ($tmpProd['flag'] != '') ?  '<img src="'.$this->BACK_PATH.'gfx/flags/'.$tmpProd['flag'].'" alt="Flag" />' : '';

							$str .= '<li><input type="checkbox" name="locale[]" id="loc_'.$tmpProd['uid'].'" value="'.$tmpProd['sys_language'].'" /><label for="loc_'.$tmpProd['uid'].'">'.$flag.$tmpProd['title'].'</label></li>';
						}

						$str .= '</ul>';
					}

					//chose sorting position
					if('overwrite' == $command) {
						//sorting position is irrelevant for overwrite
						$l = 0;
					} else {
						// Initialize tree object:
						$treedb = t3lib_div::makeInstance('tx_commerce_leaf_productdata');
						$treedb->init();

						$records = $treedb->getRecordsDbList($uidTarget);

						$l = count($records['pid'][$uidTarget]);
					}

					//Hook: beforeFormClose
					$user_ignoreClose = false;

					foreach($hookObjectsArr as $hookObj)	{
						if (method_exists($hookObj, 'beforeFormClose')) {
							$str .= $hookObj->beforeFormClose($uidClip, $uidTarget, $command, $user_ignoreClose); //set $user_ignoreClose to true if you want to force the script to print out the execute button
						}
					}

					if(0 >= $l && (0 != count($prods) || $user_ignoreClose)) {
						//no child object - sorting position is irrelevant - just print a submit button and notify users that there are not products in the category yet
						$str .= '<input type="submit" value="'.$LANG->getLL('copy.submit').'" />';
					} else if(0 < $l) {
						//at least 1 item - offer choice
						$icon = '<img'.t3lib_iconWorks::skinImg($this->BACK_PATH,'gfx/newrecord_marker_d.gif','width="281" height="8"').' alt="" title="Insert the category" />';
						$prodIcon = t3lib_iconWorks::getIconImage('tx_commerce_products',array('uid' => $uidTarget),$this->BACK_PATH,'align="top" class="c-recIcon"');
						$str .= '<h1>'.$LANG->getLL('copy.position').'</h1>';

						$str .= '<span class="nobr"><a href="javascript:void(0)" onclick="submitForm('.$records['pid'][$uidTarget][0]['uid'].')">'.$icon.'</a></span><br />';

						for($i = 0; $i < $l; $i++) {
							$record = $records['pid'][$uidTarget][$i];

							$str .= '<span class="nobr">'.$prodIcon.$record['title'].'</span><br />';
							$str .= '<span class="nobr"><a href="javascript:void(0)" onclick="submitForm(-'.$record['uid'].')">'.$icon.'</a></span><br />';
						}
					} else {
						$noActionReq = true;
					}
					break;

				case 'pasteCategory':
					//chose locale to copy from category
					$cat = t3lib_div::makeInstance('tx_commerce_category');
					$cat->init($uidClip);
					$cat->loadData();
					$cats = $cat->get_l18n_categories();

					if(0 != count($cats)) {

						$str .= '<h1>'.$LANG->getLL('copy.head.l18n').'</h1>';
						$str .= '<h2>'.$LANG->getLL('copy.category').': '.$cat->getTitle().'</h2>';

						$str .= '<ul>';

						//walk the l18n and get the selector box
						$l = count($cats);

						for($i = 0; $i < $l; $i ++) {
							$tmpCat = $cats[$i];

							$flag = ($tmpCat['flag'] != '') ?  '<img src="'.$this->BACK_PATH.'gfx/flags/'.$tmpCat['flag'].'" alt="Flag" />' : '';

							$str .= '<li><input type="checkbox" name="locale[]" id="loc_'.$tmpCat['uid'].'" value="'.$tmpCat['sys_language'].'" /><label for="loc_'.$tmpCat['uid'].'">'.$flag.$tmpCat['title'].'</label></li>';
						}

						$str .= '</ul>';
					}

					//chose sorting position
					// Initialize tree object:
					$treedb = t3lib_div::makeInstance('tx_commerce_leaf_categorydata');
					$treedb->init();

					$records = $treedb->getRecordsDbList($uidTarget);

					$l = count($records['pid'][$uidTarget]);

					//Hook: beforeFormClose
					$user_ignoreClose = false;

					foreach($hookObjectsArr as $hookObj)	{
							if (method_exists($hookObj, 'beforeFormClose')) {
								$str .= $hookObj->beforeFormClose($uidClip, $uidTarget, $command, $user_ignoreClose);
						}
					}

					if(0 == $l && (0 != count($cats) || $user_ignoreClose)) {
						//no child object - sorting position is irrelevant - just print a submit button
						$str .= '<input type="submit" value="'.$LANG->getLL('copy.submit').'" />';
					} else if(0 < $l){
						//at least 1 item - offer choice
						$icon = '<img'.t3lib_iconWorks::skinImg($this->BACK_PATH,'gfx/newrecord_marker_d.gif','width="281" height="8"').' alt="" title="Insert the category" />';
						$catIcon = t3lib_iconWorks::getIconImage('tx_commerce_categories',array('uid' => $uidTarget),$this->BACK_PATH,'align="top" class="c-recIcon"');
						$str .= '<h1>'.$LANG->getLL('copy.position').'</h1>';

						$str .= '<span class="nobr"><a href="javascript:void(0)" onclick="submitForm('.$records['pid'][$uidTarget][0]['uid'].')">'.$icon.'</a></span><br />';

						for($i = 0; $i < $l; $i++) {
							$record = $records['pid'][$uidTarget][$i];

							$str .= '<span class="nobr">'.$catIcon.$record['title'].'</span><br />';
							$str .= '<span class="nobr"><a href="javascript:void(0)" onclick="submitForm(-'.$record['uid'].')">'.$icon.'</a></span><br />';
						}
					} else {
						$noActionReq = true;
					}
					break;

				default:
					die("unknown command");
					break;
		}

		//skip transforming and execute the command if there are no locales and no positions
		if($noActionReq) {
			$this->commitCommand($uidClip, $uidTarget, $command);
			return;
		}

		//Hook: beforeTransform
		foreach($hookObjectsArr as $hookObj)	{
			if (method_exists($hookObj, 'beforeTransform')) {
				$str .= $hookObj->beforeTransform($uidClip, $uidTarget, $command);
			}
		}

		$this->content .= $str;

		$markers['CSH'] 		= '';
		$markers['FUNC_MENU'] 	= t3lib_BEfunc::getFuncMenu($uidClip, 'SET[mode]', $this->MOD_SETTINGS['mode'], $this->MOD_MENU['mode']);
		$markers['CONTENT'] 	= $this->content;
		$markers['CATINFO'] 	= '';
		$markers['CATPATH'] 	= '';

		$this->content = $this->doc->startPage($LANG->getLL('Copy'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, array(), $markers);

		$this->content.= $this->doc->endPage();
	}

	/**
	 * Commits the given command
	 *
	 */
	protected function commitCommand($uidClip, $uidTarget, $command) {
		// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/mod_cce/class.tx_commerce_cce_db.php']['commitCommandClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/mod_cce/class.tx_commerce_cce_db.php']['commitCommandClass'] as $classRef) {
				$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
			}
		}

		//Hook: beforeCommit
		foreach($hookObjectsArr as $hookObj)	{
			if (method_exists($hookObj, 'beforeCommit')) {
				$hookObj->beforeCommit($uidClip, $uidTarget, $command);
			}
		}

		//we got all info we need - commit command
		switch($command) {
			case 'overwrite':
				tx_commerce_belib::overwriteProduct($uidClip, $uidTarget, $this->locales);
				break;

			case 'pasteProduct':
				tx_commerce_belib::copyProduct($uidClip, $uidTarget, false, $this->locales, $this->sorting);
				break;

			case 'pasteCategory':
				tx_commerce_belib::copyCategory($uidClip, $uidTarget, $this->locales, $this->sorting);
				break;

			default:
				die("unknown command");
				break;
		}

		//Hook: afterCommit
		foreach($hookObjectsArr as $hookObj)	{
			if (method_exists($hookObj, 'afterCommit')) {
				$hookObj->afterCommit($uidClip, $uidTarget, $command);
			}
		}

		// Update page tree?
		if ($this->uPT && (isset($this->data['pages'])||isset($this->cmd['pages'])))	{
			t3lib_BEfunc::setUpdateSignal('updatePageTree');
		}
	}

	/**
	 * Redirecting the user after the processing has been done.
	 * Might also display error messages directly, if any.
	 *
	 * @return	void
	 */
	function finish()	{
			// Prints errors, if...
		if ($this->prErr)	{
			//$this->tce->printLogErrorMessages($this->redirect);
		}

		if('' != $this->content) {
			echo $this->content;
		} else if ($this->redirect) {
			Header('Location: '.t3lib_div::locationHeaderUrl($this->redirect));
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_cce/tx_commerce_cce_db.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_cce/tx_commerce_cce_db.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_tx_commerce_cce_db');
$SOBE->init();

$SOBE->initClipboard();
$SOBE->main();
$SOBE->finish();

?>
