.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Hooks
=====

This document describes all the hooks in the typo3 extension commerce (version 0.8.17) as of 01/04/2007. The hooks are sorted by
the classes and functions they can be found in. Each hook has a type which is either Single or Multiple. Single hook classes are
written directly to the $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/{namespacepart/classname}'][$hookname] variable as there
may never be more than one hook-object. For hooks with the type "multiple"
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][commerce/{namespacepart/classname}][$hookname][] is an array which
can contain multiple hook objects. Each of them is called in the order they are found in the array by foreach. Some hook-methods
can return data which is used by the calling function. Others may return some values but they are ignored. For those I used the
return type void as known from the C programming language.


Single object hooks
-------------------

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/' . $classname][$hookname]

Controller/BaseController
_________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	formatAttributeValue                      formatAttributeValue                      | $matrixKey                           | Integer
	                                                                                    | $attributeUid                        | Integer
	                                                                                    | $attributeValue                      | String
	                                                                                    | $result                              | String
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    | **Result**                           | String
	========================================= ========================================= ====================================== ===================================================================

Controller/BasketController
___________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	generateBasket                            | makeDelivery                            | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                          |                                         | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                          |                                         | $result                              | String
	                                          |                                         | **Result**                           | String
	                                          | makePayment                             | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $result                              | String
	                                                                                    | **Result**                           | String
	alternativePrefixId                       singleDisplayPrefixId                     | **Result**                           | String
	========================================= ========================================= ====================================== ===================================================================

Domain/Model/Article
____________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	getActualPriceforScaleUid                 getActualPriceforScaleUid                 | $count                               | Integer
	                                                                                    | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                                                                    | **Result**                           | Integer
	getSpecialPrice                           specialPrice                              | $specialPrice                        | Array
	                                                                                    | $priceUids                           | Array
	                                                                                    | **Result**                           | void
	calculateDeliveryCosts                    | calculateDeliveryCostNet                | $deliveryCostNet                     | Integer
	                                          |                                         | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                          |                                         | **Result**                           | void
	                                          | calculateDeliveryCostGross              | $deliveryCostGross                   | Integer
	                                                                                    | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Domain/Repository/ArticleRepository
___________________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	getPrices                                 | priceOrder                              | $orderField                          | String
	                                          |                                         | **Result**                           | String
	                                          | additionalPriceWhere                    | $parentRepository                    | \\CommerceTeam\\Commerce\\Domain\\Repository\\ArticleRepository
	                                                                                    | $uid                                 | Integer
	                                                                                    | **Result**                           | String
	========================================= ========================================= ====================================== ===================================================================

Domain/Repository/CategoryRepository
____________________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	getChildCategories                        | categoryOrder                           | $orderField                          | String
	                                          |                                         | $parentRepository                    | \\CommerceTeam\\Commerce\\Domain\\Repository\\CategoryRepository
	                                          |                                         | **Result**                           | String
	                                          | categoryQueryPostHook                   | $data                                | Array
	                                                                                    | $parentRepository                    | \\CommerceTeam\\Commerce\\Domain\\Repository\\CategoryRepository
	                                                                                    | **Result**                           | Array
	getChildProducts                          | productOrder                            | $orderField                          | String
	                                          |                                         | $parentRepository                    | \\CommerceTeam\\Commerce\\Domain\\Repository\\CategoryRepository
	                                          |                                         | **Result**                           | String
	                                          | productQueryPreHook                     | $queryArray                          | Array
	                                          |                                         | $parentRepository                    | \\CommerceTeam\\Commerce\\Domain\\Repository\\CategoryRepository
	                                          |                                         | **Result**                           | Array
	                                          | productQueryPostHook                    | $data                                | Array
	                                                                                    | $parentRepository                    | \\CommerceTeam\\Commerce\\Domain\\Repository\\CategoryRepository
	                                                                                    | **Result**                           | Array
	========================================= ========================================= ====================================== ===================================================================

Domain/Repository/ProductRepository
___________________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	getArticles                               | articleOrder                            | $orderField                          | String
	                                          |                                         | **Result**                           | String
	                                          | additionalWhere                         | $where                               | String
	                                                                                    | **Result**                           | String
	========================================= ========================================= ====================================== ===================================================================

Hook/OrdermailHooks
___________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	generateMail                              processMarker                             | $markerArray                         | Array
	                                                                                    | $parentHook                          | \\CommerceTeam\\Commerce\\Hook\\OrdermailHooks
	                                                                                    | **Result**                           | Array
	========================================= ========================================= ====================================== ===================================================================

Utility/ArticleCreatorUtility
_____________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	createArticle                             preinsert                                 | $articleData                         | Array
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

ViewHelpers/Navigation
______________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	makeArrayPostRender                       sortingOrder                              | $sorting                             | String
	                                                                                    | $uidRoot                             | Integer
	                                                                                    | $mainTable                           | String
	                                                                                    | $tableMm                             | String
	                                                                                    | $mDepth                              | Integer
	                                                                                    | $path                                | Integer
	                                                                                    | $parentViewHelper                    | \\CommerceTeam\\Commerce\\ViewHelper\\Navigation
	                                                                                    | **Result**                           | String
	makeSubChildArrayPostRender               sortingOrder                              | $sorting                             | String
	                                                                                    | $categoryUid                         | Integer
	                                                                                    | $mainTable                           | String
	                                                                                    | $mmTable                             | String
	                                                                                    | $mDepth                              | Integer
	                                                                                    | $path                                | Integer
	                                                                                    | $parentViewHelper                    | \\CommerceTeam\\Commerce\\ViewHelper\\Navigation
	========================================= ========================================= ====================================== ===================================================================


Multiple object hooks
---------------------

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/' . $classname][$hookname][]

Controller/AddressesController
______________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	getListing                                | processAddressMarker                    | $itemMarkerArray                     | Array
	                                          |                                         | $address                             | Array
	                                          |                                         | $piArray                             | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                          |                                         | **Result**                           | Array
	                                          | processListingMarker                    | $baseMarkerArray                     | Array
	                                                                                    | $linkMarkerArray                     | Array
	                                                                                    | $addressItems                        | String
	                                                                                    | $addressType                         | Integer
	                                                                                    | $piArray                             | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                                                                    | **Result**                           | void
	getAddressForm                            | preProcessAddress                       | $addressData                         | Array
	                                          |                                         | $action                              | String
	                                          |                                         | $addressUid                          | Integer
	                                          |                                         | $addressType                         | String
	                                          |                                         | $config                              | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                          |                                         | **Result**                           | Array
	                                          | processAddressfieldsMarkerArray         | $fieldsMarkerArray                   | Array
	                                          |                                         | $fieldTemplate                       | String
	                                          |                                         | $addressData                         | Array
	                                          |                                         | $action                              | String
	                                          |                                         | $addressUid                          | Integer
	                                          |                                         | $config                              | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                          |                                         | **Result**                           | Array
	                                          | processAddressFormMarker                | $baseMarkerArray                     | Array
	                                                                                    | $action                              | String
	                                                                                    | $addressUid                          | Integer
	                                                                                    | $addressData                         | Array
	                                                                                    | $config                              | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                                                                    | **Result**                           | void
	deleteAddress                             deleteAddress                             | $addressid                           | Integer
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                                                                    | **Result**                           | String
	getInputField                             postGetInputField                         | $content                             | String
	                                                                                    | $fieldName                           | String
	                                                                                    | $fieldConfig                         | Array
	                                                                                    | $fieldValue                          | Mixed
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                                                                    | **Result**                           | String
	checkAddressForm                          validationMethod_*                        | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                                                                    | $fieldName                           | String
	                                                                                    | $fieldValue                          | Mixed
	                                                                                    | **Result**                           | Boolean
	saveAddress                               | beforeAddressSave                       | $newData                             | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                          |                                         | **Result**                           | void
	                                          | afterAddressSave                        | $newUid                              | Integer
	                                          |                                         | $newData                             | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                          |                                         | **Result**                           | void
	                                          | beforeAddressEdit                       | $addressid                           | Integer
	                                          |                                         | $newData                             | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                          |                                         | **Result**                           | void
	                                          | afterAddressEdit                        | $addressid                           | Integer
	                                                                                    | $newData                             | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                                                                    | **Result**                           | void
	getAddresses                              editSelectStatement                       | $select                              | String
	                                                                                    | $userId                              | Integer
	                                                                                    | $addressType                         | Integer
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\AddressesController
	                                                                                    | **Result**                           | String
	========================================= ========================================= ====================================== ===================================================================

Controller/BaseController
_________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	addAdditionalLocallang                    loadAdditionalLocallang                   | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    | **Result**                           | void
	makeListView                              additionalMarker                          | $markerArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    | **Result**                           | Array
	getArticleMarker                          additionalMarkerArticle                   | $markerArray                         | Array
	                                                                                    | $article                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    | **Result**                           | Array
	makeBasketView                            postBasketView                            | $content                             | String
	                                                                                    | $articleTypes                        | Array
	                                                                                    | $lineTemplate                        | String
	                                                                                    | $template                            | String
	                                                                                    | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    | **Result**                           | Array
	makeBasketInformation                     | processMarkerTaxInformation             | $taxRateTemplate                     | String
	                                          |                                         | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                          |                                         | **Result**                           | String
	                                          | processMarkerBasketInformation          | $markerArray                         | Array
	                                                                                    | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    | **Result**                           | Array
	makeLineView                              processMarkerLineView                     | $markerArray                         | Array
	                                                                                    | $basketItem                          | \\CommerceTeam\\Commerce\\Domain\\Model\\BasketItem
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    | **Result**                           | Array
	renderValue                               postRenderValue                           | $output                              | String
	                                                                                    | $params = array(                     |
	                                                                                    |     0 => $value                      | Mixed
	                                                                                    |     1 => $typoscriptType             | String
	                                                                                    |     2 => $typoscriptConfig           | Array
	                                                                                    |     3 => $field                      | String
	                                                                                    |     4 => $table                      | String
	                                                                                    |     5 => $uid                        | Integer
	                                                                                    | )
	                                                                                    | **Result**                           | String
	renderElement                             additionalMarkerElement                   | $markerArray                         | Array
	                                                                                    | $element                             | \\CommerceTeam\\Commerce\\Domain\\Model\\AbstractEntity
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    | **Result**                           | Array
	renderProductsForList                     preProcessorProductsListView              | $products                            | Array
	                                                                                    | $templateMarker                      | Array
	                                                                                    | $iterations                          | Integer
	                                                                                    | $typoscriptMarker                    | String
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    | **Result**                           | Array
	renderProduct                             | postProcessLinkArray                    | $linkArray                           | Array
	                                          |                                         | $product                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Product
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                          |                                         | **Result**                           | Array
	                                          | additionalMarkerProduct                 | $markerArray                         | Array
	                                          |                                         | $product                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Product
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                          |                                         | **Result**                           | Array
	                                          | additionalSubpartsProduct               | $subpartArray                        | Array
	                                          |                                         | $product                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Product
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                          |                                         | **Result**                           | Array
	                                          | modifyContentProduct                    | $content                             | String
	                                                                                    | $product                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Product
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    | **Result**                           | String
	========================================= ========================================= ====================================== ===================================================================

Controller/BasketController
___________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	main                                      | postInit                                | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                          |                                         | **Result**                           | String|Boolean
	                                          | additionalMarker                        | $markerArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | **Result**                           | Array
	handleDeleteBasket                        postdelBasket                             | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | **Result**                           | void
	handleAddArticle                          | preartAddUid                            | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                          |                                         | **Result**                           | void
	                                          | postDeleteArtUidSingle                  | $articleUid                          | Integer
	                                          |                                         | $articleAddValues                    | Array
	                                          |                                         | $oldCountValue                       | Integer
	                                          |                                         | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                          |                                         | **Result**                           | void
	                                          | preartAddUidSingle                      | $articleUid                          | Integer
	                                          |                                         | $articleAddValues                    | Array
	                                          |                                         | $product                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Product
	                                          |                                         | $article                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                          |                                         | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                          |                                         | **Result**                           | void
	                                          | postartAddUidSingle                     | $articleUid                          | Integer
	                                          |                                         | $articleAddValues                    | Array
	                                          |                                         | $product                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Product
	                                          |                                         | $article                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                          |                                         | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                          |                                         | **Result**                           | void
	                                          | postartAddUid                           | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | **Result**                           | void
	handlePaymentArticle                      postpayArt                                | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | **Result**                           | void
	handleDeliveryArticle                     postdelArt                                | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | **Result**                           | void
	getQuickView                              additionalMarker                          | $basketArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | **Result**                           | Array
	generateBasketMarker                      additionalMarker                          | $basketArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | $template                            | String
	                                                                                    | **Result**                           | Array
	makeDelivery                              deliveryAllowedArticles                   | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | $allowedArticles                     | Array[Integer]
	                                                                                    | **Result**                           | Array
	makePayment                               paymentAllowedArticles                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | $allowedArticles                     | Array[Integer]
	                                                                                    | **Result**                           | Array
	makeArticleView                           additionalMarker                          | $markerArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | $article                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                                                                    | $product                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Product
	                                                                                    | $basketItem                          | \\CommerceTeam\\Commerce\\Domain\\Model\\BasketItem
	                                                                                    | **Result**                           | Array
	makeProductList                           | changeProductTemplate                   | $templateMarker                      | Array
	                                          |                                         | $basketItem                          | \\CommerceTeam\\Commerce\\Domain\\Model\\BasketItem
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                          |                                         | **Result**                           | Array
	                                          | additionalMarkerProductList             | $markerArray                         | Array
	                                                                                    | $basketItem                          | \\CommerceTeam\\Commerce\\Domain\\Model\\BasketItem
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    | **Result**                           | Array
	========================================= ========================================= ====================================== ===================================================================

Controller/CheckoutController
_____________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	init                                      checkoutSteps                             | $checkoutSteps                       | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | void
	main                                      | processData                             | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | void
	                                          | preSwitch                               | $currentStep                         | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | void
	                                          | {*}                                     | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | String
	                                          | postSwitch                              | $currentStep                         | String
	                                          |                                         | $content                             | String
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | String
	                                          | postRender                              | $currentStep                         | String
	                                                                                    | $content                             | String
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | String
	getBillingAddress                         processMarker                             | $markerArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | Array
	getDeliveryAddress                        processMarker                             | $markerArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | Array
	handlePayment                             | alternativePaymentStep                  | $payment                             | \\CommerceTeam\\Commerce\\Payment\\PaymentInterface
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | String
	                                          | processMarker                           | $markerArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | Array
	getListing                                processMarker                             | $markerArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | Array
	finishIt                                  | prepayment                              | $payment                             | \\CommerceTeam\\Commerce\\Payment\\PaymentInterface
	                                          |                                         | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                          |                                         | **Result**                           | void
	                                          | postpayment                             | $payment                             | \\CommerceTeam\\Commerce\\Payment\\PaymentInterface
	                                          |                                         | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | void
	                                          | afterMailSend                           | $orderData                           | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | Array
	                                          | processMarker                           | $markerArray                         | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | Array
	                                          | postFinish                              | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | void
	getInstanceOfTceMain                      generateOrderId                           | $orderId                             | String
	                                                                                    | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | String
	getBasketSum                              processMarker                             | $markerArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | Array
	validateAddress                           | {*}                                     | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | $fieldName                           | String
	                                          |                                         | $value                               | Mixed
	                                          |                                         | **Result**                           | Boolean
	                                          | validateField                           | $params                              | Array[fieldName, fieldValue, addressType, config]
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | Boolean
	getInputForm                              processInputForm                          | $fieldName                           | String
	                                                                                    | $fieldMarkerArray                    | Array
	                                                                                    | $config                              | Array
	                                                                                    | $step                                | String
	                                                                                    | $fieldCodeTemplate                   | String
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | void
	handleAddress                             | preProcessUserData                      | $feuData                             | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | void
	                                          | postProcessUserData                     | $feuData                             | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | void
	                                          | preProcessAddressData                   | $addressData                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | Array
	canMakeCheckout                           | canMakeCheckoutOwnTests                 | $allChecks                           | Array
	                                          |                                         | $myCheck                             | Boolean
	                                          |                                         | **Result**                           | void
	                                          | canMakeCheckoutOwnAdvancedTests         | $params = array(                     |
	                                                                                    |     'checks' => &$checks             | Array
	                                                                                    |     'myCheck' => &$myCheck           | Boolean
	                                                                                    | )
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | void
	sendUserMail                              | getUserMail                             | $userMail                            | String
	                                          |                                         | $orderUid                            | Integer
	                                          |                                         | $orderData                           | Array
	                                          |                                         | **Result**                           | void
	                                          | preGenerateMail                         | $userMail                            | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | void
	                                          | postGenerateMail                        | $userMail                            | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $mailcontent                         | String
	                                                                                    | **Result**                           | void
	sendAdminMail                             | preGenerateMail                         | $adminMail                           | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | void
	                                          | postGenerateMail                        | $adminMail                           | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | $mailcontent                         | String
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | void
	generateMail                              processMarker                             | $markerArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | Array
	saveOrder                                 | preinsert                               | $orderData                           | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | void
	                                          | modifyBasketPreSave                     | $basket                              | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | **Result**                           | void
	                                          | modifyOrderArticlePreSave               | $newUid                              | Integer
	                                          |                                         | $orderArticleData                    | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                          |                                         | $basketItem                          | \\CommerceTeam\\Commerce\\Domain\\Model\\BasketItem
	                                          |                                         | **Result**                           | void
	                                          | modifyOrderArticlePostSave              | $newUid                              | Integer
	                                                                                    | $orderArticleData                    | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\CheckoutController
	                                                                                    | **Result**                           | void
	getInstanceOfTceMain                      postTcaInit                               | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Controller/InvoiceController
____________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	main                                      additionalMarker                          | $markerArray                         | Array
	                                                                                    | $subpartArray                        | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\InvoiceController
	                                                                                    | **Result**                           | Array
	========================================= ========================================= ====================================== ===================================================================

Controller/ListController
_________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	init                                      | preInit                                 | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\ListController
	                                          |                                         | **Result**                           | void
	                                          | postInit                                | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\ListController
	                                                                                    | **Result**                           | void
	renderSingleView                          | preRenderSingleView                     | $product                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Product
	                                          |                                         | $category                            | \\CommerceTeam\\Commerce\\Domain\\Model\\Category
	                                          |                                         | $subpartName                         | String
	                                          |                                         | $subpartNameNostock                  | String
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\ListController
	                                          |                                         | **Result**                           | String
	                                          | additionalMarker                        | $markerArray                         | Array
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\ListController
	                                                                                    | **Result**                           | Array
	makeArticleView                           | additionalMarker                        | $markerArray                         | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\ListController
	                                          |                                         | $article                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                          |                                         | **Result**                           | Array
	                                          | additionalAttributeMarker               | $markerArrayItem                     | Array
	                                          |                                         | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\ListController
	                                          |                                         | $uid                                 | Integer
	                                          |                                         | **Result**                           | Array
	                                          | additionalMarkerMakeArticleView         | $markerArray                         | Array
	                                                                                    | $product                             | \\CommerceTeam\\Commerce\\Domain\\Model\\Product
	                                                                                    | $parentController                    | \\CommerceTeam\\Commerce\\Controller\\ListController
	                                                                                    | **Result**                           | Array
	========================================= ========================================= ====================================== ===================================================================

Domain/Model/Article
____________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	init                                      postinit                                  | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Domain/Model/ArticlePrice
_________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	init                                      postinit                                  | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\ArticlePrice
	                                                                                    | **Result**                           | void
	getPriceNet                               postpricenet                              | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\ArticlePrice
	                                                                                    | **Result**                           | void
	getPriceGross                             postpricegross                            | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\ArticlePrice
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Domain/Model/Attribute
______________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	init                                      postinit                                  | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\Attribute
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Domain/Model/AttributeValue
___________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	init                                      postinit                                  | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\AttributeValue
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Domain/Model/Basket
___________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	loadDataFromDatabase                      loadDataFromDatabase                      | $returnData                          | Array
	                                                                                    | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | **Result**                           | void
	loadPersistentDataFromDatabase            loadPersistantDataFromDatabase            | $returnData                          | Array
	                                                                                    | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    | **Result**                           | void
	storeDataToDatabase                       storeDataToDatabase                       | $oneItem                             | \\CommerceTeam\\Commerce\\Domain\\Model\\BasketItem
	                                                                                    | $insertData                          | Array
	                                                                                    | **Result**                           | Array
	========================================= ========================================= ====================================== ===================================================================

Domain/Model/Category
_____________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	init                                      postinit                                  | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\Category
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Domain/Model/Product
____________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	init                                      postinit                                  | $parentModel                         | \\CommerceTeam\\Commerce\\Domain\\Model\\Product
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Hook/DatamapHooks
_________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	preProcessOrder                           | moveOrdersPreMoveOrder                  | $order                               | Array
	                                          |                                         | $incomingFieldArray                  | Array
	                                          |                                         | **Result**                           | void
	                                          | moveOrdersPostMoveOrder                 | $order                               | Array
	                                                                                    | $incomingFieldArray                  | Array
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Hook/OrdermailHooks
___________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	ordermoveSendMail                         postOrdermoveSendMail                     | $mailconf                            | Array
	                                                                                    | $orderdata                           | Array
	                                                                                    | $template                            | String
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Tree/Leaf/Data
______________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	loadRecords                               addExtendedFields                         | $itemTable                           | String
	                                                                                    | $extendedFields                      | String
	                                                                                    | **Result**                           | String
	========================================= ========================================= ====================================== ===================================================================

Tree/Leaf/MasterData
____________________

.. container:: ts-properties

	========================================= =========================================== ====================================== ===================================================================
	Hookname                                  Call                                        Parameter / Result                     Type
	========================================= =========================================== ====================================== ===================================================================
	getRecordsByMountpoints                   | getRecordsByMountpointsPreLoadRecords     | $positions                           | Array
	                                          |                                           | $parentLeaf                          | \\CommerceTeam\\Commerce\\Tree\\Leaf\\MasterData
	                                          |                                           | **Result**                           | void
	                                          | getRecordsByMountpointsPostProcessRecords | $records                             | Array
	                                                                                      | $parentLeaf                          | \\CommerceTeam\\Commerce\\Tree\\Leaf\\MasterData
	                                                                                      | **Result**                           | void
	========================================= =========================================== ====================================== ===================================================================

Utility/BackendUtility
______________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	copyProduct                               | beforeCopy                              | $uid                                 | Integer
	                                          |                                         | $uidLast                             | Integer
	                                          |                                         | $overrideArray                       | Array
	                                          |                                         | **Result**                           | void
	                                          | afterCopy                               | $newUid                              | Integer
	                                                                                    | $uid                                 | Integer
	                                                                                    | $overrideArray                       | Array
	                                                                                    | **Result**                           | void
	copyCategory                              | beforeCopy                              | $uid                                 | Integer
	                                          |                                         | $uidLast                             | Integer
	                                          |                                         | $overrideArray                       | Array
	                                          |                                         | **Result**                           | void
	                                          | afterCopy                               | $newUid                              | Integer
	                                                                                    | $uid                                 | Integer
	                                                                                    | $overrideArray                       | Array
	                                                                                    | **Result**                           | void
	overwriteProduct                          | beforeOverwrite                         | $uidFrom                             | Integer
	                                          |                                         | $uidTo                               | Integer
	                                          |                                         | $datamap                             | Array
	                                          |                                         | **Result**                           | void
	                                          | beforeCopy                              | $uidFrom                             | Integer
	                                                                                    | $uidTo                               | Integer
	                                                                                    | $datamap                             | Array
	                                                                                    | $tce                                 | \\TYPO3\\CMS\\Core\\DataHandling\\DataHandler
	                                                                                    | **Result**                           | void
	========================================= ========================================= ====================================== ===================================================================

Utility/GeneralUtility
______________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================================
	generateSessionKey                        postGenerateSessionKey                    | $key                                 | String
	                                                                                    | **Result**                           | String
	sendMail                                  | preProcessMail                          | $mailconf                            | Array
	                                          |                                         | $additionalData                      | Array
	                                          |                                         | **Result**                           | void
	                                          | ownMailRendering                        | $mailconf                            | Array
	                                          |                                         | $additionalData                      | Array
	                                          |                                         | $hooks                               | Array
	                                          |                                         | **Result**                           | Boolean
	                                          | postProcessMail                         | $message                             | String
	                                                                                    | $mailconf                            | Array
	                                                                                    | $additionalData                      | Array
	                                                                                    | **Result**                           | String
	========================================= ========================================= ====================================== ===================================================================
