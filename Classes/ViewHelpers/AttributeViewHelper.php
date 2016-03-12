<?php
namespace CommerceTeam\Commerce\ViewHelpers;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A metaclass for creating inputfield fields in the backend.
 *
 * Class \CommerceTeam\Commerce\Utility\AttributeEditorUtility
 */
class AttributeViewHelper
{
    /**
     * Simply returns the value of an attribute of an article.
     *
     * @param array $parameter Parameter
     *
     * @return string
     */
    public function displayAttributeValue(array $parameter)
    {
        /** @var \CommerceTeam\Commerce\Utility\BackendUtility $belib */
        $belib = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);

        // attribute value uid
        $aUid = $parameter['fieldConf']['config']['aUid'];

        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid_valuelist, default_value, value_char',
            'tx_commerce_articles_attributes_mm',
            'uid_local = ' . (int) $parameter['row']['uid'] . ' AND uid_foreign = ' . (int) $aUid
        );

        $attributeData = $belib->getAttributeData($aUid, 'has_valuelist,multiple,unit');
        if ($attributeData['multiple'] == 1) {
            $relationData = $rows;
        } else {
            $relationData = reset($rows);
        }

        return htmlspecialchars(strip_tags($belib->getAttributeValue(
            $parameter['row']['uid'],
            $aUid,
            'tx_commerce_articles_attributes_mm',
            $relationData,
            $attributeData
        )));
    }


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
