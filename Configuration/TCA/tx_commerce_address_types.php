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
        'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_address_types',
        'label' => 'title',
        'readOnly' => 1,
        'adminOnly' => 1,
        'rootLevel' => 1,
        'is_static' => 1,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'address_types.gif',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title',
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
                'foreign_table' => 'tx_commerce_address_types',
                'foreign_table_where' => ' AND tx_commerce_address_types.pid = ###CURRENT_PID###
                    AND tx_commerce_address_types.sys_language_uid IN (-1,0)',
            ),
        ),
        'l18n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),
        'title' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_address_types.title',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ),
        ),
        'name' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_address_types.name',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ),
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2'),
    ),
    'palettes' => array(
        '1' => array('showitem' => ''),
    ),
);
