[userFunc = user_isCommercePage()]
mod.web_list.allowedNewTables = tx_commerce_products,tx_commerce_categories
[GLOBAL]

mod.txcommerceM1_category.enableDisplayBigControlPanel = selectable
mod.txcommerceM1_category.enableClipBoard = selectable
mod.txcommerceM1_category.enableLocalizationView = selectable
mod.txcommerceM1_orders.enableDisplayBigControlPanel = selectable

mod.wizards {
	newContentElement {
		wizardItems {
			plugins {
				elements {
					commerce_pi1 {
						icon = ../typo3conf/ext/commerce/Resources/Public/Icons/ce_wiz.gif
						title = LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi1
						description = LLL:EXT:sessionplaner/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi1.wiz_description
						tt_content_defValues {
							CType = list
							list_type = commerce_pi1
						}
					}

					commerce_pi2 {
						icon = ../typo3conf/ext/commerce/Resources/Public/Icons/ce_wiz.gif
						title = LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi2
						description = LLL:EXT:sessionplaner/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi2.wiz_description
						tt_content_defValues {
							CType = list
							list_type = commerce_pi2
						}
					}

					commerce_pi3 {
						icon = ../typo3conf/ext/commerce/Resources/Public/Icons/ce_wiz.gif
						title = LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi3
						description = LLL:EXT:sessionplaner/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi3.wiz_description
						tt_content_defValues {
							CType = list
							list_type = commerce_pi3
						}
					}

					commerce_pi4 {
						icon = ../typo3conf/ext/commerce/Resources/Public/Icons/ce_wiz.gif
						title = LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi4
						description = LLL:EXT:sessionplaner/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi4.wiz_description
						tt_content_defValues {
							CType = list
							list_type = commerce_pi4
						}
					}

					commerce_pi6 {
						icon = ../typo3conf/ext/commerce/Resources/Public/Icons/ce_wiz.gif
						title = LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi6
						description = LLL:EXT:sessionplaner/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi6.wiz_description
						tt_content_defValues {
							CType = list
							list_type = commerce_pi6
						}
					}
				}
			}
		}
	}
}