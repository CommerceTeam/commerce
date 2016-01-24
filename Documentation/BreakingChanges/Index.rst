.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


Breaking Changes
================

Remove of iframe rendering the category tree
--------------------------------------------

As the div rendering mode is stable the iframe rendering gets removed.
Its not needed anymore and is discouraged due to reduce the entry points in total.

Removed file
------------
Classes/ViewHelpers/IframeTreeBrowser.php

Removed properties
------------------
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::iframeContentRendering

Removed methods
---------------
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::setIframeContentRendering
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::isIframeContentRendering
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::isIframeRendering
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::getTreeContent
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::setIframeTreeBrowserScript
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::renderIframe
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::getIframeParameter

Renamed module key
------------------
Since 4.x the key of the main module of commerce isn't **txcommerceM1** but **commerce**

Removed constansts
------------------
key, replacement
COMMERCE_EXTkey, COMMERCE_EXTKEY
PATH_txcommerce, PATH_TXCOMMERCE
PATH_txcommerce_rel, PATH_TXCOMMERCE_REL
PATH_txcommerce_icon_table_rel, PATH_TXCOMMERCE_ICON_TABLE_REL
PATH_txcommerce_icon_tree_rel, PATH_TXCOMMERCE_ICON_TREE_REL
NORMALArticleType, NORMALARTICLETYPE
PAYMENTArticleType, PAYMENTARTICLETYPE
DELIVERYArticleType, DELIVERYARTICLETYPE

Changed since TYPO3 6.2
-----------------------
Replaced
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']
	with
	$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][COMMERCE_EXTKEY]

Since Version 5
---------------
Removed
_______
- Both the ClassAliasMap.php and LegacyClassesForIde.php. If you need still need to know which class was renamed into
which need name, please copy it from the 4.0.0 tag.
- PATH_TXCOMMERCE_ICON_TREE_REL as it was used only in one path.

Changed
_______
All modules are now useing the core ModuleTemplate api.
FolderRepository::initFolders now returns only the pid as integer of for the parameter given not the parents in an array
FolderRepository::initFolders does not create folders magicaly anymore. Please create it using FolderRepository::createFolder
FolderRepository::initFolders does not call the update utility anymore. Please call it on your own