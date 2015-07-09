.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Tutorial
========


Category-navigation
-------------------

Use the following TypoScript-Code in order to get a category navigation:

::

	lib.tx_commerce.navigation  = HMENU
	lib.tx_commerce.navigation {
		special = userfunction
		special.userFunc = user_tx_commerce_catmenu_pub->init
		special {
			// category for first level
			category = {$plugin.tx_commerce_lib.catUid}
			// show products
			showProducts = 0

			// comma-separated list of fields, which shall additionally be processed in the menu
			additionalFields = teaser, teaserimages
			// PID fuer die Anzeige der Seite
			overridePid = {$plugin.tx_commerce_lib.overridePid}
			// EntryLervel
			entryLevel = 0

			// list of categories, where the manufacturer will be shown with the category in the
			// menu
			displayMenuForCat <

			// add parameter deepth an dpath to URL for a even faster rendering of the navigation
			useRootlineInformationToUrl = {$plugin.tx_commerce_lib.useRootlineInformationToUrl}
			// menu level

			1 = TMENU
			2 = TMENU
			3 = TMENU
		}
		1 = TMENU
		1.itemArrayProcFunc = user_tx_commerce_catmenu_pub->clear
		2 = TMENU
		2.itemArrayProcFunc = user_tx_commerce_catmenu_pub->clear
		3 = TMENU
		3.itemArrayProcFunc = user_tx_commerce_catmenu_pub->clear
	}

