<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Volker Graubaum  (vg_typo3@e-netconsulting.de)
*  All rights reserved
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
 *	Menulibary for having a navigation menu as a normal userfunction based on
 *  categories (and products) of commerce
 *	Thanks to Daniel Thomas, who build a class for his mediadb, which was
 *	the basic for this class 
 *
 * @author	 Volker Graubaum <vg_typo3@e-netconsulting.de>  
 * @coauthor Ingo Schmitt 	<is@marketing-factory.de>
 * @coauthor Ricardo Mieres	<ricardo.mieres@502.cl>
 * @TODO: Clean Up code, documentation
 * 
 * $Id: class.tx_commerce_navigation.php 553 2007-02-05 20:05:09Z ingo $
 */

/**
 * @TODO: Buld Method to build UP Add Get Vars parameter to 
 * Have a sentral Methgod to build chahs parameters
 * @TODO: Replace & by the php configured seperator
 */

if(!class_exists('tx_graytree_db')) {
	$TYPO3_CONF_VARS = $GLOBALS['TYPO3_CONF_VARS'];
	require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_db.php');
}
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_browsetrees.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_category.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_div.php');


class user_tx_commerce_catmenu_pub extends tx_commerce_navigation {



}



class tx_commerce_navigation {

	var $prefixId = 'tx_commerce_pi1';
	var $activeCats = array();
	var $mConf;
	var $cat;
	var $tree;
	var $mTree;
	var $out;
	var $mDepth = 2;
	var $entryCat = 0;
    var $listNodes=array();
    /**
     * @var	integer	[0-1]	
     * @access private
     */
    var $useRootlineInformationToUrl = 0;
    
    /**
     * @var 	pathParentes	Array
     * Array holding the parentes of this cat as uid list
     */
    var $pathParents = array();
    
    
	/**
	 * Init Method for initialising the navigation
	 * @param $content	string	$content passed to method
	 * @param $conf	Array	TS Array
	 * @return array	array for the menurendering of TYPO3
	 *
	 */
	function init($content,$conf) {
		
		$this->mConf = $this->processConf($conf);
		if ($this->mConf['useRootlineInformationToUrl']) {
			$this->useRootlineInformationToUrl = $this->mConf['useRootlineInformationToUrl'];
		}
		
		
		
		$this->PID = $this->mConf['overridePid'] ? $this->mConf['overridePid'] : $GLOBALS['TSFE']->id;
		$this->gpVars = t3lib_div::_GP('tx_commerce_pi1');
		
		tx_commerce_div::initializeFeUserBasket();
		
		
		$this->gpVars['basketHashValue'] =  $GLOBALS['TSFE']->fe_user->tx_commerce_basket->getBasketHashValue();
		$this->pageRootline = $GLOBALS['TSFE']->rootLine;
		$this->menuType = $this->mConf['1'];
		$this->entryLevel = $this->mConf['entryLevel'];
		
		/**
		 * Detect if a user is logged in and if he or she has usergroups
		 * as we have to take in accout, taht different usergroups may have different
		 * rights on the commerce tree, so consider this whe calculation the cache hash.
		 */
		$usergroups = '';
		if (is_array($GLOBALS['TSFE']->fe_user->user)) {
			$usergroups = $GLOBALS['TSFE']->fe_user->user['usergroup'];
		}
		
		
	    $this->cat =$this->getRootCategory();

		$this->ShowUid = $this->gpVars['showUid'] ? $this->gpVars['showUid'] : 0;
		$this->mDepth = $this->gpVars['mDepth'] ? $this->gpVars['mDepth'] : 0;
		$this->PATH = $this->gpVars['path'] ? $this->gpVars['path'] : 0;
        $this->expandAll =$this->mConf['expandAll'] ? $this->mConf['expandAll'] : 0;
      
      	if (!($this->cat>0 )){
      		$MenueErrorname []= 'No category defined in TypoScript: lib.tx_commerce.navigation.special.category';
      	}
      	if (!($this->PID>0)) {
      		$MenueErrorname []= 'No OveridePID defined in TypoScript: lib.tx_commerce.navigation.special.overridePid';
      	}
      	if (count($MenueErrorname)>0) {	
      		
      		foreach ($MenueErrorname as $oneEoor) {
      			t3lib_div::debug($this->mConf,$oneEoor);
      		}
      		
        	return $this->makeErrorMenu(5);
      	}
		/**
		 * Unique Hash for this usergroup and page to display the navigation
		 */
        $hash = md5('tx_commerce_navigation'.$this->cat.'-'.$this->PID.':'.$usergroups);
        $cachedMatrix = $this->getHash($hash,0);
       
        if ($GLOBALS['TSFE']->no_cache==1) {
        	
        	// Build directly and don't sore, if no_cache=1'
        	
        	$this->mTree=$this->makeArrayPostRender($this->PID,"tx_commerce_categories","tx_commerce_categories_parent_category_mm","tx_commerce_products","tx_commerce_products_categories_mm",$this->cat,1);
	    }elseif (isset($cachedMatrix))  	{
	    	
	    	// User the cached version
			$this->mTree = unserialize($cachedMatrix);
		} else {
			
			// no cache present buld data and stor it in cache
			$this->mTree=$this->makeArrayPostRender($this->PID,"tx_commerce_categories","tx_commerce_categories_parent_category_mm","tx_commerce_products","tx_commerce_products_categories_mm",$this->cat,1);
			$this->storeHash($hash,serialize($this->mTree),'COMMERCE_MENU_NAV'.$this->cat);
		}
		
		
		$keys=array_keys($this->mTree);
		
		
		
		$this->choosenCat = $this->gpVars['catUid'] ;
		
	    if ($this->gpVars['path']) {
        	$this->PATH = $this->gpVars['path'];
        	$this->pathParents=split(",",$this->PATH);
 	    } elseif((is_numeric($this->choosenCat)) && ($this->choosenCat>0)) {
        	/**
        	 * Bulild the path by or own
        	 */
        	$myCat = t3lib_div::makeInstance('tx_commerce_category');
        	$myCat ->init($this->choosenCat);
        	$myCat ->load_data();
        	$tmpArray=$myCat->get_categorie_rootline_uidlist();
        	/**
        	 * Strip the Staring point and the value 0
        	 */
        	foreach ($tmpArray as $value) {
        		if (($value <> $this->cat) && ($value > 0)) {
        			$this->pathParents[]=$value;
        		}
        		
        	}
        	if ($this->mConf['groupOptions.']['onOptions']==1 && $GLOBALS['TSFE']->fe_user->user['usergroup']!=''){
        		$this->fixPathParents($this->pathParents,$keys[0]);	
        	}
        	
     		$this->pathParents=array_reverse($this->pathParents);
            //debug($this->pathParents);
        	if (!$this->gpVars['mDepth']) {
        		$this->mDepth = count($this->pathParents);
        	}
        	
        }else{
        	/**
        	 * If no Category is choosen by the user, so you just render the default menue
        	 * no rootline for the categories is needed and the depth is 0
        	 */
        	$this->pathParents = array();
        	$this->mDepth =0;
        }
		
		
		if($this->pathParents){
			$this->processArrayPostRender($this->mTree,$this->pathParents,$this->mDepth);
			
		}
		#t3lib_div::debug($this->pathParents, 'init::pathParents');
		#t3lib_div::debug($this->mTree, 'init::mTree');
		return  $this->mTree;
	}
	function fixPathParents(&$pathArray,$chosenCatUid){
		if ($pathArray==null){
				return;
		}
		if ($pathArray[0]==$chosenCatUid){
				return;
		}
		else{
			array_shift($pathArray);
			$this->fixPathParents($pathArray,$chosenCatUid);
		}
	}
	function getRootCategory(){
		if ($this->mConf['groupOptions.']['onOptions']==1){
			$catOptionsCount=count($this->mConf['groupOptions.']);	
			$chosenCatUid=array();
			for($i=1; $i<=$catOptionsCount;$i++){
				$chosenGroups=split(',',$this->mConf['groupOptions.'][$i.'.']['group']);
				if ($GLOBALS['TSFE']->fe_user->user['usergroup']==''){
					
					return $this->mConf['category'];
				}
				$fe_groups=split(',',$GLOBALS['TSFE']->fe_user->user['usergroup']);
					
				foreach($chosenGroups as $group){
					if (in_array($group,$fe_groups)===true){	
				    	if (in_array($this->mConf['groupOptions.'][$i.'.']['catUid'],$chosenCatUid)===false)
				    		array_push($chosenCatUid,$this->mConf['groupOptions.'][$i.'.']['catUid']);
					}
				}
			}
			if (count($chosenCatUid)==1)// ^^ vielleicht gibt es mehr als eine ausgewählete Kategorie.
				return $chosenCatUid[0];
			elseif(count($chosenCatUid)>1)
				return $chosenCatUid[0];
			else 
				return $this->mConf['category'];
		}
		else
		 return $this->mConf['category'];
	}
	function makeErrorMenu($max=5,$mDepth=1){
	 	$treeList=array();
		for($i=0;$i<$max;$i++){
		     	 $nodeArray['pid'] = $this->PID;
				 $nodeArray['uid'] =$i;
				 $nodeArray['title'] = "Error in the typoScript configuration.";
				 $nodeArray['parent_id'] = $i;
				 $nodeArray['nav_title'] = "Error in the typoScript configuration.";
				 $nodeArray['hidden'] = 0;
				 $nodeArray['depth'] = $mDepth;
				 $nodeArray['leaf'] = 1;
			   	 $treeList[$i]=$nodeArray;
				 }
		
		return $treeList;

	}
	/**
	 * Sets the clear Function for each MenuItem
	 *
	 * @param	array	$conf: TSconfig to parse
	 * @return	array	TSConfig with ItemArrayProcFunc
	 */
	function processConf($conf) {
		$i = 1;
		while(list($k,) = each($conf)) {
			if($k == $i.'.') {
				$conf[$i.'.']['itemArrayProcFunc'] = 'user_tx_commerce_catmenu_pub->clear';
				$i++;
			}
		}
		$this->mDepth = $i++;
		return $conf;
	}
	/**
	 * Makes the post array,which  the typo3 render Function will be work
	 * @author Ricardo Mieres <ricardo@mieres.cl>
	 * @param	array	$table: main table
	 * @param	array	$table_mm: mm table
	 * @param	array	$uid_root:
	 * @return	array	TSConfig with ItemArrayProcFunc
	 */
	function makeArrayPostRender($uidPage,$mainTable, $tableMm,$tableSubMain,$tableSubMm,$uid_root,$mDepth=1,$path=0) {
		$treeList=array();
		$addWhere=$tableMm.'uid_foreign='.$uid_root;
		$sql = 'SELECT '.$tableMm.'.* FROM '.$tableMm.','.$mainTable.' WHERE '.$mainTable.'.deleted =0 and '.$mainTable.'.uid = '.$tableMm.'.uid_local and '.$tableMm.'.uid_local<>"" AND '.$tableMm.'.uid_foreign ='.$uid_root;
		
		$sorting = ' order by '.$mainTable.'.sorting ';
		
		/**
		 * Add some hooks for custom sorting
		 */
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_navigation.php']['sortingOrder']) {
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_navigation.php']['sortingOrder']);
		}
		if (method_exists($hookObj, 'sortingOrder')) {
			$sorting = $hookObj->sortingOrder($sorting,$uid_root,$mainTable, $tableMm,$mDepth,$path,$this);
		}
		
		
		$sql.= $sorting;
		
		#$sql.= 'order by title';
		$res=$GLOBALS['TYPO3_DB']->sql_query($sql);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
			$nodeArray = array();
			$dataRow = $this->getDataRow($row['uid_local'],$mainTable);

			if ($dataRow['deleted']=='0'){
			 	 $nodeArray['pid'] = $dataRow['pid'];
				 $nodeArray['uid'] = $uidPage;
				 $nodeArray['title'] = $dataRow['title'];
				 $nodeArray['parent_id'] = $uid_root;
				 $nodeArray['nav_title'] = $dataRow['navtitle'];
				 $nodeArray['hidden'] = $dataRow['hidden'];
				 $nodeArray['depth'] = $mDepth;
				 $nodeArray['leaf'] = $this->isLeaf($row['uid_local'],$tableMm,$tableSubMm);
				 $nodeArray['hasSubChild'] = $this->hasSubChild($row['uid_local'],$tableSubMm);
				 $nodeArray['subChildTable'] = $tableSubMm;
				 $nodeArray['tableSubMain'] = $tableSubMain;
				 if ($path!=0) {
					 $nodeArray['path']=$dataRow['uid'].','.$path;
				 }else{				 
				 		 $nodeArray['path']=$dataRow['uid'];
				 }
				 if (!$nodeArray['leaf'] ){
								
				 	$nodeArray['--subLevel--']= $this->makeArrayPostRender($uidPage,$mainTable, $tableMm,$tableSubMain,$tableSubMm,$row['uid_local'],$mDepth+1,$nodeArray['path']);
				    
				     
				    if($nodeArray['hasSubChild']==1 && $this->mConf['showProducts']==1){
				    	
				    	
				    	
				   
				    	$arraySubChild=array();
				    	
				    	$arraySubChild=$this->makeSubChildArrayPostRender($uidPage,$tableSubMain,$tableSubMm,$row['uid_local'],$mDepth+1,$nodeArray['path']);
				    	
				    	$this->arrayMerge($nodeArray['--subLevel--'], $arraySubChild);
				    	
				    	if ($this->mConf['groupOptions.']['onOptions']==1 && $GLOBALS['TSFE']->fe_user->user['usergroup']!=''){
				    		$arraySubChild=$this->makeSubChildArrayPostRender($uidPage,$tableSubMain,$tableSubMm,$uid_root,$mDepth+1,$nodeArray['path']);
				    		$this->arrayMerge($nodeArray['--subLevel--'], $arraySubChild);
				    	}
				    	
				    }
				    if($this->expandAll){
				    	$nodeArray['_SUB_MENU']=$nodeArray['--subLevel--'];
				    }
				 	$nodeArray['_ADD_GETVARS'].= '&'.$this->prefixId.'[catUid]='.$row['uid_local'];
				 	if ($this->useRootlineInformationToUrl==1) {
				 		$nodeArray['_ADD_GETVARS'] .= ini_get('arg_separator.output') .$this->prefixId.'[mDepth]='.$mDepth.ini_get('arg_separator.output') .$this->prefixId.'[path]='.$nodeArray['path'];
				 	}
				 	if ($this->gpVars['basketHashValue']) {
						$nodeArray['_ADD_GETVARS'] .=ini_get('arg_separator.output') .$this->prefixId.'[basketHashValue]='.$this->gpVars['basketHashValue'];
					}
					$pA = t3lib_div::cHashParams($nodeArray['_ADD_GETVARS'].$GLOBALS['TSFE']->linkVars);
					$nodeArray['_ADD_GETVARS'] .= ini_get('arg_separator.output') .'cHash='.t3lib_div::shortMD5(serialize($pA));
				 	$nodeArray['ITEM_STATE'] = 'NO';
				 }
				 else{
				 	if ($nodeArray['leaf']==1 &&  $nodeArray['hasSubChild']==2)
				 	  $nodeArray['_ADD_GETVARS'] = ini_get('arg_separator.output') .$this->prefixId.'[catUid]='.$uid_root;
				 	else
				 	$nodeArray['_ADD_GETVARS'] = ini_get('arg_separator.output') .$this->prefixId.'[catUid]='.$row['uid_local'];
				 	
				 	if($nodeArray['hasSubChild']==2){
				    	$nodeArray['_ADD_GETVARS'].=ini_get('arg_separator.output') .$this->prefixId.'[showUid]='.$dataRow[uid];
				    	$nodeArray['_ADD_GETVARS'].= ini_get('arg_separator.output') .$this->prefixId.'[mDepth]='.$mDepth.ini_get('arg_separator.output') .$this->prefixId.'[path]='.$nodeArray['path'];
				    }
				 	if ($this->useRootlineInformationToUrl==1) {
				 		$nodeArray['_ADD_GETVARS'] .= ini_get('arg_separator.output') .$this->prefixId.'[mDepth]='.$mDepth.ini_get('arg_separator.output') .$this->prefixId.'[path]='.$nodeArray['path'];
				 	}
				 	if ($this->gpVars['basketHashValue']) {
						$nodeArray['_ADD_GETVARS'] .=ini_get('arg_separator.output') .$this->prefixId.'[basketHashValue]='.$this->gpVars['basketHashValue'];
					}
					$pA = t3lib_div::cHashParams($nodeArray['_ADD_GETVARS'].$GLOBALS['TSFE']->linkVars);
					$nodeArray['_ADD_GETVARS'] .= ini_get('arg_separator.output') .'cHash='.t3lib_div::shortMD5(serialize($pA));
					$nodeArray['ITEM_STATE'] = 'NO';
				 }
				 
				$treeList[$row['uid_local']]=$nodeArray; 
			}
		}
		if ($treeList==null && $this->mConf['showProducts']==1){
			$treeList=$this->makeSubChildArrayPostRender($uidPage,$tableSubMain,$tableSubMm,$uid_root,$mDepth,$path);
		}
		return $treeList;
	}
	/**
	 * Makes the prodcutlist for the menu
	 * @author Ricardo Mieres <ricardo.mieres@502.cl>
	 * @param	array	$mainTable: main table
	 * @param	array	$table_mm: mm table
	 * @param	array	$uid_root: category Uid
	 * @param	array	$mDepth:
	 * @param	array	$path: 
	 * @return	array	TSConfig with ItemArrayProcFunc
	 * 
	 */
	function makeSubChildArrayPostRender($uidPage,$mainTable, $tableMm,$uid_root,$mDepth=1,$path=0) {
		$treeList=array();
		$addWhere=$tableMm.'uid_foreign='.$uid_root;
		$sql = 'SELECT '.$tableMm.'.* FROM '.$tableMm.','.$mainTable.' WHERE '.$mainTable.'.deleted =0 and '.$mainTable.'.uid = '.$tableMm.'.uid_local and '.$tableMm.'.uid_local<>"" AND '.$tableMm.'.uid_foreign ='.$uid_root;
		
		$sorting = ' order by '.$mainTable.'.sorting ';
		
		/**
		 * Add some hooks for custom sorting
		 */
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_navigation.php']['sortingOrder']) {
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_navigation.php']['sortingOrder']);
		}
		if (method_exists($hookObj, 'sortingOrder')) {
			$sorting = $hookObj->sortingOrder($sorting,$uid_root,$mainTable, $tableMm,$mDepth,$path,$this);
		}
		
		
		$sql.= $sorting;
		
		#$sql.= 'order by title';
		$res=$GLOBALS['TYPO3_DB']->sql_query($sql);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
			$nodeArray = array();
			$dataRow = $this->getDataRow($row['uid_local'],$mainTable);
			if ($dataRow['deleted']=='0'){
			 	$nodeArray['pid'] = $dataRow['pid'];
				$nodeArray['uid'] = $uidPage;
				$nodeArray['title'] = $dataRow['title'];
				$nodeArray['parent_id'] = $uid_root;
				$nodeArray['nav_title'] = $dataRow['navtitle'];
				$nodeArray['hidden'] = $dataRow['hidden'];
				$nodeArray['depth'] = $mDepth;
				$nodeArray['leaf'] = 1;
				if ($path!=0) {
					$nodeArray['path']=$dataRow['uid'].','.$path;
				}else{				 
					$nodeArray['path']=$dataRow['uid'];
				}
				if ($nodeArray['leaf']==1 &&  $nodeArray['hasSubChild']==2)
					$nodeArray['_ADD_GETVARS'] = ini_get('arg_separator.output') .$this->prefixId.'[catUid]='.$uid_root;
			 	else
				 	$nodeArray['_ADD_GETVARS'] = ini_get('arg_separator.output') .$this->prefixId.'[catUid]='.$row['uid_local'];
				 	
		    	$nodeArray['_ADD_GETVARS'].=ini_get('arg_separator.output') .$this->prefixId.'[showUid]='.$dataRow[uid];
				$nodeArray['_ADD_GETVARS'].= ini_get('arg_separator.output') .$this->prefixId.'[mDepth]='.$mDepth.ini_get('arg_separator.output') .$this->prefixId.'[path]='.$nodeArray['path'];
			   
			 	if ($this->useRootlineInformationToUrl==1) 
				 		$nodeArray['_ADD_GETVARS'] .= ini_get('arg_separator.output') .$this->prefixId.'[mDepth]='.$mDepth.ini_get('arg_separator.output') .$this->prefixId.'[path]='.$nodeArray['path'];

			 	if ($this->gpVars['basketHashValue']) 
						$nodeArray['_ADD_GETVARS'] .=ini_get('arg_separator.output') .$this->prefixId.'[basketHashValue]='.$this->gpVars['basketHashValue'];
			 	
				$pA = t3lib_div::cHashParams($nodeArray['_ADD_GETVARS'].$GLOBALS['TSFE']->linkVars);
				$nodeArray['_ADD_GETVARS'] .= ini_get('arg_separator.output') .'cHash='.t3lib_div::shortMD5(serialize($pA));
				$nodeArray['ITEM_STATE'] = 'NO';
				$treeList[$row['uid_local']]=$nodeArray; 
			}
		}
		return $treeList;
	}
	function processArrayPostRender(&$treeArray,$path=array(),$mDepth){
		
		if ($mDepth!=0){
			if ($mDepth==1){
				
				$treeArray[$path[0]]['ITEM_STATE'] = 'ACT';
					if ($path[0] == $this->choosenCat) {
						$treeArray[$path[0]]['ITEM_STATE'] = 'CUR';
					}
					if($this->ShowUid==$path[0]){
						$treeArray[$path[0]]['ITEM_STATE'] = 'CUR';
					}
					if (count($treeArray[$path[0]]['--subLevel--'])>0) { 
						$treeArray[$path[0]]['_SUB_MENU']=$treeArray[$path[0]]['--subLevel--'];
					}
				return 0;
			}else{
				if(is_array($path)){
					if(is_array($treeArray)){
						$nodeId=array_pop($path);
						$treeArray[$nodeId]['ITEM_STATE'] = 'ACT';
						if ($nodeId == $this->choosenCat) {
							$treeArray[$nodeId]['ITEM_STATE'] = 'CUR';
						}
						if($this->ShowUid==$treeArray[$nodeId]['parent_id']){
							$treeArray[$nodeId]['ITEM_STATE'] = 'CUR';
						}
						$this->processArrayPostRender($treeArray[$nodeId]['--subLevel--'],$path,$mDepth-1);
						if (count($treeArray[$nodeId]['--subLevel--'])>0){
							$treeArray[$nodeId]['_SUB_MENU']=$treeArray[$nodeId]['--subLevel--'];
							
						}
					}
				}
			}
		}
	}
	function getDataRow($uid,$tableName){
		if ($uid=="" or $tableName==""){
			return "";
		}
		$addWhere=$GLOBALS['TSFE']->sys_page->enableFields($tableName,$GLOBALS['TSFE']->showHiddenRecords);
		
		#$sql = $sql = 'SELECT * FROM `'.$tableName.'` WHERE `uid` = '.$uid.$addWhere.' LIMIT 0, 30 ';
		$sql = $sql = 'SELECT * FROM `'.$tableName.'` WHERE `uid` = '.$uid.$addWhere.' LIMIT 1 ';
		$res=$GLOBALS['TYPO3_DB']->sql_query ($sql);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
			return $row;
	}
	function isLeaf($uid,$tableMm,$subTableMM){
		if ($uid=="" or $tableMm==""){
			return 2;
		}
		#$sql = $sql = 'SELECT * FROM `'.$tableMm.'` WHERE `uid_foreign` = '.$uid.' LIMIT 0, 30 ';
		$sql = $sql = 'SELECT * FROM `'.$tableMm.'` WHERE `uid_foreign` = '.$uid.' LIMIT 1 ';
		
		$res=$GLOBALS['TYPO3_DB']->sql_query ($sql);
		$hasSubChild=$this->hasSubchild($uid,$subTableMM);
		if (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) or $hasSubChild==1)
			return 0;
		return 1;
		}

	function hasSubChild($uid,$tableMm){
		if ($uid=="" or $tableMm==""){
			return 2;
		}
		$sql = $sql = 'SELECT * FROM `'.$tableMm.'` WHERE `uid_foreign` = '.$uid.' LIMIT 1 ';
		
		$res=$GLOBALS['TYPO3_DB']->sql_query ($sql);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
			return 1;
		return 0;
		}
	/**
	 * Gets all active categories from the rootline to change the ItemState
	 *
	 * @return	array	array of all active Categories
	 */
	function getActiveCats() {
		$active = array('0' => $this->catObj->uid);
		$rootline = $this->catObj->get_categorie_rootline_uidlist();
		foreach($rootline as $cat) {
			$active[] = $cat;
		}
		return $active;
	}
	/**
	 * Function clears all subelements. This is needed for clear error with mix up pages and categories
	 *
	 * @param	array		$menuArr: Array with menu item
	 * @param	array		$conf: TSconfig, not used
	 * @return	array		return the cleaned menu item
	 */
	function clear($menuArr,$conf) {
		while(list(,$item) = each($menuArr)) {
			if($item['DO_NOT_RENDER'] == '1') {
				$menuArr = array();
			}
		}
		return $menuArr;
	}
	
	
	/**
	 * Method for gerenartin the rootlineMenue to use in TS
	 * @author Ingo Schmitt <is@marketing-factory.de>
	 * @param $content	string	$content passed to method
	 * @param $conf	Array	TS Array 
	 * @return array	array for the menurendering of TYPO3
	 */
	
	function CommerceRootline ($content,$conf) {
	
	
		$this->mConf = $this->processConf($conf);
		$this->PID =  intval($this->mConf['overridePid'] ? $this->mConf['overridePid'] : $GLOBALS['TSFE']->id);   
		$this->gpVars = t3lib_div::_GP('tx_commerce_pi1');
		
		tx_commerce_div::initializeFeUserBasket();
		
		$this->gpVars['basketHashValue'] =  $GLOBALS['TSFE']->fe_user->tx_commerce_basket->getBasketHashValue();
		
			
		$returnArray=array();
			
		$returnArray=$this->getCategoryRootlineforTS($this->gpVars['catUid'],$this->category);
		/**
		 * Add product to rootline, if a product is displayed and showProducts is set via TS
		 */
		
		if (($this->mConf['showProducts'] == 1) && ($this->gpVars['showUid']>0)) {
		#if (($this->gpVars['showUid']>0)) {
		
			$ProductObject = t3lib_div::makeInstance('tx_commerce_product');
			$ProductObject->init($this->gpVars['showUid'],$GLOBALS['TSFE']->sys_language_uid);
			$ProductObject->load_data();	
			
			$CategoryObject= t3lib_div::makeInstance('tx_commerce_category');
			$CategoryObject->init($this->gpVars['catUid'],$GLOBALS['TSFE']->sys_language_uid);
			$CategoryObject->load_data();
			
			$add_getvars=ini_get('arg_separator.output') .$this->prefixId.'[showUid]='.$ProductObject->getUid().ini_get('arg_separator.output') .$this->prefixId.'[catUid]='.$CategoryObject->getUid();
			if (is_string($this->gpVars['basketHashValue'])) {
				$add_getvars.=ini_get('arg_separator.output') .$this->prefixId.'[basketHashValue]='.$this->gpVars['basketHashValue'];
			}
  			$GP_Temp = t3lib_div::cHashParams($add_getvars.$GLOBALS['TSFE']->linkVars);
  			
  			/**
  			 * 	Currentyl no Navtitle in tx_commerce_products
  			 * 			'nav_title' => $ProductObject->get_navtitle(),
  			 */
  			
  			$returnArray[]=array(
						'title'=>$ProductObject->get_title(),
  			
						'uid'=>$this->PID,
						 '_ADD_GETVARS' => $add_getvars.ini_get('arg_separator.output') .'cHash='.t3lib_div::shortMD5(serialize($GP_Temp)),
						 'ITEM_STATE' => 'NO',
						
						
						);
			
		
		}
			
		return $returnArray;
	}
	
	
	/**
  	 * Returns an array of array for the TS rootline
  	 * Recursive Call to buld rootline
  	 * @author Ingo Schmitt <is@marketing-factory.de>
  	 * @since 21.07.2006
  	 */
  	function getCategoryRootlineforTS($catID,$result=array()) {
  		
  		if ($catID) {
	  		$CategoryObject= t3lib_div::makeInstance('tx_commerce_category');
			$CategoryObject->init($catID,$GLOBALS['TSFE']->sys_language_uid);
			$CategoryObject->load_data();
	  		if ($CategoryObject->parent_category_uid>0)
	  		{
	  			
	  			if ($CategoryObject->parent_category_uid <> $this->category ){
	  				$result=$this->getCategoryRootlineforTS($CategoryObject->parent_category_uid,$result=array());
	  			}
	  			
	  			
	  		}
	  		
	  		/**
	  		 * Only add if Rootline below $this->category
	  		 * 
	  		 */
	  		if ($CategoryObject->parent_category_uid <> $this->category ){
	  			   
	  			$add_getvars=ini_get('arg_separator.output') .$this->prefixId.'[catUid]='.$CategoryObject->getUid();

				if (is_string($this->gpVars['basketHashValue'])) {
					$add_getvars.=ini_get('arg_separator.output') .$this->prefixId.'[basketHashValue]='.$this->gpVars['basketHashValue'];
				}
	  			$GP_Temp = t3lib_div::cHashParams($add_getvars.$GLOBALS['TSFE']->linkVars);
	  			$result[]=array('title'=>$CategoryObject->get_title(),
	  						'nav_title' => $CategoryObject->get_navtitle(),
							'uid'=>$this->PID,
							 '_ADD_GETVARS' => $add_getvars.ini_get('arg_separator.output') .'cHash='.t3lib_div::shortMD5(serialize($GP_Temp)),
							 'ITEM_STATE' => 'NO',
							
							
							);
				
				 
					
	  		}
	  		return $result;
  		}
  		
  		
  	}
	
	
		/**
	 * Stores the string value $data in the 'cache_hash' table with the hash key, $hash, and visual/symbolic identification, $ident
	 * IDENTICAL to the function by same name found in t3lib_page:
	 * Usage: 2
	 *
	 * @param	string		32 bit hash string (eg. a md5 hash of a serialized array identifying the data being stored)
	 * @param	string		The data string. If you want to store an array, then just serialize it first.
	 * @param	string		$ident is just a textual identification in order to inform about the content! May be 20 characters long.
	 * @return	void
	 */
	function storeHash($hash,$data,$ident)	{
		$insertFields = array(
			'hash' => $hash,
			'content' => $data,
			'ident' => $ident,
			'tstamp' => time()
		);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_hash', 'hash='.$GLOBALS['TYPO3_DB']->fullQuoteStr($hash, 'cache_hash'));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_hash', $insertFields);
	}

	/**
	 * Retrieves the string content stored with hash key, $hash, in cache_hash
	 * IDENTICAL to the function by same name found in t3lib_page:
	 * Usage: 2
	 *
	 * @param	string		Hash key, 32 bytes hex
	 * @param	integer		$expTime represents the expire time in seconds. For instance a value of 3600 would allow cached content within the last hour, otherwise nothing is returned.
	 * @return	string
	 */
	function getHash($hash,$expTime=0)	{
			// if expTime is not set, the hash will never expire
		$expTime = intval($expTime);
		if ($expTime)	{
			$whereAdd = ' AND tstamp > '.(time()-$expTime);
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('content', 'cache_hash', 'hash='.$GLOBALS['TYPO3_DB']->fullQuoteStr($hash, 'cache_hash').$whereAdd);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $row['content'];
		}
	}
   function arrayMerge(&$arr1,&$arr2){
   	foreach ($arr2 as $key=>$value){
   		$arr1[$key]=$value;
   	}
   }
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_navigation.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_navigation.php"]);
}


?>