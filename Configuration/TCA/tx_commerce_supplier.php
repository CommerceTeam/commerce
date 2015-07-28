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
        'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioning' => '1',
        'default_sortby' => 'ORDER BY title,uid',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dividers2tabs' => '1',
        'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'supplier.gif',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, title, street, number, zip, city, country, phone, fax, email, internet,
            contactperson, logo',
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden, title, street, number, zip, city, country, phone, fax, email, internet,
            contactperson, logo',
    ),
    'columns' => array(
        'title' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.title',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ),
        ),
        'street' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.street',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'number' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.number',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'zip' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.zip',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'city' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.city',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'country' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.country',
            'l10n_mode' => 'exclude',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'static_countries',
                'foreign_table_where' => 'ORDER BY static_countries.cn_short_en',

            ),
        ),
        'phone' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.phone',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'fax' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.fax',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'email' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.email',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'internet' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.internet',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'contactperson' => array(
            'exclude' => 1,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.contactperson',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'logo' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.logo',
            'l10n_mode' => 'mergeIfNotBlank',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
                'uploadfolder' => 'uploads/tx_commerce',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 5,
            ),
        ),
    ),
    'types' => array(
        '0' => array(
            'showitem' => '
                hidden, title, street, number, zip, city, country, phone, fax, email, internet, contactperson, logo',
            ),
    ),
);
