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
        'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'baskets.gif',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'sid, finished_time, article_id,price_id, price_gross, price_net, quantity',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sid,article_id,price_gross,price_net,quantity',
    ),
    'columns' => array(
        'sid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.sid',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ),
        ),
        'article_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.article_id',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_articles',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ),
        ),
        'price_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.price_id',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_article_prices',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ),
        ),
        'price_gross' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.price_gross',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'double2,nospace',
            ),
        ),
        'price_net' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.price_net',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'double2,nospace',
            ),
        ),
        'quantity' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.quantity',
            'config' => array(
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => array(
                    'upper' => '5000',
                    'lower' => '0',
                ),
                'default' => 0,
            ),
        ),
        'finished_time' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_basket.finished_time',
            'config' => array(
                'type' => 'input',
                'eval' => 'date',
            ),
        ),
        'readonly' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_basket.readonly',
            'config' => array(
                'type' => 'check',
            ),
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'sid;;;;1-1-1, article_id,price_id, price_gross, price_net, quantity'),
    ),
    'palettes' => array(
        '1' => array('showitem' => ''),
    ),
);
