<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $languageFile . 'tx_commerce_categories',
        'label' => 'title',
        'sortby' => 'sorting',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioning' => '1',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_categories.gif',
        'dividers2tabs' => '1',
        'searchFields' => 'uid, title, subtitle, navtitle, description',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            title, subtitle, navtitle, description, images, keywords, teaser, teaserimages',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            title, subtitle, description, images, navtitle, keywords, attributes, parent_category, teaser,
            teaserimages',
    ],
    'columns' => [
        'editlock' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_tca.php:editlock',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled',
                    ],
                ],
            ],
        ],
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
                'foreign_table' => 'tx_commerce_categories',
                'foreign_table_where' => 'AND tx_commerce_categories.pid = ###CURRENT_PID###
                    AND tx_commerce_categories.sys_language_uid IN (-1,0)',
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
            'exclude' => 0,
            'label' => $languageFile . 'tx_commerce_categories.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'required,trim',
            ],
        ],
        'subtitle' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_categories.subtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'trim',
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_categories.description',
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
        'images' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_categories.images',
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
                'maxitems' => 10,
            ],
        ],
        'navtitle' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_categories.navtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'keywords' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_categories.keywords',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'parent_category' => [
            'exclude' => 0,
            'l10n_mode' => 'exclude',
            'label' => $languageFile . 'tx_commerce_categories.parent_category',
            'config' => [
                'type' => 'select',
                'renderType' => 'commerceCategoryTree',
                'foreign_table' => 'tx_commerce_categories',
                'foreign_table_where' => 'AND tx_commerce_categories.sys_language_uid IN (-1,0)
                    AND tx_commerce_categories.uid != ###THIS_UID###
                    ORDER BY tx_commerce_categories.sorting ASC',
                'MM' => 'tx_commerce_categories_parent_category_mm',
                'size' => 10,
                'minitems' => 1,
                'maxitems' => 20,
            ],
        ],
        'ts_config' => [
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => $languageFile . 'tx_commerce_categories.ts_config',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '10',
            ],
        ],
        'attributes' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => $languageFile . 'tx_commerce_products.attributes',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:commerce/Configuration/FlexForms/attributes.xml',
                ],
            ],
        ],
        'teaser' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.teaser',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '10',
            ],
        ],
        'teaserimages' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.teaserimages',
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
                'maxitems' => 5,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --palette--;' . $languageFile . 'palette.general;general,
                title, subtitle,
                description;;;richtext:rte_transform[mode=ts_css|imgpath=uploads/tx_commerce/rte/],
                teaser, navtitle, keywords, images, teaserimages, parent_category, relatedpage, ts_config,
            --div--;' . $languageFile . 'tx_commerce_categories.select_attributes,
                attributes,
            --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
                hidden,
                --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access',
        ],
    ],
    'palettes' => [
        'general' => [
            'showitem' => 'sys_language_uid, --linebreak--, l18n_parent',
        ],
        'access' => [
            'showitem' => 'starttime, endtime, --linebreak--, fe_group',
        ],
    ],
];
