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

.. _Tx_Commerce_Controller_AddressesController:
.. csv-table::
	:header: Class, Method, "Hook", Replacement
	:widths: 25, 10, 35, 30

	Tx_Commerce_Controller_AddressesController, getListing, ['commerce/pi4/class.tx_commerce_pi4.php']['getListing'], ['commerce/Controller/AddressesController']['getListing']
	, , ['commerce/Classes/Controller/AddressesController.php']['getListing'], ['commerce/Controller/AddressesController']['getListing']
	, getAddressForm, ['commerce/pi4/class.tx_commerce_pi4.php']['getAddressFormItem'], ['commerce/Controller/AddressesController']['getAddressFormItem']
	, , ['commerce/Classes/Controller/AddressesController.php']['getAddressFormItem'], ['commerce/Controller/AddressesController']['getAddressFormItem']
	, deleteAddress, ['commerce/pi4/class.tx_commerce_pi4.php']['deleteAddress'], ['commerce/Controller/AddressesController']['deleteAddress']
	, , ['commerce/Classes/Controller/AddressesController.php']['deleteAddress'], ['commerce/Controller/AddressesController']['deleteAddress']
	, checkAddressForm, ['commerce/pi4/class.tx_commerce_pi4.php']['checkAddressForm'], ['commerce/Controller/AddressesController']['checkAddressForm']
	, , ['commerce/Classes/Controller/AddressesController.php']['checkAddressForm'], ['commerce/Controller/AddressesController']['checkAddressForm']
	, saveAddressData, ['commerce/pi4/class.tx_commerce_pi4.php']['saveAddress'], ['commerce/Controller/AddressesController']['saveAddress']
	, , ['commerce/Classes/Controller/AddressesController.php']['saveAddress'], ['commerce/Controller/AddressesController']['saveAddress']
	, getAddresses, ['commerce/pi4/class.tx_commerce_pi4.php']['getAddresses'], ['commerce/Controller/AddressesController']['getAddresses']
	, , ['commerce/Classes/Controller/AddressesController.php']['getAddresses'], ['commerce/Controller/AddressesController']['getAddresses']

