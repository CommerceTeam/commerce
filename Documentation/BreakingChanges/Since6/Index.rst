.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Since Version 6
===============

Removed Files
-------------

WizardController.php This class is not needed anymore because specific allowed tables are controlled via page typoscript config now.
Hooks/SrfeuserregisterPi1Hook.php the commerce team does not advocates to use this extension and the hook if here by removed.


Removed methods
---------------

BaseController::getDatabaseConnection removed as not used in any controller anymore
OrderEditViewhelper::getDatabaseConnection all queries are replaced with repository calls
ArticleHook::getDatabaseConnection all queries are replaced with repository calls
AbstractRepository::getDatabaseConnection all queries are migrated to doctrine dbal queryBuilder
BasicBasket::getDatabaseConnection moved to Basket::getDatabaseConnection as it is only used in that class
AbstractEntity::getDatabaseConnection to Product::getDatabaseConnection as it is only used in that class
BackendUserUtility::getDatabaseConnection query was moved to BackendUsergroupRepository
Basket::getDatabaseConnection querie were moved to BasketRepository
Commands::getDatabaseConnection query was moved to SysDomainRepository
TcaAttributeFields::getDatabaseConnection queries where moved to AttributeRepository
TcaSelectItems::getDatabaseConnection queries where moved to CategoryRepository
BackendUtility::getDatabaseConnection queries where moved to corresponding repositories
TceformsUtility::getDatabaseConnection query was moved to SysLanguageRepository and ProductRepository
StatisticTask::getDatabaseConnection queries where moved to corresponding repositories
OrdermailHook::getDatabaseConnection queries where moved to AddressRepository and MoveOrderMailRepository
CommerceLinkHandler::getDatabaseConnection query was moved to ProductRepository
DatabaseRowArticleData::getDatabaseConnection query was moved to SysRefindexRepository
DataMapHook::getDatabaseConnection queries where moved to corresponding repositories
BasicDaoMapper::getDatabaseConnection queries where moved to corresponding repositories
StatisticsUtility::getDatabaseConnection queries where moved to corresponding repositories
CategoryRecordList::getDatabaseConnection queries where changed to use queryBuilder
OrderRecordList::getDatabaseConnection queries where changed to use queryBuilder
UpdateUtility::getDatabaseConnection queries where moved to corresponding repositories
DataProvider::getDatabaseConnection queries where changed to use queryBuilder
NavigationViewHelper::getDatabaseConnection queries where changed to use queryBuilder
DisplayConditionUtility::getDatabaseConnection queries where moved to ProductRepository
Product::getAttributeMatrixQuery queries where moved to corresponding repositories
Product::getDatabaseConnection queries where moved to corresponding repositories

FolderRepository::getFolders should not be used anymore, please use ::getFolder instead
BaseController::renderValue.NUMBERFORMAT this type of formating is possible via stdWrap.numberFormat and takes the configuration decimals, dec_point, thousands_sep so just replace NUMBERFORMAT with stdWrap.numberFormat = 1 and add stdWrap.numberFormat.deci... and so on
BaseController::makeArticleView added method_exists before calling it as this method is only used in ListController
BaseController::renderSingleView because it is only implemented and called in ListController
OrderEditViewhelper::articleOrderId changed field to input and readOnly solves the same purpose
OrderEditViewhelper::crdate there is no need to render it like this. TCA was modified to use core means.
OrderEditViewhelper::sumPriceGrossFormat not needed anymore replaced with custom eval


Removed parameter
-----------------

FolderRepository::initFolders $parentTitle
FolderRepository::initFolders $executeUpdateUtility
AddressObserver::update $_ removed the unused parameter. Only the uid will be accepted.
FeuserObserver::update $_ removed the unused parameter. Only the uid will be accepted.
OrderEditViewhelper::address $_ removed the unused parameter.


Remove properties
-----------------

ListController::product_array unused property. Replace with ListController::product->returnAssocArray()


Renamed classes
---------------

OrderEditFunc renamed to OrderEditViewhelper


Changed methods
---------------

Ccvs::validateCreditCard now respects the parameter of the super method. To be able to check with a checksum please use Ccvs::validate instead
FolderRepository::initFolders order introduced in 5.0 is now the default and no fallback handling will be available anymore.

Changed hooks
-------------

Domain/Repository/CategoryRepository::getChildProducts ->productQueryPreHook first Parameter changed from $queryArray to $queryBuilder all changes made to the different array keys need to be changed to modify the queryBuilder

Changed rendering
-----------------

BaseController::renderValue FILES changed to ignore $value and imgFolder. This was necessary to be able to render FAL files. This change also removed allStdWrap and linkStdWrap. Please have a look at TypoScript lib.tx_commerce.stdImage to see how images get rendered now.
