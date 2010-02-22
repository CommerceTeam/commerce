<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Christian Ehret (chris@ehret.name)
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

class ux_ModuleMenu extends ModuleMenu {

	/**
	* gets the raw module data
	* patch for 4.2.x - navigation of submodules in Backend does not work if you
	* try to navigate to another submodule of the same module - see bug #0008851
 	*
 	* @author	Christian Ehret <chris@ehret.name>
 	*
	* @see t3lib/class.t3librecordlist.php
 	*/
	public function getRawModuleData() {
		$modules = array();

			// Remove the 'doc' module?
		if($GLOBALS['BE_USER']->getTSConfigVal('options.disableDocModuleInAB'))	{
			unset($this->loadedModules['doc']);
		}

		foreach($this->loadedModules as $moduleName => $moduleData) {
			$moduleNavigationFramePrefix = $this->getNavigationFramePrefix($moduleData);

			if($moduleNavigationFramePrefix) {
				$this->fsMod[$moduleName] = 'fsMod.recentIds["'.$moduleName.'"]="";';
			}

			$moduleLink = '';
			if(!is_array($moduleData['sub'])) {
				$moduleLink = $moduleData['script'];
			}
			$moduleLink = t3lib_div::resolveBackPath($moduleLink);

			$moduleKey   = $moduleName.'_tab';
			$moduleCssId = 'ID_'.t3lib_div::md5int($moduleName);
			$moduleIcon  = $this->getModuleIcon($moduleKey);

			if($moduleLink && $moduleNavigationFramePrefix) {
				$moduleLink = $moduleNavigationFramePrefix.rawurlencode($moduleLink);
			}

			$modules[$moduleKey] = array(
				'name'        => $moduleName,
				'title'       => $GLOBALS['LANG']->moduleLabels['tabs'][$moduleKey],
				'onclick'     => 'top.goToModule(\''.$moduleName.'\');',
				'cssId'       => $moduleCssId,
				'icon'        => $moduleIcon,
				'link'        => $moduleLink,
				'prefix'      => $moduleNavigationFramePrefix,
				'description' => $GLOBALS['LANG']->moduleLabels['labels'][$moduleKey.'label']
			);

			if(is_array($moduleData['sub'])) {

				foreach($moduleData['sub'] as $submoduleName => $submoduleData) {
					$submoduleLink = t3lib_div::resolveBackPath($submoduleData['script']);
					$submoduleNavigationFramePrefix = $this->getNavigationFramePrefix($moduleData, $submoduleData);

					$submoduleKey         = $moduleName.'_'.$submoduleName.'_tab';
					$submoduleCssId       = 'ID_'.t3lib_div::md5int($moduleName.'_'.$submoduleName);
					$submoduleIcon        = $this->getModuleIcon($submoduleKey);
					$submoduleDescription = $GLOBALS['LANG']->moduleLabels['labels'][$submoduleKey.'label'];

					$originalLink = $submoduleLink;
					if($submoduleLink && $submoduleNavigationFramePrefix) {
						$submoduleLink = $submoduleNavigationFramePrefix.rawurlencode($submoduleLink);
					}

					$modules[$moduleKey]['subitems'][$submoduleKey] = array(
						'name'         => $moduleName.'_'.$submoduleName,
						'title'        => $GLOBALS['LANG']->moduleLabels['tabs'][$submoduleKey],
						'onclick'      => 'top.goToModule(\''.$moduleName.'_'.$submoduleName.'\');',
						'cssId'        => $submoduleCssId,
						'icon'         => $submoduleIcon,
						'link'         => $submoduleLink,
						'originalLink' => $originalLink,
						'prefix'       => $submoduleNavigationFramePrefix,
						'description'  => $submoduleDescription,
						'navigationFrameScript' => $submoduleData['navFrameScript']
					);

					if($moduleData['navFrameScript']) {
						$modules[$moduleKey]['subitems'][$submoduleKey]['parentNavigationFrameScript'] = $moduleData['navFrameScript'];
					}
				}
			}
		}

		return $modules;
	}

}

?>