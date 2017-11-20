<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';
$langFile = 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:';

$attributeField = '';
if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']['simpleMode'])
    && $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']['simpleMode']
) {
    $attributeField = '--div--;' . $languageFile . 'tx_commerce_products.edit_attributes, attributesedit,';
}

return [
    'ctrl' => [
        'label' => 'title',
        'sortby' => 'sorting',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'title' => $languageFile . 'tx_commerce_articles',
        'delete' => 'deleted',
        'versioningWS' => true,
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'default_sortby' => 'ORDER BY sorting,crdate',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_articles.svg',
    ],
    'interface' => [
        'showRecordFieldList' => 'title, subtitle, navtitle, description_extra, plain_text, price_gross, price_net,
            purchase_price, tax, article_type_uid, products_uid,
            sys_language_uid, l18n_parent, starttime, endtime, fe_group',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden.I.0'
                    ]
                ]
            ]
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
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
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                        -1
                    ],
                    [
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                        -2
                    ],
                    [
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                        '--div--'
                    ]
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'enableMultiSelectFilterTextfield' => true
            ]
        ],

        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0,
            ]
        ],
        'l18n_parent' => [
            'exclude' => true,
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tx_commerce_articles',
                'foreign_table_where' => '
                    AND tx_commerce_articles.pid=###CURRENT_PID### 
                    AND tx_commerce_articles.sys_language_uid IN (-1,0)
                ',
                'default' => 0
            ]
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],

        'title' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => $languageFile . 'tx_commerce_articles.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'required,trim',
            ]
        ],
        'subtitle' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.subtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'trim',
            ]
        ],
        'navtitle' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_products.navtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'images' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.images',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('images', [
                'appearance' => [
                    'createNewRelationLinkTitle' =>
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                ],
                // custom configuration for displaying fields in the overlay/reference table
                // to use the imageoverlayPalette instead of the basicoverlayPalette
                'foreign_types' => [
                    '0' => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.audioOverlayPalette;audioOverlayPalette,
                            --palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.videoOverlayPalette;videoOverlayPalette,
                            --palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ]
                ]
            ], $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']),
        ],
        'ordernumber' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.ordernumber',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
            'l10n_mode' => 'exclude',
        ],
        'eancode' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.eancode',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '20',
                'eval' => 'trim',
            ],
            'l10n_mode' => 'exclude',
        ],
        'description_extra' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.description_extra',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
                'wizards' => [
                    '_PADDING' => 2,
                ],
            ]
        ],
        'plain_text' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.plain_text',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '10',
            ]
        ],
        'prices' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.prices',
            'config' => [
                'type' => 'inline',
                'appearance' => [
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'bottom',
                ],
                'foreign_table' => 'tx_commerce_article_prices',
                'foreign_field' => 'uid_article',
            ],
            'l10n_mode' => 'exclude',
        ],
        'tax' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.tax',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'double2,nospace',
            ],
            'l10n_mode' => 'exclude',
        ],
        'supplier_uid' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_supplier.title',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_commerce_supplier',
                'items' => [
                    [
                        $languageFile . 'tx_commerce_products.noManufacturer',
                        0,
                    ],
                ],
            ],
            'l10n_mode' => 'exclude',
        ],
        'article_type_uid' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.article_type_uid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_commerce_article_types',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 1,
            ],
            'l10n_mode' => 'exclude',
        ],
        'item_category' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.item_category',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        $languageFile . 'tx_commerce_articles.item_category.physical',
                        'Physical',
                    ],
                    [
                        $languageFile . 'tx_commerce_articles.item_category.digital',
                        'Digital',
                    ],
                ],
            ]
        ],
        'relatedpage' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.relatedpage',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ]
        ],
        'uid_product' => [
            'exclude' => true,
            'label' => $languageFile . 'tx_commerce_articles.products_uid',
            'config' => [
                'readOnly' => true,
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_products',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'behaviour' => [
                    'allowLanguageSynchronization' => false,
                ]
            ],
        ],
        'attributesedit' => [
            'label' => $languageFile . 'tx_commerce_products.edit_attributes',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:commerce/Configuration/FlexForms/attributes.xml',
                ],
            ],
            'l10n_display' => 'hideDiff',
        ],
        'classname' => [
            'label' => $languageFile . 'tx_commerce_articles.classname',
            'displayCond' => 'FIELD:classname:REQ:true',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
            'l10n_display' => 'exclude',
        ],
    ],
    'types' => [
        '0' => [
            'columnsOverrides' => [
                'description_extra' => [
                    'config' => [
                        'enableRichtext' => true,
                        'fieldControl' => [
                            'fullScreenRichtext' => [
                                'options' => [
                                    'title' => 'Full screen Rich Text Editing'
                                ]
                            ]
                        ],
                        'richtextConfiguration' => 'default',
                    ]
                ],
            ],
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    classname,  title, subtitle, ordernumber, eancode, description_extra,
                    images, plain_text, tax, supplier_uid, article_type_uid, item_category, relatedpage, products_uid,
                    article_attributes, ' . $attributeField . '
                --div--;' . $languageFile . 'tx_commerce_articles.prices,
                    prices,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access
            ',
        ],
    ],
    'palettes' => [
        'hidden' => [
            'showitem' => '
                hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden
            ',
        ],
        'language' => [
            'showitem' => '
            sys_language_uid;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sys_language_uid_formlabel,
            l18n_parent
            ',
        ],
        'access' => [
            'showitem' => '
                starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,
                endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel,
                --linebreak--,
                fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel
            ',
        ],
    ],
];
