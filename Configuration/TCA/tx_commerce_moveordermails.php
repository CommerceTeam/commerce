<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $languageFile . 'tx_commerce_moveordermails',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_moveordermails.gif',
        'dividers2tabs' => '1',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            name, mailkind, mailtemplate, htmltemplate, mailcharset, sendername, senderemail, otherreceiver, BCC',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            name, mailkind, mailtemplate, htmltemplate, mailcharset, sendername, senderemail, otherreceiver, BCC',
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
                'foreign_table' => 'tx_commerce_moveordermails',
                'foreign_table_where' => 'AND tx_commerce_moveordermails.pid = ###CURRENT_PID###
                    AND tx_commerce_moveordermails.sys_language_uid IN (-1,0)',
                'default' => 0
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ],
        ],

        'name' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_moveordermails.name',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required,trim',
            ],
        ],
        'mailkind' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_moveordermails.mailkind',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$languageFile . 'tx_commerce_moveordermails.mailkind.I.0', 0],
                    [$languageFile . 'tx_commerce_moveordermails.mailkind.I.1', 1],
                ],
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'mailtemplate' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_moveordermails.mailtemplate',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => '',
                'disallowed' => 'php',
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
                'uploadfolder' => 'uploads/tx_commerce',
                'size' => 3,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'htmltemplate' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_moveordermails.htmltemplate',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => '',
                'disallowed' => 'php',
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
                'uploadfolder' => 'uploads/tx_commerce',
                'size' => 3,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'mailcharset' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_moveordermails.mailcharset',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required,trim',
                'default' => 'utf-8',
            ],
        ],
        'sendername' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_moveordermails.sendername',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'eval' => 'trim',
            ],
        ],
        'senderemail' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_moveordermails.senderemail',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'eval' => 'trim',
            ],
        ],
        'otherreceiver' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_moveordermails.otherreceiver',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'eval' => 'trim',
            ],
        ],
        'BCC' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_moveordermails.BCC',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'eval' => 'trim',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                    --palette--;' . $languageFile . 'palette.general;general,
                    name, mailkind, mailtemplate, htmltemplate,
                    mailcharset, sendername, senderemail, otherreceiver, BCC,
                --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
                    hidden,
                    --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access
            '
        ],
    ],
    'palettes' => [
        'general' => [
            'showitem' => 'sys_language_uid, --linebreak--, l18n_parent',
        ],
        'access' => [
            'showitem' => 'starttime, endtime, --linebreak--, fe_group'
        ],
    ],
];
