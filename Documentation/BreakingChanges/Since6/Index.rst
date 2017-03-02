.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Since Version 6
===============

Removed Files
-------------

Hooks/SrfeuserregisterPi1Hook.php the commerce team does not advocates to use this extension and the hook if here by removed.


Removed functions
-----------------

BaseController::getDatabaseConnection removed as not used in any controller anymore
OrderEditFunc::getDatabaseConnection all queries are replaced with repository calls
ArticleHook::getDatabaseConnection all queries are replaced with repository calls
AbstractRepository::getDatabaseConnection all queries are migrated to doctrine dbal queryBuilder
BasicBasket::getDatabaseConnection moved to Basket::getDatabaseConnection as it is only used in that class
AbstractEntity::getDatabaseConnection to Product::getDatabaseConnection as it is only used in that class

FolderRepository::getFolders should not be used anymore, please use ::getFolder instead
BaseController::renderValue.NUMBERFORMAT this type of formating is possible via stdWrap.numberFormat and takes the configuration decimals, dec_point, thousands_sep so just replace NUMBERFORMAT with stdWrap.numberFormat = 1 and add stdWrap.numberFormat.deci... and so on
BaseController::makeArticleView added method_exists before calling it as this method is only used in ListController
BaseController::renderSingleView becauset it is only implemented and called in ListController

Removed parameter
-----------------

FolderRepository::initFolders $parentTitle
FolderRepository::initFolders $executeUpdateUtility
AddressObserver::update $_ removed the unused parameter. Only the uid will be accepted.
FeuserObserver::update $_ removed the unused parameter. Only the uid will be accepted.


Remove properties
-----------------

ListController::product_array unused property. Replace with ListController::product->returnAssocArray()


Changed methods
---------------

Ccvs::validateCreditCard now respects the parameter of the super method. To be able to check with a checksum please use Ccvs::validate instead
FolderRepository::initFolders order introduced in 5.0 is now the default and no fallback handling will be available anymore.

Changed hooks
-------------
Domain/Repository/CategoryRepository::getChildProducts ->productQueryPreHook first Parameter changed from $queryArray to $queryBuilder all changes made to the different array keys need to be changed to modify the queryBuilder