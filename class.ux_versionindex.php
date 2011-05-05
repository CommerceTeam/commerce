<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Ingo Schmitt (is@marketing-factory.de)
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
 * This class replaces the version preview of version index.ogo
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @see sysext/version/index.php
 */
class ux_tx_version_cm1 extends tx_version_cm1 {

	/** Administrative links for a table / record
	 *
	 * @param	string		Table name
	 * @param	array		Record for which administrative links are generated.
	 * @return	string		HTML link tags.
	 */
	function adminLinks($table,$row)	{
		global $BE_USER;

			// Edit link:
		$adminLink = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit['.$table.']['.$row['uid'].']=edit',$this->doc->backPath)).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' alt="" title="Edit"/>'.
						'</a>';

			// Delete link:
		$adminLink.= '<a href="'.htmlspecialchars($this->doc->issueCommand('&cmd['.$table.']['.$row['uid'].'][delete]=1')).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','width="11" height="12"').' alt="" title="Delete"/>'.
						'</a>';



		if ($table == 'pages')	{

				// If another page module was specified, replace the default Page module with the new one
			$newPageModule = trim($BE_USER->getTSConfigVal('options.overridePageModule'));
			$pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

				// Perform some acccess checks:
			$a_wl = $BE_USER->check('modules','web_list');
			$a_wp = t3lib_extMgm::isLoaded('cms') && $BE_USER->check('modules',$pageModule);

			$adminLink.='<a href="#" onclick="top.loadEditId('.$row['uid'].');top.goToModule(\''.$pageModule.'\'); return false;">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('cms').'layout/layout.gif','width="14" height="12"').' title="" alt="" />'.
						'</a>';
			$adminLink.='<a href="#" onclick="top.loadEditId('.$row['uid'].');top.goToModule(\'web_list\'); return false;">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'mod/web/list/list.gif','width="14" height="12"').' title="" alt="" />'.
						'</a>';

				// "View page" icon is added:
			$adminLink.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($row['uid'],$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($row['uid']))).'">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','width="12" height="12"').' title="" alt="" />'.
				'</a>';
		} else if ($table == 'tx_commerce_products') {
				if ($row['pid']==-1)	{
					$pagesTSC = t3lib_BEfunc::getPagesTSconfig($GLOBALS['_POST']['popViewId']); // get page TSconfig
					if ($pagesTSC['tx_commerce.']['singlePid']) {
						$previewPageID = $pagesTSC['tx_commerce.']['singlePid'];
					}else{
						$previewPageID = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['previewPageID'];
					}
					$productObj = t3lib_div::makeInstance('tx_commerce_product');
					$productObj -> init($row['t3ver_oid'],sys_language_uid);
					$productObj ->load_data();
					$parentCateory = $productObj->getMasterparentCategory();
					$getVars = ($fieldArray['sys_language_uid']>0?'&L='.$fieldArray['sys_language_uid']:'').
							'&ADMCMD_vPrev&no_cache=1&tx_commerce[showUid]='.$row['t3ver_oid'].
					 		'&tx_commerce[catUid]='.$parentCateory;
					$adminLink.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($previewPageID,$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($row['_REAL_PID']),'','',$getVars)).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','width="12" height="12"').' title="" alt="" />'.
						'</a>';
				}
				
		}else{
			if ($row['pid']==-1)	{
				$getVars = '&ADMCMD_vPrev['.rawurlencode($table.':'.$row['t3ver_oid']).']='.$row['uid'];

					// "View page" icon is added:
				$adminLink.='!!!<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($row['_REAL_PID'],$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($row['_REAL_PID']),'','',$getVars)).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','width="12" height="12"').' title="" alt="" />'.
					'</a>';
			}
		}

		return $adminLink;
	}
	 
	
	
	
	
	

	
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['commerce/ux_versinondex.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['commerce/ux_versinondex.php']);
}
?>