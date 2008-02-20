<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Ingo Schmitt <is@marketing-factory.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * ItemProc Methods for flexforms
 *
 * @package commerce
 * @author Ingo Schmitt <is@marketing-factory.de>
 * 
 * 
 */
 require_once (t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_folder_db.php');
 require_once (t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_create_folder.php');
 
 class tx_commerce_forms_select {
 	
 	function productsSelector(&$data,&$pObj){
 		
 		$numArticleNumbersShow=3;
 		#debug($data);
 		#debug($pObj);
 		#debug($data['row']['sys_language_uid']);
 		#tx_commerce_create_folder::init_folders();
 		#list($modPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Commerce', 'commerce');
		#list($prodPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Products', 'commerce',$modPid);
		$addWhere = ' AND tx_commerce_articles.article_type_uid='.NORMALArticleType.' ';
		if ($data['row']['sys_language_uid']>0) {
			$addWhere .= ' and tx_commerce_products.sys_language_uid='.$data['row']['sys_language_uid'].' ';
		}
		$addWhere .= ' and tx_commerce_products.deleted = 0 and tx_commerce_articles.deleted =0 ';
		$resProducts=$GLOBALS['TYPO3_DB']->exec_SELECTquery('distinct tx_commerce_products.title,tx_commerce_products.uid, tx_commerce_products.sys_language_uid, count(tx_commerce_articles.uid) as anzahl','tx_commerce_products,tx_commerce_articles',"tx_commerce_products.uid=tx_commerce_articles.uid_product ".$addWhere,'tx_commerce_products.title,tx_commerce_products.uid, tx_commerce_products.sys_language_uid','tx_commerce_products.title,tx_commerce_products.sys_language_uid');
		#debug($GLOBALS['TYPO3_DB']->SELECTquery('distinct tx_commerce_products.title,tx_commerce_products.uid, tx_commerce_products.sys_language_uid','tx_commerce_products,tx_commerce_articles',"tx_commerce_products.uid=tx_commerce_articles.uid_product ".$addWhere,'','tx_commerce_products.title'));
		$data['items'] = array();
		$items=array();
		$items[] = array('',-1);
		while ($rowProducts=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($resProducts)) {
			// Select Languages
			$language='';
		
			if ($rowProducts['sys_language_uid']> 0) {
				$resLanguage=$GLOBALS['TYPO3_DB']->exec_SELECTquery('title','sys_language','uid='.$rowProducts['sys_language_uid']);
				if ($rowLanguage=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($resLanguage)) {
					$language = $rowLanguage['title'];
						
				}
			}
			
			$title='';
			if ($language){
				$title=$rowProducts['title'].' ['.$language.'] ';
			}else{
				$title=$rowProducts['title'];
			}
			if ($rowProducts['anzahl'] > 0) {
				
				
				$resArticles=$GLOBALS['TYPO3_DB']->exec_SELECTquery('eancode,l18n_parent,ordernumber','tx_commerce_articles','tx_commerce_articles.uid_product='.$rowProducts['uid'].' and tx_commerce_articles.deleted=0 ');
				
				
				if ($resArticles) {
					
					$NumRows=$GLOBALS['TYPO3_DB']->sql_num_rows($resArticles);
					$count=0;
					$eancodes=array();
					$ordernumbers=array();
					/**
					 * @since 2007-04-04
					 * @author	Ingo Schmitt <is@marketing-factory.de>
					 * Bugfix Using ordernumber from l18nparent
					 */
					while (($rowArticles = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resArticles)) && ($count < $numArticleNumbersShow)) {
						if ($rowArticles['l18n_parent']>0) {
							$resL18nParent = $GLOBALS['TYPO3_DB']->exec_SELECTquery('eancode,ordernumber','tx_commerce_articles','tx_commerce_articles.uid='.$rowArticles['l18n_parent']);
							if ($resL18nParent) {
								$rowL18nParents = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resL18nParent);
								if ($rowL18nParents['eancode']<>'') {
									$eancodes[]=$rowL18nParents['eancode'];
								}
								if ($rowL18nParents['ordernumber']<>'') {
									$ordernumbers[]=$rowL18nParents['ordernumber'];
								}
								
							}else{
								if ($rowArticles['eancode']<>'') {
								$eancodes[]=$rowArticles['eancode'];
								}
								if ($rowArticles['ordernumber']<>'') {
									$ordernumbers[]=$rowArticles['ordernumber'];
								}
							}
							
						}else{
							if ($rowArticles['eancode']<>'') {
								$eancodes[]=$rowArticles['eancode'];
							}
							if ($rowArticles['ordernumber']<>'') {
								$ordernumbers[]=$rowArticles['ordernumber'];
							}
						}
						$count++;
					#	debug($rowArticles);
					}
				
					if (count($ordernumbers)>=count($eancodes) ){
						$numbers=implode(',',$ordernumbers);
					}else{
						$numbers=implode(',',$eancodes);
					}
					if ($NumRows > $count){
						$numbers.= ',...';
					}
					$title.=' ('.$numbers.')';
				}
			}
			$items[]=array($title,$rowProducts['uid']);
			
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($resProducts);
		#debug($items);
 		$data['items'] = $items;
 		
 	}
 	
 }
 
 
 if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_forms_select.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_forms_select.php"]);
}

?>
