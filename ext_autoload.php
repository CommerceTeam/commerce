<?php

$extensionPath = t3lib_extMgm::extPath('commerce');
$classPath = $extensionPath . 'Classes/';

return array(
	/* backend controller */
	'tx_commerce_controller_accesscontroller' => $classPath . 'Controller/AccessController.php',
	'sc_mod_access_perm_index' => $classPath . 'Controller/AccessController.php',
	'tx_commerce_controller_categoriescontroller' => $classPath . 'Controller/CategoriesController.php',
	'tx_commerce_categories' => $classPath . 'Controller/CategoriesController.php',
	'tx_commerce_controller_orderscontroller' => $classPath . 'Controller/OrdersController.php',
	'tx_commerce_orders' => $classPath . 'Controller/OrdersController.php',
	'tx_commerce_controller_statisticcontroller' => $classPath . 'Controller/StatisticController.php',
	'tx_commerce_statistic' => $classPath . 'Controller/StatisticController.php',
	'tx_commerce_controller_systemdatacontroller' => $classPath . 'Controller/SystemdataController.php',
	'tx_commerce_systemdata' => $classPath . 'Controller/SystemdataController.php',

	/* frontend controller */
	'tx_commerce_controller_addressescontroller' => $classPath . 'Controller/AddressesController.php',
	'tx_commerce_pi4' => $classPath . 'Controller/AddressesController.php',
	'tx_commerce_controller_basketcontroller' => $classPath . 'Controller/BasketController.php',
	'tx_commerce_pi2' => $classPath . 'Controller/BasketController.php',
	'tx_commerce_controller_checkoutcontroller' => $classPath . 'Controller/CheckoutController.php',
	'tx_commerce_pi3' => $classPath . 'Controller/CheckoutController.php',
	'tx_commerce_controller_invoicecontroller' => $classPath . 'Controller/InvoiceController.php',
	'tx_commerce_pi6' => $classPath . 'Controller/InvoiceController.php',
	'tx_commerce_controller_listcontroller' => $classPath . 'Controller/ListController.php',
	'tx_commerce_pi1' => $classPath . 'Controller/ListController.php',

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
	'tx_commerce_article' => $classPath . 'Domain/Model/class.tx_commerce_article.php',
	'tx_commerce_article_price' => $classPath . 'Domain/Model/class.tx_commerce_article_price.php',
	'tx_commerce_attribute' => $classPath . 'Domain/Model/class.tx_commerce_attribute.php',
	'tx_commerce_attribute_value' => $classPath . 'Domain/Model/class.tx_commerce_attribute_value.php',
	'tx_commerce_basic_basket' => $classPath . 'Domain/Model/class.tx_commerce_basic_basket.php',
	'tx_commerce_basket' => $classPath . 'Domain/Model/class.tx_commerce_basket.php',
	'tx_commerce_basket_item' => $classPath . 'Domain/Model/class.tx_commerce_basket_item.php',
	'tx_commerce_category' => $classPath . 'Domain/Model/class.tx_commerce_category.php',
	'tx_commerce_element_alib' => $classPath . 'Domain/Model/class.tx_commerce_element_alib.php',
	'tx_commerce_product' => $classPath . 'Domain/Model/class.tx_commerce_product.php',

	/* domain repository */
	'tx_commerce_db_alib' => $classPath . 'Domain/Repository/class.tx_commerce_db_alib.php',
	'tx_commerce_db_article' => $classPath . 'Domain/Repository/class.tx_commerce_db_article.php',
	'tx_commerce_db_attribute' => $classPath . 'Domain/Repository/class.tx_commerce_db_attribute.php',
	'tx_commerce_db_attribute_value' => $classPath . 'Domain/Repository/class.tx_commerce_db_attribute_value.php',
	'tx_commerce_db_category' => $classPath . 'Domain/Repository/class.tx_commerce_db_category.php',
	'tx_commerce_db_price' => $classPath . 'Domain/Repository/class.tx_commerce_db_price.php',
	'tx_commerce_db_product' => $classPath . 'Domain/Repository/class.tx_commerce_db_product.php',

	/* module related classes */
	'tx_commerce_module_access_navigation' => $classPath . 'Module/Access/navigation.php',
	'sc_mod_access_perm_ajax' => $classPath . 'Module/Access/class.sc_mod_access_perm_ajax.php',
	'tx_commerce_module_category_navigation' => $classPath . 'Module/Category/navigation.php',
	'tx_commerce_order_pagetree' => $classPath . 'Module/Orders/class.tx_commerce_order_pagetree.php',
	'tx_commerce_statistic_pagetree' => $classPath . 'Module/Statistic/class.tx_commerce_statistic_pagetree.php',

	/* payment */
	'tx_commerce_payment_abstract' => $classPath . 'Payment/class.tx_commerce_payment_abstract.php',
	'tx_commerce_payment_cashondelivery' => $classPath . 'Payment/class.tx_commerce_payment_cashondelivery.php',
	'tx_commerce_payment_ccvs' => $classPath . 'Payment/class.tx_commerce_payment_ccvs.php',
	'tx_commerce_payment_creditcard' => $classPath . 'Payment/class.tx_commerce_payment_creditcard.php',
	'tx_commerce_payment_debit' => $classPath . 'Payment/class.tx_commerce_payment_debit.php',
	'tx_commerce_payment_invoice' => $classPath . 'Payment/class.tx_commerce_payment_invoice.php',
	'tx_commerce_payment_prepayment' => $classPath . 'Payment/class.tx_commerce_payment_prepayment.php',
	'creditcardvalidationsolution' => $classPath . 'Payment/ccvs/class.tx_commerce_payment_ccvs.php',
	'tx_commerce_payment_criterion_abstract' => $classPath . 'Payment/criteria/class.tx_commerce_payment_criterion_abstract.php',
	'tx_commerce_payment_criterion' => $classPath . 'Payment/criteria/interfaces/interface.tx_commerce_payment_criterion.php',
	'tx_commerce_payment' => $classPath . 'Payment/interfaces/interface.tx_commerce_payment.php',
	'payment' => $classPath . 'Payment/libs/class.payment.php',
	'wirecard' => $classPath . 'Payment/libs/class.wirecard.php',
	'tx_commerce_payment_provider_abstract' => $classPath . 'Payment/provider/class.tx_commerce_payment_provider_abstract.php',
	'tx_commerce_payment_provider_wirecard' => $classPath . 'Payment/provider/class.tx_commerce_payment_provider_wirecard.php',
	'tx_commerce_payment_provider_criterion_abstract' => $classPath . 'Payment/provider/criteria/class.tx_commerce_payment_provider_criterion_abstract.php',
	'tx_commerce_payment_provider_criterion' => $classPath . 'Payment/provider/criteria/interfaces/interface.tx_commerce_payment_provider_criterion.php',
	'tx_commerce_payment_provider' => $classPath . 'Payment/provider/interfaces/interface.tx_commerce_payment_provider.php',
		// fix missing file
	'tx_commerce_payment_wirecard_lib' => $extensionPath . 'payment/libs/class.tx_commerce_payment_wirecard_lib.php',

	/* task */
	'tx_commerce_task_statistictask' => $classPath . 'Task/StatisticTask.php',
	'tx_commerce_task_statistictaskadditionalfieldprovider' => $classPath . 'Task/StatisticTaskAdditionalFieldProvider.php',

	/* tree */
	'browsetree' => $classPath . 'Tree/class.browsetree.php',
	'langbase' => $classPath . 'Tree/class.langbase.php',
	'leaf' => $classPath . 'Tree/class.leaf.php',
	'leafdata' => $classPath . 'Tree/class.leafData.php',
	'leafmaster' => $classPath . 'Tree/class.leafMaster.php',
	'leafmasterdata' => $classPath . 'Tree/class.leafMasterData.php',
	'leafslave' => $classPath . 'Tree/class.leafSlave.php',
	'leafslavedata' => $classPath . 'Tree/class.leafSlaveData.php',
	'leafview' => $classPath . 'Tree/class.leafView.php',
	'mounts' => $classPath . 'Tree/class.mounts.php',

	/* utilities */
	'tx_commerce_utility_articlecreatorutility' => $classPath . 'Utility/ArticleCreatorUtility.php',
	'tx_commerce_utility_attributeeditorutility' => $classPath . 'Utility/AttributeEditorUtility.php',
	'tx_commerce_utility_backendutility' => $classPath . 'Utility/BackendUtility.php',
	'tx_commerce_belib' => $classPath . 'Utility/BackendUtility.php',
	'tx_commerce_utility_folderutility' => $classPath . 'Utility/FolderUtility.php',
	'tx_commerce_create_folder' => $classPath . 'Utility/FolderUtility.php',
	'tx_commerce_utility_tceformsutility' => $classPath . 'Utility/TceformsUtility.php',
	'tx_commerce_forms_select' => $classPath . 'Utility/TceformsUtility.php',


	'tx_commerce_db_list' => $extensionPath . 'lib/class.tx_commerce_db_list.php',
	'commercerecordlist' => $extensionPath . 'lib/class.tx_commerce_db_list_extra.php',
	'tx_commerce_div' => $extensionPath . 'lib/class.tx_commerce_div.php',
	'tx_commerce_folder_db' => $extensionPath . 'lib/class.tx_commerce_folder_db.php',
	'tx_commerce_navigation' => $extensionPath . 'lib/class.tx_commerce_navigation.php',
	'user_tx_commerce_catmenu_pub' => $extensionPath . 'lib/class.user_tx_commerce_catmenu_pub.php',
	'tx_commerce_feusers_localrecordlist' => $extensionPath . 'lib/class.tx_commerce_feusers_localrecordlist.php',
	'tx_commerce_order_localrecordlist' => $extensionPath . 'lib/class.tx_commerce_order_localrecordlist.php',
	'tx_commerce_pibase' => $extensionPath . 'lib/class.tx_commerce_pibase.php',
	'tx_commerce_statistics' => $extensionPath . 'lib/class.tx_commerce_statistics.php',

	'tx_commerce_categorytree' => $extensionPath . 'treelib/class.tx_commerce_categorytree.php',
	'tx_commerce_categorymounts' => $extensionPath . 'treelib/class.tx_commerce_categorymounts.php',
	'tx_commerce_leaf_article' => $extensionPath . 'treelib/class.tx_commerce_leaf_article.php',
	'tx_commerce_leaf_articledata' => $extensionPath . 'treelib/class.tx_commerce_leaf_articledata.php',
	'tx_commerce_leaf_articleview' => $extensionPath . 'treelib/class.tx_commerce_leaf_articleview.php',
	'tx_commerce_leaf_category' => $extensionPath . 'treelib/class.tx_commerce_leaf_category.php',
	'tx_commerce_leaf_categorydata' => $extensionPath . 'treelib/class.tx_commerce_leaf_categorydata.php',
	'tx_commerce_leaf_categoryview' => $extensionPath . 'treelib/class.tx_commerce_leaf_categoryview.php',
	'tx_commerce_leaf_product' => $extensionPath . 'treelib/class.tx_commerce_leaf_product.php',
	'tx_commerce_leaf_productdata' => $extensionPath . 'treelib/class.tx_commerce_leaf_productdata.php',
	'tx_commerce_leaf_productview' => $extensionPath . 'treelib/class.tx_commerce_leaf_productview.php',
	'tx_commerce_treelib_browser' => $extensionPath . 'treelib/class.tx_commerce_treelib_browser.php',
	'tx_commerce_treelib_link_categorytree' => $extensionPath . 'treelib/link/class.tx_commerce_treelib_link_categorytree.php',
	'tx_commerce_treelib_link_leaf_categoryview' => $extensionPath . 'treelib/link/class.tx_commerce_treelib_link_leaf_categoryview.php',
	'tx_commerce_treelib_link_leaf_productview' => $extensionPath . 'treelib/link/class.tx_commerce_treelib_link_leaf_productview.php',
	'tx_commerce_treelib_tceforms' => $extensionPath . 'treelib/class.tx_commerce_treelib_tceforms.php',

	'user_orderedit_func' => $extensionPath . 'mod_orders/class.user_orderedit_func.php',

	'tx_moneylib' => t3lib_extMgm::extPath('moneylib') . 'class.tx_moneylib.php',
	'tx_staticinfotables_pi1' => t3lib_extMgm::extPath('static_info_tables') . 'pi1/class.tx_staticinfotables_pi1.php',

	'recordlist' => PATH_site . 'typo3/class.db_list.inc',
	'localrecordlist' => PATH_site . 'typo3/class.db_list_extra.inc',
);

?>