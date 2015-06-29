<?php

$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('commerce');
$classPath = $extensionPath . 'Classes/';
$typo3Path = PATH_typo3;

return array(
	/* task */
	'tx_commerce_task_statistictask' => $classPath . 'Task/StatisticTask.php',
	'tx_commerce_task_statistictaskadditionalfieldprovider' => $classPath . 'Task/StatisticTaskAdditionalFieldProvider.php',

	/* tree */
	'tx_commerce_tree_browsetree' => $classPath . 'Tree/Browsetree.php',
	'tx_commerce_tree_categorymounts' => $classPath . 'Tree/CategoryMounts.php',
	'tx_commerce_tree_categorytree' => $classPath . 'Tree/CategoryTree.php',
	'tx_commerce_tree_ordertree' => $classPath . 'Tree/OrderTree.php',
	'tx_commerce_tree_statistictree' => $classPath . 'Tree/StatisticTree.php',
	'tx_commerce_tree_leaf_article' => $classPath . 'Tree/Leaf/Article.php',
	'tx_commerce_tree_leaf_base' => $classPath . 'Tree/Leaf/Base.php',
	'tx_commerce_tree_leaf_leaf' => $classPath . 'Tree/Leaf/Leaf.php',
	'tx_commerce_tree_leaf_data' => $classPath . 'Tree/Leaf/Data.php',
	'tx_commerce_tree_leaf_master' => $classPath . 'Tree/Leaf/Master.php',
	'tx_commerce_tree_leaf_masterdata' => $classPath . 'Tree/Leaf/MasterData.php',
	'tx_commerce_tree_leaf_slave' => $classPath . 'Tree/Leaf/Slave.php',
	'tx_commerce_tree_leaf_slavedata' => $classPath . 'Tree/Leaf/SlaveData.php',
	'tx_commerce_tree_leaf_view' => $classPath . 'Tree/Leaf/View.php',
	'tx_commerce_tree_leaf_mounts' => $classPath . 'Tree/Leaf/Mounts.php',
	'tx_commerce_tree_leaf_articledata' => $classPath . 'Tree/Leaf/ArticleData.php',
	'tx_commerce_tree_leaf_articleview' => $classPath . 'Tree/Leaf/ArticleView.php',
	'tx_commerce_tree_leaf_category' => $classPath . 'Tree/Leaf/Category.php',
	'tx_commerce_tree_leaf_categorydata' => $classPath . 'Tree/Leaf/CategoryData.php',
	'tx_commerce_tree_leaf_categoryview' => $classPath . 'Tree/Leaf/CategoryView.php',
	'tx_commerce_tree_leaf_product' => $classPath . 'Tree/Leaf/Product.php',
	'tx_commerce_tree_leaf_productdata' => $classPath . 'Tree/Leaf/ProductData.php',
	'tx_commerce_tree_leaf_productview' => $classPath . 'Tree/Leaf/ProductView.php',

	/* utilities */
	'tx_commerce_utility_articlecreatorutility' => $classPath . 'Utility/ArticleCreatorUtility.php',
	'tx_commerce_utility_attributeeditorutility' => $classPath . 'Utility/AttributeEditorUtility.php',
	'tx_commerce_utility_backendutility' => $classPath . 'Utility/BackendUtility.php',
	'tx_commerce_utility_datahandlerutility' => $classPath . 'Utility/DataHandlerUtility.php',
	'tx_commerce_utility_generalutility' => $classPath . 'Utility/GeneralUtility.php',
	'tx_commerce_utility_folderutility' => $classPath . 'Utility/FolderUtility.php',
	'tx_commerce_utility_statisticsutility' => $classPath . 'Utility/StatisticsUtility.php',
	'tx_commerce_utility_tceformsutility' => $classPath . 'Utility/TceformsUtility.php',
	'tx_commerce_utility_updateutility' => $classPath . 'Utility/UpdateUtility.php',

	/* ViewHelpers */
	'tx_commerce_viewhelpers_categoryrecordlist' => $classPath . 'ViewHelpers/CategoryRecordList.php',
	'tx_commerce_viewhelpers_feuserrecordlist' => $classPath . 'ViewHelpers/FeuserRecordList.php',
	'tx_commerce_viewhelpers_navigation' => $classPath . 'ViewHelpers/Navigation.php',
	'tx_commerce_viewhelpers_orderrecordlist' => $classPath . 'ViewHelpers/OrderRecordList.php',
	'tx_commerce_viewhelpers_tcefunc' => $classPath . 'ViewHelpers/TceFunc.php',
	'tx_commerce_viewhelpers_treelibbrowser' => $classPath . 'ViewHelpers/TreelibBrowser.php',
	'tx_commerce_viewhelpers_treelibtceforms' => $classPath . 'ViewHelpers/TreelibTceforms.php',
	'tx_commerce_viewhelpers_attributeeditfunc' => $classPath . 'ViewHelpers/AttributeEditFunc.php',
	'tx_commerce_viewhelpers_ordereditfunc' => $classPath . 'ViewHelpers/OrderEditFunc.php',
	'tx_commerce_viewhelpers_money' => $classPath . 'ViewHelpers/Money.php',
	'tx_commerce_viewhelpers_browselinks_categorytree' => $classPath . 'ViewHelpers/Browselinks/CategoryTree.php',
	'tx_commerce_viewhelpers_browselinks_categoryview' => $classPath . 'ViewHelpers/Browselinks/CategoryView.php',
	'tx_commerce_viewhelpers_browselinks_productview' => $classPath . 'ViewHelpers/Browselinks/ProductView.php',
	'tx_commerce_viewhelpers_navigation_accessviewhelper' => $classPath . 'ViewHelpers/Navigation/AccessViewHelper.php',
	'tx_commerce_viewhelpers_navigation_categoryviewhelper' => $classPath . 'ViewHelpers/Navigation/CategoryViewHelper.php',
	'tx_commerce_viewhelpers_navigation_ordersviewhelper' => $classPath . 'ViewHelpers/Navigation/OrdersViewHelper.php',
	'tx_commerce_viewhelpers_navigation_statisticviewhelper' => $classPath . 'ViewHelpers/Navigation/StatisticViewHelper.php',
	'tx_commerce_viewhelpers_navigation_systemdataviewhelper' => $classPath . 'ViewHelpers/Navigation/SystemdataViewHelper.php',

	'tx_staticinfotables_pi1' =>
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('static_info_tables') . 'pi1/class.tx_staticinfotables_pi1.php',
);
