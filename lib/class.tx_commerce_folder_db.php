<?php
/**
 * Misc commerce db functions
 *
 * @author	Marketing Factory
 * @maintainer	Erik Frister
 * @package TYPO3
 * @subpackage tx_commerce
 *
 */
 
require_once(PATH_t3lib.'class.t3lib_befunc.php');


class tx_commerce_folder_db {

	
/***************************************
	 *
	 *	 Commerce sysfolder
	 *
	 ***************************************/


	/**
	 * Create your database table folder
	 * overwrite this if wanted
	 * 
	 * @param	[type]		$pid: ...
	 * @return	void		
	 * @TODO	title aus extkey ziehen
	 * @TODO	Sortierung
	 */#
	function createFolder($title = 'Commerce', $module = 'commerce', $pid=0) {		
		$fields_values = array();
		$fields_values['pid'] = $pid;
		$fields_values['sorting'] = 10111; #TODO
		$fields_values['perms_user'] = 31;
		$fields_values['perms_group'] = 31;
		$fields_values['perms_everybody'] = 31;
		$fields_values['title'] = $title;
		$fields_values['tx_graytree_foldername'] =  strtolower($title); ###MAKE IT tx_commerce_foldername###
		$fields_values['doktype'] = 254;
		$fields_values['module'] = $module;
		$fields_values['crdate'] = time();
		$fields_values['tstamp'] = time();
		return $GLOBALS['TYPO3_DB']->exec_INSERTquery('pages', $fields_values);
	}


	/**
	 * Find the extension folders
	 * 
	 * @return	array		rows of found extension folders
	 */#
	function getFolders($module = 'commerce',$pid = 0,$title ='' ) {
		$rows=array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,title', 'pages', 'doktype=254 and tx_graytree_foldername = "'.strtolower($title).'"and pid = "'.$pid.'"and module="'.$module.'" '.t3lib_BEfunc::deleteClause('pages'));
    		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$rows[$row['uid']]=$row;
		}
		return $rows;
	}
	
	
	/**
	 * Returns pidList of extension Folders
	 * 
	 * @return	string		commalist of PIDs
	 */
	function getFolderPidList($module = 'commerce') {
		return implode(',',array_keys(tx_commerce_folder_db::getFolders($module)));
	}


	/**
	 * Find the extension folders or create one.
	 * @param  (title) Folder Title as named in pages table
	 * @param  (module) Extension Moduke
	 * @param  (pid) Parent Page id 
	 * @param  (parenTitle) Parent Folder Title
	 * 
	 * @return	array		
	 */#
	function initFolders($title = 'Commerce', $module = 'commerce',$pid=0,$parentTitle='')	{
		// creates a GRAYTREE folder on the fly
		// not really a clean way ...
		
		if($parentTitle){
		    $pFolders = tx_commerce_folder_db::getFolders($module,$pid,$parentTitle);
		    $pf = current($pFolders);
		    $pid = $pf['uid'];
		}
	
		$folders = tx_commerce_folder_db::getFolders($module,$pid,$title);
		if (!count($folders)) {
			tx_commerce_folder_db::createFolder($title, $module,$pid);
			$folders = tx_commerce_folder_db::getFolders($module,$pid,$title);
		
		}
		$cf = current($folders);
				
		return array ($cf['uid'],implode(',',array_keys($folders)));	
	
	}	
	
	

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_folder_db.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_folder_db.php']);
}

?>
