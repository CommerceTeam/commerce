.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


Breaking Changes
================

Since Version 4
---------------

Remove of iframe rendering the category tree
____________________________________________

As the div rendering mode is stable the iframe rendering gets removed.
Its not needed anymore and is discouraged due to reduce the entry points in total.

Removed file
____________
Classes/ViewHelpers/IframeTreeBrowser.php

Removed constansts
__________________
key, replacement
COMMERCE_EXTkey, removed
COMMERCE_EXTKEY, removed
PATH_txcommerce, PATH_TXCOMMERCE
PATH_txcommerce_rel, PATH_TXCOMMERCE_REL
PATH_txcommerce_icon_table_rel, PATH_TXCOMMERCE_ICON_TABLE_REL
PATH_txcommerce_icon_tree_rel, PATH_TXCOMMERCE_ICON_TREE_REL
NORMALArticleType, NORMALARTICLETYPE
PAYMENTArticleType, PAYMENTARTICLETYPE
DELIVERYArticleType, DELIVERYARTICLETYPE

Renamed module key
__________________
Since 4.x the key of the main module of commerce isn't **txcommerceM1** but **commerce**

Changed configuration
_____________________
Replaced
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['extConf']
	with
	$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']


Since Version 5
---------------

Removed Files
_____________
- Both the ClassAliasMap.php and LegacyClassesForIde.php. If you need still need to know which class was renamed into
which need name, please copy it from the 4.0.0 tag.
Classes/ViewHelpers/FeuserRecordList.php
Classes/ViewHelpers/TceFunc.php
Classes/ViewHelpers/TreelibBrowser.php
Classes/ViewHelpers/TreelibTceforms.php
Classes/ViewHelpers/Browselinks/ProductView.php
Classes/ViewHelpers/Browselinks/CategoryTree.php
Classes/ViewHelpers/Browselinks/CategoryView.php
Classes/Tree/Browsetree.php
Classes/Tree/CategoryMounts.php
Classes/Tree/CategoryTree.php
Classes/Tree/OrderTree.php
Classes/Tree/StatisticTree.php
Classes/Tree/Leaf/Master.php
Classes/Tree/Leaf/Leaf.php
Classes/Tree/Leaf/Slave.php
Classes/Tree/Leaf/Article.php
Classes/Tree/Leaf/ArticleData.php
Classes/Tree/Leaf/ArticleView.php
Classes/Tree/Leaf/Base.php
Classes/Tree/Leaf/Category.php
Classes/Tree/Leaf/CategoryData.php
Classes/Tree/Leaf/CategoryView.php
Classes/Tree/Leaf/Data.php
Classes/Tree/Leaf/MasterData.php
Classes/Tree/Leaf/Mounts.php
Classes/Tree/Leaf/Product.php
Classes/Tree/Leaf/ProductData.php
Classes/Tree/Leaf/ProductView.php
Classes/Tree/Leaf/SlaveData.php
Classes/Tree/Leaf/View.php

Removed constansts
__________________
- PATH_TXCOMMERCE_ICON_TREE_REL as it was used only in one path.
- PATH_TXCOMMERCE_ICON_TABLE_REL as it was replaced in the TCA with EXT:commerce... pathes
- PATH_TXCOMMERCE as it was used only in 3 occasions
- PATH_TXCOMMERCE_REL is not used anymore

Changed modules
_______________
All modules are now using the core ModuleTemplate api.

FolderRepository::initFolders
 - now returns only the pid as integer of for the parameter given not the parents in an array.
 - does not create folders magicaly anymore. Please create it using FolderRepository::createFolder.
 - does not call the update utility anymore. Please call it on your own.
 - parameter $module and $pid changes position in method call. Fallback handling will be dropped in version 6

The basket is now a singleton object and not attached to the feuser object anymore. As long as you
use the api method \CommerceTeam\Commerce\Utility\GeneralUtility::getBasket() you dont need to change
your code.

Drop overwrite action from clickmenu. It does not make sense and only leads to strange side effects.
Drop DataHandlerUtility as it was only used for overwrite action.

In domain models the databaseClass is renamed into repositoryClass
In AbstractEntity getDatabaseConnection() got renamed into getRepository() to make match what it returns and a
new getDatabaseConnection() is put instead to return the TYPO3 databaseConnection.

Changed configuration
_____________________
Product categories changed from type passthrough to select
Categories parent_category changed from type passthrough to select