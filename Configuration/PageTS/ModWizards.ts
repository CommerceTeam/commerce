[CommerceTeam\Commerce\Utility\TyposcriptConfig]
mod.web_list.allowedNewTables = tx_commerce_products, tx_commerce_categories
[else]
mod.web_list.deniedNewTables = tx_commerce_address_types, tx_commerce_article_prices, tx_commerce_article_types, tx_commerce_articles, tx_commerce_attribute_values, tx_commerce_attributes, tx_commerce_baskets, tx_commerce_categories, tx_commerce_manufacturer, tx_commerce_moveordermails, tx_commerce_newclients, tx_commerce_order_articles, tx_commerce_order_types, tx_commerce_orders, tx_commerce_products, tx_commerce_salesfigures, tx_commerce_supplier, tx_commerce_tracking, tx_commerce_trackingcodes, tx_commerce_user_states
[GLOBAL]

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