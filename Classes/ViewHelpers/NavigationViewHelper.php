<?php
namespace CommerceTeam\Commerce\ViewHelpers;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use CommerceTeam\Commerce\Domain\Repository\ProductRepository;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Menu libary a navigation menu as a normal user function based on
 * categories (and products) of commerce
 * Thanks to Daniel Thomas, who build a class for his mediadb, which was
 * the basic for this class.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\Navigation
 */
class NavigationViewHelper
{
    /**
     * Commerce plugin id.
     *
     * @var string
     */
    public $prefixId = 'tx_commerce_pi1';

    /**
     * Content object renderer.
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * Active category.
     *
     * @var array
     */
    public $activeCats = [];

    /**
     * Configuration.
     *
     * @var array
     */
    public $mConf;

    /**
     * Category.
     *
     * @var int
     */
    public $cat;

    /**
     * Tree.
     *
     * @var
     */
    public $tree;

    /**
     * Module tree.
     *
     * @var
     */
    public $mTree;

    /**
     * Output.
     *
     * @var string
     */
    public $out;

    /**
     * Depth.
     *
     * @var int
     */
    public $mDepth = 2;

    /**
     * Entry category.
     *
     * @var int
     */
    public $entryCat = 0;

    /**
     * List nodes.
     *
     * @var array
     */
    public $listNodes = [];

    /**
     * Manufacturer identifier.
     *
     * @var int
     */
    public $manufacturerIdentifier = PHP_INT_MAX;

    /**
     * Use rootline information to url.
     *
     * @var int [0-1]
     */
    public $useRootlineInformationToUrl = 0;

    /**
     * Array holding the parentes of this cat as uid list.
     *
     * @var array pathParents
     */
    public $pathParents = [];

    /**
     * Translation Mode for getRecordOverlay.
     *
     * @var string
     */
    protected $translationMode = 'hideNonTranslated';

    /**
     * Default Menue Items States order by the defined Order.
     *
     * @var array
     */
    public $menueItemStates = [
        0 => 'USERDEF2',
        1 => 'USERDEF1',
        2 => 'SPC',
        3 => 'USR',
        4 => 'CURIFSUB',
        5 => 'CUR',
        6 => 'ACTIFSUB',
        7 => 'ACT',
        8 => 'IFSUB',
    ];

    /**
     * Get and post variables.
     *
     * @var array
     */
    public $gpVars = [];

    /**
     * Maximum Level for Menu, Default all PHP_INT_MAX.
     *
     * @var int
     */
    protected $maxLevel = PHP_INT_MAX;

    /**
     * Separator.
     *
     * @var string
     */
    protected $separator = '&';

    /**
     * Do not check if an element is active.
     *
     * @var bool
     */
    protected $noAct = false;

    /**
     * Choosen category.
     *
     * @var int
     */
    public $choosenCat;

    /**
     * Page id.
     *
     * @var int
     */
    public $pid;

    /**
     * Path.
     *
     * @var string
     */
    public $path;

    /**
     * Entry level.
     *
     * @var int
     */
    public $entryLevel;

    /**
     * Expand all.
     *
     * @var bool
     */
    public $expandAll;

    /**
     * Show uid.
     *
     * @var int
     */
    public $showUid;

    /**
     * Node additional fields.
     *
     * @var array
     */
    protected $nodeArrayAdditionalFields;

    /**
     * Page rootline.
     *
     * @var array
     */
    protected $pageRootline;

    /**
     * Menu type.
     *
     * @var int
     */
    protected $menuType;

    /**
     * Category object.
     *
     * @var \CommerceTeam\Commerce\Domain\Model\Category
     */
    protected $catObj;

    /**
     * Category object.
     *
     * @var \CommerceTeam\Commerce\Domain\Model\Category
     */
    protected $category;

    /**
     * Repository names with table name as key.
     *
     * @var array
     */
    protected $repositoryNames = [
        'tx_commerce_categories' => \CommerceTeam\Commerce\Domain\Repository\CategoryRepository::class,
        'tx_commerce_products' => \CommerceTeam\Commerce\Domain\Repository\ProductRepository::class,
        'tx_commerce_categories_parent_category_mm' =>
            \CommerceTeam\Commerce\Domain\Repository\CategoryRepository::class,
        'tx_commerce_products_categories_mm' => \CommerceTeam\Commerce\Domain\Repository\ProductRepository::class,
    ];

    /**
     * Repository stack.
     *
     * @var array
     */
    protected $repositoryStack = [];

    /**
     * Init Method for initialising the navigation.
     *
     * @param string $_ Content passed to method
     * @param array $conf Typoscript Array
     *
     * @return array array for the menurendering of TYPO3
     */
    public function init($_, array $conf)
    {
        $this->mConf = $this->processConf($conf);
        if ($this->mConf['useRootlineInformationToUrl']) {
            $this->useRootlineInformationToUrl = $this->mConf['useRootlineInformationToUrl'];
        }
        $this->choosenCat = $this->mConf['category'];

        $this->nodeArrayAdditionalFields = GeneralUtility::trimExplode(',', $this->mConf['additionalFields'], 0);

        $this->pid = $this->mConf['overridePid'] ?
            $this->mConf['overridePid'] :
            $this->getTypoScriptFrontendController()->id;
        $this->gpVars = GeneralUtility::_GPmerged($this->prefixId);

        \CommerceTeam\Commerce\Utility\GeneralUtility::initializeFeUserBasket();

        $this->gpVars['basketHashValue'] = $this->getBasket()->getBasketHashValue();
        $this->pageRootline = $this->getTypoScriptFrontendController()->rootLine;
        $this->menuType = $this->mConf['1'];
        $this->entryLevel = (int) $this->mConf['entryLevel'];

        if ((int) $this->mConf['noAct'] > 0) {
            $this->noAct = true;
        }

        if ((int) $this->mConf['maxLevel'] > 0) {
            $this->maxLevel = (int) $this->mConf['maxLevel'];
        }

        /*
         * Detect if a user is logged in and if he has usergroups as we have to take in
         * accout, that different usergroups may have different rights on the commerce
         * tree, so consider this we calculation the cache hash.
         */
        $usergroups = '';
        /** @noinspection PhpInternalEntityUsedInspection */
        $frontendUserData = $this->getFrontendUser()->user;

        if (is_array($frontendUserData)) {
            $usergroups = $frontendUserData['usergroup'];
        }

        $this->cat = $this->getRootCategory();
        // Define a default
        $this->choosenCat = $this->mConf['category'];

        $this->showUid = $this->gpVars['showUid'] ? (int)$this->gpVars['showUid'] : 0;
        $this->mDepth = $this->gpVars['mDepth'] ? (int)$this->gpVars['mDepth'] : 0;
        $this->path = $this->gpVars['path'] ? $this->gpVars['path'] : 0;
        $this->expandAll = $this->mConf['expandAll'] ? (int)$this->mConf['expandAll'] : 0;

        $menuErrorName = [];
        if (!($this->cat > 0)) {
            $menuErrorName[] = 'No category defined in TypoScript: lib.tx_commerce.navigation.special.category';
        }

        if (!($this->pid > 0)) {
            $menuErrorName[] = 'No OveridePID defined in TypoScript: lib.tx_commerce.navigation.special.overridePid';
        }

        if (!empty($menuErrorName)) {
            foreach ($menuErrorName as $oneError) {
                \TYPO3\CMS\Core\Utility\DebugUtility::debug($this->mConf, $oneError);
            }

            return $this->makeErrorMenu(5);
        }

        /*
         * Unique Hash for this usergroup and page to display the navigation
         */
        $hash = md5(
            'tx_commerce_navigation:' . implode('-', $this->mConf) . ':' . $usergroups . ':' .
            $this->getTypoScriptFrontendController()->linkVars . ':' . GeneralUtility::getIndpEnv('HTTP_HOST')
        );

        /*
         * Render Menue Array and store in cache, if possible
         */
        if ($this->getTypoScriptFrontendController()->no_cache) {
            // Build directly and don't sore, if no_cache=1'
            $this->mTree = $this->makeArrayPostRender(
                $this->pid,
                'tx_commerce_categories',
                'tx_commerce_categories_parent_category_mm',
                'tx_commerce_products',
                'tx_commerce_products_categories_mm',
                $this->cat,
                1,
                0,
                $this->maxLevel
            );

            /*
             * Sorting Options, there is only one type 'alphabetiDesc' :)
             * the others must to program
             *
             * @todo: implement sortType: alphabetiAsc, byUid, bySorting
             */
            if ($this->mConf['sortAllitems.']['type'] == 'alphabetiDesc') {
                $this->sortAllMenuArray($this->mTree, 'alphabetiDesc');
            }
        } else {
            $cachedMatrix = $this->getHash($hash);

            if (!empty($cachedMatrix)) {
                // User the cached version
                $this->mTree = $cachedMatrix;
            } else {
                // no cache present buld data and stor it in cache
                $this->mTree = $this->makeArrayPostRender(
                    $this->pid,
                    'tx_commerce_categories',
                    'tx_commerce_categories_parent_category_mm',
                    'tx_commerce_products',
                    'tx_commerce_products_categories_mm',
                    $this->cat,
                    1,
                    0,
                    $this->maxLevel
                );

                /*
                 * Sorting Options, there is only one type 'alphabetiDesc' :)
                 * the others must to program
                 *
                 * @todo: implement sortType: alphabetiAsc, byUid, bySorting
                 */
                if ($this->mConf['sortAllitems.']['type'] == 'alphabetiDesc') {
                    $this->sortAllMenuArray($this->mTree, 'alphabetiDesc');
                }

                $this->storeHash($hash, $this->mTree);
            }
        }

        /*
         * Finish menue array rendering, now postprocessing
         * with current status of menue
         */
        $keys = array_keys($this->mTree);

        /*
         * Detect rootline, necessary
         */
        if ($this->noAct === true) {
            $this->pathParents = [];
            $this->mDepth = 0;
        } elseif ($this->gpVars['catUid']) {
            $this->choosenCat = (int)$this->gpVars['catUid'];
        } elseif ($this->showUid) {
            /*
             * If a product is shown, we have to detect the parent category as well
             * even if wo haven't walked thrue the categories
             */
            /**
             * Product.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Product $product
             */
            $product = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Product::class,
                $this->showUid
            );
            $product->loadData();
            $this->choosenCat = $product->getMasterparentCategory();
        }

        if ($this->gpVars['path']) {
            $this->path = $this->gpVars['path'];
            $this->pathParents = explode(',', $this->path);
        } elseif ((is_numeric($this->choosenCat)) && ($this->choosenCat > 0)) {
            /**
             * Build the path by or own.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Category $category
             */
            $category = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                $this->choosenCat
            );
            $category->loadData();
            // Get the right path with custom method
            $aPath = $this->getRootLine($this->mTree, $this->choosenCat, $this->expandAll);
            if (!$aPath) {
                /*
                 * If the methode getRootLine fail, we take the path direct from the DB.
                 */
                $tmpArray = $category->getParentCategoriesUidlist();
                $this->fixPathParents($tmpArray, $this->cat);
            } else {
                $tmpArray = $aPath;
            }

            /*
             * Strip the Staring point and the value 0
             */
            if (!is_array($tmpArray)) {
                $tmpArray = [];
            }

            foreach ((array) $tmpArray as $value) {
                if (($value != $this->cat) && ($value > 0)) {
                    $this->pathParents[] = $value;
                }
            }

            if ($this->mConf['groupOptions.']['onOptions'] == 1 && $frontendUserData['usergroup'] != '') {
                $this->fixPathParents($this->pathParents, $keys[0]);
            }

            $this->pathParents = array_reverse($this->pathParents);
            if (!$this->gpVars['mDepth']) {
                $this->mDepth = count($this->pathParents);
                if ($this->gpVars['manufacturer']) {
                    ++$this->mDepth;
                }
            }
        } else {
            /*
             * If no Category is choosen by the user, so you just render the default menue
             * no rootline for the categories is needed and the depth is 0
             */
            $this->pathParents = [];
            $this->mDepth = 0;
        }

        /*
         * If we do have an entry level,
         * we strip away the number of array levels of the entry level value
         */
        if ($this->entryLevel > 0) {
            $newParentes = array_reverse($this->pathParents);

            /**
             * Foreach entry level detect the array for this level and remove
             * it from $this->mTree.
             */
            for ($i = 0; $i < $this->entryLevel; ++$i) {
                $this->mTree = $this->mTree[$newParentes[$i]]['--subLevel--'];

                /*
                 * Reduce elementes in pathParents and decrese menue depth
                 */
                array_pop($this->pathParents);
                --$this->mDepth;
            }
        }

        if ($this->pathParents) {
            $this->processArrayPostRender($this->mTree, $this->pathParents, $this->mDepth);
        }

        // never ever tough this piece of crap its needed to return an array
        return $this->mTree;
    }

    /**
     * Fix path parents.
     *
     * @param array $pathArray Path by reference
     * @param int $chosenCatUid Choosen category uid
     *
     * @return void
     */
    public function fixPathParents(array &$pathArray, $chosenCatUid)
    {
        if ($pathArray == null) {
            return;
        }

        if ($pathArray[0] != $chosenCatUid) {
            array_shift($pathArray);
            $this->fixPathParents($pathArray, $chosenCatUid);
        }
    }

    /**
     * Get root category.
     *
     * @return int
     */
    public function getRootCategory()
    {
        if ($this->mConf['groupOptions.']['onOptions'] == 1) {
            /** @noinspection PhpInternalEntityUsedInspection */
            $frontendUserData = $this->getFrontendUser()->user;
            $catOptionsCount = count($this->mConf['groupOptions.']);
            $chosenCatUid = [];
            for ($i = 1; $i <= $catOptionsCount; ++$i) {
                $chosenGroups = GeneralUtility::trimExplode(',', $this->mConf['groupOptions.'][$i . '.']['group']);
                if ($frontendUserData['usergroup'] == '') {
                    return $this->mConf['category'];
                }
                $feGroups = explode(',', $frontendUserData['usergroup']);

                foreach ($chosenGroups as $group) {
                    if (in_array($group, $feGroups) === true) {
                        if (in_array($this->mConf['groupOptions.'][$i . '.']['catUid'], $chosenCatUid) === false) {
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
     * Make menu error node.
     *
     * @param int $max Max
     * @param int $mDepth Depth
     *
     * @return array
     */
    public function makeErrorMenu($max = 5, $mDepth = 1)
    {
        $treeList = [];
        for ($i = 0; $i < $max; ++$i) {
            $node = [
                'pid' => $this->pid,
                'uid' => $i,
                'parent_id' => $i,
                'depth' => $mDepth,
                'leaf' => 1,
                'hidden' => 0,
                'title' => 'Error in the typoScript configuration.',
                'nav_title' => 'Error in the typoScript configuration.',
            ];
            $treeList[$i] = $node;
        }

        return $treeList;
    }

    /**
     * Sets the clear Function for each MenuItem.
     *
     * @param array $conf TSconfig to parse
     *
     * @return array TSConfig with ItemArrayProcFunc
     */
    public function processConf(array $conf)
    {
        $i = 1;
        foreach ($conf as $k) {
            if ($k == $i . '.') {
                $conf[$i . '.']['itemArrayProcFunc'] = self::class . '->clear';
                ++$i;
            }
        }
        $this->mDepth = $i;

        $this->separator = ini_get('arg_separator.output');

        return $conf;
    }

    /**
     * Makes the post array,which  the typo3 render Function will be work.
     *
     * @param int $uidPage Page uid
     * @param string $mainTable Main table
     * @param string $tableMm Relation table
     * @param string $tableSubMain Sub table
     * @param string $tableSubMm Sub relation table
     * @param int $uidRoot Root uid
     * @param int $mDepth Depth
     * @param int $path Path
     * @param int $maxLevel Max level
     *
     * @return array TSConfig with ItemArrayProcFunc
     */
    public function makeArrayPostRender(
        $uidPage,
        $mainTable,
        $tableMm,
        $tableSubMain,
        $tableSubMm,
        $uidRoot,
        $mDepth = 1,
        $path = 0,
        $maxLevel = PHP_INT_MAX
    ) {
        $treeList = [];
        --$maxLevel;
        if ($maxLevel < 0) {
            return [];
        }

        $sorting = ' ORDER BY ' . $mainTable . '.sorting';

        /*
         * Add hook for custom sorting
         */
        $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
            'ViewHelpers/Navigation',
            'makeArrayPostRender'
        );
        if (is_object($hookObject) && method_exists($hookObject, 'sortingOrder')) {
            $sorting = $hookObject->sortingOrder($sorting, $uidRoot, $mainTable, $tableMm, $mDepth, $path, $this);
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mainTable);
        $result = $queryBuilder
            ->select('mm.*')
            ->from($mainTable, 't')
            ->innerJoin('t', $tableMm, 'mm', 't.uid = mm.uid_local')
            ->where(
                $queryBuilder->expr()->neq(
                    'mm.uid_local',
                    $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($uidRoot, \PDO::PARAM_INT)
                )
            )
            ->orderBy(str_replace($mainTable, 't', str_replace('ORDER BY', '', $sorting)))
            ->execute();

        while ($row = $result->fetch()) {
            $nodeArray = [];
            $dataRow = $this->getDataRow($row['uid_local'], $mainTable);

            if ($dataRow['deleted'] == '0') {
                $nodeArray['CommerceMenu'] = true;
                $nodeArray['pid'] = $dataRow['pid'];
                $nodeArray['uid'] = $uidPage;
                $nodeArray['title'] = htmlspecialchars(strip_tags($dataRow['title']));
                /*
                 * Add Pages Overlay to Array, if sys language uid set
                 */
                if ($this->getTypoScriptFrontendController()->sys_language_uid) {
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
                    $nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[mDepth]=' . $mDepth .
                        $this->separator . $this->prefixId . '[path]=' . $nodeArray['path'];
                }

                if (in_array($row['uid_local'], $aCatToManu) || strtolower(trim($aCatToManu['0'])) == 'all') {
                    $nodeArray['--subLevel--'] = [];
                    $this->arrayMerge(
                        $nodeArray['--subLevel--'],
                        $this->getManufacturerAsCategory(
                            $dataRow['pid'],
                            $uidPage,
                            $tableMm,
                            $tableSubMain,
                            $tableSubMm,
                            $row['uid_local'],
                            $mDepth + 1,
                            $nodeArray['path']
                        )
                    );
                }

                if (!$nodeArray['leaf']) {
                    if (!is_array($nodeArray['--subLevel--'])) {
                        $nodeArray['--subLevel--'] = [];
                    }

                    $this->arrayMerge(
                        $nodeArray['--subLevel--'],
                        $this->makeArrayPostRender(
                            $uidPage,
                            $mainTable,
                            $tableMm,
                            $tableSubMain,
                            $tableSubMm,
                            $row['uid_local'],
                            $mDepth + 1,
                            $nodeArray['path'],
                            $maxLevel
                        )
                    );

                    if ($nodeArray['hasSubChild'] == 1 && $this->mConf['showProducts'] == 1) {
                        // @todo check if $maxLevel is correct here
                        $arraySubChild = $this->makeSubChildArrayPostRender(
                            $uidPage,
                            $tableSubMain,
                            $tableSubMm,
                            $row['uid_local'],
                            $mDepth + 1,
                            $nodeArray['path'],
                            $maxLevel
                        );

                        $this->arrayMerge($nodeArray['--subLevel--'], $arraySubChild);

                        /** @noinspection PhpInternalEntityUsedInspection */
                        $frontendUserData = $this->getFrontendUser()->user;
                        if ($this->mConf['groupOptions.']['onOptions'] == 1
                            && $frontendUserData['usergroup'] != ''
                        ) {
                            // @todo check if $maxLevel is correct here
                            $arraySubChild = $this->makeSubChildArrayPostRender(
                                $uidPage,
                                $tableSubMain,
                                $tableSubMm,
                                $row['uid_local'],
                                $mDepth + 1,
                                $nodeArray['path'],
                                $maxLevel
                            );
                            $this->arrayMerge($nodeArray['--subLevel--'], $arraySubChild);
                        }
                    }

                    if (($this->expandAll > 0) || ($this->expandAll < 0 && (-$this->expandAll >= $mDepth))) {
                        $nodeArray['_SUB_MENU'] = $nodeArray['--subLevel--'];
                    }

                    if ($this->gpVars['basketHashValue']) {
                        $nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[basketHashValue]=' .
                            $this->gpVars['basketHashValue'];
                    }

                    $nodeArray['ITEM_STATE'] = 'IFSUB';
                    $nodeArray['ITEM_STATES_LIST'] = 'IFSUB,NO';
                } else {
                    if ($nodeArray['hasSubChild'] == 2) {
                        $nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[showUid]=' .
                            $dataRow['uid'];
                        $nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[mDepth]=' . $mDepth .
                            $this->separator . $this->prefixId . '[path]=' . $nodeArray['path'];
                    }
                    if ($this->useRootlineInformationToUrl == 1) {
                        $nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[mDepth]=' . $mDepth .
                            $this->separator . $this->prefixId . '[path]=' . $nodeArray['path'];
                    }
                    if ($this->gpVars['basketHashValue']) {
                        $nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[basketHashValue]=' .
                            $this->gpVars['basketHashValue'];
                    }

                    $nodeArray['ITEM_STATE'] = 'NO';
                }

                if (strpos($nodeArray['_ADD_GETVARS'], 'showUid') === false) {
                    $nodeArray['_ADD_GETVARS'] = $this->separator . $this->prefixId . '[showUid]=' .
                        $nodeArray['_ADD_GETVARS'];
                }

                $nodeArray['_ADD_GETVARS'] .= $this->separator . 'cHash=' .
                    $this->generateChash(
                        $nodeArray['_ADD_GETVARS'] . $this->getTypoScriptFrontendController()->linkVars
                    );

                $treeList[$row['uid_local']] = $nodeArray;
            }
        }

        if ($treeList == null && $this->mConf['showProducts'] == 1) {
            // @todo check if $maxLevel is correct here
            $treeList = $this->makeSubChildArrayPostRender(
                $uidPage,
                $tableSubMain,
                $tableSubMm,
                $uidRoot,
                $mDepth,
                $path,
                $maxLevel
            );
        }

        return $treeList;
    }

    /**
     * Makes a set of  ItemMenu product list  of a category.
     *
     * @param int $pageUid Page uid
     * @param string $mainTable Main table
     * @param string $mmTable Relation table
     * @param int $categoryUid Category Uid
     * @param int $mDepth Depth
     * @param int $path Path
     * @param bool $manufacturerUid Manufacturer uid
     *
     * @return array array to be processed by HMENU
     */
    public function makeSubChildArrayPostRender(
        $pageUid,
        $mainTable,
        $mmTable,
        $categoryUid,
        $mDepth = 1,
        $path = 0,
        $manufacturerUid = false
    ) {
        $treeList = [];

        $sorting = ' ORDER BY ' . $mainTable . '.sorting ';

        /*
         * Add some hooks for custom sorting
         */
        $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
            'ViewHelpers/Navigation',
            'makeSubChildArrayPostRender'
        );
        if (is_object($hookObject) && method_exists($hookObject, 'sortingOrder')) {
            $sorting = $hookObject->sortingOrder($sorting, $categoryUid, $mainTable, $mmTable, $mDepth, $path, $this);
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mainTable);

        $queryBuilder
            ->select('mm.*')
            ->from($mainTable, 't')
            ->innerJoin('t', $mmTable, 'mm', 't.uid = mm.uid_local')
            ->where(
                $queryBuilder->expr()->neq(
                    'mm.uid_local',
                    $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->neq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($categoryUid, \PDO::PARAM_INT)
                ) // $sqlManufacturer
            )
            ->orderBy(str_replace($mainTable, 't', str_replace('ORDER BY', '', $sorting)));

        if (is_numeric($manufacturerUid)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    't.manufacturer_uid',
                    $queryBuilder->createNamedParameter($manufacturerUid, \PDO::PARAM_INT)
                )
            );
        }

        $result = $queryBuilder->execute();

        while ($row = $result->fetch()) {
            $nodeArray = [];
            $dataRow = $this->getDataRow($row['uid_local'], $mainTable);
            if ($dataRow['deleted'] == '0') {
                $nodeArray['CommerceMenu'] = true;
                $nodeArray['pid'] = $dataRow['pid'];
                $nodeArray['uid'] = $pageUid;
                $nodeArray['parent_id'] = $categoryUid;
                $nodeArray['hidden'] = $dataRow['hidden'];
                $nodeArray['title'] = htmlspecialchars(strip_tags($dataRow['title']));
                $nodeArray['nav_title'] = htmlspecialchars(strip_tags($dataRow['navtitle']));

                // Add custom Fields to array
                foreach ($this->nodeArrayAdditionalFields as $field) {
                    $nodeArray[$field] = htmlspecialchars(strip_tags($dataRow[$field]));
                }

                /*
                 * Add Pages Overlay to Array, if sys language uid set
                 */
                if ($this->getTypoScriptFrontendController()->sys_language_uid) {
                    $nodeArray['_PAGES_OVERLAY'] = htmlspecialchars(strip_tags($dataRow['title']));
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
                    $nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[mDepth]=' . $mDepth .
                        $this->separator . $this->prefixId . '[path]=' . $nodeArray['path'];
                }

                if ($this->gpVars['basketHashValue']) {
                    $nodeArray['_ADD_GETVARS'] .= $this->separator . $this->prefixId . '[basketHashValue]=' .
                        $this->gpVars['basketHashValue'];
                }

                $nodeArray['_ADD_GETVARS'] .= $this->separator . 'cHash=' .
                    $this->generateChash(
                        $nodeArray['_ADD_GETVARS'] . $this->getTypoScriptFrontendController()->linkVars
                    );

                if ($this->gpVars['manufacturer']) {
                    $nodeArray['_ADD_GETVARS'] .= '&' . $this->prefixId . '[manufacturer]=' .
                        $this->gpVars['manufacturer'];
                }

                // if this product is displayed set to CUR
                if ($mainTable == 'tx_commerce_products' && $dataRow['uid'] == $this->showUid) {
                    $nodeArray['ITEM_STATE'] = 'CUR';
                }

                $treeList[$row['uid_local']] = $nodeArray;
            }
        }

        return $treeList;
    }

    /**
     * Process the menuArray to set state for a selected item.
     *
     * @param array $treeArray Tree
     * @param array $path Path of the itemMen
     * @param int $mDepth Depth of the itemMenu
     *
     * @return void
     */
    public function processArrayPostRender(array &$treeArray, array $path = [], $mDepth = 0)
    {
        if ($this->gpVars['manufacturer']) {
            foreach ($treeArray as $val) {
                if ($val['parent_id'] == $this->choosenCat && $val['manu'] == $this->gpVars['manufacturer']) {
                    $path = GeneralUtility::trimExplode(',', $val['path']);
                }
            }
        }

        /*
         * We also store the possible States for each element, to select later,
         * which state will be set in clear
         */
        if ($mDepth != 0) {
            if ($mDepth == 1) {
                /*
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
                    /*
                     * Set CUR:
                     * a menu item if the item is the current page.
                     */
                    $treeArray[$path[0]]['ITEM_STATE'] = 'CUR';
                    $treeArray[$path[0]]['ITEM_STATES_LIST'] = 'CUR,ACT,NO';

                    /*
                     * Set IFSUB:
                     */
                    if (!empty($treeArray[$path[0]]['--subLevel--'])) {
                        $treeArray[$path[0]]['ITEM_STATE'] = 'IFSUB';
                        $treeArray[$path[0]]['ITEM_STATES_LIST'] = 'CURIFSUB,CUR,ACTIFSUB,ACT,IFSUB,NO';
                    }

                    /*
                     * Sets this node (Product) as current item
                     */
                    if ($this->showUid > 0) {
                        /*
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
                    if (!empty($treeArray[$path[0]]['--subLevel--'])) {
                        $treeArray[$path[0]]['ITEM_STATE'] = 'IFSUB';
                        $treeArray[$path[0]]['ITEM_STATES_LIST'] = 'IFSUB,NO';
                    } else {
                        $treeArray[$path[0]]['ITEM_STATE'] = 'CUR';
                        $treeArray[$path[0]]['ITEM_STATES_LIST'] = 'CUR,ACT,IFSUB,NO';
                    }
                }

                if (!empty($treeArray[$path[0]]['--subLevel--'])) {
                    $treeArray[$path[0]]['_SUB_MENU'] = $treeArray[$path[0]]['--subLevel--'];
                }
            } else {
                if (is_array($path)) {
                    if (is_array($treeArray)) {
                        $nodeId = array_pop($path);
                        $treeArray[$nodeId]['ITEM_STATE'] = 'ACT';
                        $treeArray[$nodeId]['ITEM_STATES_LIST'] = 'ACT,NO';
                        if (!empty($treeArray[$nodeId]['--subLevel--'])) {
                            $treeArray[$nodeId]['ITEM_STATE'] = 'IFSUB';
                            $treeArray[$nodeId]['ITEM_STATES_LIST'] = 'ACTIFSUB,ACT,IFSUB,NO';
                        }

                        if ($nodeId == $this->choosenCat) {
                            if (!empty($treeArray[$nodeId]['--subLevel--'])) {
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

                            if (!empty($treeArray[$nodeId]['--subLevel--'])) {
                                $treeArray[$nodeId]['ITEM_STATE'] = 'IFSUB';
                                $treeArray[$nodeId]['ITEM_STATES_LIST'] = 'CURIFSUB,CUR,ACTIFSUB,ACT,NO';
                            }
                        }
                        $this->processArrayPostRender($treeArray[$nodeId]['--subLevel--'], $path, $mDepth - 1);
                        if (!empty($treeArray[$nodeId]['--subLevel--'])) {
                            $treeArray[$nodeId]['_SUB_MENU'] = $treeArray[$nodeId]['--subLevel--'];
                        }
                    }
                }
            }
        }
    }

    /**
     * Gets the data to fill a node.
     *
     * @param int $uid Uid
     * @param string $tableName Table name
     *
     * @return array
     */
    public function getDataRow($uid, $tableName)
    {
        if (!$uid || !$tableName) {
            return [];
        }

        $row = $this->getRepository($this->repositoryNames[$tableName])->findByUid($uid);

        if ($this->getTypoScriptFrontendController()->sys_language_uid && $row) {
            $langUid = $this->getTypoScriptFrontendController()->sys_language_uid;

            /*
             * Get Overlay, if available
             */
            $row = $this->getTypoScriptFrontendController()->sys_page->getRecordOverlay(
                $tableName,
                $row,
                $langUid,
                $this->translationMode
            );
        }

        if ($this->mConf['hideEmptyCategories'] == 1 && $tableName == 'tx_commerce_categories' && is_array($row)) {
            // Process Empty Categories
            /**
             * Category.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Category $category
             */
            $category = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                $row['uid'],
                $row['sys_language_uid']
            );
            $category->loadData();
            if (!$category->hasProductsInSubCategories()) {
                return [];
            }
        }

        if ($row) {
            return $row;
        }

        return [];
    }

    /**
     * Determines if a item has no sub item.
     *
     * @param int $uid Uid
     * @param string $tableMm Relation table
     * @param string $subTableMm Sub relation table
     *
     * @return int : 0|1|2
     */
    public function isLeaf($uid, $tableMm, $subTableMm)
    {
        if (!$uid || !$tableMm) {
            return 2;
        }

        $row = $this->getRepository($this->repositoryNames[$tableMm])->findRelationByForeignUid($uid);

        $hasSubChild = $this->hasSubChild($uid, $subTableMm);
        if (!empty($row) || $hasSubChild == 1) {
            return 0;
        }

        return 1;
    }

    /**
     * Determines if an item has sub items in another table.
     *
     * @param int $uid Uid
     * @param string $tableMm Relation table
     *
     * @return int : 0|1|2
     */
    public function hasSubChild($uid, $tableMm)
    {
        if (!$uid || !$tableMm) {
            return 2;
        }

        $row = $this->getRepository($this->repositoryNames[$tableMm])->findRelationByForeignUid($uid);

        if (!empty($row)) {
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
     * pages and categories.
     *
     * @param array $menuArr Array with menu item
     * @param array $conf TSconfig, not used
     *
     * @return array return the cleaned menu item
     */
    public function clear(array $menuArr, array $conf)
    {
        if ($menuArr[0]['CommerceMenu'] != true) {
            $menuArr = [];
        }

        foreach ($menuArr as $item) {
            if ($item['DO_NOT_RENDER'] == '1') {
                $menuArr = [];
            }
        }

        if ($menuArr[0]['CommerceMenu'] == true) {
            $availiableItemStates = $conf['parentObj']->mconf;
            /*
             * @todo Try to get the expAll state from the TS Menue config and process it
             * Data from TS Menue is stored in $conf['parentObj']->mconf['expAll']
             */
            foreach (array_keys($menuArr) as $mIndex) {
                if ($menuArr[$mIndex]['ITEM_STATE'] != 'NO') {
                    if ($menuArr[$mIndex]['ITEM_STATES_LIST']) {
                        /*
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
     * Method for generating the rootlineMenu to use in TS.
     *
     * @param string $_ Passed to method
     * @param array $conf TS Array
     *
     * @return array for the menurendering of TYPO3
     */
    public function renderRootline($_, array $conf)
    {
        $this->mConf = $this->processConf($conf);
        $this->pid = $this->mConf['overridePid'] ?
            (int)$this->mConf['overridePid'] :
            $this->getTypoScriptFrontendController()->id;
        $this->gpVars = GeneralUtility::_GPmerged($this->prefixId);

        \CommerceTeam\Commerce\Utility\GeneralUtility::initializeFeUserBasket();

        $this->gpVars['basketHashValue'] = $this->getBasket()->getBasketHashValue();
        if (!is_object($this->category)) {
            $this->category = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                (int)$this->mConf['category'],
                $this->getTypoScriptFrontendController()->sys_language_uid
            );
            $this->category->loadData();
        }

        $returnArray = [];
        $returnArray = $this->getCategoryRootlineforTypoScript((int)$this->gpVars['catUid'], $returnArray);

        /*
         * Add product to rootline, if a product is displayed and showProducts
         * is set via TS
         */
        if ($this->mConf['showProducts'] == 1 && $this->gpVars['showUid'] > 0) {
            /**
             * Product.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Product $product
             */
            $product = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Product::class,
                (int)$this->gpVars['showUid'],
                $this->getTypoScriptFrontendController()->sys_language_uid
            );
            $product->loadData();

            /**
             * Category.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Category $category
             */
            $category = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                (int)$this->gpVars['catUid'],
                $this->getTypoScriptFrontendController()->sys_language_uid
            );
            $category->loadData();

            $addGetvars = $this->separator . $this->prefixId . '[showUid]=' . $product->getUid() .
                $this->separator . $this->prefixId . '[catUid]=' . $category->getUid();
            if (is_string($this->gpVars['basketHashValue'])) {
                $addGetvars .= $this->separator . $this->prefixId . '[basketHashValue]=' .
                    $this->gpVars['basketHashValue'];
            }
            $cHash = $this->generateChash($addGetvars . $this->getTypoScriptFrontendController()->linkVars);

            /*
             * Currentyl no Navtitle in tx_commerce_products
             */
            if ($product->getUid() == $this->gpVars['showUid']) {
                $itemState = 'CUR';
                $itemStateList = 'CUR,NO';
            } else {
                $itemState = 'NO';
                $itemStateList = 'NO';
            }

            $returnArray[] = [
                'uid' => $this->pid,
                '_ADD_GETVARS' => $addGetvars . $this->separator . 'cHash=' . $cHash,
                'ITEM_STATE' => $itemState,
                'ITEM_STATES_LIST' => $itemStateList,
                'title' => $product->getTitle(),
                '_PAGES_OVERLAY' => $product->getTitle(),
            ];
        }

        return $returnArray;
    }

    /**
     * Returns an array of array for the TS rootline
     * Recursive Call to build rootline.
     *
     * @param int $categoryUid Category uid
     * @param array $result Result
     *
     * @return array
     */
    public function getCategoryRootlineforTypoScript($categoryUid, array $result = [])
    {
        if ($categoryUid) {
            /**
             * Category.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Category $category
             */
            $category = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                (int)$categoryUid,
                $this->getTypoScriptFrontendController()->sys_language_uid
            );
            $category->loadData();

            if (is_object($parentCategory = $category->getParentCategory())) {
                if ($parentCategory->getUid() != $this->category->getUid()) {
                    $result = $this->getCategoryRootlineforTypoScript($parentCategory->getUid(), $result);
                }
            }

            $additionalParams = $this->separator . $this->prefixId . '[showUid]=' .
                $this->separator . $this->prefixId . '[catUid]=' . $category->getUid();

            if (is_string($this->gpVars['basketHashValue'])) {
                $additionalParams .= $this->separator . $this->prefixId . '[basketHashValue]=' .
                    $this->gpVars['basketHashValue'];
            }
            $cHash = $this->generateChash($additionalParams . $this->getTypoScriptFrontendController()->linkVars);

            if ($this->mConf['showProducts'] == 1 && $this->gpVars['showUid'] > 0) {
                $itemState = 'NO';
            } else {
                $itemState = ($category->getUid() === $categoryUid ? 'CUR' : 'NO');
            }

            $result[] = [
                'uid' => $this->pid,
                '_ADD_GETVARS' => $additionalParams . $this->separator . 'cHash=' . $cHash,
                'ITEM_STATE' => $itemState,
                'title' => $category->getTitle(),
                'nav_title' => $category->getNavtitle(),
                '_PAGES_OVERLAY' => $category->getTitle(),
            ];
        }

        return $result;
    }

    /**
     * Stores the string value $data in the 'cache_hash' table with the hash
     * key, $hash, and visual/symbolic identification, $ident
     * IDENTICAL to the function by same name found in PageRepository:
     * Usage: 2.
     *
     * @param string $hash Hash string 32 bit (eg. a md5 hash of a serialized
     *      array identifying the data being stored)
     * @param string $data The data string. If you want to store an array,
     *      then just serialize it first.
     *
     * @return void
     */
    public function storeHash($hash, $data)
    {
        /**
         * Navigation cache frontend.
         *
         * @var \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend $navigationCache
         */
        $navigationCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('commerce_navigation');
        $navigationCache->set($hash, $data);
    }

    /**
     * Retrieves the string content stored with hash key, $hash, in cache_hash
     * IDENTICAL to the function by same name found in PageRepository:
     * Usage: 2.
     *
     * @param string $hash Hash key, 32 bytes hex
     *
     * @return string
     */
    public function getHash($hash)
    {
        /**
         * Navigation cache frontend.
         *
         * @var \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend $navigationCache
         */
        $navigationCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('commerce_navigation');

        return $navigationCache->get($hash);
    }

    /**
     * Merges the Array elementes of the second element into the first element.
     *
     * @param array $arr1 Array one
     * @param array $arr2 Array two
     *
     * @return void
     */
    public function arrayMerge(array &$arr1, array &$arr2)
    {
        if (is_array($arr2)) {
            foreach ($arr2 as $key => $value) {
                $arr1[$key] = $value;
            }
        }
    }

    /**
     * Generates the Rootline of a category to have the right parent elements
     * if a category has more than one parentes.
     *
     * @param array $tree Menuetree
     * @param int $choosencat The actual category
     * @param int $expand If the menue has to be expanded
     *
     * @return array Rootline as Array
     */
    public function getRootLine(array &$tree, $choosencat, $expand)
    {
        $result = [];

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
     * Adds the manufacturer to the category, as simulated category.
     *
     * @param int $pid Page PID for the level
     * @param int $uidPage UidPage for the level
     * @param string $tableMm Relation Table
     * @param string $tableSubMain Sub table
     * @param string $tableSubMm Sub Table Relationship
     * @param int $categoryUid Category ID
     * @param int $mDepth Menu Depth
     * @param string $path Path for fast resolving
     *
     * @return array|bool
     */
    public function getManufacturerAsCategory(
        $pid,
        $uidPage,
        $tableMm,
        $tableSubMain,
        $tableSubMm,
        $categoryUid,
        $mDepth,
        $path
    ) {
        /**
         * Category repository.
         *
         * @var ProductRepository $productRepository
         */
        $productRepository = $this->getRepository($this->repositoryNames[$tableSubMm]);
        $productRelations = $productRepository->findRelationByCategoryUid($categoryUid);

        $productUids = [];
        foreach ($productRelations as $mmRow) {
            $productUids[] = $mmRow['uid_local'];
        }

        if (empty($productUids)) {
            return false;
        }

        $products = $productRepository->findByUids($productUids);

        $output = [];
        $firstPath = $path;
        foreach ($products as $productRow) {
            if ($productRow['manufacturer_uid']) {
                // @todo not a realy good solution
                $path = $this->manufacturerIdentifier . $productRow['manufacturer_uid'] . ',' . $firstPath;

                /**
                 * Product.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Product $product
                 */
                $product = GeneralUtility::makeInstance(
                    \CommerceTeam\Commerce\Domain\Model\Product::class,
                    $productRow['uid']
                );
                $product->loadData();

                $manufacturerTitle = htmlspecialchars(strip_tags($product->getManufacturerTitle()));
                $addGet = $this->separator . $this->prefixId . '[catUid]=' . $categoryUid .
                    $this->separator . $this->prefixId . '[manufacturer]=' . $productRow['manufacturer_uid'];
                $cHash = $this->generateChash($addGet . $this->getTypoScriptFrontendController()->linkVars);
                $addGet .= $this->separator . 'cHash=' . $cHash;

                $aLevel = [
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
                ];

                if ($this->gpVars['manufacturer']) {
                    $this->choosenCat = $this->manufacturerIdentifier . $this->gpVars['manufacturer'];
                }

                if ($aLevel['hasSubChild'] == 1 && $this->mConf['showProducts'] == 1) {
                    $aLevel['--subLevel--'] = $this->makeSubChildArrayPostRender(
                        $uidPage,
                        $tableSubMain,
                        $tableSubMm,
                        $categoryUid,
                        $mDepth + 1,
                        $path,
                        $productRow['manufacturer_uid']
                    );
                }

                if ($this->expandAll > 0 || ($this->expandAll < 0 && -$this->expandAll >= $mDepth)) {
                    $aLevel['_SUB_MENU'] = $aLevel['--subLevel--'];
                }

                $output[$this->manufacturerIdentifier . $productRow['manufacturer_uid']] = $aLevel;
            }
        }

        return $output;
    }

    /**
     * Sorts all items of the array menu.
     *
     * @param array $treeArray Tree
     * @param string $sortType Sort type
     *
     * @return void
     */
    public function sortAllMenuArray(array &$treeArray, $sortType = 'alphabetiDesc')
    {
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
     * Sorts a list of menu items.
     *
     * @param array $listNodes Node list
     * @param string $sortType Sorting type
     *
     * @return bool
     * @todo: implement sortType: alphabetiAsc, byUid, bySorting
     */
    public function sortArrayList(array &$listNodes, $sortType = 'alphabetiDesc')
    {
        if ($sortType == 'alphabetiDesc') {
            return uasort(
                $listNodes,
                function ($a, $b) {
                    return strcmp(strtoupper($a['title']), strtoupper($b['title']));
                }
            );
        }

        return false;
    }

    /**
     * Calculate cache hash.
     *
     * @param array|string $parameter Parameter
     *
     * @return string
     */
    protected function generateChash($parameter)
    {
        /**
         * Cache hash calculator.
         *
         * @var \TYPO3\CMS\Frontend\Page\CacheHashCalculator $cacheHashCalculator
         */
        $cacheHashCalculator = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\CacheHashCalculator::class);

        return $cacheHashCalculator->calculateCacheHash($cacheHashCalculator->getRelevantParameters($parameter));
    }

    /**
     * Get repository by name.
     *
     * @param string $repositoryName Repository name
     *
     * @return mixed
     */
    protected function getRepository($repositoryName)
    {
        if (!isset($this->repositoryStack[$repositoryName])) {
            $this->repositoryStack[$repositoryName] = GeneralUtility::makeInstance($repositoryName);
        }

        return $this->repositoryStack[$repositoryName];
    }


    /**
     * Get typoscript frontend controller.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Get frontend user.
     *
     * @return \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
     */
    protected function getFrontendUser()
    {
        return $this->getTypoScriptFrontendController()->fe_user;
    }

    /**
     * Get basket.
     *
     * @return \CommerceTeam\Commerce\Domain\Model\Basket
     */
    protected function getBasket()
    {
        return \CommerceTeam\Commerce\Utility\GeneralUtility::getBasket();
    }
}
