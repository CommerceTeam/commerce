<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Carsten Lausen
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
* class basic dao parser
* This class is used by the dao to parse objects to database model objects (transfer objects) and vice versa.
* All knowledge about the database model is in this class.
*
* Extend this class to fit specific needs.
*
*
*
* @access public
* @package TYPO3
* @subpackage commerce
* @author Carsten Lausen <cl@e-netconsulting.de>
*/

 class basic_dao_parser {



 	//------------- constructor ----------------

 	function basic_dao_parser() {
 	}


 	//---------- public functions --------------

	function &parseObjectToModel($obj) {

		//parse attribs
 		//$attribList = array_keys(get_class_vars(get_class($obj)));
 		$attribList = array_keys(get_object_vars($obj));
 		foreach($attribList as $attrib) {
 			if($attrib!='id') {
				if(method_exists($obj, 'get'.ucfirst($attrib))) {
					$model[$attrib]=call_user_func(array(&$obj,'get'.ucfirst($attrib)),null);
				} else {
					$model[$attrib]=$obj->$attrib;
				}
 			}
 		}


 		//remove any uid
 		unset ($model['uid']);

		return $model;

	}

	function parseModelToObject($model,&$obj) {

		//parse attribs
 		//$attribList = array_keys(get_class_vars(get_class($obj)));
 		$attribList = array_keys(get_object_vars($obj));
 		foreach($attribList as $attrib) {
 			if($attrib!='id') {
				if (array_key_exists($attrib,$model)) {
					if(method_exists($obj, 'set'.ucfirst($attrib))) {
						call_user_func(array(&$obj,'set'.ucfirst($attrib)),$model[$attrib]);
					} else {
						$obj->$attrib = $model[$attrib];
					}
				}
 			}
 		}

//		debug($attribList,'$attribList');
//		debug($obj,'$obj');

	}

	function setPid(&$model,$pid) {

 		$model['pid']=$pid;

//		debug($model,'$model');

	}

 }
 // Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.basic_dao_parser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.basic_dao_parser.php']);
}

?>