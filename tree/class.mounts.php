<?php
/**
 * Implements the mounts for leafMaster
 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 **/

require_once(PATH_t3lib.'class.t3lib_beuserauth.php');
require_once(t3lib_extmgm::extPath('commerce').'tree/class.langbase.php');

class mounts extends langbase{
	/*****************************
	 * Variable Definition
	 ****************************/
	
	protected $user_uid;  	//Uid of the User
	protected $mountlist; 	//List with all mounts
	protected $mountdata; 	//Array with all mounts
	protected $pointer;		//Walk-Pointer
	protected $user;		//User for this mount
	protected $group;		//Group for this mount
	protected $byGroup;		//Flag if we want to read the mounts by group
	
	protected $table 			= 'be_users';
	protected $grouptable 		= 'be_groups';
	protected $field			= null;	//overwrite this
	protected $usergroupField 	= 'usergroup';
	protected $where 			= '';
	
	/****************************
	 * Functions
	 ***************************/
	
	/**
	 * Constructor - initializes the values
	 * 
	 * @return {void}
	 */
	public function __construct() {
		
		$this->user_uid  = 0;
		$this->mountlist = '';
		$this->mountdata = array();
		$this->pointer	 = 0;
		$this->user		 = t3lib_div::makeInstance('t3lib_beUserAuth');
		$this->group	 = 0;
		$this->byGroup	 = false;
		
		//init langbase
		parent::__construct();
	}
	
	/**
	 * Initializes the Mounts for a user
	 * Overwrite this function if you plan to not read Mountpoints from the be_users table
	 * 
	 * @param $uid {int}	User UID
	 * @return {void}
	 */
	public function init($uid) {	
	
		//Return if the UID is not numeric - could also be because we have a new user
		if(!is_numeric($uid) || null == $this->field) {
			if (TYPO3_DLOG) t3lib_div::devLog('init (mounts) gets passed invalid parameters. Script is aborted.', COMMERCE_EXTkey, 2);	
			return;
		}
		
		$this->user_uid = $uid;
		$this->user->setBeUserByUid($uid);
		
		$mounts = $this->getMounts();
		
		//If neither User nor Group have mounts, return
		if(null == $mounts) {
			return;
		}

		//Store the results
		$this->mountlist = t3lib_div::uniqueList($mounts); //Clean duplicates
		$this->mountdata = explode(',', $this->mountlist);
	}
	
	/**
	 * Initializes the Mounts for a group
	 * Overwrite this function if you plan to not read Mountpoints from the be_groups table
	 * 
	 * @param $uid {int}	Group UID
	 * @return {void}
	 */
	public function initByGroup($uid) {	
		
		//Return if the UID is not numeric - could also be because we have a new user
		if(!is_numeric($uid) || null == $this->field) {
			if (TYPO3_DLOG) t3lib_div::devLog('initByGroup (mounts) gets passed invalid parameters. Script is aborted.', COMMERCE_EXTkey, 2);	
			return;
		}
		
		$this->byGroup  = true;
		$this->group 	= $uid;
		$this->user_uid = 0;
		
		$mounts = $this->getMounts();
		
		//If the Group has no mounts, return
		if(null == $mounts) {
			return;
		}

		//Store the results
		$this->mountlist = t3lib_div::uniqueList($mounts); //Clean duplicates
		$this->mountdata = explode(',', $this->mountlist);
	}
	
	/**
	 * Returns a comma-separeted list of mounts
	 * 
	 * @return {string}		item1, item2, ..., itemN
	 */
	protected function getMounts() {
		$mounts = '';
		
		//Set mount to 0 if the User is a admin
		if(!$this->byGroup && $this->user->isAdmin()) {
			$mounts = '0';
		} else {
			//Read usermounts - if none are set, mounts are set to NULL	
			if(!$this->byGroup) {
				$res   = $GLOBALS['TYPO3_DB']->exec_SELECTquery($this->field.','.$this->usergroupField, $this->table, 'uid='.$this->user_uid, $this->where);
				
				$row 	= $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	            $mounts = $row[$this->field];
				
				//Read Usergroup mounts
				$groups = t3lib_div::uniqueList($row[$this->usergroupField]);
			} else {
				$groups = $this->group;
			}
			
			if('' != trim($groups)) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($this->field, $this->grouptable, 'uid IN ('.$groups.')');
				
				//Walk the groups and add the mounts
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$mounts .= ','.$row[$this->field];
				}
				
				//Make nicely formated list
				$mounts = t3lib_div::uniqueList($mounts);
			}
		} 
		
		return $mounts;
	}
	
	/**
	 * Checks whether the User has mounts
	 * 
	 * @return {boolean}
	 */
	function hasMounts() {
		return ($this->mountlist != '');
	}
	
	/**
	 * Returns the mountlist of the current BE User
	 * 
	 * @return {string}
	 */
	function getMountList() {
		return $this->mountlist;
	}
	
	/**
	 * Returns the array with the mounts of the current BE User
	 * 
	 * @return {array}
	 */
	function getMountData() {
		return $this->mountdata;
	}
	
	/**
	 * Walks the category mounts
	 * Returns the mount-id or FALSE
	 * 
	 * @return {int}
	 */
	function walk() {
		//Abort if we reached the end of this collection
		if(!isset($this->mountdata[$this->pointer])) {
			$this->resetPointer();
			return false;
		}
		
		return $this->mountdata[$this->pointer ++];
	}
	
	/**
	 * Sets the internal pointer to 0
	 * 
	 * @return {void}
	 */
	function resetPointer() {
		$this->pointer = 0;
	}
}
?>
