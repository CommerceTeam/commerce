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

return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'enablecolumns' => array(
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ),
        'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'moveordermails.gif',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            name, mailkind, mailtemplate, htmltemplate, mailcharset, sendername, senderemail, otherreceiver, BCC',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            name, mailkind, mailtemplate, htmltemplate, mailcharset, sendername, senderemail, otherreceiver, BCC',
    ),
    'columns' => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0),
                ),
            ),
        ),
        'l18n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_commerce_moveordermails',
                'foreign_table_where' => ' AND tx_commerce_moveordermails.pid = ###CURRENT_PID###
                    AND tx_commerce_moveordermails.sys_language_uid IN (-1,0)',
            ),
        ),
        'l18n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0',
            ),
        ),
        'starttime' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
            'config' => array(
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
                'checkbox' => '0',
            ),
        ),
        'endtime' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
            'config' => array(
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'checkbox' => '0',
                'default' => '0',
                'range' => array(
                    'upper' => mktime(0, 0, 0, 12, 31, 2020),
                    'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y')),
                ),
            ),
        ),
        'fe_group' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
            'config' => array(
                'type' => 'select',
                'size' => 5,
                'maxitems' => 50,
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.hide_at_login', -1),
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.any_login', -2),
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.usergroups', '--div--'),
                ),
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',

            ),
        ),
        'name' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.name',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'required,trim',
            ),
        ),
        'mailkind' => array(
            'exclude' => 1,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.mailkind',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.mailkind.I.0', 0),
                    array('LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.mailkind.I.1', 1),
                ),
                'size' => 1,
                'maxitems' => 1,
            ),
        ),
        'mailtemplate' => array(
            'exclude' => 1,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.mailtemplate',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => '',
                'disallowed' => 'php,php3',
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
                'uploadfolder' => 'uploads/tx_commerce',
                'size' => 3,
                'minitems' => 0,
                'maxitems' => 1,
            ),
        ),
        'htmltemplate' => array(
            'exclude' => 1,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.htmltemplate',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => '',
                'disallowed' => 'php,php3',
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
                'uploadfolder' => 'uploads/tx_commerce',
                'size' => 3,
                'minitems' => 0,
                'maxitems' => 1,
            ),
        ),
        'mailcharset' => array(
            'exclude' => 1,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.mailcharset',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'required,trim',
                'default' => 'utf-8',
            ),
        ),
        'sendername' => array(
            'exclude' => 1,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.sendername',
            'config' => array(
                'type' => 'input',
                'size' => '48',
                'eval' => 'trim',
            ),
        ),
        'senderemail' => array(
            'exclude' => 1,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.senderemail',
            'config' => array(
                'type' => 'input',
                'size' => '48',
                'eval' => 'trim',
            ),
        ),
        'otherreceiver' => array(
            'exclude' => 1,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.otherreceiver',
            'config' => array(
                'type' => 'input',
                'size' => '48',
                'eval' => 'trim',
            ),
        ),
        'BCC' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_moveordermails.BCC',
            'config' => array(
                'type' => 'input',
                'size' => '48',
                'eval' => 'trim',
            ),
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'sys_language_uid, l18n_parent, l18n_diffsource,hidden;;1;;1-1-1, name,
            mailkind, mailtemplate,htmltemplate, mailcharset, sendername, senderemail, otherreceiver, BCC'),
    ),
    'palettes' => array(
        '1' => array('showitem' => 'starttime, endtime, fe_group'),
    ),
);
