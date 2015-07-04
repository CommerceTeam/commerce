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
Classes/ViewHelpers/IframeTreeBrowser.php

Removed properties
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::iframeContentRendering

Removed methods
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::setIframeContentRendering
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::isIframeContentRendering
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::isIframeRendering
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::getTreeContent
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::setIframeTreeBrowserScript
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::renderIframe
CommerceTeam\Commerce\ViewHelpers\TreelibTceforms::getIframeParameter