<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2005 - 2006 Ingo Schmitt <is@marketing-factory.de>
*  All   rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Abstract Libary for rendering the forntend output of tx_commerce objects. 
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private, do not use this class 
 * directly, always use inherited classes.
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_element_alib
 * 
 * $Id$
 */
 /**
  * @todo always instatiate comm_db
  * 
  */
  
 /**
  * Contstands definition for Attribute correlation_types
  * Add new contants to array in alib class
  */ 
 
 /**
  * @var ATTRIB_selector constant =1 
  * @see sql tx_commerce_attribute_correlationtypes
  */ 
 define ('ATTRIB_selector',1 );
  /**
  * @var ATTRIB_shal constant =2 
  * @see sql tx_commerce_attribute_correlationtypes
  */ 
 define ('ATTRIB_shal',2 );
  /**
  * @var ATTRIB_can constant =3 
  * @see sql tx_commerce_attribute_correlationtypes
  */ 
 define ('ATTRIB_can',3 );
 /**
  * @var ATTRIB_product constant =4 
  * @see sql tx_commerce_attribute_correlationtypes
  */ 
 define ('ATTRIB_product',4 );
 /**
 * Basic abtract Class for element
 * tx_commerce_product
 * tx_commerce_article
 * tx_commerce_category
 * tx_commerce_attribute
 * 
 * 
 *
 * @author		Ingo Schmitt <is@marketing-factory.de>

 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_element_alib
 */
 
 /**
  *  @since 21.09.05
  *  new add field_to_fieldlist
  *  new add fields_to_fieldlist
  * 
  */
 class tx_commerce_element_alib{

	/**
  	 * @var integer uid of product
  	 * @access private
  	 */
	var  $uid;	
	
	
	/**
	 * @var integer language uid
	 */
	var  $lang_uid;
	
	/**
	 * @var integer language uid
	 */
	var  $l18n_parent;
	
	/**
	 * @var Database class for inhertitation
	 */
	var $database_class='';
	
	/**
	 * @var fieldlist for inhertitation
	 */
	var $fieldlist=array('title','lang_uid','l18n_parent');
	
	/**
	 * Changes hier must be made, if a new correewlation_type is invented
	 * @var array of possible attribute correlation_types
	 */
	var $correlation_types=array(ATTRIB_selector,ATTRIB_shal,ATTRIB_can,ATTRIB_product );
	
	/**
	 * @var default_add_where for deleted hidden and more
	 */
	var $default_add_where= ' AND hidden = 0 AND deleted = 0';
	
	
	/**
	 * @var array of attribute UIDs
	 * @acces private
	 */
	
	var $attributes_uids = array();
	
	/**
	 * @var array of attributes
	 * @access private
	 */
	 var $attribute = array();
	/**
	 * @var Database Object, should be instantiated with the constructer each
	 * inherited class
	 * @access private
	 * 
	 */
	
	var $conn_db = '';
	
	/**
	 * @Var Translation Mode for getRecordOverlay
	 * @see class.t3lib_page.php
	 * @acces private
	 */
	
	var $translationMode='hideNonTranslated';

	 /**
	  * @return 	boolean	if a record is translaed
	  * @acces private
	  */
	 
	var $recordTranslated=false;


	/**
	 * 
	 * @return return language id
	 * @access public
	 */
	function get_lang(){
		return $this->lang_uid;
	}
	
	/**
	 * 
	 * @return return l18n_partent uid
	 * @access public
	 */
	function getL18nParent(){
		return $this->l18n_parent;
	}
	
	
	

	/**
	 * Loads the Data from the database
	 * via the named database class $database_class
	 * 
	 */
	
	function load_data(){	
		if ($this->conn_db){
			$data=$this->conn_db->get_data($this->uid,$this->lang_uid);	
		}else{
    			
			$this->conn_db = new $this->database_class();
			$data=$this->conn_db->get_data($this->uid,$this->lang_uid);
		}
		if (!$data){
			$this->recordTranslated=false;
			return false;
			
		}else{
			$this->recordTranslated=true;
		}
	
		// seems to be useless
		#$this->recordTranslated=true;
		
		// Load data and walk true assoc field list
	
		foreach ($this->fieldlist as $field){
			$this->$field=$data[$field];	
		}
		return $data;
	}
 	
 	/**
 	 * Addes a field to Class fieldlist
 	 * used for hook to add own fields to output
 	 * @param $fieldname Databas fieldname
 	 * @todo Add Check if field exists in Database
 	 */
 	
 	function add_field_to_fieldlist($fieldname){
 		array_push($this->fieldlist, trim($fieldname));
 	}
 	/**
 	 * Addes a fields to Class fieldlist
 	 * used for hook to add own fields to output
 	 * Basically calls $this->add_fiel_to_fieldlist for each element
 	 * @param $fieldlistr arary of databse filednames
 	 * @todo Add Check if field exists in Database
 	 */
 	
 	function add_fields_to_fieldlist($fieldarray){
 		foreach ($fieldarray as $newfield){
 			$this->add_field_to_fieldlist($newfield);
 		}
 		
 	}
 	
	/**
	 * 
	 * @return return uid, use e.g. with pi-link-functions
	 * @depricated 
	 * @access public
	 */
	 
	 
	function getUid(){
		return $this->uid;
	}



 	
 	/**
 	 * @deprecated version - 08.11.2005 
 	 * Returns  the data of this object als array
  	 * @param prefix Prefix for the keys or returnung array optional
  	 * @return array Assoc Arry of data
  	 * @acces public
  	 * @since 2005 11 08 depricated
  	 * 
  	 */
  	function return_assoc_array($prefix=''){
  		return $this->returnAssocArray($prefix);
  	}
  	
  	/**
  	 * Returns the data of this object als array
  	 * @param prefix Prefix for the keys or returnung array optional
  	 * @return array Assoc Arry of data
  	 * @acces public
  	 * @since 2005 11 08
  	 * 
  	 */
  	function returnAssocArray($prefix=''){
  		$data=array();
  		foreach ($this->fieldlist as $field){
				$data[$prefix.$field]=$this->$field;
		}
		return $data;
  	}
	
  	/**
  	 * @since 2005 11 08
  	 * depricated, use tx_commerce_pibase->renderrow in combinintion with
  	 * $this->return_assoc_array
  	 * 
  	 *     Renders    values from fieldlist to markers
  	 * 
  	 * @param &$cobj refference to cobj class
  	 * @param $conf configuration for this viewmode to render cObj
  	 * @param prefix optinonal prefix for marker
	 * @author Volker Graubaum <vg@e-netconsulting.de>
  	 * @return html-code
  	 * @todo fill in code
  	 */
  	
 	function getMarkerArray(&$cobj,$conf,$prefix=''){
 		$output='';
 		$markContentArray=$this->return_assoc_array('');
 		foreach ($markContentArray as $k => $v){
			switch(strtoupper($conf[$k])) {
				case 'IMGTEXT' :
				case 'IMAGE' :
						$i = 1;
						$imgArray = explode(';',$v);
						foreach($imgArray as $img){
							$conf[$k.'.'][$i.'.'] = $conf[$k.'.']['defaultImgConf.'];
							$conf[$k.'.'][$i.'.']['file'] = $conf['imageFolder'].$img;
							$vr = $cobj->IMAGE($conf[$k.'.'][$i.'.']);

						}
				break;
				case 'STDWRAP' :
					    $vr = $cobj->stdWrap($v,$conf[$k.'.']);
				break;
				default : $vr = $v;
				break;			
			}
			$markerArray['###'.strtoupper($prefix.$k).'###'] = $vr;
		}
		return $markerArray; 	
 		
 	}
 	
	/**
 	 * Checks if the UID is valid and availiable in the database
 	 * @return boolen true if uid is valid
 	 * @todo revise access-check
 	 */
 	
 	function isValidUid(){
 		if ($this->conn_db){
 			return $this->conn_db->isUid($this->uid);
 		}else{
			$this->conn_db = new $this->database_class();
 			return $this->conn_db->isUid($this->uid);
 		}
 	}
	

	/**
 	 * Checks if the UID is valid and availiable in the database
 	 * @return boolen true if uid is valid
 	 * @todo revise access-check
	 * @deprecated
 	 */
 	
 	function is_valid_uid()	{
 		
 		if ($this->conn_db)	{
 			return $this->conn_db->isUid($this->uid);
 		} else {
			$this->conn_db = new $this->database_class();
 			return $this->conn_db->isUid($this->uid);
 		} 			
 	}

 	
 	/**
 	 * Checks in the Database if object is 
 	 * basically checks against the enableFields
 	 * @see: class.tx_commerce_db_alib.php ->isAccessible(

 	 * @return 	true	if is accessible
 	 * 			false	if is not accessible
 	 * @author	Ingo Schmitt	<is@marketing-factory.de>
 	 */
 	
 	function isAccessible(){
 		if ($this->conn_db){
 			return $this->conn_db->isAccessible($this->uid);
 		}
 		else{
			$this->conn_db = new $this->database_class();
 			return $this->conn_db->isAccessible($this->uid);
 		}
 		
 	}
 	
 	/**
 	 * Sets the PageTitle titile from via the TSFE
 	 * @param field (default title) for setting as title
 	 * @author Volker Graubaum <vg@e-netconsulting.de>
 	 */
	
	function setPageTitle($field='title'){
	     
	     $GLOBALS['TSFE']->page['title'] = $this->$field. ' : '.  $GLOBALS['TSFE']->page['title']; 
             // set pagetitle for indexed search also
	     $GLOBALS['TSFE']->indexedDocTitle = $this->$field. ' : '.  $GLOBALS['TSFE']->indexedDocTitle;
	}
	
 	/** 
  	 * 
  	 * returns the possible attributes
  	 * @param array of attribut_correlation_types
  	 * @return array
  	 */		
  	function get_attributes($attribute_corelation_type_list=''){
  		if ($this->attributes_uids=$this->conn_db->get_attributes($this->uid,$attribute_corelation_type_list)){
  			foreach ($this->attributes_uids as $attribute_uid){
  				// initialise Array of articles 
  				$this->attribute[$attribute_uid] = t3lib_div::makeInstance('tx_commerce_attribute');
  				$this->attribute[$attribute_uid] ->init($attribute_uid,$this->lang_uid);
  				$this->attribute[$attribute_uid]->load_data();
  			}
  			return $this->attributes_uids;
  		}
  	}
  	
  	/** 
  	 * set a given field, only to use with custom field without own method 
  	 * 
  	 * Warning: commerce provides getMethods for all default fields. For Compatibility
  	 * reasons always use the built in Methods. Only use this method with you own added fields 
  	 * @see add_fields_to_fieldlist
  	 * @see add_field_to_fieldlist
  	 * 
  	 * @param string	$field: fieldname
  	 * @param mixed	$value: value
  	 * @return void
  	 */	
  	
  	function setField($field,$value){
		$this->$field = $value;
	}

  	/** 
  	 * get a given field value, only to use with custom field without own method 
  	 * 
  	 * Warning: commerce provides getMethods for all default fields. For Compatibility
  	 * reasons always use the built in Methods. Only use this method with you own added fields 
  	 * @see add_fields_to_fieldlist
  	 * @see add_field_to_fieldlist
  	 * 
  	 * @param string	$field: fieldname
  	 * @return mixed	value of the field
  	 */	

	function getField($field){
		return $this->$field;
	}
  	

	/**
	 * 
	 * @return return uid, use e.g. with pi-link-functions
	 * @deprecated 
	 * @access public
	 */
	 
	 
	function get_uid(){
		return $this->getUid();
	}


 	
 }

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_element_alib.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_element_alib.php']);
}
 ?>