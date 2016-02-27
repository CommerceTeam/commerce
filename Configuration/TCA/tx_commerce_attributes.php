<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

/**
 * Dynamic config file for tx_commerce_attributes
 *
 * @author 2005-2010 Ingo Schmitt <is@marketing-factory.de>
 */
return [
    'ctrl' => [
        'title' => $languageFile . 'tx_commerce_attributes',
        'label' => 'internal_title',
        'label_alt' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'versioning' => '1',
        'dividers2tabs' => '1',
        'requestUpdate' => 'has_valuelist',
        'languageField' => 'sys_language_uid',
        'thumbnail' => 'icon',
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
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_attributes.gif',
        'typeicon_column' => 'has_valuelist',
        'typeicons' => [
            '0' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_attributes_free.gif',
            '1' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_attributes_list.gif',
        ],
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            has_valuelist, title, internal_title, unit, valueformat, valuelist',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            has_valuelist, title, internal_title, unit, valuelist, icon',
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
                'foreign_table' => 'tx_commerce_attributes',
                'foreign_table_where' => 'AND tx_commerce_attributes.pid = ###CURRENT_PID###
                    AND tx_commerce_attributes.sys_language_uid IN (-1,0)',
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
            'label' => $languageFile . 'tx_commerce_attributes.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ],
        ],
        'internal_title' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.internal_title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '160',
                'eval' => 'trim',
            ],
        ],
        'unit' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.unit',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'valueformat' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:has_valuelist:=:0',
            'label' => $languageFile . 'tx_commerce_attributes.valueformat',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', ''],
                    [$languageFile . 'tx_commerce_attributes.valueformat.money', '%01.2f'],
                    [$languageFile . 'tx_commerce_attributes.valueformat.integer', '%d'],
                    [$languageFile . 'tx_commerce_attributes.valueformat.float', '%f'],
                ],
            ],
        ],
        'icon' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.icon',
            'l10n_mode' => 'mergeIfNotBlank',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
                'uploadfolder' => 'uploads/tx_commerce',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'show_thumbs' => 1,
            ],
        ],
        'parent' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_attributes.parent',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_commerce_attributes',
                'foreign_label' => 'title',
                'foreign_table_where' => ' AND tx_commerce_attributes.uid != ###THIS_UID###',
                'size' => 10,
                'autoSizeMax' => 20,
                'minitems' => 0,
                'maxitems' => 30,
                'renderMode' => 'tree',
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => [
                        'expandAll' => true,
                        'showHeader' => true,
                    ],
                ],
            ],
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
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showRemovedLocalizationRecords' => 1,
                    'useSortable' => 1,
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
                    --palette--;' . $languageFile . 'palette.general;general,
                    parent, multiple, valueformat, title, internal_title, unit, icon,
                --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
                    hidden,
                    --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
                --div--;' . $languageFile . 'tx_commerce_attributes.valuelistlist,
                    --palette--;' . $languageFile . 'palette.valuechecks;valuechecks,
                    valuelist
            ',
        ],
    ],
    'palettes' => [
        'valuechecks' => [
            'showitem' => 'has_valuelist, multiple, iconmode',
        ],
        'general' => [
            'showitem' => 'sys_language_uid, --linebreak--, l18n_parent',
        ],
        'access' => [
            'showitem' => 'starttime, endtime, --linebreak--, fe_group',
        ],
    ],
];
