<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Carsten Lausen 
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
* class basic dao mapper
* This class used by the dao for database storage.
* It defines how to insert, update, find and delete a transfer object in the database.
* Extend this class to fit specific needs.
* 
* This class has no knowledge about the internal design of the model transfer object. 
* Object <-> model (transfer object) mapping and all model design is done by the parser.
* 
* The class needs a parser for object <-> model (transfer object) mapping. 
*
*
* @access public
* @package TYPO3
* @subpackage commerce
* @author Carsten Lausen <cl@e-netconsulting.de>
*/

class basic_dao_mapper {

 	var $dbTable = '';  //place dbtable here!!
 	var $parser;		//parser
 	var $createPid;		//pid of newly created records
 	
 	//------------- constructor ----------------
 	
 	function basic_dao_mapper(&$parser, $createPid=0, $dbTable=null) {
 		$this->init();
		$this->parser = &$parser;
		if(!empty($createPid))$this->createPid = $createPid;
		if(!empty($dbTable))$this->dbTable = $dbTable;
 	} 	
 	
 	function init() {
	 	$this->dbTable = '';	//dbtable for persistence
	 	$this->createPid = 0;   //new record pid
 	}
 	
 	//---------- public functions --------------
 	
 	function load(&$obj) {
 		if($obj->issetId()) {
 			$this->dbSelectById($obj->getId(),$obj);
 		}
 	}
 	
 	function save(&$obj) {
 		if($obj->issetId()) {
 			$this->dbUpdate($obj->getId(),$obj);
 		} else {
 			$this->dbInsert($obj);
 		}
 	}

 	function remove(&$obj) {
 		if($obj->issetId()) {
 			$this->dbDelete($obj->getId(),$obj);
 		}
 	}
 	
 	//---------- private db functions -------------
 	
 	function dbInsert(&$obj) {

 		$dbTable = $this->dbTable;
 		$dbModel = $this->parser->parseObjectToModel($obj);
 		
 		//set pid
 		$this->parser->setPid($dbModel,$this->createPid);
 		
		//execute query
//		debug(array('$dbModel' => $dbModel));
//		debug(array('dbInsert' => $GLOBALS['TYPO3_DB']->INSERTquery($dbTable, $dbModel)));
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($dbTable, $dbModel);

		//any errors
		$error=$GLOBALS['TYPO3_DB']->sql_error();
		if(!empty($error)) $this->addError(array($error,$GLOBALS['TYPO3_DB']->INSERTquery($dbTable, $dbModel),'$dbModel' => $dbModel));

		//set object id
		$obj->setId($GLOBALS['TYPO3_DB']->sql_insert_id());

 	}
 	
 	function dbUpdate($uid, &$obj) {

 		$dbTable = $this->dbTable;
 		$dbWhere = 'uid="'.$uid.'"';
 		$dbModel = $this->parser->parseObjectToModel($obj);
 		
		//execute query
//		debug($dbModel);
//		debug(array('dbUpdate' => $GLOBALS['TYPO3_DB']->UPDATEquery($dbTable, $dbWhere, $dbModel)));
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($dbTable, $dbWhere, $dbModel);

		//any errors
		$error=$GLOBALS['TYPO3_DB']->sql_error();
		if(!empty($error)) $this->addError(array($error,$GLOBALS['TYPO3_DB']->UPDATEquery($dbTable, $dbWhere, $dbModel),'$dbModel' => $dbModel));


 	}
 	
 	function dbDelete($uid, &$obj) {

 		$dbTable = $this->dbTable;
 		$dbWhere = 'uid="'.$uid.'"';

		//execute query
//		debug(array('dbDelete' => $GLOBALS['TYPO3_DB']->DELETEquery($dbTable, $dbWhere)));
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery($dbTable, $dbWhere);

		//any errors
		$error=$GLOBALS['TYPO3_DB']->sql_error();
		if(!empty($error)) $this->addError(array($error,$GLOBALS['TYPO3_DB']->DELETEquery($dbTable, $dbWhere)));

		//remove object itself
 		$obj->destroy();
 	}

	function dbSelectById($uid, &$obj) {

 		$dbFields = '*';
 		$dbTable = $this->dbTable;
 		$dbWhere = '(uid="'.$uid.'")';
 		$dbWhere .= 'AND (deleted="0")';
 		
		//execute query
//		debug(array('dbSelectById' => $GLOBALS['TYPO3_DB']->SELECTquery($dbFields, $dbTable, $dbWhere)),get_class($this));
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($dbFields, $dbTable, $dbWhere);

        //insert into object
		$model=$GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res);
		if($model) {
			//parse into object
			$this->parser->parseModelToObject($model,$obj);
		} else {
			//no object found, empty obj and id
			$obj->clear();
		}

		//free results
		$GLOBALS["TYPO3_DB"]->sql_free_result($res);

	}


 	//---------- private parse functions -------------

	function addError($error) {
		$this->error[] = $error;
		debug($error,'error');
		debug($this);
	}
	
	function isError() {
		if(empty($this->error)) return false;
		else return true;
	}
	
	function getError() {
		if(empty($this->error)) return false;
		else return	$this->error;	
	}
	 	
 };
 
 // Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']['ext/commerce/dao/class.basic_dao_mapper.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']['ext/commerce/dao/class.basic_dao_mapper.php']);
}
 
?>