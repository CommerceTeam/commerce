<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $languageFile . 'tx_commerce_articles',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'cruser_id' => 'cruser_id',
        'versioning' => '1',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY sorting,crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'dividers2tabs' => '1',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_articles.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            title, subtitle, navtitle, description_extra, plain_text, price_gross, price_net, purchase_price, tax,
            article_type_uid, products_uid',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime,
            fe_group, title, subtitle, navtitle, images, ordernumber, eancode, description_extra,plain_text,
            price_gross, price_net, purchase_price, tax, article_type_uid, products_uid, article_attributes',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden.I.0'
                    ]
                ]
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => 0
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 50,
                'items' => [
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.hide_at_login', -1],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.any_login', -2],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.usergroups', '--div--'],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
            ],
        ],
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0,
            ],
        ],
        'l18n_parent' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_commerce_articles',
                'foreign_table_where' => 'AND tx_commerce_articles.pid = ###CURRENT_PID###
                    AND tx_commerce_articles.sys_language_uid IN (-1,0)',
                'default' => 0
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ],
        ],

        'title' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'required,trim',
            ],
        ],
        'subtitle' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.subtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'trim',
            ],
        ],
        'navtitle' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.navtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'images' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.images',
            'l10n_mode' => 'mergeIfNotBlank',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
                'uploadfolder' => 'uploads/tx_commerce',
                'show_thumbs' => 1,
                'size' => 3,
                'minitems' => 0,
                'maxitems' => 200,
                'autoSizeMax' => 40,
            ],
        ],
        'ordernumber' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.ordernumber',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'eancode' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.eancode',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '20',
                'eval' => 'trim',
            ],
        ],
        'description_extra' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.description_extra',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
                'wizards' => [
                    '_PADDING' => 2,
                    'RTE' => [
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'type' => 'script',
                        'title' => 'Full screen Rich Text Editing',
                        'icon' => 'wizard_rte2.gif',
                        'module' => [
                            'name' => 'wizard_rte'
                        ]
                    ],
                ],
            ],
        ],
        'plain_text' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.plain_text',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '10',
            ],
        ],
        'prices' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.prices',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'inline',
                'appearance' => [
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'bottom',
                            ],
                'foreign_table' => 'tx_commerce_article_prices',
                'foreign_field' => 'uid_article',
            ],
        ],
        'tax' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.tax',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'double2,nospace',
            ],
        ],
        'supplier_uid' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.title',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_commerce_supplier',
                'items' => [
                    [
                        $languageFile . 'tx_commerce_products.noManufacturer',
                        0,
                    ],
                ],
            ],
        ],
        'article_type_uid' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.article_type_uid',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_commerce_article_types',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 1,
            ],
        ],
        'relatedpage' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.relatedpage',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'uid_product' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.products_uid',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_products',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'attributesedit' => [
            'label' => $languageFile . 'tx_commerce_products.edit_attributes',
            'l10n_display' => 'hideDiff',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
<T3DataStructure>
	<meta>
		<langDisable>1</langDisable>
	</meta>
	<ROOT>
		<type>array</type>
		<el>
			<dummy>
				<TCEforms>
					<config>
						<type>input</type>
					</config>
				</TCEforms>
			</dummy>
		</el>
	</ROOT>
</T3DataStructure>',
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                title, subtitle, ordernumber, eancode,
                description_extra;;;richtext:rte_transform[mode=ts_css|imgpath=uploads/tx_commerce/rte/],
                images, plain_text, tax, supplier_uid, article_type_uid, relatedpage, products_uid,
                article_attributes, ' .
                    (
                        (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']['simpleMode'])
                            && $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']['simpleMode']) ? '' : '
                --div--;' . $languageFile . 'tx_commerce_products.edit_attributes,
                attributesedit,
                        '
                    ) .
                    '
                --div--;' . $languageFile . 'tx_commerce_articles.prices, prices,
                --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
                    hidden,
                    --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access',
        ],
    ],
    'palettes' => [
        'access' => [
            'showitem' => 'starttime, endtime, --linebreak--, fe_group'
        ],
    ],
];
