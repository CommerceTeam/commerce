<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';
$langFile = 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:';

return [
    'ctrl' => [
        'label' => 'internal_title',
        'label_alt' => 'title',
        'sortby' => 'sorting',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'title' => $languageFile . 'tx_commerce_attributes',
        'delete' => 'deleted',
        'versioningWS' => true,
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'requestUpdate' => 'has_valuelist',
        'thumbnail' => 'icon',
        'default_sortby' => 'ORDER BY sorting,crdate',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_attributes.gif',
        'typeicon_column' => 'has_valuelist',
        'typeicons' => [
            '0' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_attributes_free.gif',
            '1' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_attributes_list.gif',
        ],
    ],
    'interface' => [
        'showRecordFieldList' => 'title, internal_title, unit, valueformat, icon, has_valuelist, valuelist, multiple,
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
                'foreign_table' => 'tx_commerce_attributes',
                'foreign_table_where' => '
                    AND tx_commerce_attributes.pid=###CURRENT_PID### 
                    AND tx_commerce_attributes.sys_language_uid IN (-1,0)
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
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ]
        ],
        'internal_title' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.internal_title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '160',
                'eval' => 'trim',
            ]
        ],
        'unit' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.unit',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'valueformat' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:has_valuelist:=:0',
            'label' => $languageFile . 'tx_commerce_attributes.valueformat',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', ''],
                    [$languageFile . 'tx_commerce_attributes.valueformat.money', '%01.2f'],
                    [$languageFile . 'tx_commerce_attributes.valueformat.integer', '%d'],
                    [$languageFile . 'tx_commerce_attributes.valueformat.float', '%f'],
                ]
            ]
        ],
        'icon' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.icon',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('icon', [
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
        'parent' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.parent',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'tx_commerce_attributes',
                'foreign_label' => 'title',
                'foreign_table_where' => '
                    AND tx_commerce_attributes.sys_language_uid IN (0,-1)
                    AND tx_commerce_attributes.uid != ###THIS_UID###
                ',
                'size' => 10,
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => [
                        'expandAll' => true,
                        'showHeader' => true,
                    ],
                ],
            ]
        ],

        'has_valuelist' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.has_valuelist',
            'displayCond' => 'FIELD:sys_language_uid:=:0',
            'config' => [
                'type' => 'check',
            ],
        ],
        'iconmode' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.iconMode',
            'l10n_mode' => 'exclude',
            'displayCond' => 'FIELD:has_valuelist:=:1',
            'config' => [
                'type' => 'check',
            ],
        ],
        'multiple' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:has_valuelist:=:1',
            'label' => $languageFile . 'tx_commerce_attributes.multiple',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'check',
            ],
        ],
        'valuelist' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:has_valuelist:=:1',
            'label' => $languageFile . 'tx_commerce_attributes.valuelist',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_commerce_attribute_values',
                'foreign_field' => 'attributes_uid',
                'foreign_sortby' => 'sorting',
                'foreign_label' => 'value',

                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'showSynchronizationLink' => true,
                    'showAllLocalizationLink' => true,
                    'showPossibleLocalizationRecords' => true,
                    'showRemovedLocalizationRecords' => true,
                    'useSortable' => true,
                    'newRecordLinkTitle' => $languageFile . 'tx_commerce_attributes.add_value',
                ],
                'behaviour' => [
                    'localizationMode' => 'select',
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    parent, multiple, valueformat, title, internal_title, unit, icon,
                --div--;' . $languageFile . 'tx_commerce_attributes.valuelisttab,
                    --palette--;' . $languageFile . 'palette.valuechecks;valuechecks,
                    valuelist,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access
            ',
        ],
    ],
    'palettes' => [
        'valuechecks' => [
            'showitem' => 'has_valuelist, multiple, iconmode',
        ],
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
