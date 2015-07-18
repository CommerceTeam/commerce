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

$tempColumns = array(
    'tx_commerce_user_state_id' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:fe_users.tx_commerce_user_state_id',
        'config' => array(
            'type' => 'select',
            'item' => array(
                array('', 0),
            ),
            'foreign_table' => 'tx_commerce_user_states',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
        ),
    ),
    'tx_commerce_tt_address_id' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:fe_users.tx_commerce_tt_address_id',
        'config' => array(
            'type' => 'select',
            'foreign_table' => 'tt_address',
            'foreign_table_where' => 'AND tt_address.tx_commerce_fe_user_id=###THIS_UID###'.
                ' AND tt_address.tx_commerce_fe_user_id!=0 AND tt_address.pid = '.
                (int) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['extConf']['create_address_pid'],
            'minitems' => 0,
            'maxitems' => 1,
            'wizards' => array(
                '_PADDING' => 1,
                '_VERTICAL' => 1,
                'edit' => array(
                    'type' => 'popup',
                    'notNewRecords' => true,
                    'title' => 'Edit',
                    'script' => 'wizard_edit.php',
                    'popup_onlyOpenIfSelected' => 1,
                    'icon' => 'edit2.gif',
                    'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                ),
            ),
        ),
    ),
    'tx_commerce_orders' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:fe_users.tx_commerce_feuser_orders',
        'config' => array(
            'type' => 'user',
            'userFunc' => 'CommerceTeam\\Commerce\\ViewHelpers\\OrderEditFunc->feUserOrders',
        ),
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    '--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:fe_users.tx_commerce,
		tx_commerce_tt_address_id,tx_commerce_user_state_id,tx_commerce_orders'
);
