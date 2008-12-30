<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2008 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Module: Permission setting
 *
 * @author	Marketing Factory
 * @maintainer Erik Frister
 */
unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');
require('class.sc_mod_access_perm_ajax.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_belib.php');
$LANG->includeLLFile('EXT:commerce/mod_access/locallang_mod_access_perm.xml');

//Tree
require_once(t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_categorytree.php');

$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
t3lib_BEfunc::lockRecords();

/**
 * Module: Permission setting
 *
 * Script Class for the Web > Access module
 * This module lets you view and change permissions for pages.
 *
 * Variables:
 * $this->MOD_SETTINGS['depth']: intval 1-3: decides the depth of the list
 * $this->MOD_SETTINGS['mode']: 'perms' / '': decides if we view a user-overview or the permissions.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Andreas Kundoch <typo3@mehrwert.de>
 * @package	TYPO3
 * @subpackage	core
 * @version	$Id: index.php 3775 2008-06-10 12:05:31Z patrick $
 */
class SC_mod_access_perm_index {

	/**
	 * Number of levels to enable recursive settings for
	 * @var integer
	 */
	public $getLevels = 10;

	/**
	 * Module config
	 * Internal static
	 * @var array
	 */
	protected $MCONF = array();

	/**
	 * Document Template Object
	 * @var template
	 */
	public $doc;

	/**
	 * Content accumulation
	 * @var string
	 */
	public $content;
	
	var $BACK_PATH = '../../../../typo3/'; ###MAKE THIS BE CALCULATED OR ANDERS ERMITTELT###
	var $iconPath = '../typo3conf/ext/commerce/res/icons/table/';
	var $rootIconName = 'commerce_globus.gif';
	
	/**
	 * Module menu
	 * @var array
	 */
	public $MOD_MENU = array();

	/**
	 * Module settings, cleansed.
	 * @var aray
	 */
	public $MOD_SETTINGS = array();

	/**
	 * Page select permissions
	 * @var string
	 */
	public $perms_clause;

	/**
	 * Current page record
	 * @var array
	 */
	public $pageinfo;

	/**
	 *  Background color 1
	 * @var string
	 */
	public $color;

	/**
	 * Background color 2
	 * @var string
	 */
	public $color2;

	/**
	 * Background color 3
	 * @var string
	 */
	public $color3;

	/**
	 * Set internally if the current user either OWNS the category OR is admin user!
	 * @var boolean
	 */
	public $editingAllowed;

	/**
	 * Internal, static: GPvars: Category uid.
	 * @var integer
	 */
	public $id;

	/**
	 * If set, editing of the category permissions will occur (showing the editing screen). Notice:
	 * This value is evaluated against permissions and so it will change internally!
	 * @var boolean
	 */
	public $edit;

	/**
	 * ID to return to after editing.
	 * @var integer
	 */
	public $return_id;

	/**
	 * Id of the category which was just edited.
	 * @var integer
	 */
	public $lastEdited;

	/**
	 * Initialization of the class
	 *
	 * @return	void
	 */
	public function init() {

		// Setting GPvars:
		$controlParams = t3lib_div::_GP('control');
		if ($controlParams) {
			$this->table = key ($controlParams);
			$this->id = $controlParams[$this->table]['uid'];
			$this->controlArray = current($controlParams);
		} else {
			$this->id = intval(t3lib_div::_GP('id'));
		}

		$this->edit 	 	= t3lib_div::_GP('edit');
		$this->return_id 	= t3lib_div::_GP('return_id');
		$this->lastEdited 	= t3lib_div::_GP('lastEdited');
		
		// Module name;
		$this->MCONF = $GLOBALS['MCONF'];

		$this->perms_clause = tx_commerce_belib::getCategoryPermsClause(1);

		// Initializing document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath 	= $GLOBALS['BACK_PATH'];
		$this->doc->docType 	= 'xhtml_trans';
		$this->doc->setModuleTemplate('../typo3conf/ext/commerce/mod_access/templates/perm.html'); ###MAKE PATH BE CALCULATED, NOT FIXED###
		$this->doc->form = '<form action="'.$GLOBALS['BACK_PATH'].'tce_db.php" method="post" name="editform">';
		//$this->doc->loadJavascriptLib('../t3lib/jsfunc.updateform.js');
		//$this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
		$this->doc->loadJavascriptLib('../typo3conf/ext/commerce/mod_access/perm.js'); ###MAKE PATH BE CALCULATED, NOT FIXED###

			// Setting up the context sensitive menu:
		//$this->doc->getContextMenuCode();

			// Set up menus:
		$this->menuConfig();
	}

	/**
	 * Configuration of the menu and initialization of ->MOD_SETTINGS
	 *
	 * @return	void
	 */
	public function menuConfig() {
		global $LANG;

			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$temp = $LANG->getLL('levels');
		$this->MOD_MENU = array(
			'depth' => array(
				1 => '1 '.$temp,
				2 => '2 '.$temp,
				3 => '3 '.$temp,
				4 => '4 '.$temp,
				10 => '10 '.$temp
			),
			'mode' => array(
				0 => $LANG->getLL('user_overview'),
				'perms' => $LANG->getLL('permissions')
			)
		);

		// Clean up settings:
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
	}
	
	/**
	 * Returns the info for the Category Path
	 * 
	 * @param {array} $row - Row
	 * @return {string} 
	 */
	public function categoryInfo(&$row) {
		global $BE_USER;
				// Add icon with clickmenu, etc:
		if ($row['uid'])	{	// If there IS a real page
			$alttext = t3lib_BEfunc::getRecordIconAltText($row, 'tx_commerce_categories');
			$iconImg = t3lib_iconWorks::getIconImage('tx_commerce_categories', $row, $this->BACK_PATH, 'class="absmiddle" title="'. htmlspecialchars($alttext) . '"');
		} else {	// On root-level of page tree
				// Make Icon
			$iconImg = '<img' . t3lib_iconWorks::skinImg($this->BACK_PATH, 'gfx/i/_icon_website.gif') . ' alt="root" />';
		}

			// Setting icon with clickmenu + uid
		$pageInfo = $iconImg . '<em>[pid: ' . $row['uid'] . ']</em>';
		return $pageInfo;
	}
	
	/**
	 * Returns the Category Path info
	 * @return {string}
	 * @param $row {array} - Row
	 */
	public function categoryPath(&$row) {
		global $LANG;
			
		$title = $row['title'];
	
		// Setting the path of the page
		$pagePath = $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.path', 1) . ': <span class="typo3-docheader-pagePath">' . htmlspecialchars(t3lib_div::fixed_lgd_cs($title, -50)) . '</span>';
		return $pagePath;
	}

	/**
	 * Main function, creating the content for the access editing forms/listings
	 *
	 * @return	void
	 */
	public function main() {
		global $BE_USER, $LANG;
		
		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = tx_commerce_belib::readCategoryAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo);
		
		
			// Checking access:
		if (($this->id && $access) || ($BE_USER->isAdmin() && !$this->id)) {
			if ($BE_USER->isAdmin() && !$this->id)	{
				$this->pageinfo=array('title' => '[Category]','uid'=>0,'pid'=>0);
			}

			// This decides if the editform can and will be drawn:
			$this->editingAllowed = ($this->pageinfo['perms_userid'] == $BE_USER->user['uid'] || $BE_USER->isAdmin());
			$this->edit = $this->edit && $this->editingAllowed;

			// If $this->edit then these functions are called in the end of the page...
			if ($this->edit)	{
				$this->doc->postCode.= $this->doc->wrapScriptTags('
					setCheck("check[perms_user]","data[tx_commerce_categories]['.$this->id.'][perms_user]");
					setCheck("check[perms_group]","data[tx_commerce_categories]['.$this->id.'][perms_group]");
					setCheck("check[perms_everybody]","data[tx_commerce_categories]['.$this->id.'][perms_everybody]");
				');
			}

			// Draw the HTML page header.
			$this->content .= $this->doc->header($LANG->getLL('permissions') . ($this->edit ? ': '.$LANG->getLL('Edit') : ''));
			$this->content .= $this->doc->spacer(5);

			/*$vContent = $this->doc->getVersionSelector($this->id, 1);
			if ($vContent) {
				$this->content .= $this->doc->section('',$vContent);
			}*/

			// Main function, branching out:
			if (!$this->edit) {
				$this->notEdit();
			} else {
				$this->doEdit();
			}

			$docHeaderButtons = $this->getButtons();

			$markers['CSH'] = $this->docHeaderButtons['csh'];
			$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu($this->id, 'SET[mode]', $this->MOD_SETTINGS['mode'], $this->MOD_MENU['mode']);
			$markers['CONTENT'] = $this->content;
			$markers['CATINFO'] = $this->categoryInfo($this->pageinfo);
			$markers['CATPATH'] = $this->categoryPath($this->pageinfo);

				// Build the <body> for the module
			$this->content = $this->doc->startPage($LANG->getLL('permissions'));
			$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		} else {
				// If no access or if ID == zero
			$this->content.=$this->doc->startPage($LANG->getLL('permissions'));
			$this->content.=$this->doc->header($LANG->getLL('permissions'));
		}
		$this->content.= $this->doc->endPage();
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	public function printContent() {
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array		all available buttons as an assoc. array
	 */
	protected function getButtons() {

		$buttons = array(
			'csh' => '',
			'view' => '',
			'record_list' => '',
			'shortcut' => '',
		);
			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_info', '', $GLOBALS['BACK_PATH'], '', TRUE);

			// View page
		$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewonclick($this->pageinfo['uid'], $GLOBALS['BACK_PATH'], t3lib_BEfunc::BEgetRootLine($this->pageinfo['uid']))) . '">' .
				'<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom.gif') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1) . '" hspace="3" alt="" />' .
				'</a>';

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}

			// If access to Web>List for user, then link to that module.
		if ($GLOBALS['BE_USER']->check('modules','web_list'))	{
			$href = $GLOBALS['BACK_PATH'] . 'db_list.php?id=' . $this->pageinfo['uid'] . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
			$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">' .
					'<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/list.gif') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1) . '" alt="" />' .
					'</a>';
		}
		return $buttons;
	}
	/*****************************
	 *
	 * Listing and Form rendering
	 *
	 *****************************/

	/**
	 * Creating form for editing the permissions	($this->edit = true)
	 * (Adding content to internal content variable)
	 *
	 * @return	void
	 */
	public function doEdit() {
		global $BE_USER,$LANG;

		if ($BE_USER->workspace != 0)	{
				// Adding section with the permission setting matrix:
			$this->content.=$this->doc->divider(5);
			$this->content.=$this->doc->section($LANG->getLL('WorkspaceWarning'),'<div class="warningbox">'.$LANG->getLL('WorkspaceWarningText').'</div>',0,1,3);
		}

		// Get usernames and groupnames
		$beGroupArray = t3lib_BEfunc::getListGroupNames('title,uid');
		$beGroupKeys = array_keys($beGroupArray);

		$beUserArray = t3lib_BEfunc::getUserNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beUserArray = t3lib_BEfunc::blindUserNames($beUserArray,$beGroupKeys,1);
		}
		$beGroupArray_o = $beGroupArray = t3lib_BEfunc::getGroupNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beGroupArray = t3lib_BEfunc::blindGroupNames($beGroupArray_o,$beGroupKeys,1);
		}
		$firstGroup = $beGroupKeys[0] ? $beGroupArray[$beGroupKeys[0]] : '';	// data of the first group, the user is member of


			// Owner selector:
		$options = '';
		$userset = 0;	// flag: is set if the page-userid equals one from the user-list
		foreach($beUserArray as $uid => $row)	{
			if ($uid == $this->pageinfo['perms_userid'])	{
				$userset = 1;
				$selected=' selected="selected"';
			} else {
				$selected='';
			}
			$options.='
				<option value="'.$uid.'"'.$selected.'>'.htmlspecialchars($row['username']).'</option>';
		}
		$options='
				<option value="0"></option>'.$options;
		$selector='
			<select name="data[tx_commerce_categories]['.$this->id.'][perms_userid]">
				'.$options.'
			</select>';

		$this->content.=$this->doc->section($LANG->getLL('Owner').':',$selector);


		// Group selector:
		$options='';
		$userset=0;
		foreach($beGroupArray as $uid => $row)	{
			if ($uid == $this->pageinfo['perms_groupid'])	{
				$userset 	= 1;
				$selected 	= ' selected="selected"';
			} else {
				$selected = '';
			}
			$options.='
				<option value="'.$uid.'"'.$selected.'>'.htmlspecialchars($row['title']).'</option>';
		}
		if (!$userset && $this->pageinfo['perms_groupid'])	{	// If the group was not set AND there is a group for the page
			$options='
				<option value="'.$this->pageinfo['perms_groupid'].'" selected="selected">'.
						htmlspecialchars($beGroupArray_o[$this->pageinfo['perms_groupid']]['title']).
						'</option>'.
						$options;
		}
		$options='
				<option value="0"></option>'.$options;
		$selector='
			<select name="data[tx_commerce_categories]['.$this->id.'][perms_groupid]">
				'.$options.'
			</select>';

		$this->content.=$this->doc->divider(5);
		$this->content.=$this->doc->section($LANG->getLL('Group').':',$selector);

			// Permissions checkbox matrix:
		$code='
			<table border="0" cellspacing="2" cellpadding="0" id="typo3-permissionMatrix">
				<tr>
					<td></td>
					<td class="bgColor2">'.str_replace(' ','<br />',$LANG->getLL('1',1)).'</td>
					<td class="bgColor2">'.str_replace(' ','<br />',$LANG->getLL('16',1)).'</td>
					<td class="bgColor2">'.str_replace(' ','<br />',$LANG->getLL('2',1)).'</td>
					<td class="bgColor2">'.str_replace(' ','<br />',$LANG->getLL('4',1)).'</td>
					<td class="bgColor2">'.str_replace(' ','<br />',$LANG->getLL('8',1)).'</td>
				</tr>
				<tr>
					<td align="right" class="bgColor2">'.$LANG->getLL('Owner',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_user',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_user',5).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_user',2).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_user',3).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_user',4).'</td>
				</tr>
				<tr>
					<td align="right" class="bgColor2">'.$LANG->getLL('Group',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_group',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_group',5).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_group',2).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_group',3).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_group',4).'</td>
				</tr>
				<tr>
					<td align="right" class="bgColor2">'.$LANG->getLL('Everybody',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_everybody',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_everybody',5).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_everybody',2).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_everybody',3).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_everybody',4).'</td>
				</tr>
			</table>
			<br />

			<input type="hidden" name="data[tx_commerce_categories]['.$this->id.'][perms_user]" value="'.$this->pageinfo['perms_user'].'" />
			<input type="hidden" name="data[tx_commerce_categories]['.$this->id.'][perms_group]" value="'.$this->pageinfo['perms_group'].'" />
			<input type="hidden" name="data[tx_commerce_categories]['.$this->id.'][perms_everybody]" value="'.$this->pageinfo['perms_everybody'].'" />
			'.$this->getRecursiveSelect($this->id,$this->perms_clause).'
			<input type="submit" name="submit" value="'.$LANG->getLL('Save',1).'" />'.
			'<input type="submit" value="'.$LANG->getLL('Abort',1).'" onclick="'.htmlspecialchars('jumpToUrl(\'index.php?id='.$this->id.'\'); return false;').'" />
			<input type="hidden" name="redirect" value="'.htmlspecialchars(TYPO3_MOD_PATH.'index.php?mode='.$this->MOD_SETTINGS['mode'].'&depth='.$this->MOD_SETTINGS['depth'].'&id='.intval($this->return_id).'&lastEdited='.$this->id).'" />
		';
		
			// Adding section with the permission setting matrix:
		$this->content.=$this->doc->divider(5);
		$this->content.=$this->doc->section($LANG->getLL('permissions').':',$code);

			// CSH for permissions setting
		$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'perm_module_setting', $GLOBALS['BACK_PATH'],'<br/><br/>');

			// Adding help text:
		if ($BE_USER->uc['helpText'])	{
			$this->content.=$this->doc->divider(20);
			$legendText = '<b>'.$LANG->getLL('1',1).'</b>: '.$LANG->getLL('1_t',1);
			$legendText.= '<br /><b>'.$LANG->getLL('16',1).'</b>: '.$LANG->getLL('16_t',1);
			$legendText.= '<br /><b>'.$LANG->getLL('2',1).'</b>: '.$LANG->getLL('2_t',1);
			$legendText.= '<br /><b>'.$LANG->getLL('4',1).'</b>: '.$LANG->getLL('4_t',1);
			$legendText.= '<br /><b>'.$LANG->getLL('8',1).'</b>: '.$LANG->getLL('8_t',1);

			$code=$legendText.'<br /><br />'.$LANG->getLL('def',1);
			$this->content.=$this->doc->section($LANG->getLL('Legend',1).':',$code);
		}
	}

	/**
	 * Showing the permissions in a tree ($this->edit = false)
	 * (Adding content to internal content variable)
	 *
	 * @return	void
	 */
	public function notEdit() {
		global $BE_USER,$LANG,$BACK_PATH;
	
		// Get usernames and groupnames: The arrays we get in return contains only 1) users which are members of the groups of the current user, 2) groups that the current user is member of
		$beGroupKeys = $BE_USER->userGroupsUID;
		$beUserArray = t3lib_BEfunc::getUserNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beUserArray = t3lib_BEfunc::blindUserNames($beUserArray,$beGroupKeys,0);
		}
		$beGroupArray = t3lib_BEfunc::getGroupNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beGroupArray = t3lib_BEfunc::blindGroupNames($beGroupArray,$beGroupKeys,0);
		}

			// Length of strings:
		$tLen= ($this->MOD_SETTINGS['mode']=='perms' ? 20 : 30);


			// Selector for depth:
		$code.=$LANG->getLL('Depth').': ';
		$code.=t3lib_BEfunc::getFuncMenu($this->id,'SET[depth]',$this->MOD_SETTINGS['depth'],$this->MOD_MENU['depth']);
		$this->content.=$this->doc->section('',$code);
		$this->content.=$this->doc->spacer(5);

			// Initialize tree object:
		$tree = t3lib_div::makeInstance('tx_commerce_categorytree');
	
		$tree->setBare();
		$tree->init();
		$tree->readRecursively($this->id, $this->MOD_SETTINGS['depth']);
		// Create the tree from $this->id:
		$tree->getTree();
		
		//Get the tree records
		$records = $tree->getRecordsAsArray($this->id);
		
			// Make header of table:
		$code='';
		
		if ($this->MOD_SETTINGS['mode']=='perms') {
			$code.='
				<tr>
					<td class="bgColor2" colspan="2">&nbsp;</td>
					<td class="bgColor2"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td class="bgColor2" align="center"><b>'.$LANG->getLL('Owner',1).'</b></td>
					<td class="bgColor2"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td class="bgColor2" align="center"><b>'.$LANG->getLL('Group',1).'</b></td>
					<td class="bgColor2"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td class="bgColor2" align="center"><b>'.$LANG->getLL('Everybody',1).'</b></td>
					<td class="bgColor2"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td class="bgColor2" align="center"><b>'.$LANG->getLL('EditLock',1).'</b></td>
				</tr>
			';
		} else {
			$code.='
				<tr>
					<td class="bgColor2" colspan="2">&nbsp;</td>
					<td class="bgColor2"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td class="bgColor2" align="center" nowrap="nowrap"><b>'.$LANG->getLL('User',1).':</b> '.$BE_USER->user['username'].'</td>
					'.(!$BE_USER->isAdmin()?'<td class="bgColor2"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td class="bgColor2" align="center"><b>'.$LANG->getLL('EditLock',1).'</b></td>':'').'
				</tr>';
		}
		
		// Creating category Icon
		$icon 		= t3lib_iconWorks::getIconImage('tx_commerce_categories',$this->pageinfo,$BACK_PATH,'align="top" class="c-recIcon"');
		$rootIcon 	= '<img'.t3lib_iconWorks::skinImg($this->BACK_PATH.$this->iconPath, $this->rootIconName,'width="18" height="16"').' title="Root" alt="" />';
		
		// Traverse tree:
		$depthStop = array();	//stores which depths already have their last item
		$l = count($records);
		$lastDepth = 0;
		
		for($i = 0; $i < $l; $i ++) {
			$row = $records[$i]['record'];
			
			$cells  = array();
			$pageId = $row['uid'];
		
			// Background colors:
			$bgCol = ($this->lastEdited == $pageId ? ' class="bgColor-20"' : '');
			$lE_bgCol = $bgCol;

			// User/Group names:
			$userName = $beUserArray[$row['perms_userid']] ? $beUserArray[$row['perms_userid']]['username'] : ($row['perms_userid'] ? '<i>['.$row['perms_userid'].']!</i>' : '');
			$userName = SC_mod_access_perm_ajax::renderOwnername($pageId, $row['perms_userid'], htmlspecialchars(t3lib_div::fixed_lgd_cs($userName, 20)));

			$groupName = $beGroupArray[$row['perms_groupid']] ? $beGroupArray[$row['perms_groupid']]['title']  : ($row['perms_groupid'] ? '<i>['.$row['perms_groupid'].']!</i>' : '');
			$groupName = SC_mod_access_perm_ajax::renderGroupname($pageId, $row['perms_groupid'], htmlspecialchars(t3lib_div::fixed_lgd_cs($groupName, 20)));


			// Seeing if editing of permissions are allowed for that page:
			$editPermsAllowed = ($row['perms_userid'] == $BE_USER->user['uid'] || $BE_USER->isAdmin());


			// First column:
			//$cellAttrib = ($data['row']['_CSSCLASS'] ? ' class="'.$data['row']['_CSSCLASS'].'"' : '');
			$cellAttrib = '';
			
			$PMicon = '';

			//Add PM only if we are not looking at the root	
			if(0 < $records[$i]['depth']) {
		
					//Add simple join-images for categories that are deeper level than 1
					if(1 < $records[$i]['depth']) {
						$k = $records[$i]['depth'];
						$n = count($depthStop);
						
						for($j = 1; $j < $k; $j ++) {
							if(!array_key_exists($j, $depthStop) || $depthStop[$j] != 1) {
								$PMicon .= '<img'.t3lib_iconWorks::skinImg($this->BACK_PATH,'gfx/ol/line.gif','width="18" height="16"').' alt="" />';
							} else if($depthStop[$j] == 1){
								$PMicon .= '<img'.t3lib_iconWorks::skinImg($this->BACK_PATH,'gfx/ol/blank.gif','width="18" height="16"').' alt="" />';
							}
						}
					}
					
					if($lastDepth > $records[$i]['depth']) {
						for($j = $records[$i]['depth'] + 1; $j <= $lastDepth; $j ++) {
							$depthStop[$j] = 0;
						}
					}
					
					//Add cross or bottom
					$bottom = (true == $records[$i]['last']) ? 'bottom' : '';
					
					//save that the depth of the current record has its last item - is used to add blanks, not lines to following deeper elements
					if(true == $records[$i]['last']) {
						$depthStop[$records[$i]['depth']] = 1;
					}	
					
					$lastDepth = $records[$i]['depth'];
					
					$PMicon .= '<img'.t3lib_iconWorks::skinImg($this->BACK_PATH,'gfx/ol/join'.$bottom.'.gif','width="18" height="16"').' alt="" />';
			}
			
			//determine which icon to use
			$rowIcon = (0 == $pageId) ? $rootIcon : $icon;
			
			$cells[]='
					<td align="left" nowrap="nowrap"'.($cellAttrib ? $cellAttrib : $bgCol).'>'.$PMicon.$rowIcon.htmlspecialchars(t3lib_div::fixed_lgd($row['title'],$tLen)).'&nbsp;</td>';

				// "Edit permissions" -icon
			if ($editPermsAllowed && $pageId) {
				$aHref = 'index.php?mode='.$this->MOD_SETTINGS['mode'].'&depth='.$this->MOD_SETTINGS['depth'].'&id='.$pageId.'&return_id='.$this->id.'&edit=1';
				$cells[]='
					<td'.$bgCol.'><a href="'.htmlspecialchars($aHref).'"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/edit2.gif','width="11" height="12"').' border="0" title="'.$LANG->getLL('ch_permissions',1).'" align="top" alt="" /></a></td>';
			} else {
				$cells[]='
					<td'.$bgCol.'></td>';
			}
			
				// Rest of columns (depending on mode)
			if ($this->MOD_SETTINGS['mode'] == 'perms') {
				$cells[]='
					<td'.$bgCol.'><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td'.$bgCol.' nowrap="nowrap">'.($pageId ? SC_mod_access_perm_ajax::renderPermissions($row['perms_user'], $pageId, 'user').' '.$userName : '').'</td>

					<td'.$bgCol.'><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td'.$bgCol.' nowrap="nowrap">'.($pageId ? SC_mod_access_perm_ajax::renderPermissions($row['perms_group'], $pageId, 'group').' '.$groupName : '').'</td>

					<td'.$bgCol.'><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td'.$bgCol.' nowrap="nowrap">'.($pageId ? ' '.SC_mod_access_perm_ajax::renderPermissions($row['perms_everybody'], $pageId, 'everybody') : '').'</td>

					<td'.$bgCol.'><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td'.$bgCol.' nowrap="nowrap">'.($row['editlock']?'<span id="el_'.$pageId.'" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\''.$pageId.'\', \'1\');"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/recordlock_warning2.gif','width="22" height="16"').' title="'.$LANG->getLL('EditLock_descr',1).'" alt="Edit Lock" /></a></span>' : ( $pageId === 0 ? '' : '<span id="el_'.$pageId.'" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\''.$pageId.'\', \'0\');" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">[+]</a></span>')).'</td>
				';
			} else {
				$cells[]='
					<td'.$bgCol.'><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>';

				$bgCol = ($BE_USER->user['uid'] == $row['perms_userid'] ? ' class="bgColor-20"' : $lE_bgCol);

				// FIXME $owner undefined
				$cells[]='
					<td'.$bgCol.' nowrap="nowrap" align="center">'.($pageId ? $owner.SC_mod_access_perm_ajax::renderPermissions($BE_USER->calcPerms($row), $pageId, 'user') : '').'</td>
					'.(!$BE_USER->isAdmin()?'
					<td'.$bgCol.'><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/line.gif','width="5" height="16"').' alt="" /></td>
					<td'.$bgCol.' nowrap="nowrap">'.($row['editlock']?'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/recordlock_warning2.gif','width="22" height="16"').' title="'.$LANG->getLL('EditLock_descr',1).'" alt="" />' : '').'</td>
					':'');
				$bgCol = $lE_bgCol;
			}

				// Compile table row:
			$code .= '
				<tr>
					'.implode('
					',$cells).'
				</tr>';
		}

			// Wrap rows in table tags:
		$code = '<table border="0" cellspacing="0" cellpadding="0" id="typo3-permissionList" width="99.5%">'.$code.'</table>';

			// Adding the content as a section:
		$this->content.=$this->doc->section('',$code);

			// CSH for permissions setting
		$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'perm_module', $GLOBALS['BACK_PATH'],'<br/>|');

			// Creating legend table:
		$legendText = '<b>'.$LANG->getLL('1',1).'</b>: '.$LANG->getLL('1_t',1);
		$legendText.= '<br /><b>'.$LANG->getLL('16',1).'</b>: '.$LANG->getLL('16_t',1);
		$legendText.= '<br /><b>'.$LANG->getLL('2',1).'</b>: '.$LANG->getLL('2_t',1);
		$legendText.= '<br /><b>'.$LANG->getLL('4',1).'</b>: '.$LANG->getLL('4_t',1);
		$legendText.= '<br /><b>'.$LANG->getLL('8',1).'</b>: '.$LANG->getLL('8_t',1);

		$code='<table border="0" id="typo3-legendTable">
			<tr>
				<td valign="top"><img src="legend.gif" width="86" height="75" alt="" /></td>
				<td valign="top" nowrap="nowrap">'.$legendText.'</td>
			</tr>
		</table>';
		$code.='<div id="perm-legend">'.$LANG->getLL('def',1);
		$code.='<br /><br /><span class="perm-allowed">*</span>: '.$LANG->getLL('A_Granted', 1);
		$code.='<br /><span class="perm-denied">x</span>: '.$LANG->getLL('A_Denied', 1);
		$code.='</div>';

			// Adding section with legend code:
		$this->content.=$this->doc->spacer(20);
		$this->content.=$this->doc->section($LANG->getLL('Legend').':',$code,0,1);
	}







	/*****************************
	 *
	 * Helper functions
	 *
	 *****************************/

	/**
	 * Print a checkbox for the edit-permission form
	 *
	 * @param	string		Checkbox name key
	 * @param	integer		Checkbox number index
	 * @return	string		HTML checkbox
	 */
	public function printCheckBox($checkName, $num) {
		$onclick = 'checkChange(\'check['.$checkName.']\', \'data[tx_commerce_categories]['.$GLOBALS['SOBE']->id.']['.$checkName.']\')';
		return '<input type="checkbox" name="check['.$checkName.']['.$num.']" onclick="'.htmlspecialchars($onclick).'" /><br />';
	}


	/**
	 * Returns the permissions for a group based of the perms_groupid of $row. If the $row[perms_groupid] equals the $firstGroup[uid] then the function returns perms_everybody OR'ed with perms_group, else just perms_everybody
	 *
	 * @param	array		Row array (from pages table)
	 * @param	array		First group data
	 * @return	integer		Integer: Combined permissions.
	 */
	public function groupPerms($row, $firstGroup) {
		if (is_array($row))	{
			$out = intval($row['perms_everybody']);
			if ($row['perms_groupid'] && $firstGroup['uid']==$row['perms_groupid'])	{
				$out |= intval($row['perms_group']);
			}
			return $out;
		}
	}

	/**
	 * Finding tree and offer setting of values recursively.
	 *
	 * @param	integer		Page id.
	 * @param	string		Select clause
	 * @return	string		Select form element for recursive levels (if any levels are found)
	 */
	public function getRecursiveSelect($id,$perms_clause) {
		
		// Initialize tree object:
		$tree = t3lib_div::makeInstance('tx_commerce_categorytree');
		$tree->setBare();
		$tree->readRecursively($this->id, $this->getLevels);
		$tree->init();
		
		// Create the tree from $this->id:
		$tree->getTree();
		
		//Get the tree records
		$recsPerLevel = $tree->getRecordsPerLevelArray($this->id);

		// If there are a hierarchy of category ids, then...
		if ($GLOBALS['BE_USER']->user['uid'] && count($recsPerLevel)) {

			// Init:
			$label_recur 	= $GLOBALS['LANG']->getLL('recursive');
			$label_levels 	= $GLOBALS['LANG']->getLL('levels');
			$label_pA 		= $GLOBALS['LANG']->getLL('pages_affected');
			$theIdListArr	= array();
			
			//Put dummy entry so user is not forced to select
			$opts = '<option value=""></option>';

			// Traverse the number of levels we want to allow recursive setting of permissions for:
			for ($a = 0; $a <= $this->getLevels; $a ++)	{
				//Go through the levels
				if (is_array($recsPerLevel[$a])) {
					
					foreach($recsPerLevel[$a] as $theId)	{
						
						$cat = $tree->getCategory($theId); //get the category record
						
						//Check if the category uid should be added as a child
						if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->user['uid'] == $cat['perms_userid'])	{
							$theIdListArr[] = $theId;
						}
					}
					$lKey = $a + 1;
					$opts .= '
						<option value="'.htmlspecialchars(implode(',',$theIdListArr)).'">'.
							t3lib_div::deHSCentities(htmlspecialchars($label_recur.' '.$lKey.' '.$label_levels)).' ('.count($theIdListArr).' '.$label_pA.')'.
							'</option>';
				}
			}

				// Put the selector box together:
			$theRecursiveSelect = '<br />
					<select name="mirror[tx_commerce_categories]['.$id.']">
						'.$opts.'
					</select>
				<br /><br />';
		} else {
			$theRecursiveSelect = '';
		}

			// Return selector box element:
		return $theRecursiveSelect;
	}
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/mod_access/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/mod_access/index.php']);
}

//Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_access_perm_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

if ($TYPO3_CONF_VARS['BE']['compressionLevel'])	{
	new gzip_encode($TYPO3_CONF_VARS['BE']['compressionLevel']);
}

?>
