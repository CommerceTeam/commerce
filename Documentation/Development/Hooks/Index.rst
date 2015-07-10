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

	========================================= ========================================= ====================================== ===================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================
	formatAttributeValue                      formatAttributeValue                      $matrixKey                             Integer
	                                                                                    $attributeUid                          Integer
	                                                                                    $attributeValue                        String
	                                                                                    $result                                String
	                                                                                    $parentController                      \\CommerceTeam\\Commerce\\Controller\\BaseController
	                                                                                    Result                                 String
	========================================= ========================================= ====================================== ===================================================

Controller/BasketController
___________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================
	Hookname                                  Call                                      Parameter / Result                     Type
	========================================= ========================================= ====================================== ===================================================
	generateBasket                            makeDelivery                              $parentController                      \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    $basket                                \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    $result                                String
	                                                                                    Result                                 String
	                                          makePayment                               $parentController                      \\CommerceTeam\\Commerce\\Controller\\BasketController
	                                                                                    $basket                                \\CommerceTeam\\Commerce\\Domain\\Model\\Basket
	                                                                                    $result                                String
	                                                                                    Result                                 String
	alternativePrefixId                       singleDisplayPrefixId                     Result                                 String
	========================================= ========================================= ====================================== ===================================================

Domain/Model/Article
____________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================
	Hookname                                  Call                                      Parameter                              Type
	========================================= ========================================= ====================================== ===================================================
	getActualPriceforScaleUid                 getActualPriceforScaleUid                 $count                                 Integer
	                                                                                    $parentModel                           \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                                                                    Result                                 Integer
	getSpecialPrice                           specialPrice                              $specialPrice                          Array
	                                                                                    $priceUids                             Array
	                                                                                    Result                                 void
	calculateDeliveryCosts                    calculateDeliveryCostNet                  $deliveryCostNet                       Integer
	                                                                                    $parentModel                           \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                                                                    Result                                 void
	                                          calculateDeliveryCostGross                $deliveryCostGross                     Integer
	                                                                                    $parentModel                           \\CommerceTeam\\Commerce\\Domain\\Model\\Article
	                                                                                    Result                                 void
	========================================= ========================================= ====================================== ===================================================

Domain/Repository/ArticleRepository
___________________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================
	Hookname                                  Call                                      Parameter                              Type
	========================================= ========================================= ====================================== ===================================================
	getPrices                                 priceOrder                                $orderField                            String
	                                                                                    Result                                 String
	                                          additionalPriceWhere                      $parentRepository                      \
	                                                                                    $uid                                   Integer
	                                                                                    Result                                 String
	========================================= ========================================= ====================================== ===================================================

Domain/Repository/CategoryRepository
____________________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================
	Hookname                                  Call                                      Parameter                              Type
	========================================= ========================================= ====================================== ===================================================
	getChildCategories                        categoryOrder
	                                          categoryQueryPostHook
	getChildProducts                          productOrder
	                                          productQueryPreHook
	                                          productQueryPostHook
	========================================= ========================================= ====================================== ===================================================

Domain/Repository/ProductRepository
___________________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================
	Hookname                                  Call                                      Parameter                              Type
	========================================= ========================================= ====================================== ===================================================
	getArticles                               articleOrder
	                                          additionalWhere
	========================================= ========================================= ====================================== ===================================================

Hook/OrdermailHooks
___________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================
	Hookname                                  Call                                      Parameter                              Type
	========================================= ========================================= ====================================== ===================================================
	generateMail                              processMarker
	========================================= ========================================= ====================================== ===================================================

Utility/ArticleCreatorUtility
_____________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================
	Hookname                                  Call                                      Parameter                              Type
	========================================= ========================================= ====================================== ===================================================
	createArticle                             preinsert
	========================================= ========================================= ====================================== ===================================================

ViewHelpers/Navigation
______________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================
	Hookname                                  Call                                      Parameter                              Type
	========================================= ========================================= ====================================== ===================================================
	makeArrayPostRender                       sortingOrder
	makeSubChildArrayPostRender               sortingOrder
	========================================= ========================================= ====================================== ===================================================


Multiple object hooks
---------------------

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/' . $classname][$hookname][]

Controller/AddressesController
______________________________

.. container:: ts-properties

	========================================= ========================================= ====================================== ===================================================
	Hookname                                  Call                                      Parameter                              Type
	========================================= ========================================= ====================================== ===================================================
	getListing                                processAddressMarker
	                                          processListingMarker
	getAddressForm                            processAddressfieldsMarkerArray
	                                          processAddressFormMarker
	deleteAddress                             deleteAddress
	getInputField                             postGetInputField
	checkAddressForm                          validationMethod_*
	saveAddress                               beforeAddressSave
	                                          afterAddressSave
	                                          beforeAddressEdit
	                                          afterAddressEdit
	getAddresses                              editSelectStatement
	d
	addAdditionalLocallang                    loadAdditionalLocallang
	makeListView                              additionalMarker
	getArticleMarker                          additionalMarkerArticle
	makeBasketView                            postBasketView
	makeBasketInformation                     processMarkerTaxInformation
	                                          processMarkerBasketInformation
	makeLineView                              processMarkerLineView
	renderValue                               postRenderValue
	renderElement                             additionalMarkerElement
	renderProductsForList                     preProcessorProductsListView
	renderProduct                             postProcessLinkArray
	                                          additionalMarkerProduct
	                                          additionalSubpartsProduct
	                                          modifyContentProduct
	d
	main                                      postInit
	                                          additionalMarker
	handleDeleteBasket                        postdelBasket
	handleAddArticle                          preartAddUid
	                                          postDeleteArtUidSingle
	                                          preartAddUidSingle
	                                          postartAddUidSingle
	                                          postartAddUid
	handlePaymentArticle                      postpayArt
	handleDeliveryArticle                     postdelArt
	getQuickView                              additionalMarker
	generateBasketMarker                      additionalMarker
	makeDelivery                              deliveryAllowedArticles
	makePayment                               paymentAllowedArticles
	makeArticleView                           additionalMarker
	makeProductList                           changeProductTemplate
	                                          additionalMarkerProductList
	d
	init                                      checkoutSteps
	main                                      processData
	                                          preSwitch
	                                          {*}
	                                          postSwitch
	                                          postRender
	getBillingAddress                         processMarker
	getDeliveryAddress                        processMarker
	handlePayment                             alternativePaymentStep
	                                          processMarker
	getListing                                processMarker
	finishIt                                  prepayment
	                                          postpayment
	                                          afterMailSend
	                                          processMarker
	                                          postFinish
	getInstanceOfTceMain                      generateOrderId
	getBasketSum                              processMarker
	validateAddress                           {*}
	                                          validateField
	getInputForm                              processInputForm
	handleAddress                             preProcessUserData
	                                          postProcessUserData
	                                          preProcessAddressData
	canMakeCheckout                           canMakeCheckoutOwnTests
	                                          canMakeCheckoutOwnAdvancedTests
	sendUserMail                              getUserMail
	                                          preGenerateMail
	                                          postGenerateMail
	sendAdminMail                             preGenerateMail
	generateMail                              processMarker
	saveOrder                                 preinsert
	                                          modifyBasketPreSave
	                                          modifyOrderArticlePreSave
	                                          modifyOrderArticlePostSave
	getInstanceOfTceMain                      postTcaInit
	d
	main                                      additionalMarker
	d
	init                                      preInit
	                                          postInit
	renderSingleView                          preRenderSingleView
	                                          additionalMarker
	makeArticleView                           additionalMarker
	                                          additionalAttributeMarker
	                                          additionalMarkerMakeArticleView
	d
	init                                      postinit
	d
	init                                      postinit
	getPriceNet                               postpricenet
	getPriceGross                             postpricegross
	d
	init                                      postinit
	d
	init                                      postinit
	d
	loadDataFromDatabase                      loadDataFromDatabase
	loadPersistentDataFromDatabase            loadPersistantDataFromDatabase
	storeDataToDatabase                       storeDataToDatabase
	d
	init                                      postinit
	d
	init                                      postinit
	d
	preProcessOrder                           moveOrdersPreMoveOrder
	                                          moveOrdersPostMoveOrder
	d
	ordermoveSendMail                         postOrdermoveSendMail
	d
	loadRecords                               addExtendedFields
	d
	getRecordsByMountpoints                   getRecordsByMountpointsPreLoadRecords
	                                          getRecordsByMountpointsPostProcessRecords
	d
	copyProduct                               beforeCopy
	                                          afterCopy
	copyCategory                              beforeCopy
	                                          afterCopy
	overwriteProduct                          beforeOverwrite
	                                          beforeCopy
	d
	showCopyWizard                            beforeFormClose
	                                          beforeFormClose
	                                          beforeTransform
	commitCommand                             beforeCommit
	                                          afterCommit
	d
	generateSessionKey                        postGenerateSessionKey
	sendMail                                  preProcessMail
	                                          ownMailRendering
	                                          postProcessMail
	d
	processItemArrayForBrowseableTreeDefault  processDefault
	========================================= ========================================= ====================================== ===================================================

:usage: formatAttributeValue
:parameter:
	- $key String
	- $attributeUid Integer
	- $attributeValue mixed
	- $formatedResult String
	- $baseController \CommerceTeam\Commerce\Controller\BaseController
:return: String