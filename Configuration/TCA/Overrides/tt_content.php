<?php

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['commerce_pi1'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'commerce_pi1',
    'FILE:EXT:commerce/Configuration/FlexForms/flexform_pi1.xml'
);

/* ################# PI1 (product listing) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['commerce_pi1'] = 'layout,select_key';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi1',
        'commerce_pi1'
    ],
    'list_type',
    'commerce'
);

/* ################# PI2 (basket) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['commerce_pi2'] = 'layout,select_key,pages';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi2',
        'commerce_pi2'
    ],
    'list_type',
    'commerce'
);

/* ################# PI3 (checkout) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['commerce_pi3'] = 'layout,select_key,pages';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi3',
        'commerce_pi3'
    ],
    'list_type',
    'commerce'
);

/* ################# PI4 (addresses) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['commerce_pi4'] = 'layout,select_key,pages';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi4',
        'commerce_pi4'
    ],
    'list_type',
    'commerce'
);

/* ################ PI6 (invoice) ############################*/
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['commerce_pi6'] = 'layout,select_key,pages';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:tt_content.list_type_pi6',
        'commerce_pi6'
    ],
    'list_type',
    'commerce'
);
