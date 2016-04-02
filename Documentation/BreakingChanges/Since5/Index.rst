.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Since Version 5
===============


Removed database fields
-----------------------

tx_commerce_products.articleslok This field is not needed anymore. It was previously to have only the edit articles but
not the create articles flexform part in translated products. As of TYPO3 7 its possible to have displayCond on flexform
sections. By this no separate flexforms are needed anymore.


Removed Files
-------------

- Both the ClassAliasMap.php and LegacyClassesForIde.php. If you need still need to know which class was renamed into
which need name, please copy it from the 4.0.0 tag.
Classes/Utility/FolderUtility.php methods are moved to FolderRepository
Classes/ViewHelpers/AttributeEditFunc.php
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
Configuration/DCA/Articles.php
Configuration/DCA/Categories.php
Configuration/DCA/Products.php


Removed constansts
------------------

- PATH_TXCOMMERCE_ICON_TREE_REL as it was used only in one path.
- PATH_TXCOMMERCE_ICON_TABLE_REL as it was replaced in the TCA with EXT:commerce... pathes
- PATH_TXCOMMERCE as it was used only in 3 occasions
- PATH_TXCOMMERCE_REL is not used anymore


Removed functions
-----------------

\CommerceTeam\Commerce\Utility\BackendUtility::deleteL18n
\CommerceTeam\Commerce\Utility\BackendUtility::getArticlesOfProduct replaced with ArticleRepository::findByProductUid
\CommerceTeam\Commerce\Utility\BackendUtility::getArticlesOfProductAsUidList replaced with ArticleRepository::findUidsByProductUid
\CommerceTeam\Commerce\Utility\BackendUtility::getAttributeData replaced with AttributeRepository::findByUid
\CommerceTeam\Commerce\Utility\BackendUtility::getAttributesForCategory replaced with CategoryRepository::findAttributesByCategoryUid
\CommerceTeam\Commerce\Utility\BackendUtility::getAttributeTitle
\CommerceTeam\Commerce\Utility\BackendUtility::getAttributeTitles
\CommerceTeam\Commerce\Utility\BackendUtility::getAttributeValue
\CommerceTeam\Commerce\Utility\BackendUtility::getCategoryData replaced with CategoryRepository::findByUid
\CommerceTeam\Commerce\Utility\BackendUtility::getCategoriesForProductFromDb
\CommerceTeam\Commerce\Utility\BackendUtility::getOrderFolderUid replaced with FolderRepository::initFolders('Orders', FolderRepository::initFolders())
\CommerceTeam\Commerce\Utility\BackendUtility::getOverwriteData
\CommerceTeam\Commerce\Utility\BackendUtility::getProductFolderUid
\CommerceTeam\Commerce\Utility\BackendUtility::getProductOfArticle replaced with ProductRepository::findByArticleUid
\CommerceTeam\Commerce\Utility\BackendUtility::getProductParentCategories
\CommerceTeam\Commerce\Utility\BackendUtility::isAjaxRequest
\CommerceTeam\Commerce\Utility\BackendUtility::isNumber replaced with \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger
\CommerceTeam\Commerce\Utility\BackendUtility::overwriteArticle
\CommerceTeam\Commerce\Utility\BackendUtility::overwriteProduct
\CommerceTeam\Commerce\Domain\Repository\OrderRepository::updateByUid replaced with ::updateRecord


Changed modules
---------------

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
---------------------

Product categories changed from type passthrough to select
Categories parent_category changed from type passthrough to select


Changed class
-------------

\CommerceTeam\Commerce\Domain\Repository\AbstractRepository is now abstract to make it impossible to instantiate this base
repository class


Changed methods
---------------

- BackendUtility::getAttributesForCategoryList had 3 parameters from which three were not used in commerce context. The not needed parameter are removed.

- These two changes enables making better use of default values
  \CommerceTeam\Commerce\Domain\Repository\FolderRepository::initFolders changed the order of the parameters
  \CommerceTeam\Commerce\Domain\Repository\FolderRepository::createFolder changed the order of the parameters

- Repository::enableFields made protected to only allow access from inside of an repository that extends this base repository
  The showHiddenRecords was changed from -1 to 0 as -1 matches a boolean true which is not what was intended
  In addition the parameter $showHiddenRecords and $ass where swapped to make better use of default values

- Repository::getAttributes was moved to ArticleRepository and ProductRepository as they are only used in this context.
  In ProductRepository it returns an array of uids.
  In ArticleRepository it returns an array of attributes with all data.


Renamed classes
---------------

\CommerceTeam\Commerce\Domain\Repository\Repository to \CommerceTeam\Commerce\Domain\Repository\AbstractRepository
\CommerceTeam\Commerce\ViewHelpers\Navigation to \CommerceTeam\Commerce\ViewHelpers\NavigationViewHelper


Renamed functions
-----------------

\CommerceTeam\Commerce\Utility\BackendUtility::mergeAttributeListFromFFData to mergeAttributeListFromFlexFormData


Renamed tables
--------------

tx_commerce_articles_article_attributes_mm to tx_commerce_articles_attributes_mm
The update script takes care of renaming existing relation tables when executed in the extension manager.
