<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2005-2013 Volker Graubaum <vg_typo3@e-netconsulting.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Menu libary a navigation menu as a normal user function based on
 * categories (and products) of commerce
 * Thanks to Daniel Thomas, who build a class for his mediadb, which was
 * the basic for this class
 */

/**
 * Class Tx_Commerce_ViewHelpers_Navigation
 */
class Tx_Commerce_ViewHelpers_Navigation {
	const navigationIdent = 'COMMERCE_MENU_NAV';

	/**
	 * @var string
	 */
	public $prefixId = 'tx_commerce_pi1';

	/**
	 * @var tslib_cObj
	 */
	public $cObj;

	/**
	 * @var array
	 */
	public $activeCats = array();

	/**
	 * @var array
	 */
	public $mConf;

	/**
	 * @var integer
	 */
	public $cat;

	/**
	 * @var
	 */
	public $tree;

	/**
	 * @var
	 */
	public $mTree;

	/**
	 * @var string
	 */
	public $out;

	/**
	 * @var integer
	 */
	public $mDepth = 2;

	/**
	 * @var integer
	 */
	public $entryCat = 0;

	/**
	 * @var array
	 */
	public $listNodes = array();

	/**
	 * @var integer
	 */
	public $manufacturerIdentifier = PHP_INT_MAX;

	/**
	 * @var    integer    [0-1]
	 * @access private
	 */
	public $useRootlineInformationToUrl = 0;

	/**
	 * @var array pathParents
	 * Array holding the parentes of this cat as uid list
	 */
	public $pathParents = array();

	/**
	 * @Var Translation Mode for getRecordOverlay
	 * @see class.t3lib_page.php
	 * @acces private
	 */
	public $translationMode = 'hideNonTranslated';

	/**
	 * Default Menue Items States order by the defined Order
	 *
	 * @var array
	 */
	public $menueItemStates = array(
		0 => 'USERDEF2',
		1 => 'USERDEF1',
		2 => 'SPC',
		3 => 'USR',
		4 => 'CURIFSUB',
		5 => 'CUR',
		6 => 'ACTIFSUB',
		7 => 'ACT',
		8 => 'IFSUB',
	);

	/**
	 * @var array
	 */
	public $gpVars = array();

	/**
	 * @var int Maximum Level for Menu, Default all PHP_INT_MAX
	 */
	protected $maxLevel = PHP_INT_MAX;

	/**
	 * @var string
	 */
	protected $separator = '&';

	/**
	 * @var boolean Do not check if an element is active
	 */
	protected $noAct = FALSE;

	/**
	 * @var integer
	 */
	public $choosenCat;

	/**
	 * @var integer
	 */
	public $pid;

	/**
	 * @var string
	 */
	public $path;

	/**
	 * @var integer
	 */
	public $entryLevel;

	/**
	 * @var boolean
	 */
	public $expandAll;

	/**
	 * @var integer
	 */
	public $showUid;

	/**
	 * @var array
	 */
	protected $nodeArrayAdditionalFields;

	/**
	 * @var array
	 */
	protected $pageRootline;

	/**
	 * @var integer
	 */
	protected $menuType;

	/**
	 * @var Tx_Commerce_Domain_Model_Category
	 */
	protected $catObj;

	/**
	 * @var Tx_Commerce_Domain_Model_Category
	 */
	protected $category;

	/**
	 * Init Method for initialising the navigation
	 *
	 * @param string $content $content passed to method
	 * @param array $conf TS Array
	 * @return array array for the menurendering of TYPO3
	 */
	public function init($content, $conf) {
		$this->mConf = $this->processConf($conf);
		if ($this->mConf['useRootlineInformationToUrl']) {
			$this->useRootlineInformationToUrl = $this->mConf['useRootlineInformationToUrl'];
		}
		$this->choosenCat = $this->mConf['category'];

		$this->nodeArrayAdditionalFields = GeneralUtility::trimExplode(',', $this->mConf['additionalFields'], 0);

		$this->pid = $this->mConf['overridePid'] ?
			$this->mConf['overridePid'] :
			$GLOBALS['TSFE']->id;
		$this->gpVars = GeneralUtility::_GPmerged($this->prefixId);

		Tx_Commerce_Utility_GeneralUtility::initializeFeUserBasket();

		$this->gpVars['basketHashValue'] = $GLOBALS['TSFE']->fe_user->tx_commerce_basket->getBasketHashValue();
		$this->pageRootline = $GLOBALS['TSFE']->rootLine;
		$this->menuType = $this->mConf['1'];
		$this->entryLevel = (int) $this->mConf['entryLevel'];

		if ((int) $this->mConf['noAct'] > 0) {
			$this->noAct = TRUE;
		}

		if ((int) $this->mConf['maxLevel'] > 0) {
			$this->maxLevel = (int) $this->mConf['maxLevel'];
		}

		/**
		 * Detect if a user is logged in and if he or she has usergroups
		 * as we have to take in accout, that different usergroups may have different
		 * rights on the commerce tree, so consider this whe calculation the cache hash.
		 */
		$usergroups = '';
		if (is_array($GLOBALS['TSFE']->fe_user->user)) {
			$usergroups = $GLOBALS['TSFE']->fe_user->user['usergroup'];
		}

		$this->cat = $this->getRootCategory();
		// Define a default
		$this->choosenCat = $this->mConf['category'];

		$this->showUid = $this->gpVars['showUid'] ? $this->gpVars['showUid'] : 0;
		$this->mDepth = $this->gpVars['mDepth'] ? $this->gpVars['mDepth'] : 0;
		$this->path = $this->gpVars['path'] ? $this->gpVars['path'] : 0;
		$this->expandAll = $this->mConf['expandAll'] ? $this->mConf['expandAll'] : 0;

		$menueErrorName = array();
		if (!($this->cat > 0)) {
			$menueErrorName[] = 'No category defined in TypoScript: lib.tx_commerce.navigation.special.category';
		}

		if (!($this->pid > 0)) {
			$menueErrorName[] = 'No OveridePID defined in TypoScript: lib.tx_commerce.navigation.special.overridePid';
		}

		if (count($menueErrorName) > 0) {
			foreach ($menueErrorName as $oneError) {
				\TYPO3\CMS\Core\Utility\DebugUtility::debug($this->mConf, $oneError);
			}

			return $this->makeErrorMenu(5);
		}

		/**
		 * Unique Hash for this usergroup and page to display the navigation
		 */
		$hash = md5(
			'tx_commerce_navigation:' . implode('-', $this->mConf) . ':' . $usergroups . ':' .
			$GLOBALS['TSFE']->linkVars . ':' . GeneralUtility::getIndpEnv('HTTP_HOST')
		);

		$cachedMatrix = $this->getHash($hash, 0);

		/**
		 * Render Menue Array and store in cache, if possible
		 */
		if (($GLOBALS['TSFE']->no_cache == 1)) {
			// Build directly and don't sore, if no_cache=1'
			$this->mTree = $this->makeArrayPostRender(
				$this->pid, 'tx_commerce_categories', 'tx_commerce_categories_parent_category_mm', 'tx_commerce_products',
				'tx_commerce_products_categories_mm', $this->cat, 1, 0, $this->maxLevel
			);

			/**
			 * Sorting Options, there is only one type 'alphabetiDesc' :)
			 * the others must to program
			 *
			 * @todo: implement sortType:alphabetiAsc,byUid, bySorting
			 */
			if ($this->mConf['sortAllitems.']['type'] == 'alphabetiDesc') {
				$this->sortAllMenuArray($this->mTree, 'alphabetiDesc');
			}
		} elseif ($cachedMatrix != '') {
			// User the cached version
			$this->mTree = unserialize($cachedMatrix);
		} else {

			// no cache present buld data and stor it in cache
			$this->mTree = $this->makeArrayPostRender(
				$this->pid, 'tx_commerce_categories', 'tx_commerce_categories_parent_category_mm', 'tx_commerce_products',
				'tx_commerce_products_categories_mm', $this->cat, 1, 0, $this->maxLevel
			);

			/**
			 * Sorting Options, there is only one type 'alphabetiDesc' :)
			 * the others must to program
			 *
			 * @todo: implement sortType:alphabetiAsc,byUid, bySorting
			 */
			if ($this->mConf['sortAllitems.']['type'] == 'alphabetiDesc') {
				$this->sortAllMenuArray($this->mTree, 'alphabetiDesc');
			}
			$this->storeHash($hash, serialize($this->mTree), self::navigationIdent . $this->cat);
		}

		/**
		 * finish menue array rendering, now postprocessing with current status of menue
		 */
		$keys = array_keys($this->mTree);

		/**
		 * Detect rootline, necessary
		 */
		if ($this->noAct === TRUE) {
			$this->pathParents = array();
			$this->mDepth = 0;
		} elseif ($this->gpVars['catUid']) {
			$this->choosenCat = $this->gpVars['catUid'];
		} elseif ($this->gpVars['showUid']) {
			/**
			 * If a product is shown, we have to detect the parent category as well
			 * even if wo haven't walked thrue the categories
			 */
			/** @var Tx_Commerce_Domain_Model_Product $myProduct */
			$myProduct = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $this->gpVars['showUid']);
			$myProduct->loadData();
			$this->choosenCat = $myProduct->getMasterparentCategory();
		}

		if ($this->gpVars['path']) {
			$this->path = $this->gpVars['path'];
			$this->pathParents = explode(',', $this->path);
		} elseif ((is_numeric($this->choosenCat)) && ($this->choosenCat > 0)) {
			/**
			 * Build the path by or own
			 *
			 * @var Tx_Commerce_Domain_Model_Category $myCat
			 */
			$myCat = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $this->choosenCat);
			$myCat->loadData();
			// MODIF DE LUC >AMEOS : Get the right path with custom method
			$aPath = $this->getRootLine($this->mTree, $this->choosenCat, $this->expandAll);
			if (!$aPath) {
				/**
				 * if the methode getRootLine fail, we take the path direct from the DB.
				 */
				$tmpArray = $myCat->getParentCategoriesUidlist();
				$this->fixPathParents($tmpArray, $this->cat);
			} else {
				$tmpArray = $aPath;
			}

			/**
			 * Strip the Staring point and the value 0
			 */
			if (!is_array($tmpArray)) {
				$tmpArray = array();
			}

			foreach ((array) $tmpArray as $value) {
				if (($value <> $this->cat) && ($value > 0)) {
					$this->pathParents[] = $value;
				}
			}

			if ($this->mConf['groupOptions.']['onOptions'] == 1 && $GLOBALS['TSFE']->fe_user->user['usergroup'] != '') {
				$this->fixPathParents($this->pathParents, $keys[0]);
			}

			$this->pathParents = array_reverse($this->pathParents);
			if (!$this->gpVars['mDepth']) {
				$this->mDepth = count($this->pathParents);
				if ($this->gpVars['manufacturer']) {
					$this->mDepth++;
				}
			}
		} else {
			/**
			 * If no Category is choosen by the user, so you just render the default menue
			 * no rootline for the categories is needed and the depth is 0
			 */
			$this->pathParents = array();
			$this->mDepth = 0;
		}

		/**
		 * If we do have an entry level,
		 * we strip away the number of array levels of the entry level value
		 */
		if ($this->entryLevel > 0) {
			$newParentes = array_reverse($this->pathParents);

			/**
			 * Foreach entry level detect the array for this level and remove
			 * it from $this->mTree
			 */
			for ($i = 0; $i < $this->entryLevel; $i++) {
				$this->mTree = $this->mTree[$newParentes[$i]]['--subLevel--'];

				/**
				 * Reduce elementes in pathParents and decrese menue depth
				 */
				array_pop($this->pathParents);
				$this->mDepth--;
			}
		}

		if ($this->pathParents) {
			$this->processArrayPostRender($this->mTree, $this->pathParents, $this->mDepth);
		}

		// never ever tough this piece of crap its needed to return an array
		return $this->mTree;
	}

	/**
	 * Fix path parents
	 *
	 * @param array &$pathArray by reference
	 * @param integer $chosenCatUid
	 * @return void
	 */
	public function fixPathParents(&$pathArray, $chosenCatUid) {
		if ($pathArray == NULL) {
			return;
		}
		if ($pathArray[0] != $chosenCatUid) {
			array_shift($pathArray);
			$this->fixPathParents($pathArray, $chosenCatUid);
		}
	}

	/**
	 * Get root category
	 *
	 * @return integer
	 */
	public function getRootCategory() {
		if ($this->mConf['groupOptions.']['onOptions'] == 1) {
			$catOptionsCount = count($this->mConf['groupOptions.']);
			$chosenCatUid = array();
			for ($i = 1; $i <= $catOptionsCount; $i++) {
				$chosenGroups = GeneralUtility::trimExplode(',', $this->mConf['groupOptions.'][$i . '.']['group']);
				if ($GLOBALS['TSFE']->fe_user->user['usergroup'] == '') {

					return $this->mConf['category'];
				}
				$feGroups = explode(',', $GLOBALS['TSFE']->fe_user->user['usergroup']);

				foreach ($chosenGroups as $group) {
					if (in_array($group, $feGroups) === TRUE) {
						if (in_array($this->mConf['groupOptions.'][$i . '.']['catUid'], $chosenCatUid) === FALSE) {
							array_push($chosenCatUid, $this->mConf['groupOptions.'][$i . '.']['catUid']);
						}
					}
				}
			}

			if (count($chosenCatUid) == 1) {
				return $chosenCatUid[0];
			} elseif (count($chosenCatUid) > 1) {
				return $chosenCatUid[0];
			} else {
				return $this->mConf['category'];
			}
		}

		return $this->mConf['category'];
	}

	/**
	 * Make menu error node
	 *
	 * @param integer $max
	 * @param integer $mDepth
	 * @return array
	 */
	public function makeErrorMenu($max = 5, $mDepth = 1) {
		$treeList = array();
		for ($i = 0; $i < $max; $i++) {
			$nodeArray['pid'] = $this->pid;
			$nodeArray['uid'] = $i;
			$nodeArray['title'] = 'Error in the typoScript configuration.';
			$nodeArray['parent_id'] = $i;
			$nodeArray['nav_title'] = 'Error in the typoScript configuration.';
			$nodeArray['hidden'] = 0;
			$nodeArray['depth'] = $mDepth;
			$nodeArray['leaf'] = 1;
			$treeList[$i] = $nodeArray;
		}

		return $treeList;
	}

	/**
	 * Sets the clear Function for each MenuItem
	 *
	 * @param array $conf TSconfig to parse
	 * @return array TSConfig with ItemArrayProcFunc
	 */
	public function processConf($conf) {
		$i = 1;
		foreach ($conf as $k) {
			if ($k == $i . '.') {
				$conf[$i . '.']['itemArrayProcFunc'] = 'Tx_Commerce_ViewHelpers_Navigation->clear';
				$i++;
			}
		}
		$this->mDepth = $i;

		$this->separator = ini_get('arg_separator.output');

		return $conf;
	}

	/**
	 * Makes the post array,which  the typo3 render Function will be work
	 *
	 * @param integer $uidPage
	 * @param string $mainTable
	 * @param string $tableMm
	 * @param string $tableSubMain
	 * @param string $tableSubMm
	 * @param integer $uidRoot
	 * @param integer $mDepth
	 * @param integer $path
	 * @param integer $maxLevel
	 * @return array TSConfig with ItemArrayProcFunc
	 */
	public function makeArrayPostRender($uidPage, $mainTable, $tableMm, $tableSubMain, $tableSubMm, $uidRoot, $mDepth = 1,
		$path = 0, $maxLevel = PHP_INT_MAX) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$treeList = array();

		$maxLevel--;
		if ($maxLevel < 0) {
			return array();
		}

		$sql = 'SELECT ' . $tableMm . '.* FROM ' . $tableMm . ',' . $mainTable . ' WHERE ' . $mainTable . '.deleted = 0 AND ' .
			$mainTable . '.uid = ' . $tableMm . '.uid_local AND ' . $tableMm . '.uid_local <> "" AND ' . $tableMm .
			'.uid_foreign = ' . $uidRoot;

		$sorting = ' ORDER BY ' . $mainTable . '.sorting';

		/**
		 * Add some hooks for custom sorting
		 */
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_navigation.php']['sortingOrder']) {
			GeneralUtility::deprecationLog(
				'
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_navigation.php\'][\'sortingOrder\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/ViewHelpers/Navigation.php\'][\'sortingOrder\']
				'
			);
			$hookObj = & GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_navigation.php']['sortingOrder']
			);
			if (method_exists($hookObj, 'sortingOrder')) {
				$sorting = $hookObj->sortingOrder($sorting, $uidRoot, $mainTable, $tableMm, $mDepth, $path, $this);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/ViewHelpers/Navigation.php']['sortingOrder']) {
			$hookObj = & GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/ViewHelpers/Navigation.php']['sortingOrder']
			);
			if (method_exists($hookObj, 'sortingOrder')) {
				$sorting = $hookObj->sortingOrder($sorting, $uidRoot, $mainTable, $tableMm, $mDepth, $path, $this);
			}
		}

		$sql .= $sorting;

		$res = $database->sql_query($sql);

		while (($row = $database->sql_fetch_assoc($res))) {
			$nodeArray = array();
			$dataRow = $this->getDataRow($row['uid_local'], $mainTable);

			if ($dataRow['deleted'] == '0') {
				$nodeArray['CommerceMenu'] = TRUE;
				$nodeArray['pid'] = $dataRow['pid'];
				$nodeArray['uid'] = $uidPage;
				$nodeArray['title'] = htmlspecialchars(strip_tags($dataRow['title']));
				if ($GLOBALS['TSFE']->sys_language_uid) {
					/**
					 * Add Pages Overlayto Array, if not syslaguage
					 */
					$nodeArray['_PAGES_OVERLAY'] = htmlspecialchars(strip_tags($dataRow['title']));
				}
				$nodeArray['parent_id'] = $uidRoot;
				$nodeArray['nav_title'] = htmlspecialchars(strip_tags($dataRow['navtitle']));

				// Add custom Fields to array
				foreach ($this->nodeArrayAdditionalFields as $field) {
					$nodeArray[$field] = htmlspecialchars(strip_tags($dataRow[$field]));
				}

				$nodeArray['hidden'] = $dataRow['hidden'];
				$nodeArray['depth'] = $mDepth;
				$nodeArray['leaf'] = $this->isLeaf($row['uid_local'], $tableMm, $tableSubMm);
				$nodeArray['hasSubChild'] = $this->hasSubChild($row['uid_local'], $tableSubMm);
				$nodeArray['subChildTable'] = $tableSubMm;
				$nodeArray['tableSubMain'] = $tableSubMain;
				$nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[catUid]=' . $row['uid_local'];
				if ($path != 0) {
					$nodeArray['path'] = $dataRow['uid'] . ',' . $path;
				} else {
					$nodeArray['path'] = $dataRow['uid'];
				}

				$aCatToManu = explode(',', $this->mConf['displayManuForCat']);
				if ($this->useRootlineInformationToUrl == 1) {
					$nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[mDepth]=' . $mDepth . $this->separator .
						$this->prefixId . '[path]=' . $nodeArray['path'];
				}

				if (in_array($row['uid_local'], $aCatToManu) || strtolower(trim($aCatToManu['0'])) == 'all') {
					$nodeArray['--subLevel--'] = array();
					$this->arrayMerge(
						$nodeArray['--subLevel--'], $this->getManufacturerAsCategory(
							$dataRow['pid'], $uidPage, $tableMm, $tableSubMain, $tableSubMm, $row['uid_local'], $mDepth + 1,
							$nodeArray['path']
						)
					);
				}

				if (!$nodeArray['leaf']) {
					if (!is_array($nodeArray['--subLevel--'])) {
						$nodeArray['--subLevel--'] = array();
					}

					$this->arrayMerge(
						$nodeArray['--subLevel--'], $this->makeArrayPostRender(
							$uidPage, $mainTable, $tableMm, $tableSubMain, $tableSubMm, $row['uid_local'], $mDepth + 1,
							$nodeArray['path'], $maxLevel
						)
					);

					if ($nodeArray['hasSubChild'] == 1 && $this->mConf['showProducts'] == 1) {
						$arraySubChild = $this->makeSubChildArrayPostRender(
							$uidPage, $tableSubMain, $tableSubMm, $row['uid_local'], $mDepth + 1, $nodeArray['path'], $maxLevel
						);

						$this->arrayMerge($nodeArray['--subLevel--'], $arraySubChild);

						if ($this->mConf['groupOptions.']['onOptions'] == 1 && $GLOBALS['TSFE']->fe_user->user['usergroup'] != '') {
							$arraySubChild = $this->makeSubChildArrayPostRender(
								$uidPage, $tableSubMain, $tableSubMm, $row['uid_local'], $mDepth + 1, $nodeArray['path'],
								$maxLevel
							);
							$this->arrayMerge($nodeArray['--subLevel--'], $arraySubChild);
						}
					}

					if (($this->expandAll > 0) || ($this->expandAll < 0 && ( - $this->expandAll >= $mDepth))) {
						$nodeArray['_SUB_MENU'] = $nodeArray['--subLevel--'];
					}
					if ($this->gpVars['basketHashValue']) {
						$nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[basketHashValue]=' . $this->gpVars['basketHashValue'];
					}

					$nodeArray['ITEM_STATE'] = 'IFSUB';
					$nodeArray['ITEM_STATES_LIST'] = 'IFSUB,NO';
				} else {
					if ($nodeArray['hasSubChild'] == 2) {
						$nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[showUid]=' . $dataRow['uid'];
						$nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[mDepth]=' . $mDepth . $this->separator .
							$this->prefixId . '[path]=' . $nodeArray['path'];
					}
					if ($this->useRootlineInformationToUrl == 1) {
						$nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[mDepth]=' . $mDepth . $this->separator .
							$this->prefixId . '[path]=' . $nodeArray['path'];
					}
					if ($this->gpVars['basketHashValue']) {
						$nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[basketHashValue]=' . $this->gpVars['basketHashValue'];
					}

					$nodeArray['ITEM_STATE'] = 'NO';
				}

				if (strpos($nodeArray['_ADD_GETVARS'], 'showUid') === FALSE) {
					$nodeArray['_ADD_GETVARS'] = $this->separator . $this->prefixId . '[showUid]=' . $nodeArray['_ADD_GETVARS'];
				}

				$nodeArray['_ADD_GETVARS'] .= $this->separator . 'cHash=' .
					$this->generateChash($nodeArray['_ADD_GETVARS'] . $GLOBALS['TSFE']->linkVars);

				$treeList[$row['uid_local']] = $nodeArray;
			}
		}

		if ($treeList == NULL && $this->mConf['showProducts'] == 1) {
			$treeList = $this->makeSubChildArrayPostRender(
				$uidPage, $tableSubMain, $tableSubMm, $uidRoot, $mDepth, $path, $maxLevel
			);
		}

		return $treeList;
	}

	/**
	 * Makes a set of  ItemMenu product list  of a category.
	 *
	 * @param integer $pageUid
	 * @param string $mainTable main table
	 * @param string $mmTable
	 * @param integer $categoryUid category Uid
	 * @param integer $mDepth
	 * @param integer $path
	 * @param boolean $manufacturerUid
	 * @return array array to be processed by HMENU
	 */
	public function makeSubChildArrayPostRender($pageUid, $mainTable, $mmTable, $categoryUid, $mDepth = 1, $path = 0,
			$manufacturerUid = FALSE) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$treeList = array();
		$sqlManufacturer = '';
		if (is_numeric($manufacturerUid)) {
			$sqlManufacturer = ' AND ' . $mainTable . '.manufacturer_uid = ' . (int) $manufacturerUid . ' ';
		}
		$sql = 'SELECT ' . $mmTable . '.* FROM ' . $mmTable . ',' . $mainTable . ' WHERE ' . $mainTable . '.deleted = 0 AND ' .
			$mainTable . '.uid = ' . $mmTable . '.uid_local AND ' . $mmTable . '.uid_local<>"" AND ' . $mmTable . '.uid_foreign = ' .
			(int) $categoryUid . ' ' . $sqlManufacturer;

		$sorting = ' order by ' . $mainTable . '.sorting ';

		/**
		 * Add some hooks for custom sorting
		 */
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_navigation.php']['sortingOrder']) {
			GeneralUtility::deprecationLog(
				'
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_navigation.php\'][\'sortingOrder\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/ViewHelpers/Navigation.php\'][\'sortingOrder\']
				'
			);
			$hookObj = & GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_navigation.php']['sortingOrder']
			);
			if (method_exists($hookObj, 'sortingOrder')) {
				$sorting = $hookObj->sortingOrder($sorting, $categoryUid, $mainTable, $mmTable, $mDepth, $path, $this);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/ViewHelpers/Navigation.php']['sortingOrder']) {
			$hookObj = & GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/ViewHelpers/Navigation.php']['sortingOrder']
			);
			if (method_exists($hookObj, 'sortingOrder')) {
				$sorting = $hookObj->sortingOrder($sorting, $categoryUid, $mainTable, $mmTable, $mDepth, $path, $this);
			}
		}

		$sql .= $sorting;

		$res = $database->sql_query($sql);
		while (($row = $database->sql_fetch_assoc($res))) {
			$nodeArray = array();
			$dataRow = $this->getDataRow($row['uid_local'], $mainTable);
			if ($dataRow['deleted'] == '0') {
				$nodeArray['CommerceMenu'] = TRUE;
				$nodeArray['pid'] = $dataRow['pid'];
				$nodeArray['uid'] = $pageUid;
				$nodeArray['title'] = htmlspecialchars(strip_tags($dataRow['title']));
				$nodeArray['parent_id'] = $categoryUid;
				$nodeArray['nav_title'] = htmlspecialchars(strip_tags($dataRow['navtitle']));
				$nodeArray['hidden'] = $dataRow['hidden'];
				// Add custom Fields to array
				foreach ($this->nodeArrayAdditionalFields as $field) {
					$nodeArray[$field] = htmlspecialchars(strip_tags($dataRow[$field]));
				}
				// Add Pages Overlay to Array, if sys_language_uid ist set
				if ($GLOBALS['TSFE']->sys_language_uid) {
					$nodeArray['_PAGES_OVERLAY'] = $dataRow['title'];
				}
				$nodeArray['depth'] = $mDepth;
				$nodeArray['leaf'] = 1;
				$nodeArray['table'] = $mainTable;
				if ($path != 0) {
					$nodeArray['path'] = $dataRow['uid'] . ',' . $path;
				} else {
					$nodeArray['path'] = $dataRow['uid'];
				}
				// Set default
				$nodeArray['ITEM_STATE'] = 'NO';
				if ($nodeArray['leaf'] == 1) {
					$nodeArray['_ADD_GETVARS'] = $this->separator . $this->prefixId . '[catUid]=' . $categoryUid;
				} else {
					$nodeArray['_ADD_GETVARS'] = $this->separator . $this->prefixId . '[catUid]=' . $row['uid_foreign'];
				}
				$nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[showUid]=' . $dataRow['uid'];

				if ($this->useRootlineInformationToUrl == 1) {
					$nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[mDepth]=' . $mDepth . $this->separator .
						$this->prefixId . '[path]=' . $nodeArray['path'];
				}

				if ($this->gpVars['basketHashValue']) {
					$nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[basketHashValue]=' . $this->gpVars['basketHashValue'];
				}

				$nodeArray['_ADD_GETVARS'] .= $this->separator . 'cHash=' .
					$this->generateChash($nodeArray['_ADD_GETVARS'] . $GLOBALS['TSFE']->linkVars);

				if ($this->gpVars['manufacturer']) {
					$nodeArray['_ADD_GETVARS'] .= '&' . $this->prefixId . '[manufacturer]=' . $this->gpVars['manufacturer'];
				}

				// if this product is displayed set to CUR
				if (($mainTable == 'tx_commerce_products') && ($dataRow['uid'] == $this->showUid)) {
					$nodeArray['ITEM_STATE'] = 'CUR';
				}

				$treeList[$row['uid_local']] = $nodeArray;
			}
		}

		return $treeList;
	}

	/**
	 * Process the menuArray to set state for a selected item
	 *
	 * @param array &$treeArray
	 * @param array $path path of the itemMen
	 * @param integer $mDepth depth of the itemMenu
	 * @return void
	 */
	public function processArrayPostRender(&$treeArray, $path = array(), $mDepth) {
		if ($this->gpVars['manufacturer']) {
			foreach ($treeArray as $val) {
				if ($val['parent_id'] == $this->choosenCat && $val['manu'] == $this->gpVars['manufacturer']) {
					$path = GeneralUtility::trimExplode(',', $val['path']);
				}
			}
		}

		/**
		 * We also store the possible States for each element, to select later,
		 * which state will be set in clear
		 */
		if ($mDepth != 0) {
			if ($mDepth == 1) {
				/**
				 * States: If you would like to preset an element to be recognized as a
				 * SPC, IFSUB, ACT, CUR or USR mode item, you can do so by specifying one
				 * of these values in the key “ITEM_STATE” of the page record. This setting
				 * will override the natural state-evaluation.
				 * So Only Implement ACT, CUR, IFSUB
				 * Other states should be implemented below in clear
				 */
				$treeArray[$path[0]]['ITEM_STATE'] = 'ACT';
				$treeArray[$path[0]]['ITEM_STATES_LIST'] = 'ACT,NO';
				if ($path[0] == $this->choosenCat) {

					/**
					 * Set CUR:
					 * a menu item if the item is the current page.
					 */
					$treeArray[$path[0]]['ITEM_STATE'] = 'CUR';
					$treeArray[$path[0]]['ITEM_STATES_LIST'] = 'CUR,ACT,NO';

					/**
					 * Set IFSUB:
					 */
					if (count($treeArray[$path[0]]['--subLevel--']) > 0) {
						$treeArray[$path[0]]['ITEM_STATE'] = 'IFSUB';
						$treeArray[$path[0]]['ITEM_STATES_LIST'] = 'CURIFSUB,CUR,ACTIFSUB,ACT,IFSUB,NO';
					}

					/**
					 * Sets this node (Product) as current item
					 */
					if ($this->showUid > 0) {
						/**
						 * If we do have a product
						 * This must be the deepethst Element in the menue
						 * SO, this MUST Be CUR and The Level Above must be
						 */
						$treeArray[$path[0]]['--subLevel--'][$this->showUid]['ITEM_STATE'] = 'CUR';
						$treeArray[$path[0]]['--subLevel--'][$this->showUid]['ITEM_STATES_LIST'] = 'CUR,ACT,NO';

						$treeArray[$path[0]]['ITEM_STATE'] = 'ACT';
						$treeArray[$path[0]]['ITEM_STATES_LIST'] = 'ACTIFSUB,ACT,IFSUB,NO';
					}
				}

				if ($this->showUid == $path[0]) {
					if (count($treeArray[$path[0]]['--subLevel--']) > 0) {
						$treeArray[$path[0]]['ITEM_STATE'] = 'IFSUB';
						$treeArray[$path[0]]['ITEM_STATES_LIST'] = 'IFSUB,NO';
					} else {
						$treeArray[$path[0]]['ITEM_STATE'] = 'CUR';
						$treeArray[$path[0]]['ITEM_STATES_LIST'] = 'CUR,ACT,IFSUB,NO';
					}
				}

				if (count($treeArray[$path[0]]['--subLevel--']) > 0) {
					$treeArray[$path[0]]['_SUB_MENU'] = $treeArray[$path[0]]['--subLevel--'];
				}
			} else {
				if (is_array($path)) {
					if (is_array($treeArray)) {
						$nodeId = array_pop($path);
						$treeArray[$nodeId]['ITEM_STATE'] = 'ACT';
						$treeArray[$nodeId]['ITEM_STATES_LIST'] = 'ACT,NO';
						if (count($treeArray[$nodeId]['--subLevel--']) > 0) {
							$treeArray[$nodeId]['ITEM_STATE'] = 'IFSUB';
							$treeArray[$nodeId]['ITEM_STATES_LIST'] = 'ACTIFSUB,ACT,IFSUB,NO';
						}

						if ($nodeId == $this->choosenCat) {
							if (count($treeArray[$nodeId]['--subLevel--']) > 0) {
								$treeArray[$nodeId]['ITEM_STATE'] = 'CUR';
								$treeArray[$nodeId]['ITEM_STATES_LIST'] = 'CURIFSUB,CUR,ACTIFSUB,ACT,NO';
							}
							if ($treeArray[$nodeId]['tableSubMain'] == 'tx_commerce_products') {
								$treeArray[$nodeId]['ITEM_STATE'] = 'ACT';
								$treeArray[$nodeId]['ITEM_STATES_LIST'] = 'ACTIFSUB,ACT,IFSUB,NO';
							}
						}
						if ($this->showUid == $treeArray[$nodeId]['parent_id']) {
							$treeArray[$nodeId]['ITEM_STATE'] = 'CUR';
							$treeArray[$nodeId]['ITEM_STATES_LIST'] = 'CUR,ACT,NO';

							if (count($treeArray[$nodeId]['--subLevel--']) > 0) {
								$treeArray[$nodeId]['ITEM_STATE'] = 'IFSUB';
								$treeArray[$nodeId]['ITEM_STATES_LIST'] = 'CURIFSUB,CUR,ACTIFSUB,ACT,NO';
							}
						}
						$this->processArrayPostRender($treeArray[$nodeId]['--subLevel--'], $path, $mDepth - 1);
						if (count($treeArray[$nodeId]['--subLevel--']) > 0) {
							$treeArray[$nodeId]['_SUB_MENU'] = $treeArray[$nodeId]['--subLevel--'];
						}
					}
				}
			}
		}
	}

	/**
	 * Gets the data to fill a node
	 *
	 * @param integer $uid
	 * @param string $tableName
	 * @return array
	 */
	public function getDataRow($uid, $tableName) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if ($uid == '' or $tableName == '') {
			return '';
		}
		$addWhere = $GLOBALS['TSFE']->sys_page->enableFields($tableName, $GLOBALS['TSFE']->showHiddenRecords);
		$where = 'uid = ' . (int) $uid;
		$row = $database->exec_SELECTgetRows(
			'*', $tableName, $where . $addWhere, $groupBy = '', $orderBy = '', '1', ''
		);

		if (($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] > 0) && $row[0]) {
			$langUid = $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];

			/**
			 * Get Overlay, if available
			 */
			$row[0] = $GLOBALS['TSFE']->sys_page->getRecordOverlay($tableName, $row[0], $langUid, $this->translationMode);
		}

		if ($this->mConf['hideEmptyCategories'] == 1 && $tableName == 'tx_commerce_categories' && is_array($row[0])) {
			// Process Empty Categories
			/** @var Tx_Commerce_Domain_Model_Category $localCategory */
			$localCategory = GeneralUtility::makeinstance(
				'Tx_Commerce_Domain_Model_Category', $row[0]['uid'], $row[0]['sys_language_uid']
			);
			$localCategory->loadData();
			if (!$localCategory->hasProductsInSubCategories()) {
				return array();
			}
		}

		if ($row[0]) {
			return $row[0];
		}

		return array();
	}

	/**
	 * Determines if a item has no sub item
	 *
	 * @param integer $uid
	 * @param string $tableMm
	 * @param string $subTableMm
	 * @return integer : 0|1|2
	 */
	public function isLeaf($uid, $tableMm, $subTableMm) {
		if ($uid == '' || $tableMm == '') {
			return 2;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		$res = $database->exec_SELECTquery('*', $tableMm, 'uid_foreign = ' . (int) $uid, '', '', 1);

		$hasSubChild = $this->hasSubchild($uid, $subTableMm);
		if (($row = $database->sql_fetch_assoc($res)) or $hasSubChild == 1) {
			return 0;
		}

		return 1;
	}

	/**
	 * Determines if an item has sub items in another table
	 *
	 * @param integer $uid
	 * @param string $tableMm
	 * @return integer : 0|1|2
	 */
	public function hasSubChild($uid, $tableMm) {
		if ($uid == '' or $tableMm == '') {
			return 2;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		$res = $database->exec_SELECTquery('*', $tableMm, 'uid_foreign = ' . (int) $uid, '', '', 1);

		if (($row = $database->sql_fetch_assoc($res))) {
			return 1;
		}

		return 0;
	}

	/**
	 * Functions Sets needed Item-States, based on the Item-States set by the
	 * TS Admin for the Menue. Available Item-States for a Menue-Levels are stored
	 * as array in $conf['parentObj']->mconf
	 * Menue Item State Priority Order of priority:
	 *        USERDEF2, USERDEF1, SPC, USR, CURIFSUB, CUR, ACTIFSUB, ACT, IFSUB
	 * Function clears all subelements. This is needed for clear error with mix up
	 * pages and categories
	 *
	 * @param array $menuArr Array with menu item
	 * @param array $conf TSconfig, not used
	 * @return array return the cleaned menu item
	 */
	public function clear($menuArr, $conf) {
		if ($menuArr[0]['CommerceMenu'] <> TRUE) {
			$menuArr = array();
		}

		foreach ($menuArr as $item) {
			if ($item['DO_NOT_RENDER'] == '1') {
				$menuArr = array();
			}
		}

		if ($menuArr[0]['CommerceMenu'] == TRUE) {
			$availiableItemStates = $conf['parentObj']->mconf;
			/**
			 * @TODO: Try to get the expAll state from the TS Menue config and process it
			 * Data from TS Menue is stored in $conf['parentObj']->mconf['expAll']
			 */
			foreach (array_keys($menuArr) as $mIndex) {
				if ($menuArr[$mIndex]['ITEM_STATE'] <> 'NO') {

					if ($menuArr[$mIndex]['ITEM_STATES_LIST']) {
						/**
						 * Get the possible Item States
						 * and walk thrue the list of configured item states
						 * and set the first item-State match as Item State
						 */
						$menuArr[$mIndex]['ITEM_STATE'] = '';
						$possibleItemStatesForItem = explode(',', $menuArr[$mIndex]['ITEM_STATES_LIST']);

						ksort($this->menueItemStates);
						foreach ($this->menueItemStates as $state) {
							if ($availiableItemStates[$state] == 1 && empty($menuArr[$mIndex]['ITEM_STATE'])) {
								if (in_array($state, $possibleItemStatesForItem)) {
									$menuArr[$mIndex]['ITEM_STATE'] = $state;
								}
							}
						}

						if (empty($menuArr[$mIndex]['ITEM_STATE'])) {
							$menuArr[$mIndex]['ITEM_STATE'] = 'NO';
						}
					}
				}
			}
		}

		return $menuArr;
	}


	/**
	 * Method for generating the rootlineMenu to use in TS
	 *
	 * @param string $content passed to method
	 * @param array $conf TS Array
	 * @return array for the menurendering of TYPO3
	 */
	public function renderRootline($content, $conf) {
		$this->mConf = $this->processConf($conf);
		$this->pid = (int) ($this->mConf['overridePid'] ?
			$this->mConf['overridePid'] :
			$GLOBALS['TSFE']->id);
		$this->gpVars = GeneralUtility::_GPmerged($this->prefixId);

		Tx_Commerce_Utility_GeneralUtility::initializeFeUserBasket();

		$this->gpVars['basketHashValue'] = $GLOBALS['TSFE']->fe_user->tx_commerce_basket->getBasketHashValue();
		if (!is_object($this->category)) {
			$this->category = GeneralUtility::makeInstance(
				'Tx_Commerce_Domain_Model_Category', $this->mConf['category'], $GLOBALS['TSFE']->sys_language_uid
			);
			$this->category->loadData();
		}

		$returnArray = array();
		$returnArray = $this->getCategoryRootlineforTypoScript($this->gpVars['catUid'], $returnArray);

		/**
		 * Add product to rootline, if a product is displayed and showProducts
		 * is set via TS
		 */
		if ($this->mConf['showProducts'] == 1 && $this->gpVars['showUid'] > 0) {
			/** @var Tx_Commerce_Domain_Model_Product $productObject */
			$productObject = GeneralUtility::makeInstance(
				'Tx_Commerce_Domain_Model_Product', $this->gpVars['showUid'], $GLOBALS['TSFE']->sys_language_uid
			);
			$productObject->loadData();

			/** @var Tx_Commerce_Domain_Model_Category $categoryObject */
			$categoryObject = GeneralUtility::makeInstance(
				'Tx_Commerce_Domain_Model_Category', $this->gpVars['catUid'], $GLOBALS['TSFE']->sys_language_uid
			);
			$categoryObject->loadData();

			$addGetvars = $this->separator . $this->prefixId . '[showUid]=' . $productObject->getUid(
				) . $this->separator . $this->prefixId . '[catUid]=' . $categoryObject->getUid();
			if (is_string($this->gpVars['basketHashValue'])) {
				$addGetvars .= $this->separator . $this->prefixId . '[basketHashValue]=' . $this->gpVars['basketHashValue'];
			}
			$cHash = $this->generateChash($addGetvars . $GLOBALS['TSFE']->linkVars);

			/**
			 * Currentyl no Navtitle in tx_commerce_products
			 * 'nav_title' => $ProductObject->get_navtitle(),
			 */
			if ($productObject->getUid() == $this->gpVars['showUid']) {
				$itemState = 'CUR';
				$itemStateList = 'CUR,NO';
			} else {
				$itemState = 'NO';
				$itemStateList = 'NO';
			}

			$returnArray[] = array(
				'title' => $productObject->getTitle(),
				'uid' => $this->pid,
				'_ADD_GETVARS' => $addGetvars . $this->separator . 'cHash=' . $cHash,
				'ITEM_STATE' => $itemState,
				'ITEM_STATES_LIST' => $itemStateList,
				'_PAGES_OVERLAY' => $productObject->getTitle(),
			);
		}

		return $returnArray;
	}

	/**
	 * Returns an array of array for the TS rootline
	 * Recursive Call to build rootline
	 *
	 * @param integer $categoryUid
	 * @param array $result
	 * @return array
	 */
	public function getCategoryRootlineforTypoScript($categoryUid, $result = array()) {
		if ($categoryUid) {
			/** @var Tx_Commerce_Domain_Model_Category $categoryObject */
			$categoryObject = GeneralUtility::makeInstance(
				'Tx_Commerce_Domain_Model_Category', $categoryUid, $GLOBALS['TSFE']->sys_language_uid
			);
			$categoryObject->loadData();

			if (is_object($parentCategory = $categoryObject->getParentCategory())) {
				if ($parentCategory->getUid() <> $this->category->getUid()) {
					$result = $this->getCategoryRootlineforTypoScript($parentCategory->getUid(), $result);
				}
			}

			$additionalParams = $this->separator . $this->prefixId . '[showUid]=' .
				$this->separator . $this->prefixId . '[catUid]=' . $categoryObject->getUid();

			if (is_string($this->gpVars['basketHashValue'])) {
				$additionalParams .= $this->separator . $this->prefixId . '[basketHashValue]=' . $this->gpVars['basketHashValue'];
			}
			$cHash = $this->generateChash($additionalParams . $GLOBALS['TSFE']->linkVars);

			if ($this->mConf['showProducts'] == 1 && $this->gpVars['showUid'] > 0) {
				$itemState = 'NO';
			} else {
				$itemState = ($categoryObject->getUid() === $categoryUid ?
					'CUR' :
					'NO');
			}

			$result[] = array(
				'title' => $categoryObject->getTitle(),
				'nav_title' => $categoryObject->getNavtitle(),
				'uid' => $this->pid,
				'_ADD_GETVARS' => $additionalParams . $this->separator . 'cHash=' . $cHash,
				'ITEM_STATE' => $itemState,
				'_PAGES_OVERLAY' => $categoryObject->getTitle(),
			);
		}

		return $result;
	}

	/**
	 * Stores the string value $data in the 'cache_hash' table with the hash
	 * key, $hash, and visual/symbolic identification, $ident
	 * IDENTICAL to the function by same name found in t3lib_page:
	 * Usage: 2
	 *
	 * @param string $hash 32 bit hash string (eg. a md5 hash of a serialized
	 *        array identifying the data being stored)
	 * @param string $data The data string. If you want to store an array,
	 *        then just serialize it first.
	 * @param string $ident is just a textual identification in order to inform
	 *        about the content! May be 20 characters long.
	 * @return void
	 */
	public function storeHash($hash, $data, $ident) {
		$insertFields = array(
			'hash' => $hash,
			'content' => $data,
			'ident' => $ident,
			'tstamp' => time()
		);

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$database->exec_DELETEquery('cache_hash', 'hash=' . $database->fullQuoteStr($hash, 'cache_hash'));
		$database->exec_INSERTquery('cache_hash', $insertFields);
	}

	/**
	 * Retrieves the string content stored with hash key, $hash, in cache_hash
	 * IDENTICAL to the function by same name found in t3lib_page:
	 * Usage: 2
	 *
	 * @param string $hash key, 32 bytes hex
	 * @param integer $expTime represents the expire time in seconds. For instance
	 *        a value of 3600 would allow cached content within the last hour,
	 *        otherwise nothing is returned.
	 * @return string
	 */
	public function getHash($hash, $expTime = 0) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		// if expTime is not set, the hash will never expire
		$expTime = (int) $expTime;
		$whereAdd = '';
		if ($expTime) {
			$whereAdd = ' AND tstamp > ' . (time() - $expTime);
		}
		$res = $database->exec_SELECTquery(
			'content', 'cache_hash', 'hash=' . $database->fullQuoteStr($hash, 'cache_hash') . $whereAdd
		);
		if (($row = $database->sql_fetch_assoc($res))) {
			return $row['content'];
		}

		return '';
	}

	/**
	 * Merges the Array elementes of the second element into the first element
	 *
	 * @param array &$arr1
	 * @param array &$arr2
	 * @return void
	 */
	public function arrayMerge(&$arr1, &$arr2) {
		if (is_array($arr2)) {
			foreach ($arr2 as $key => $value) {
				$arr1[$key] = $value;
			}
		}
	}

	/**
	 * Generates the Rootline of a category to have the right parent elements
	 * if a category has more than one parentes
	 *
	 * @param array &$tree Menuetree
	 * @param integer $choosencat The actual category
	 * @param integer $expand If the menue has to be expanded
	 * @return array Rootline as Array
	 */
	public function getRootLine(&$tree, $choosencat, $expand) {
		$result = array();

		foreach ($tree as $key => $val) {
			if ($key == $choosencat) {
				$path = $val['path'];
				$aPath = explode(',', $path);
				$aPath = array_reverse($aPath);

				$result = $aPath;
				break;
			} else {
				if (is_array($val)) {
					if (!$val['subChildTable']) {
						break;
					}
					if ($val['--subLevel--']) {
						$path = $this->getRootLine($val['--subLevel--'], $choosencat, $expand);
						if ($path) {
							if (is_array($path)) {
								$aPath = $path;
							} else {
								$aPath = explode(',', $path);
								$aPath = array_reverse($aPath);
							}

							$result = $aPath;
							break;
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Adds the manuafacturer to the category, as simulated category
	 *
	 * @param integer $pid Page PID for the level
	 * @param integer $uidPage UidPage for the level
	 * @param string $tableMm Relation Table
	 * @param string $tableSubMain
	 * @param string $tableSubMm Sub Table Relationship
	 * @param integer $categoryUid Category ID
	 * @param integer $mDepth Menue Deepth
	 * @param string $path Path for fast resolving
	 * @return array|boolean
	 */
	public function getManufacturerAsCategory($pid, $uidPage, $tableMm, $tableSubMain, $tableSubMm, $categoryUid, $mDepth, $path) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = $database->exec_SELECTquery('*', 'tx_commerce_products_categories_mm', 'uid_foreign = ' . (int) $categoryUid);

		$productUids = array();
		while (($mmRow = $database->sql_fetch_assoc($result)) !== FALSE) {
			$productUids[] = (int) $mmRow['uid_local'];
		}

		if (!count($productUids)) {
			return FALSE;
		}

		$result = $database->exec_SELECTquery(
			'uid, manufacturer_uid', 'tx_commerce_products',
			'uid IN (' . implode(',', $productUids) . ')' . $this->cObj->enableFields('tx_commerce_products')
		);

		$outout = array();
		$firstPath = $path;
		while (($productRow = $database->sql_fetch_assoc($result)) !== FALSE) {
			if ($productRow['manufacturer_uid'] != '0') {

				/**
				 * @TODO not a realy good solution
				 */
				$path = $this->manufacturerIdentifier . $productRow['manufacturer_uid'] . ',' . $firstPath;

				/** @var Tx_Commerce_Domain_Model_Product $product */
				$product = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $productRow['uid']);
				$product->loadData();

				$manufacturerTitle = htmlspecialchars(strip_tags($product->getManufacturerTitle()));
				$addGet = $this->separator . $this->prefixId . '[catUid]=' . $categoryUid . $this->separator . $this->prefixId .
					'[manufacturer]=' . $productRow['manufacturer_uid'] . '';
				$cHash = $this->generateChash($addGet . $GLOBALS['TSFE']->linkVars);
				$addGet .= $this->separator . 'cHash=' . $cHash;
				$aLevel = array(
					'pid' => $pid,
					'uid' => $uidPage,
					'title' => $manufacturerTitle,
					'parent_id' => $categoryUid,
					'nav_title' => $manufacturerTitle,
					'hidden' => '0',
					'depth' => $mDepth,
					'leaf' => $this->isLeaf($categoryUid, $tableMm, $tableSubMm),
					'hasSubChild' => $this->hasSubChild($categoryUid, $tableSubMm),
					'subChildTable' => $tableSubMm,
					'tableSubMain' => $tableSubMain,
					'path' => $path,
					'_ADD_GETVARS' => $addGet,
					'ITEM_STATE' => 'NO',
					'manu' => $productRow['manufacturer_uid'],
				);

				if ($this->gpVars['manufacturer']) {
					$this->choosenCat = $this->manufacturerIdentifier . $this->gpVars['manufacturer'];
				}

				if ($aLevel['hasSubChild'] == 1 && $this->mConf['showProducts'] == 1) {
					$aLevel['--subLevel--'] = $this->makeSubChildArrayPostRender(
						$uidPage, $tableSubMain, $tableSubMm, $categoryUid, $mDepth + 1, $path, $productRow['manufacturer_uid']
					);
				}

				if ($this->expandAll > 0 || ($this->expandAll < 0 && ( - $this->expandAll >= $mDepth))) {
					$aLevel['_SUB_MENU'] = $aLevel['--subLevel--'];
				}

				$outout[$this->manufacturerIdentifier . $productRow['manufacturer_uid']] = $aLevel;
			}
		}

		return $outout;
	}

	/**
	 * Sorts all items of the array menu
	 *
	 * @param array &$treeArray
	 * @param string $sortType
	 * @return void
	 */
	public function sortAllMenuArray(&$treeArray, $sortType = 'alphabetiDesc') {
		if ($treeArray) {
			foreach ($treeArray as $nodeUid => $node) {
				if (is_array($node['--subLevel--'])) {
					$this->sortArrayList($treeArray[$nodeUid]['--subLevel--'], $sortType);
					$this->sortAllMenuArray($treeArray[$nodeUid]['--subLevel--'], $sortType);
				}
			}
		}
	}

	/**
	 * Sorts a list of menu items
	 *
	 * @param array &$listNodes
	 * @param string $sortType
	 * @return boolean
	 * @todo: implement sortType:alphabetiAsc,byUid, bySorting
	 */
	public function sortArrayList(&$listNodes, $sortType = 'alphabetiDesc') {
		if ($sortType == 'alphabetiDesc') {
			return uasort(
				$listNodes, function ($a, $b) {
					return strcmp(strtoupper($a['title']), strtoupper($b['title']));
				}
			);
		}

		return FALSE;
	}


	/**
	 * Adds the manuafacturer to the category, as simulated category
	 *
	 * @param integer $pid Page PID for the level
	 * @param integer $uidPage UidPage for the level
	 * @param string $mainTable Main Database Table
	 * @param string $tableMm RelationChip Table
	 * @param string $tableSubMain
	 * @param string $tableSubMm Sub Table Relationship
	 * @param integer $categoryUid Category ID
	 * @param integer $mDepth Menue Deepth
	 * @param string $path Path for fast resolving
	 * @return array|boolean
	 * @deprecated sinde commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getManufacturerAsCategory instead
	 */
	public function getManuAsCat($pid, $uidPage, $mainTable, $tableMm, $tableSubMain, $tableSubMm, $categoryUid, $mDepth, $path) {
		GeneralUtility::logDeprecatedFunction();

		return $this->getManufacturerAsCategory(
			$pid, $uidPage, $tableMm, $tableSubMain, $tableSubMm, $categoryUid, $mDepth, $path
		);
	}

	/**
	 * Returns an array of array for the TS rootline
	 * Recursive Call to buld rootline
	 *
	 * @param integer $catId
	 * @param array $result
	 * @return array
	 * @deprecated sinde commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getCategoryRootlineforTypoScript instead
	 */
	public function getCategoryRootlineforTS($catId, $result = array()) {
		GeneralUtility::logDeprecatedFunction();

		return $this->getCategoryRootlineforTypoScript($catId, $result);
	}

	/**
	 * Gets all active categories from the rootline to change the ItemState
	 *
	 * @return array of all active Categories
	 * @depricated since commerce 1.0.0, this function will be removed in commerce 1.4.0, method is no API and should be used
	 */
	public function getActiveCats() {
		GeneralUtility::logDeprecatedFunction();

		$active = array('0' => $this->catObj->getUid());
		$rootline = $this->catObj->getParentCategoriesUidlist();
		foreach ($rootline as $cat) {
			$active[] = $cat;
		}

		return $active;
	}

	/**
	 * Method for generating the rootlineMenu to use in TS
	 *
	 * @param string $content passed to method
	 * @param array $conf TS Array
	 * @return array for the menurendering of TYPO3
	 * @deprecated sinde commerce 1.0.0, this function will be removed in commerce 1.4.0, please use renderRootline instead
	 */
	public function CommerceRootline($content, $conf) {
		GeneralUtility::logDeprecatedFunction();

		return $this->renderRootline($content, $conf);
	}

	/**
	 * @param array|string $parameter
	 * @return string
	 */
	protected function generateChash($parameter) {
		/** @var \TYPO3\CMS\Frontend\Page\CacheHashCalculator $cacheHashCalculator */
		$cacheHashCalculator = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator');
		return $cacheHashCalculator->calculateCacheHash($cacheHashCalculator->getRelevantParameters($parameter));
	}
}
