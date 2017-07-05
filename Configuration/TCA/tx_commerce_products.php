<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';
$langFile = 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:';

$GLOBALS['TCA']['tx_commerce_products'] = [
    'ctrl' => [
        'label' => 'title',
        'sortby' => 'sorting',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'title' => $languageFile . 'tx_commerce_products',
        'delete' => 'deleted',
        'versioningWS' => true,
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'thumbnail' => 'images',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_products.gif',
        'searchFields' => 'uid, title, subtitle, navtitle, description',
    ],
    'interface' => [
        'showRecordFieldList' => 'title, subtitle, navtitle, description, teaser, keywords, images, teaserimages,
            manufacturer_uid, sys_language_uid, l18n_parent, starttime, endtime, fe_group,',
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
                'foreign_table' => 'tx_commerce_products',
                'foreign_table_where' => '
                    AND tx_commerce_products.pid=###CURRENT_PID### 
                    AND tx_commerce_products.sys_language_uid IN (-1,0)
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
            ],
        ],

        'title' => [
            'exclude' => 0,
            'label' => $languageFile . 'tx_commerce_products.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'required,trim',
            ]
        ],
        'subtitle' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.subtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'trim',
            ]
        ],
        'navtitle' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.navtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'keywords' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.keywords',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ]
        ],
        'description' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
            ]
        ],
        'images' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.images',
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
        'teaser' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.teaser',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
            ]
        ],
        'teaserimages' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.teaserimages',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('teaserimages', [
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

        'categories' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => $languageFile . 'tx_commerce_products.categories',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCommerceCategoryTree',
                'size' => 10,
                'minitems' => 1,
                'maxitems' => 20,
                'foreign_table' => 'tx_commerce_categories',
                'foreign_table_where' => 'AND tx_commerce_categories.sys_language_uid IN (-1,0)
                    ORDER BY tx_commerce_categories.sorting ASC',
                'MM' => 'tx_commerce_products_categories_mm',
                'enableMultiSelectFilterTextfield' => true,
            ]
        ],

        'manufacturer_uid' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.title',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_commerce_manufacturer',
                'foreign_table_where' => 'ORDER BY tx_commerce_manufacturer.title ASC',
                'items' => [
                    [
                        $languageFile . 'tx_commerce_products.noManufacturer',
                        0
                    ],
                ],
            ]
        ],
        'relatedpage' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => $languageFile . 'tx_commerce_products.relatedpage',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ]
        ],
        'relatedproducts' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.relatedproducts',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_products',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 20,
                'MM' => 'tx_commerce_products_related_mm',
                'foreign_table' => 'tx_commerce_products',
            ]
        ],
        'attributes' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.attributes',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:commerce/Configuration/FlexForms/attributes.xml',
                ],
            ],
        ],
        'attributesedit' => [
            'exclude' => 1,
            'l10n_display' => 'hideDiff',
            'label' => $languageFile . 'tx_commerce_products.attributes',
            'displayCond' => 'USER:' . \CommerceTeam\Commerce\Utility\DisplayConditionUtility::class
            . '->checkProductCorrelationType',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:commerce/Configuration/FlexForms/attributes.xml',
                ],
            ],
        ],
        'articles' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.articles',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:commerce/Configuration/FlexForms/articles.xml',
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    title, subtitle, navtitle, description, teaser, keywords, images, teaserimages,
                --div--;' . $languageFile . 'tabs.references,
                    categories, manufacturer_uid, relatedpage, relatedproducts,
                --div--;' . $languageFile . 'tx_commerce_products.create_articles,
                    articles,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
                --div--;' . $languageFile . 'tx_commerce_products.select_attributes,
                    attributes,
                --div--;' . $languageFile . 'tx_commerce_products.edit_attributes,
                    attributesedit
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

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']['simpleMode'])
    && $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']['simpleMode']) {
    // In simple mode articles are created as inline element without article editor utility
    $GLOBALS['TCA']['tx_commerce_products']['columns']['articles'] = [
        'exclude' => 1,
        'label' => $languageFile . 'tx_commerce_products.articles',
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_commerce_articles',
            'foreign_field' => 'uid_product',
            'minitems' => 0,
        ],
    ];
}

return $GLOBALS['TCA']['tx_commerce_products'];
