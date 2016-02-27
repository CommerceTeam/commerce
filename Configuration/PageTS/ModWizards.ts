mod.web_list.deniedNewTables := addToList(tx_commerce_address_types, tx_commerce_attribute_correlationtypes, tx_commerce_attribute_values, tx_commerce_baskets, tx_commerce_orders, tx_commerce_order_articles, tx_commerce_order_types, tx_commerce_salesfigures, tx_commerce_supplier, tx_commerce_tracking, tx_commerce_trackingcodes, tx_commerce_user_states)

# module and foldername to match
[CommerceTeam\Commerce\Utility\TyposcriptConfig web_list, commerce]
mod.web_list.allowedNewTables = tx_commerce_moveordermails, tx_commerce_newclients, tx_commerce_trackingcodes, tx_commerce_user_states, pages_language_overlay
mod.web_list.deniedNewTables := removeFromList(tx_commerce_moveordermails, tx_commerce_newclients, tx_commerce_trackingcodes, tx_commerce_user_states)
[end]

[CommerceTeam\Commerce\Utility\TyposcriptConfig web_list, attributes]
mod.web_list.allowedNewTables = tx_commerce_attributes, pages_language_overlay
[products]

[CommerceTeam\Commerce\Utility\TyposcriptConfig web_list, products]
mod.web_list.allowedNewTables = pages_language_overlay
[products]

[CommerceTeam\Commerce\Utility\TyposcriptConfig web_list, 'orders,incoming,working,waiting,delivered']
mod.web_list.allowedNewTables = pages_language_overlay
[end]

[CommerceTeam\Commerce\Utility\TyposcriptConfig commerce_category, products]
mod.web_list.allowedNewTables = tx_commerce_products, tx_commerce_categories, pages_language_overlay
[end]

[CommerceTeam\Commerce\Utility\TyposcriptConfig commerce_order, 'orders,incoming,working,waiting,delivered']
mod.web_list.allowedNewTables = tx_commerce_orders, tx_commerce_order_articles
[end]

mod.commerce_category.enableDisplayBigControlPanel = selectable
mod.commerce_category.enableClipBoard = selectable
mod.commerce_category.enableLocalizationView = selectable
mod.commerce_category.enableDisplayBigControlPanel = selectable

mod.wizards {
	newContentElement {
		wizardItems {
			plugins {
				elements {
					commerce_pi1 {
						icon = ../typo3conf/ext/commerce/Resources/Public/Icons/ce_wiz.gif
						title = LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi1
						description = LLL:EXT:sessionplaner/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi1.wiz_description
						tt_content_defValues {
							CType = list
							list_type = commerce_pi1
						}
					}

					commerce_pi2 {
						icon = ../typo3conf/ext/commerce/Resources/Public/Icons/ce_wiz.gif
						title = LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi2
						description = LLL:EXT:sessionplaner/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi2.wiz_description
						tt_content_defValues {
							CType = list
							list_type = commerce_pi2
						}
					}

					commerce_pi3 {
						icon = ../typo3conf/ext/commerce/Resources/Public/Icons/ce_wiz.gif
						title = LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi3
						description = LLL:EXT:sessionplaner/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi3.wiz_description
						tt_content_defValues {
							CType = list
							list_type = commerce_pi3
						}
					}

					commerce_pi4 {
						icon = ../typo3conf/ext/commerce/Resources/Public/Icons/ce_wiz.gif
						title = LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi4
						description = LLL:EXT:sessionplaner/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi4.wiz_description
						tt_content_defValues {
							CType = list
							list_type = commerce_pi4
						}
					}

					commerce_pi6 {
						icon = ../typo3conf/ext/commerce/Resources/Public/Icons/ce_wiz.gif
						title = LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi6
						description = LLL:EXT:sessionplaner/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi6.wiz_description
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