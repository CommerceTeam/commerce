.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _emails:

Removed methods since 4.x
=========================

.. contents::
	:local:
	:depth: 1


.. _Tx_Commerce_Controller_BaseController:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Controller_BaseController, addTypoLinkToTS, addTypoLinkToTypoScript
	, makeControl, "Method got removed from the api. Method was not used in pibase context"
	, makeproductAttributList, "Method got removed from the api"
	, makeArticleAttributList, "Method got removed from the api"
	, makeSingleView, "Method got removed from the api"


.. _Tx_Commerce_Domain_Model_AbstractEntity:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Model_AbstractEntity, getMarkerArray, "please use Tx_Commerce_Controller_BaseController->renderElement in combination with Tx_Commerce_Domain_Model_AbstractEntity::returnAssocArray instead"
	, get_uid, getUid
	, get_LOCALIZED_UID, getLocalizedUid
	, get_lang, getLang
	, return_assoc_array, returnAssocArray
	, add_field_to_fieldlist, addFieldToFieldlist
	, add_fields_to_fieldlist, addFieldsToFieldlist
	, is_valid_uid, isValidUid
	, get_attributes, getAttributes
	, load_data, loadData


.. _Tx_Commerce_Domain_Model_Article:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Model_Article, get_title, getTitle
	, get_subtitle, getSubtitle
	, get_classname, getClassname
	, get_description_extra, getDescriptionExtra
	, get_article_price_uid, getPriceUid
	, get_price_gross, getPriceGross
	, get_price_net, getPriceNet
	, getArticlePriceUid, getPriceUid
	, getPossiblePriceUids, getPriceUids
	, get_tax, getTax
	, get_ordernumber, getOrdernumber
	, get_parent_product, getParentProduct
	, get_article_attributes, getArticleAttributes
	, get_article_type_uid, getArticleTypeUid
	, load_prices, loadPrices


.. _Tx_Commerce_Domain_Model_ArticlePrice:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Model_ArticlePrice, get_price_net, getPriceNet
	, get_price_gross, getPriceGross


.. _Tx_Commerce_Domain_Model_Attribute:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Model_Attribute, get_all_values, getAllValues
	, get_values, getValues
	, get_value, getValue
	, get_title, getTitle
	, get_attributes, getAttributes
	, get_unit, getUnit


.. _Tx_Commerce_Domain_Model_AttributeValue:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Model_AttributeValue, getshowicon, "Method got removed from the api"
	, get_attributes, getAttributes
	, get_value, getValue


.. _Tx_Commerce_Domain_Model_BasicBasket:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Model_BasicBasket, recalculate_sums, recalculateSums
	, delete_all_articles, deleteAllArticles
	, delete_article, deleteArticle
	, change_quantity, changeQuantity
	, get_articles_by_article_type_uid_asUidlist, getArticlesByArticleTypeUidAsUidlist
	, get_assoc_arrays, getAssocArrays
	, add_article, addArticle
	, isReadOnly, getReadOnly
	, getIsReadOnly, getReadOnly
	, get_gross_sum, getSumGross
	, get_net_sum, getSumNet


.. _Tx_Commerce_Domain_Model_Basket:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Model_Basket, loadPersistantDataFromDatabase, loadPersistentDataFromDatabase
	, load_data_from_database, loadDataFromDatabase
	, store_data_to_database, storeDataToDatabase
	, store_data, storeData
	, set_session_id, setSessionId
	, get_session_id, getSessionId


.. _Tx_Commerce_Domain_Model_BasketItem:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Model_BasketItem, recalculate_item_sums, recalculateItemSums
	, calculate_gross_sum, calculateGrossSum
	, calculate_net_sum, calculateNetSum
	, change_quantity, changeQuantity
	, get_article_uid, getArticleUid
	, get_tax, getTax
	, get_price_gross, getPriceGross
	, get_price_uid, getPriceUid
	, get_item_sum_net, getItemSumNet
	, get_item_sum_gross, getItemSumGross
	, get_item_sum_tax, getItemSumTax
	, get_article_assoc_array, getArticleAssocArray
	, get_product_assoc_array, getProductAssocArray
	, get_array_of_assoc_array, getArrayOfAssocArray
	, get_price_net, getPriceNet
	, get_article_article_type_uid, getArticleTypeUid
	, get_quantity, getQuantity


.. _Tx_Commerce_Domain_Model_Category:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Model_Category, isPSet, isPermissionSet
	, get_rec_child_categories_uidlist, getChildCategoriesUidlist
	, get_categorie_rootline_uidlist, getParentCategoriesUidlist
	, ProductsBelowCategory, hasProductsInSubCategories
	, get_l18n_categories, getL18nCategories
	, getCategoryTSconfig, getTyposcriptConfig
	, numOfChildCategories, getChildCategoriesCount
	, get_subproducts, getChildProducts
	, get_child_products, getChildProducts
	, has_subproducts, hasProducts
	, has_subcategories, hasSubcategories
	, getSubcategories, getChildCategories
	, get_child_categories, getChildCategories
	, get_category_path, getCategoryPath
	, get_keywords, getKeywords
	, get_parent_category, getParentCategory
	, getAllProducts, getProducts
	, load_perms, loadPermissions
	, get_navtitle, getNavtitle
	, get_description, getDescription
	, get_images, getImages
	, get_teaser, getTeaser
	, get_subtitle, getSubtitle
	, get_title, getTitle
	, load_data, loadData


.. _Tx_Commerce_Domain_Model_Product:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	_Tx_Commerce_Domain_Model_Product, getRelevantArticles, "Method got removed from the api"
	, get_selectattribute_matrix, getSelectAttributeMatrix
	, get_Articles_by_Attribute, getArticlesByAttribute
	, get_Articles_by_AttributeArray, getArticlesByAttributeArray
	, compareBySorting, "Method got removed from the api"
	, get_l18n_products, getL18nProducts
	, getNumberOfArticles, getArticlesCount
	, get_teaser, getTeaser
	, get_description, getDescription
	, get_subtitle, getSubtitle
	, get_t3ver_oid, getT3verOid
	, get_pid, getPid
	, getMasterparentCategorie, getMasterparentCategory
	, get_title, getTitle
	, get_masterparent_categorie, getMasterparentCategory
	, get_parent_categories, getParentCategories
	, get_images, getImages
	, set_leng_description, "Please use typoscript instead"
	, get_attribute_matrix, getAttributeMatrix
	, get_atrribute_matrix, getAttributeMatrix
	, get_product_attribute_matrix, getAttributeMatrix
	, get_product_atrribute_matrix, getAttributeMatrix
	, getArticles, getArticleUids
	, load_articles, loadArticles


.. _Tx_Commerce_Domain_Repository_ArticleRepository:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Repository_ArticleRepository, get_parent_product_uid, getParentProductUid
	, get_prices, getPrices
	, get_attributes, getAttributes


.. _Tx_Commerce_Domain_Repository_AttributeRepository:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Repository_AttributeRepository, get_attribute_value_uids, getAttributeValueUids


.. _Tx_Commerce_Domain_Repository_CategoryRepository:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Repository_CategoryRepository, getUid, "Method got removed from the api"
	, getLangUid, "Method got removed from the api"
	, get_parent_category, getParentCategory
	, get_parent_categories, getParentCategories
	, get_l18n_categories, getL18nCategories
	, get_child_categories, getChildCategories
	, get_child_products, getChildProducts

.. _Tx_Commerce_Domain_Repository_FolderRepository:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Repository_FolderRepository, getFolderPidList, "Method got removed from the api"


.. _Tx_Commerce_Domain_Repository_ProductRepository:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Repository_ProductRepository, get_articles, getArticles
	, get_attributes, getAttributes
	, get_l18n_products, getL18nProducts
	, get_related_product_uids, getRelatedProductUids
	, get_parent_category, getMasterParentCategory
	, get_parent_categorie, getMasterParentCategory
	, get_parent_categories, getParentCategories


.. _Tx_Commerce_Domain_Repository_Repository:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Domain_Repository_Repository, get_attributes, getAttributes


.. _Tx_Commerce_Hook_Pi4Hooks:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Hook_Pi4Hooks, deleteAddress, beforeDeleteAddress
	, beforeAddressSave, "Method got removed from the api"
	, beforeAddressEdit, "Method got removed from the api"
	, notify_addressObserver, "afterAddressSave or afterAddressEdit"
	, checkAddressDelete, "Method got removed from the api"


.. _Tx_Commerce_Hook_TcehooksHandlerHooks:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Hook_TcehooksHandlerHooks, notify_feuserObserver, notifyFeuserObserver
	, notify_addressObserver, notifyAddressObserver


.. _Tx_Commerce_Payment_PaymentAbstract:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Payment_PaymentAbstract, getPObj, getParentObject


.. _Tx_Commerce_Utility_ArticleCreatorUtility:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Utility_ArticleCreatorUtility, createNewPriceCB, "Method got removed from the api"
	, createNewScalePricesCount, "Method got removed from the api"
	, createNewScalePricesSteps, "Method got removed from the api"
	, createNewScalePricesStartAmount, "Method got removed from the api"
	, deletePriceButton, "Method got removed from the api"


.. _Tx_Commerce_Utility_BackendUtility:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Utility_BackendUtility, savePriceFlexformWithArticle, "Method got removed from the api"
	, fix_articles_price, updatePriceXMLFromDatabase
	, fix_product_atributte, updateXML
	, fix_category_atributte, updateXML
	, isPSet, isPermissionSet


.. _Tx_Commerce_Utility_FolderUtility:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Utility_FolderUtility, init_folders, initFolders


.. _Tx_Commerce_Utility_GeneralUtility:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_Utility_GeneralUtility, formatPrice, getAttributes


.. _Tx_Commerce_ViewHelpers_Navigation:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_ViewHelpers_Navigation, getManuAsCat, getManufacturerAsCategory
	, getCategoryRootlineforTS, getCategoryRootlineforTypoScript
	, getActiveCats, "Method got removed from the api"
	, CommerceRootline, renderRootline


.. _Tx_Commerce_ViewHelpers_OrderEditFunc:
.. csv-table::
	:header: Class, Method, Replacement
	:widths: 30, 35, 35

	Tx_Commerce_ViewHelpers_OrderEditFunc, article_order_id, articleOrderId
	, sum_price_gross_format, sumPriceGrossFormat
	, order_articles, orderArticles
	, order_status, orderStatus
	, invoice_adress, invoiceAddress
	, delivery_adress, deliveryAddress
	, adress, address
	, fe_user_orders, feUserOrders


Removed hooks since 4.x
=======================

.. _Tx_Commerce_Controller_AddressesController_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Controller_AddressesController, getListing, ['commerce/pi4/class.tx_commerce_pi4.php']['getListing'], ['commerce/Controller/AddressesController']['getListing']
	, , ['commerce/Classes/Controller/AddressesController.php']['getListing'], ['commerce/Controller/AddressesController']['getListing']
	, getAddressForm, ['commerce/pi4/class.tx_commerce_pi4.php']['getAddressFormItem'], ['commerce/Controller/AddressesController']['getAddressForm']
	, , ['commerce/Classes/Controller/AddressesController.php']['getAddressFormItem'], ['commerce/Controller/AddressesController']['getAddressForm']
	, deleteAddress, ['commerce/pi4/class.tx_commerce_pi4.php']['deleteAddress'], ['commerce/Controller/AddressesController']['deleteAddress']
	, , ['commerce/Classes/Controller/AddressesController.php']['deleteAddress'], ['commerce/Controller/AddressesController']['deleteAddress']
	, checkAddressForm, ['commerce/pi4/class.tx_commerce_pi4.php']['checkAddressForm'], ['commerce/Controller/AddressesController']['checkAddressForm']
	, , ['commerce/Classes/Controller/AddressesController.php']['checkAddressForm'], ['commerce/Controller/AddressesController']['checkAddressForm']
	, saveAddressData, ['commerce/pi4/class.tx_commerce_pi4.php']['saveAddress'], ['commerce/Controller/AddressesController']['saveAddress']
	, , ['commerce/Classes/Controller/AddressesController.php']['saveAddress'], ['commerce/Controller/AddressesController']['saveAddress']
	, getAddresses, ['commerce/pi4/class.tx_commerce_pi4.php']['getAddresses'], ['commerce/Controller/AddressesController']['getAddresses']
	, , ['commerce/Classes/Controller/AddressesController.php']['getAddresses'], ['commerce/Controller/AddressesController']['getAddresses']


.. _Tx_Commerce_Controller_BaseController_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Controller_BaseController, addAdditionalLocallang, ['commerce/lib/class.tx_commerce_pibase.php']['locallang'], ['commerce/Controller/BaseController']['addAdditionalLocallang']
	, , ['commerce/Classes/Controller/BaseController.php']['locallang'], ['commerce/Controller/BaseController']['addAdditionalLocallang']
	, makeListView, ['commerce/Classes/Controller/ListController.php']['preRenderListView'], "Removed because no methods of this hook were used"
	, , ['commerce/lib/class.tx_commerce_pibase.php']['listview'], ['commerce/Controller/BaseController']['makeListView']
	, , ['commerce/Classes/Controller/BaseController.php']['listView'], ['commerce/Controller/BaseController']['makeListView']
	, getArticleMarker, ['commerce/lib/class.tx_commerce_pibase.php']['articleMarker'], ['commerce/Controller/BaseController']['getArticleMarker']
	, , ['commerce/Classes/Controller/BaseController.php']['articleMarker'], ['commerce/Controller/BaseController']['getArticleMarker']
	, makeBasketView, ['commerce/lib/class.tx_commerce_pibase.php']['makeBasketView'], ['commerce/Controller/BaseController']['makeBasketView']
	, , ['commerce/Classes/Controller/BaseController.php']['makeBasketView'], ['commerce/Controller/BaseController']['makeBasketView']
	, makeBasketInformation, ['commerce/lib/class.tx_commerce_pibase.php']['makeBasketInformation'], ['commerce/Controller/BaseController']['makeBasketInformation']
	, , ['commerce/Classes/Controller/BaseController.php']['makeBasketInformation'], ['commerce/Controller/BaseController']['makeBasketInformation']
	, makeLineView, ['commerce/lib/class.tx_commerce_pibase.php']['makeLineView'], ['commerce/Controller/BaseController']['makeLineView']
	, , ['commerce/Classes/Controller/BaseController.php']['makeLineView'], ['commerce/Controller/BaseController']['makeLineView']
	, renderValue, ['commerce/lib/class.tx_commerce_pibase.php']['renderValue'], ['commerce/Controller/BaseController']['renderValue']
	, , ['commerce/Classes/Controller/BaseController.php']['renderValue'], ['commerce/Controller/BaseController']['renderValue']
	, renderElement, ['commerce/lib/class.tx_commerce_pibase.php']['generalElement'], ['commerce/Controller/BaseController']['renderElement']
	, , ['commerce/Classes/Controller/BaseController.php']['renderElement'], ['commerce/Controller/BaseController']['renderElement']
	, formatAttributeValue, ['commerce/lib/class.tx_commerce_pibase.php']['formatAttributeValue'], ['commerce/Controller/BaseController']['formatAttributeValue']
	, , ['commerce/Classes/Controller/BaseController.php']['formatAttributeValue'], ['commerce/Controller/BaseController']['formatAttributeValue']
	, renderProductsForList, ['commerce/lib/class.tx_commerce_pibase.php']['renderProductsForList'], ['commerce/Controller/BaseController']['renderProductsForList']
	, , ['commerce/Classes/Controller/BaseController.php']['renderProductsForList'], ['commerce/Controller/BaseController']['renderProductsForList']
	, renderProduct, ['commerce/lib/class.tx_commerce_pibase.php']['product'], ['commerce/Controller/BaseController']['renderProduct']
	, , ['commerce/Classes/Controller/BaseController.php']['renderProduct'], ['commerce/Controller/BaseController']['renderProduct']


.. _Tx_Commerce_Controller_BasketController_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Controller_BasketController, main, ['commerce/pi2/class.tx_commerce_pi2.php']['main'], ['commerce/Controller/BasketController']['main']
	, , ['commerce/Classes/Controller/BasketController.php']['main'], ['commerce/Controller/BasketController']['main']
	, handleDeleteBasket, ['commerce/pi2/class.tx_commerce_pi2.php']['postdelBasket'], ['commerce/Controller/BasketController']['handleDeleteBasket']
	, , ['commerce/Classes/Controller/BasketController.php']['postDeleteBasket'], ['commerce/Controller/BasketController']['handleDeleteBasket']
	, handleAddArticle, ['commerce/pi2/class.tx_commerce_pi2.php']['artAddUid'], ['commerce/Controller/BasketController']['handleAddArticle']
	, , ['commerce/Classes/Controller/BasketController.php']['addArticleUid'], ['commerce/Controller/BasketController']['handleAddArticle']
	, handlePaymentArticle, ['commerce/pi2/class.tx_commerce_pi2.php']['postpayArt'], ['commerce/Controller/BasketController']['handlePaymentArticle']
	, , ['commerce/Classes/Controller/BasketController.php']['postPaymentArticle'], ['commerce/Controller/BasketController']['handlePaymentArticle']
	, handleDeliveryArticle, ['commerce/pi2/class.tx_commerce_pi2.php']['postdelArt'], ['commerce/Controller/BasketController']['handleDeliveryArticle']
	, , ['commerce/Classes/Controller/BasketController.php']['postDeliveryArticle'], ['commerce/Controller/BasketController']['handleDeliveryArticle']
	, getQuickView, ['commerce/pi2/class.tx_commerce_pi2.php']['getQuickView'], ['commerce/Controller/BasketController']['getQuickView']
	, , ['commerce/Classes/Controller/BasketController.php']['getQuickView'], ['commerce/Controller/BasketController']['getQuickView']
	, generateBasket, ['commerce/pi2/class.tx_commerce_pi2.php']['generateBasket'], ['commerce/Controller/BasketController']['generateBasket']
	, , ['commerce/Classes/Controller/BasketController.php']['generateBasket'], ['commerce/Controller/BasketController']['generateBasket']
	, , ['commerce/pi2/class.tx_commerce_pi2.php']['generateBasketMarker'], ['commerce/Controller/BasketController']['generateBasketMarker']
	, , ['commerce/Classes/Controller/BasketController.php']['generateBasketMarker'], ['commerce/Controller/BasketController']['generateBasketMarker']
	, makeDelivery, ['commerce/pi2/class.tx_commerce_pi2.php']['deliveryArticles'], ['commerce/Controller/BasketController']['makeDelivery']
	, , ['commerce/Classes/Controller/BasketController.php']['deliveryArticles'], ['commerce/Controller/BasketController']['makeDelivery']
	, makePayment, ['commerce/pi2/class.tx_commerce_pi2.php']['paymentArticles'], ['commerce/Controller/BasketController']['makePayment']
	, , ['commerce/Classes/Controller/BasketController.php']['paymentArticles'], ['commerce/Controller/BasketController']['makePayment']
	, makeArticleView, ['commerce/pi2/class.tx_commerce_pi2.php']['makeArticleView'], ['commerce/Controller/BasketController']['makeArticleView']
	, , ['commerce/Classes/Controller/BasketController.php']['makeArticleView'], ['commerce/Controller/BasketController']['makeArticleView']
	, makeProductList, ['commerce/pi2/class.tx_commerce_pi2.php']['makeProductList'], ['commerce/Controller/BasketController']['makeProductList']
	, , ['commerce/Classes/Controller/BasketController.php']['makeProductList'], ['commerce/Controller/BasketController']['makeProductList']
	, , ['commerce/pi2/class.tx_commerce_pi2.php']['alternativePrefixId'], ['commerce/Controller/BasketController']['alternativePrefixId']
	, , ['commerce/Classes/Controller/BasketController.php']['alternativePrefixId'], ['commerce/Controller/BasketController']['alternativePrefixId']


.. _Tx_Commerce_Controller_CheckoutController_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Controller_CheckoutController, validateAddress, ['commerce/pi3/class.tx_commerce_pi3.php']['bevorValidateAddress'], ['commerce/Controller/CheckoutController']['validateAddress']
	, , ['commerce/Classes/Controller/CheckoutController.php']['bevorValidateAddress'], ['commerce/Controller/CheckoutController']['validateAddress']
	, , ['commerce/pi3/class.tx_commerce_pi3.php']['beforeValidateAddress'], ['commerce/Controller/CheckoutController']['validateAddress']
	, , ['commerce/Classes/Controller/CheckoutController.php']['beforeValidateAddress'], ['commerce/Controller/CheckoutController']['validateAddress']
	, getInstanceOfTceMain, ['commerce/pi3/class.tx_commerce_pi3.php']['postTcaInit'], ['commerce/Controller/CheckoutController']['getInstanceOfTceMain']
	, , ['commerce/Classes/Controller/CheckoutController.php']['postTcaInit'], ['commerce/Controller/CheckoutController']['getInstanceOfTceMain']
	, saveOrder, ['commerce/pi3/class.tx_commerce_pi3.php']['finishIt'], ['commerce/Controller/CheckoutController']['saveOrder']
	, , ['commerce/Classes/Controller/CheckoutController.php']['finishIt'], ['commerce/Controller/CheckoutController']['saveOrder']
	, generateMail, ['commerce/pi3/class.tx_commerce_pi3.php']['generateMail'], ['commerce/Controller/CheckoutController']['generateMail']
	, , ['commerce/Classes/Controller/CheckoutController.php']['generateMail'], ['commerce/Controller/CheckoutController']['generateMail']
	, init, ['commerce/pi3/class.tx_commerce_pi3.php']['init'], ['commerce/Controller/CheckoutController']['init']
	, , ['commerce/Classes/Controller/CheckoutController.php']['init'], ['commerce/Controller/CheckoutController']['init']
	, main, ['commerce/pi3/class.tx_commerce_pi3.php']['main'], ['commerce/Controller/CheckoutController']['main']
	, , ['commerce/Classes/Controller/CheckoutController.php']['main'], ['commerce/Controller/CheckoutController']['main']
	, getBillingAddress, ['commerce/pi3/class.tx_commerce_pi3.php']['getBillingAddress'], ['commerce/Controller/CheckoutController']['getBillingAddress']
	, , ['commerce/Classes/Controller/CheckoutController.php']['getBillingAddress'], ['commerce/Controller/CheckoutController']['getBillingAddress']
	, getDeliveryAddress, ['commerce/pi3/class.tx_commerce_pi3.php']['getDeliveryAddress'], ['commerce/Controller/CheckoutController']['getDeliveryAddress']
	, , ['commerce/Classes/Controller/CheckoutController.php']['getDeliveryAddress'], ['commerce/Controller/CheckoutController']['getDeliveryAddress']
	, handlePayment, ['commerce/pi3/class.tx_commerce_pi3.php']['handlePayment'], ['commerce/Controller/CheckoutController']['handlePayment']
	, , ['commerce/Classes/Controller/CheckoutController.php']['handlePayment'], ['commerce/Controller/CheckoutController']['handlePayment']
	, getListing, ['commerce/pi3/class.tx_commerce_pi3.php']['getListing'], ['commerce/Controller/CheckoutController']['getListing']
	, , ['commerce/Classes/Controller/CheckoutController.php']['getListing'], ['commerce/Controller/CheckoutController']['getListing']
	, finishIt, ['commerce/pi3/class.tx_commerce_pi3.php']['finishIt'], ['commerce/Controller/CheckoutController']['finishIt']
	, , ['commerce/Classes/Controller/CheckoutController.php']['finishIt'], ['commerce/Controller/CheckoutController']['finishIt']
	, getOrderId, ['commerce/pi3/class.tx_commerce_pi3.php']['finishIt'], ['commerce/Controller/CheckoutController']['getOrderId']
	, , ['commerce/Classes/Controller/CheckoutController.php']['finishIt'], ['commerce/Controller/CheckoutController']['getOrderId']
	, getBasketSum, ['commerce/pi3/class.tx_commerce_pi3.php']['getBasketSum'], ['commerce/Controller/CheckoutController']['getBasketSum']
	, , ['commerce/Classes/Controller/CheckoutController.php']['getBasketSum'], ['commerce/Controller/CheckoutController']['getBasketSum']
	, getInputForm, ['commerce/pi3/class.tx_commerce_pi3.php']['processInputForm'], ['commerce/Controller/CheckoutController']['getInputForm']
	, , ['commerce/Classes/Controller/CheckoutController.php']['processInputForm'], ['commerce/Controller/CheckoutController']['getInputForm']
	, handleAddress, ['commerce/pi3/class.tx_commerce_pi3.php']['handleAddress'], ['commerce/Controller/CheckoutController']['handleAddress']
	, , ['commerce/Classes/Controller/CheckoutController.php']['handleAddress'], ['commerce/Controller/CheckoutController']['handleAddress']
	, canMakeCheckout, ['commerce/pi3/class.tx_commerce_pi3.php']['canMakeCheckout'], ['commerce/Controller/CheckoutController']['canMakeCheckout']
	, , ['commerce/Classes/Controller/CheckoutController.php']['canMakeCheckout'], ['commerce/Controller/CheckoutController']['canMakeCheckout']
	, sendUserMail, ['commerce/pi3/class.tx_commerce_pi3.php']['sendUserMail'], ['commerce/Controller/CheckoutController']['sendUserMail']
	, , ['commerce/Classes/Controller/CheckoutController.php']['sendUserMail'], ['commerce/Controller/CheckoutController']['sendUserMail']
	, sendAdminMail, ['commerce/pi3/class.tx_commerce_pi3.php']['sendAdminMail'], ['commerce/Controller/CheckoutController']['sendAdminMail']
	, , ['commerce/Classes/Controller/CheckoutController.php']['sendAdminMail'], ['commerce/Controller/CheckoutController']['sendAdminMail']


.. _Tx_Commerce_Controller_InvoiceController_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Controller_InvoiceController, main, ['commerce/pi6/class.tx_commerce_pi6.php']['invoice'], ['commerce/Controller/InvoiceController']['main']
	, , ['commerce/Classes/Controller/InvoiceController.php']['invoice'], ['commerce/Controller/InvoiceController']['main']


.. _Tx_Commerce_Controller_ListController_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Controller_ListController, init, ['commerce/pi1/class.tx_commerce_pi1.php']['init'], ['commerce/Controller/ListController']['init']
	, , ['commerce/Classes/Controller/ListController.php']['init'], ['commerce/Controller/ListController']['init']
	, , ['commerce/pi1/class.tx_commerce_pi1.php']['postInit'], ['commerce/Controller/ListController']['init']
	, , ['commerce/Classes/Controller/ListController.php']['postInit'], ['commerce/Controller/ListController']['init']
	, renderSingleView, ['commerce/lib/class.tx_commerce_pibase.php']['singleview'], ['commerce/Controller/ListController']['renderSingleView']
	, , ['commerce/Classes/Controller/ListController.php']['renderSingleView'], ['commerce/Controller/ListController']['renderSingleView']
	, makeArticleView, ['commerce/lib/class.tx_commerce_pibase.php']['articleview'], ['commerce/Controller/ListController']['makeArticleView']
	, , ['commerce/Classes/Controller/ListController.php']['articleView'], ['commerce/Controller/ListController']['makeArticleView']


.. _Tx_Commerce_Domain_Model_Article_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Domain_Model_Article, init, ['commerce/lib/class.tx_commerce_article.php']['postinit'], ['commerce/Domain/Model/Article']['init']
	, , ['commerce/Classes/Domain/Model/Article.php']['postinit'], ['commerce/Domain/Model/Article']['init']
	, getActualPriceforScaleUid, ['commerce/lib/class.tx_commerce_article.php']['getActualPriceforScaleUid'], ['commerce/Domain/Model/Article']['getActualPriceforScaleUid']
	, , ['commerce/Classes/Domain/Model/Article.php']['getActualPriceforScaleUid'], ['commerce/Domain/Model/Article']['getActualPriceforScaleUid']
	, getSpecialPrice, ['commerce/lib/class.tx_commerce_article.php']['specialPrice'], ['commerce/Domain/Model/Article']['getSpecialPrice']
	, , ['commerce/Classes/Domain/Model/Article.php']['specialPrice'], ['commerce/Domain/Model/Article']['getSpecialPrice']
	, calculateDeliveryCosts, ['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost'], ['commerce/Domain/Model/Article']['calculateDeliveryCosts']
	, , ['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost'], ['commerce/Domain/Model/Article']['calculateDeliveryCosts']


.. _Tx_Commerce_Domain_Model_ArticlePrice_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Domain_Model_ArticlePrice, init, ['commerce/lib/class.tx_commerce_article_price.php']['postinit'], ['commerce/Domain/Model/ArticlePrice']['init']
	, , ['commerce/Classes/Domain/Model/ArticlePrice.php']['postinit'], ['commerce/Domain/Model/ArticlePrice']['init']
	, getPriceNet, ['commerce/lib/class.tx_commerce_article_price.php']['postpricenet'], ['commerce/Domain/Model/ArticlePrice']['getPriceNet']
	, , ['commerce/Classes/Domain/Model/ArticlePrice.php']['postPriceNet'], ['commerce/Domain/Model/ArticlePrice']['getPriceNet']
	, getPriceGross, ['commerce/lib/class.tx_commerce_article_price.php']['postpricegross'], ['commerce/Domain/Model/ArticlePrice']['getPriceGross']
	, , ['commerce/Classes/Domain/Model/ArticlePrice.php']['postPriceGross'], ['commerce/Domain/Model/ArticlePrice']['getPriceGross']


.. _Tx_Commerce_Domain_Model_Attribute_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Domain_Model_Attribute, init, ['commerce/lib/class.tx_commerce_attribute.php']['postinit'], ['commerce/Domain/Model/Attribute']['init']
	, , ['commerce/Classes/Domain/Model/Attribute.php']['postinit'], ['commerce/Domain/Model/Attribute']['init']


.. _Tx_Commerce_Domain_Model_AttributeValue_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Domain_Model_AttributeValue, init, ['commerce/lib/class.tx_commerce_attribute_value.php']['postinit'], ['commerce/Domain/Model/AttributeValue']['init']
	, , ['commerce/Classes/Domain/Model/AttributeValue.php']['postinit'], ['commerce/Domain/Model/AttributeValue']['init']


.. _Tx_Commerce_Domain_Model_Basket_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Domain_Model_Basket, loadDataFromDatabase, ['commerce/lib/class.tx_commerce_basket.php']['load_data_from_database'], ['commerce/Domain/Model/Basket']['loadDataFromDatabase']
	, , ['commerce/Classes/Domain/Model/Basket.php']['loadDataFromDatabase'], ['commerce/Domain/Model/Basket']['loadDataFromDatabase']
	, storeDataToDatabase, ['commerce/lib/class.tx_commerce_basket.php']['store_data_to_database'], ['commerce/Domain/Model/Basket']['storeDataToDatabase']
	, , ['commerce/Classes/Domain/Model/Basket.php']['storeDataToDatabase'], ['commerce/Domain/Model/Basket']['storeDataToDatabase']


.. _Tx_Commerce_Domain_Model_Category_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Domain_Model_Category, init, ['commerce/lib/class.tx_commerce_category.php']['postinit'], ['commerce/Domain/Model/Category']['init']
	, , ['commerce/Classes/Domain/Model/Category.php']['postinit'], ['commerce/Domain/Model/Category']['init']


.. _Tx_Commerce_Domain_Model_Product_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30


	Tx_Commerce_Domain_Model_Product, init, ['commerce/lib/class.tx_commerce_product.php']['postinit'], ['commerce/Domain/Model/Product']['init']
	, , ['commerce/Classes/Domain/Model/Product.php']['postinit'], ['commerce/Domain/Model/Product']['init']


.. _Tx_Commerce_Domain_Repository_ArticleRepository_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Domain_Repository_ArticleRepository, getPrices, ['commerce/lib/class.tx_commerce_article.php']['priceOrder'], ['commerce/Domain/Repository/ArticleRepository']['getPrices']
	, , ['commerce/Classes/Domain/Repository/ArticleRepository.php']['priceOrder'], ['commerce/Domain/Repository/ArticleRepository']['getPrices']
	, , ['commerce/lib/class.tx_commerce_article.php']['additionalPriceWhere'], ['commerce/Domain/Repository/ArticleRepository']['getPrices']
	, , ['commerce/Classes/Domain/Repository/ArticleRepository.php']['additionalPriceWhere'], ['commerce/Domain/Repository/ArticleRepository']['getPrices']


.. _Tx_Commerce_Domain_Repository_CategoryRepository_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Domain_Repository_CategoryRepository, getChildCategories, ['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder'], ['commerce/Domain/Repository/CategoryRepository']['getChildCategories']
	, , ['commerce/Classes/Domain/Repository/CategoryRepository.php']['categoryOrder'], ['commerce/Domain/Repository/CategoryRepository']['getChildCategories']
	, , ['commerce/lib/class.tx_commerce_db_category.php']['categoryQueryPostHook'], ['commerce/Domain/Repository/CategoryRepository']['getChildCategories']
	, , ['commerce/Classes/Domain/Repository/CategoryRepository.php']['categoryQueryPostHook'], ['commerce/Domain/Repository/CategoryRepository']['getChildCategories']
	, getChildProducts, ['commerce/lib/class.tx_commerce_db_category.php']['productOrder'], ['commerce/Domain/Repository/CategoryRepository']['getChildProducts']
	, , ['commerce/Classes/Domain/Repository/CategoryRepository.php']['productOrder'], ['commerce/Domain/Repository/CategoryRepository']['getChildProducts']
	, , ['commerce/lib/class.tx_commerce_db_category.php']['productQueryPreHook'], ['commerce/Domain/Repository/CategoryRepository']['getChildProducts']
	, , ['commerce/Classes/Domain/Repository/CategoryRepository.php']['productQueryPreHook'], ['commerce/Domain/Repository/CategoryRepository']['getChildProducts']
	, , ['commerce/lib/class.tx_commerce_db_category.php']['productQueryPostHook'], ['commerce/Domain/Repository/CategoryRepository']['getChildProducts']
	, , ['commerce/Classes/Domain/Repository/CategoryRepository.php']['productQueryPostHook'], ['commerce/Domain/Repository/CategoryRepository']['getChildProducts']


.. _Tx_Commerce_Domain_Repository_ProductRepository_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Domain_Repository_ProductRepository, getArticles, ['commerce/lib/class.tx_commerce_product.php']['articleOrder'], ['commerce/Domain/Repository/ProductRepository']['getArticles']
	, , ['commerce/Classes/Domain/Repository/ProductRepository.php']['articleOrder'], ['commerce/Domain/Repository/ProductRepository']['getArticles']
	, , ['commerce/lib/class.tx_commerce_product.php']['aditionalWhere'], ['commerce/Domain/Repository/ProductRepository']['getArticles']
	, , ['commerce/lib/class.tx_commerce_product.php']['additionalWhere'], ['commerce/Domain/Repository/ProductRepository']['getArticles']
	, , ['commerce/Classes/Domain/Repository/ProductRepository.php']['additionalWhere'], ['commerce/Domain/Repository/ProductRepository']['getArticles']


.. _Tx_Commerce_Hook_DataMapHooks_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Hook_DataMapHooks, preProcessOrder, ['commerce/Classes/Hook/class.tx_commerce_dmhooks.php']['moveOrders'], ['commerce/Hook/DataMapHooks']['preProcessOrder']
	, , ['commerce/Classes/Hook/DataMapHooks.php']['moveOrders'], ['commerce/Hook/DataMapHooks']['preProcessOrder']


.. _Tx_Commerce_Hook_OrdermailHooks_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Hook_OrdermailHooks, ordermoveSendMail, ['commerce/Classes/Hook/class.tx_commerce_ordermailhooks.php']['ordermoveSendMail'], ['commerce/Hook/OrdermailHooks']['ordermoveSendMail']
	, , ['commerce/Classes/Hook/OrdermailHooks.php']['ordermoveSendMail'], ['commerce/Hook/OrdermailHooks']['ordermoveSendMail']
	, generateMail, ['commerce_ordermails/mod1/class.tx_commerce_moveordermail.php']['generateMail'], ['commerce/Hook/OrdermailHooks']['generateMail']
	, , ['commerce/Classes/Hook/OrdermailHooks.php']['generateMail'], ['commerce/Hook/OrdermailHooks']['generateMail']


.. _Tx_Commerce_Tree_Leaf_Data_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Tree_Leaf_Data, loadRecords, ['commerce/tree/class.leafData.php']['loadRecords'], ['commerce/Tree/Leaf/Data']['loadRecords']
	, , ['commerce/Classes/Tree/Leaf/Data.php']['loadRecords'], ['commerce/Tree/Leaf/Data']['loadRecords']


.. _Tx_Commerce_Utility_ArticleCreatorUtility_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Utility_ArticleCreatorUtility, createArticle, ['commerce/class.tx_commerce_articlecreator.php']['preinsert'], ['commerce/Utility/ArticleCreatorUtility']['createArticle']
	, , ['commerce/Classes/Utility/ArticleCreatorUtility.php']['createArticlePreInsert'], ['commerce/Utility/ArticleCreatorUtility']['createArticle']


.. _Tx_Commerce_Utility_BackendUtility_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Utility_BackendUtility, copyProduct, ['commerce/lib/class.tx_commerce_belib.php']['copyProductClass'], ['commerce/Utility/BackendUtility']['copyProduct']
	, , ['commerce/Classes/Utility/BackendUtility.php']['copyProduct'], ['commerce/Utility/BackendUtility']['copyProduct']
	, copyCategory, ['commerce/lib/class.tx_commerce_belib.php']['copyCategoryClass'], ['commerce/Utility/BackendUtility']['copyCategory']
	, , ['commerce/Classes/Utility/BackendUtility.php']['copyCategory'], ['commerce/Utility/BackendUtility']['copyCategory']
	, overwriteProduct, ['commerce/lib/class.tx_commerce_belib.php']['overwriteProductClass'], ['commerce/Utility/BackendUtility']['overwriteProduct']
	, , ['commerce/Classes/Utility/BackendUtility.php']['overwriteProduct'], ['commerce/Utility/BackendUtility']['overwriteProduct']


.. _Tx_Commerce_Utility_DataHandlerUtility_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Utility_DataHandlerUtility, showCopyWizard, ['commerce/mod_cce/class.tx_commerce_cce_db.php']['copyWizardClass'], ['commerce/Utility/DataHandlerUtility']['showCopyWizard']
	, , ['commerce/Classes/Utility/DataHandlerUtility.php']['copyWizard'], ['commerce/Utility/DataHandlerUtility']['showCopyWizard']
	, commitCommand, ['commerce/mod_cce/class.tx_commerce_cce_db.php']['commitCommandClass'], ['commerce/Utility/DataHandlerUtility']['commitCommand']
	, , ['commerce/Classes/Utility/DataHandlerUtility.php']['commitCommand'], ['commerce/Utility/DataHandlerUtility']['commitCommand']


.. _Tx_Commerce_Utility_FolderUtility_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Utility_FolderUtility, initFolders, ['commerce/class.tx_commerce_create_folder.php']['postcreatefolder'], "Method got removed from the api"
	, , ['commerce/class.tx_commerce_create_folder.php']['precreatesyscategory'], "Method got removed from the api"
	, , ['commerce/class.tx_commerce_create_folder.php']['postcreatesyscategory'], "Method got removed from the api"
	, , ['commerce/class.tx_commerce_create_folder.php']['postcreatediliveryarticles'], "Method got removed from the api"


.. _Tx_Commerce_Utility_GeneralUtility_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Utility_GeneralUtility, generateSessionKey, ['commerce/lib/class.tx_commerce_div.php']['generateSessionKey'], ['commerce/Utility/GeneralUtility']['generateSessionKey']
	, , ['commerce/Classes/Utility/GeneralUtility.php']['generateSessionKey'], ['commerce/Utility/GeneralUtility']['generateSessionKey']
	, sendMail, ['commerce/lib/class.tx_commerce_div.php']['sendMail'], ['commerce/Utility/GeneralUtility']['sendMail']
	, , ['commerce/Classes/Utility/GeneralUtility.php']['sendMail'], ['commerce/Utility/GeneralUtility']['sendMail']


.. _Tx_Commerce_ViewHelpers_Navigation_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_ViewHelpers_Navigation, makeArrayPostRender, ['commerce/lib/class.tx_commerce_db_navigation.php']['sortingOrder'], ['commerce/ViewHelpers/Navigation']['makeArrayPostRender']
	, , ['commerce/Classes/ViewHelpers/Navigation.php']['sortingOrder'], ['commerce/ViewHelpers/Navigation']['makeArrayPostRender']
	, makeSubChildArrayPostRender, ['commerce/lib/class.tx_commerce_db_navigation.php']['sortingOrder'], ['commerce/ViewHelpers/Navigation']['makeSubChildArrayPostRender']
	, , ['commerce/Classes/ViewHelpers/Navigation.php']['sortingOrder'], ['commerce/ViewHelpers/Navigation']['makeSubChildArrayPostRender']


.. _Tx_Commerce_ViewHelpers_TreelibTceforms_hooks:
.. csv-table::
	:header: Class, Method, Hook, Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_ViewHelpers_TreelibTceforms, processItemArrayForBrowseableTreeDefault, ['commerce/treelib/class.tx_commerce_treelib_tceforms.php']['processItemArrayForBrowseableTreeDefault'], ['commerce/ViewHelpers/TreelibTceforms']['processItemArrayForBrowseableTreeDefault']
	, , ['commerce/Classes/ViewHelpers/TreelibTceforms.php']['processItemArrayForBrowseableTreeDefault'], ['commerce/ViewHelpers/TreelibTceforms']['processItemArrayForBrowseableTreeDefault']
