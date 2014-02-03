<?php
/**
 * Implements the Command-Wizard
 *
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister
 */

unset($MCONF);
require ('conf.php');
require ($BACK_PATH . 'init.php');
require ($BACK_PATH . 'template.php');

$LANG->includeLLFile('EXT:lang/locallang_misc.xml');

class tx_commerce_cmd_wizard {
	var $pageinfo;
	var $pidInfo;
	var $newContentInto;
	var $web_list_modTSconfig;
	var $allowedNewTables;
	var $web_list_modTSconfig_pid;
	var $allowedNewTables_pid;
	var $code;
	var $R_URI;

		// Internal, static: GPvar
	var $id;			// see init()
	var $returnUrl;		// Return url.
	var $pagesOnly;		// pagesOnly flag.

		// Internal
	var $perms_clause;	// see init()
	var $doc;			// see init()
	var $content;		// Accumulated HTML output
	var $param;
	var $defVals;		// default values to be used

	/**
	 * Constructor function for the class
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH;
			// page-selection permission clause (reading)
		$this->perms_clause = $BE_USER->getPagePermsClause(1);

			// Setting GPvars:
		$this->id 			= (0 != t3lib_div::_GP('id')) ? intval(t3lib_div::_GP('id')) : Tx_Commerce_Utility_BackendUtility::getProductFolderUid();	// The page id to operate from
		$this->returnUrl 	= t3lib_div::_GP('returnUrl');

		$this->param 		= t3lib_div::_GP('edit');	// this to be accomplished from the caller: &edit['.$table.'][-'.$uid.']=new&
		$this->defVals 		= t3lib_div::_GP('defVals');

			// Create instance of template class for output
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType= 'xhtml_trans';
		$this->doc->JScode='';

		$this->head = $LANG->getLL('newRecordGeneral',1);;

			// Creating content
		$this->content='';
		$this->content.=$this->doc->startPage($this->head);
		$this->content.=$this->doc->header($this->head);

			// Id a positive id is supplied, ask for the page record with permission information contained:
		if ($this->id > 0)	{
			$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		}

			// If a page-record was returned, the user had read-access to the page.
		if ($this->pageinfo['uid'])	{
				// Get record of parent page
			$this->pidInfo = t3lib_BEfunc::getRecord('pages',$this->pageinfo['pid']);
				// Checking the permissions for the user with regard to the parent page: Can he create new pages, new content record, new page after?
			if ($BE_USER->doesUserHaveAccess($this->pageinfo,16))	{
				$this->newContentInto = 1;
			}
		} elseif ($BE_USER->isAdmin())	{
				// Admins can do it all
			$this->newContentInto = 1;
		} else {
				// People with no permission can do nothing
			$this->newContentInto = 0;
		}
	}



	/**
	 * Main processing, creating the list of new record tables to select from
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG;

			// If there was a page - or if the user is admin (admins has access to the root) we proceed:
		if ($this->pageinfo['uid'] || $BE_USER->isAdmin())	{
				// Acquiring TSconfig for this module/current page:
			$this->web_list_modTSconfig = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'],'mod.web_list');
			$this->allowedNewTables = t3lib_div::trimExplode(',',$this->web_list_modTSconfig['properties']['allowedNewTables'],1);

				// Acquiring TSconfig for this module/parent page:
			$this->web_list_modTSconfig_pid = t3lib_BEfunc::getModTSconfig($this->pageinfo['pid'],'mod.web_list');
			$this->allowedNewTables_pid = t3lib_div::trimExplode(',',$this->web_list_modTSconfig_pid['properties']['allowedNewTables'],1);

				// Set header-HTML and return_url
			$this->code=$this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />
			';
			$this->R_URI=$this->returnUrl;

			$this->regularNew();

				// Create go-back link.
			if ($this->R_URI)	{
				$this->code.='<br />
		<a href="'.htmlspecialchars($this->R_URI).'" class="typo3-goBack">'.
		'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/goback.gif','width="14" height="14"').' alt="" />'.
		$LANG->getLL('goBack',1).
		'</a>';
			}
				// Add all the content to an output section
			$this->content.=$this->doc->section('',$this->code);
		}
	}


	/**
	 * Create a regular new element (pages and records)
	 *
	 * @return	void
	 */
	function regularNew()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA,$LANG;

			// Slight spacer from header:
		$this->code.='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/halfline.gif','width="18" height="8"').' alt="" /><br />';

		// New tables INSIDE this category
		foreach ($this->param as $table=>$param) {
			if ($this->showNewRecLink($table)
					&& $this->isTableAllowedForThisPage($this->pageinfo, $table)
					&& $BE_USER->check('tables_modify',$table)
					&& (($v['ctrl']['rootLevel'] xor $this->id) || $v['ctrl']['rootLevel']==-1)
					)	{
				$val = key($param);
				$cmd = ($param[$val]);
				switch ($cmd) {
					case 'new':
							// Create new link for record:
						$rowContent = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/join.gif','width="18" height="16"').' alt="" />'.
								$this->linkWrap(
								t3lib_iconWorks::getIconImage($table,array(),$BACK_PATH,'').
								$LANG->sL($TCA[$table]['ctrl']['title'],1)
							,$table
							,$this->id);

							// Compile table row:
						$tRows[] = '
				<tr>
					<td nowrap="nowrap">'.$rowContent.'</td>
					<td>'.t3lib_BEfunc::cshItem($t,'',$BACK_PATH,'',$doNotShowFullDescr).'</td>
				</tr>
				';
						break;

					default:
						break;
				}
			}
		}

			// Compile table row:
		$tRows[]='
			<tr>
				<td><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/stopper.gif','width="18" height="16"').' alt="" /></td>
				<td></td>
			</tr>
		';


			// Make table:
		$this->code.='
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-newRecord">
			'.implode('',$tRows).'
			</table>
		';

			// Add CSH:
		$this->code.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'new_regular', $GLOBALS['BACK_PATH'],'<br/>');
	}

	/**
	 * Ending page output and echo'ing content to browser.
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Links the string $code to a create-new form for a record in $table created on page $pid
	 *
	 * @param	string		Link string
	 * @param	string		Table name (in which to create new record)
	 * @param	integer		PID value for the "&edit['.$table.']['.$pid.']=new" command (positive/negative)
	 * @param	boolean		If $addContentTable is set, then a new contentTable record is created together with pages
	 * @return	string		The link.
	 */
	function linkWrap($code,$table,$pid,$addContentTable=0)	{
		$params = '&edit['.$table.']['.$pid.']=new' .$this->compileDefVals($table);
		$onClick = t3lib_BEfunc::editOnClick($params,$this->doc->backPath,$this->returnUrl);
		return '<a href="#" onclick="'.htmlspecialchars($onClick).'">'.$code.'</a>';
	}

	function compileDefVals($table) {
		$data = t3lib_div::_GP('defVals');
		if (is_array($data[$table])) {
			$result = '';
			foreach ($data[$table] as $key => $value) {
				$result .= '&defVals[' .$table .'][' .$key .']=' .urlencode($value);
			}
		} else {
			$result = '';
		}
		return $result;
	}


	/**
	 * Returns true if the tablename $checkTable is allowed to be created on the page with record $pid_row
	 *
	 * @param	array		Record for parent page.
	 * @param	string		Table name to check
	 * @return	boolean		Returns true if the tablename $checkTable is allowed to be created on the page with record $pid_row
	 */
	function isTableAllowedForThisPage($pid_row, $checkTable)	{
		global $PAGES_TYPES;
		if (!is_array($pid_row))	{
			if ($GLOBALS['BE_USER']->user['admin'])	{
				return true;
			} else {
				return false;
			}
		}
			// be_users and be_groups may not be created anywhere but in the root.
		if ($checkTable=='be_users' || $checkTable=='be_groups')	{
			return false;
		}
			// Checking doktype:
		$doktype = intval($pid_row['doktype']);
		if (!$allowedTableList = $PAGES_TYPES[$doktype]['allowedTables'])	{
			$allowedTableList = $PAGES_TYPES['default']['allowedTables'];
		}
		if (strstr($allowedTableList,'*') || t3lib_div::inList($allowedTableList,$checkTable))	{		// If all tables or the table is listed as a allowed type, return true
			return true;
		}
	}

	/**
	 * Returns true if the $table tablename is found in $allowedNewTables (or if $allowedNewTables is empty)
	 *
	 * @param	string		Table name to test if in allowedTables
	 * @param	array		Array of new tables that are allowed.
	 * @return	boolean		Returns true if the $table tablename is found in $allowedNewTables (or if $allowedNewTables is empty)
	 */
	function showNewRecLink($table,$allowedNewTables='')	{
		$allowedNewTables = is_array($allowedNewTables) ? $allowedNewTables : $this->allowedNewTables;
		return !count($allowedNewTables) || in_array($table,$allowedNewTables);
	}


}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_category/class.tx_commerce_cmd_wizard.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_category/class.tx_commerce_cmd_wizard.php']);
}

/** @var tx_commerce_cmd_wizard $SOBE */
$SOBE = t3lib_div::makeInstance('tx_commerce_cmd_wizard');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>