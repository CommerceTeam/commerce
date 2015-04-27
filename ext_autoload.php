<?php

$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('commerce');
$classPath = $extensionPath . 'Classes/';
$typo3Path = PATH_typo3;

return array(
	/* backend controller */
	'tx_commerce_controller_wizardcontroller' => $classPath . 'Controller/WizardController.php',

	/* frontend controller */
	'tx_commerce_controller_basecontroller' => $classPath . 'Controller/BaseController.php',
	'tx_commerce_controller_addressescontroller' => $classPath . 'Controller/AddressesController.php',
	'tx_commerce_controller_basketcontroller' => $classPath . 'Controller/BasketController.php',
	'tx_commerce_controller_checkoutcontroller' => $classPath . 'Controller/CheckoutController.php',
	'tx_commerce_controller_invoicecontroller' => $classPath . 'Controller/InvoiceController.php',
	'tx_commerce_controller_listcontroller' => $classPath . 'Controller/ListController.php',

	/* data access objects */
	'tx_commerce_dao_addressdao' => $classPath . 'Dao/AddressDao.php',
	'tx_commerce_dao_addressdaomapper' => $classPath . 'Dao/AddressDaoMapper.php',
	'tx_commerce_dao_addressdaoobject' => $classPath . 'Dao/AddressDaoObject.php',
	'tx_commerce_dao_addressdaoparser' => $classPath . 'Dao/AddressDaoParser.php',
	'tx_commerce_dao_addressobserver' => $classPath . 'Dao/AddressObserver.php',
	'tx_commerce_dao_basicdao' => $classPath . 'Dao/BasicDao.php',
	'tx_commerce_dao_basicdaomapper' => $classPath . 'Dao/BasicDaoMapper.php',
	'tx_commerce_dao_basicdaoobject' => $classPath . 'Dao/BasicDaoObject.php',
	'tx_commerce_dao_basicdaoparser' => $classPath . 'Dao/BasicDaoParser.php',
	'tx_commerce_dao_feuserdao' => $classPath . 'Dao/FeuserDao.php',
	'tx_commerce_dao_feuserdaomapper' => $classPath . 'Dao/FeuserDaoMapper.php',
	'tx_commerce_dao_feuserdaoobject' => $classPath . 'Dao/FeuserDaoObject.php',
	'tx_commerce_dao_feuserdaoparser' => $classPath . 'Dao/FeuserDaoParser.php',
	'tx_commerce_dao_feuserobserver' => $classPath . 'Dao/FeuserObserver.php',
	'tx_commerce_dao_feuseraddressfieldmapper' => $classPath . 'Dao/FeuserAddressFieldmapper.php',

	/* domain models */
	'tx_commerce_domain_model_abstractentity' => $classPath . 'Domain/Model/AbstractEntity.php',
	'tx_commerce_domain_model_article' => $classPath . 'Domain/Model/Article.php',
	'tx_commerce_domain_model_articleprice' => $classPath . 'Domain/Model/ArticlePrice.php',
	'tx_commerce_domain_model_attribute' => $classPath . 'Domain/Model/Attribute.php',
	'tx_commerce_domain_model_attributevalue' => $classPath . 'Domain/Model/AttributeValue.php',
	'tx_commerce_domain_model_basicbasket' => $classPath . 'Domain/Model/BasicBasket.php',
	'tx_commerce_domain_model_basket' => $classPath . 'Domain/Model/Basket.php',
	'tx_commerce_domain_model_basketitem' => $classPath . 'Domain/Model/BasketItem.php',
	'tx_commerce_domain_model_category' => $classPath . 'Domain/Model/Category.php',
	'tx_commerce_domain_model_product' => $classPath . 'Domain/Model/Product.php',

	/* domain repository */
	'tx_commerce_domain_repository_repository' => $classPath . 'Domain/Repository/Repository.php',
	'tx_commerce_domain_repository_articlerepository' => $classPath . 'Domain/Repository/ArticleRepository.php',
	'tx_commerce_domain_repository_articlepricerepository' => $classPath . 'Domain/Repository/ArticlePriceRepository.php',
	'tx_commerce_domain_repository_attributerepository' => $classPath . 'Domain/Repository/AttributeRepository.php',
	'tx_commerce_domain_repository_attributevaluerepository' => $classPath . 'Domain/Repository/AttributeValueRepository.php',
	'tx_commerce_domain_repository_categoryrepository' => $classPath . 'Domain/Repository/CategoryRepository.php',
	'tx_commerce_domain_repository_folderrepository' => $classPath . 'Domain/Repository/FolderRepository.php',
	'tx_commerce_domain_repository_productrepository' => $classPath . 'Domain/Repository/ProductRepository.php',

	/* payment */
	'tx_commerce_payment_paymentabstract' => $classPath . 'Payment/PaymentAbstract.php',
	'tx_commerce_payment_abstract' => $classPath . 'Payment/PaymentAbstract.php',
	'tx_commerce_payment_cashondelivery' => $classPath . 'Payment/Cashondelivery.php',
	'tx_commerce_payment_ccvs' => $classPath . 'Payment/Ccvs.php',
	'tx_commerce_payment_creditcard' => $classPath . 'Payment/Creditcard.php',
	'tx_commerce_payment_debit' => $classPath . 'Payment/Debit.php',
	'tx_commerce_payment_invoice' => $classPath . 'Payment/Invoice.php',
	'tx_commerce_payment_prepayment' => $classPath . 'Payment/Prepayment.php',
	'tx_commerce_payment_payment' => $classPath . 'Payment/Payment.php',
	'tx_commerce_payment_wirecard' => $classPath . 'Payment/Wirecard.php',
	'creditcardvalidationsolution' => $classPath . 'Payment/Ccvs/CreditCardValidationSolution.php',
	'tx_commerce_payment_criterion_criterionabstract' => $classPath . 'Payment/Criterion/CriterionAbstract.php',
	'tx_commerce_payment_criterion_abstract' => $classPath . 'Payment/Criterion/CriterionAbstract.php',
	'tx_commerce_payment_criterion_providercriterionabstract' => $classPath . 'Payment/Criterion/ProviderCriterionAbstract.php',
	'tx_commerce_payment_provider_criterion_abstract' => $classPath . 'Payment/Criterion/ProviderCriterionAbstract.php',
	'tx_commerce_payment_interface_criterion' => $classPath . 'Payment/Interface/Criterion.php',
	'tx_commerce_payment_interface_payment' => $classPath . 'Payment/Interface/Payment.php',
	'tx_commerce_payment_interface_provider' => $classPath . 'Payment/Interface/Provider.php',
	'tx_commerce_payment_interface_providercriterion' => $classPath . 'Payment/Interface/ProviderCriterion.php',
	'tx_commerce_payment_provider_providerabstract' => $classPath . 'Payment/Provider/ProviderAbstract.php',
	'tx_commerce_payment_provider_abstract' => $classPath . 'Payment/Provider/ProviderAbstract.php',
	'tx_commerce_payment_provider_wirecard' => $classPath . 'Payment/Provider/Wirecard.php',

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
