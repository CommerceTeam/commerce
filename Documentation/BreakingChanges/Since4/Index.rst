.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Since Version 4
===============


Remove of iframe rendering the category tree
--------------------------------------------

As the div rendering mode is stable the iframe rendering gets removed.
Its not needed anymore and is discouraged due to reduce the entry points in total.


Removed file
------------

Classes/ViewHelpers/IframeTreeBrowser.php


Removed constansts
------------------

.. container:: php

	========================================= =========================================
	Key                                       Replacement
	========================================= =========================================
	COMMERCE_EXTkey                           removed
	COMMERCE_EXTKEY                           removed
	PATH_txcommerce                           PATH_TXCOMMERCE
	PATH_txcommerce_rel                       PATH_TXCOMMERCE_REL
	PATH_txcommerce_icon_table_rel            PATH_TXCOMMERCE_ICON_TABLE_REL
	PATH_txcommerce_icon_tree_rel             PATH_TXCOMMERCE_ICON_TREE_REL
	NORMALArticleType                         NORMALARTICLETYPE
	PAYMENTArticleType                        PAYMENTARTICLETYPE
	DELIVERYArticleType                       DELIVERYARTICLETYPE


Renamed module key
------------------

Since 4.x the key of the main module of commerce isn't **txcommerceM1** but **commerce**


Changed configuration
---------------------

Replaced
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['extConf']
	with
	$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']


Renamed columns
---------------

pages.tx_graytree_foldername to pages.tx_commerce_foldername
The update script takes care of migrating content after the database analyzer added the new columns previously.

!!! Attention do not remove columns with the database analyzer before the content was migrated !!!