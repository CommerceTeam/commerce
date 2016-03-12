<?php
namespace CommerceTeam\Commerce\Utility;

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

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A metaclass for creating inputfield fields in the backend.
 *
 * Class \CommerceTeam\Commerce\Utility\AttributeEditorUtility
 */
class AttributeEditorUtility
{
    /**
     * Backend utility.
     *
     * @var \CommerceTeam\Commerce\Utility\BackendUtility
     */
    protected $belib;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->belib = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);
    }

    /**
     * This method creates a dynaflex field configuration from the submitted
     * database entry. The variable "configData" contains the complete dynaflex
     * configuration of the field and the data that where maybe fetched from
     * the database.
     *
     * We have to fill the fields
     *
     * $config['name']
     * $config['label']
     * $config['config']
     *
     * @param array $aData The data array contains in element "row" the dataset
     *     of the table we're creating
     * @param array $config The config array is the dynaflex fieldconfiguration.
     * @param bool  $fetchFromDatabase If true the attribute data is fetched
     *     from database
     * @param bool  $onlyDisplay If true the field is not an input field but
     *     is displayed
     *
     * @return array The modified dynaflex configuration
     */
    public function getAttributeEditField(array $aData, array &$config, $fetchFromDatabase = true, $onlyDisplay = false)
    {
        // first of all, fetch data from attribute table
        if ($fetchFromDatabase) {
            $aData = $this->belib->getAttributeData(
                $aData['row']['uid_foreign'],
                'uid, title, has_valuelist, multiple, unit, deleted'
            );
        }

        if ($aData['deleted'] == 1) {
            return [];
        }

        /*
         * Try to detect article UID since there is currently no way to get the
         * data from the method and get language_uid from article
         */
        $sysLanguageUid = 0;
        $getPostedit = GeneralUtility::_GPmerged('edit');
        if (is_array($getPostedit['tx_commerce_articles'])) {
            $articleUid = array_keys($getPostedit['tx_commerce_articles']);
            if ($articleUid[0] > 0) {
                $temporaryData = BackendUtility::getRecord('tx_commerce_articles', $articleUid[0], 'sys_language_uid');
                $sysLanguageUid = (int) $temporaryData['sys_language_uid'];
            }
        } elseif (is_array($getPostedit['tx_commerce_products'])) {
            $articleUid = array_keys($getPostedit['tx_commerce_products']);
            if ($articleUid[0] > 0) {
                $temporaryData = BackendUtility::getRecord('tx_commerce_products', $articleUid[0], 'sys_language_uid');
                $sysLanguageUid = (int) $temporaryData['sys_language_uid'];
            }
        }

        // set label and name
        $config['label'] = $aData['title'];
        $config['name'] = 'attribute_' . $aData['uid'];

        // get the value
        if ($onlyDisplay) {
            $config['config']['type'] = 'user';
            $config['config']['userFunc'] = 'tx_commerce_attributeEditor->displayAttributeValue';
            $config['config']['aUid'] = $aData['uid'];

            return $config;
        }

        /*
         * Get PID to select only the Attribute Values in the correct PID
         */
        FolderUtility::initFolders();
        $modPid = FolderRepository::initFolders('Commerce');
        FolderRepository::initFolders('Products', $modPid);
        $attrPid = FolderRepository::initFolders('Attributes', $modPid);

        if ($aData['has_valuelist'] == 1) {
            $config['config'] = [
                'type' => 'select',
                'foreign_table' => 'tx_commerce_attribute_values',
                'foreign_table_where' => ' AND attributes_uid = ' . (int) $aData['uid'] .
                    ' AND tx_commerce_attribute_values.pid = '. (int) $attrPid . ' ORDER BY value',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'items' => [
                    ['', 0],
                ],
            ];

            if ((int) $aData['multiple'] == 1) {
                // create a selectbox for multiple selection
                $config['config']['multiple'] = 1;
                $config['config']['size'] = 5;
                $config['config']['maxitems'] = 100;
                unset($config['config']['items']);
            }
        } else {
            // the field should be a simple input field
            if ($aData['unit'] != '') {
                $config['label'] .= ' (' . $aData['unit'] . ')';
            }
            $config['config'] = ['type' => 'input'];
        }

        // Dont display in lokalised version Attributes with valuelist
        if (($aData['has_valuelist'] == 1) && ($sysLanguageUid != 0)) {
            $config['config']['type'] = '';

            return false;
        }

        return $config;
    }

    /**
     * Returns the editfield dynaflex config for all attributes of a product.
     *
     * @param array $funcDataArray Function data
     * @param array $baseConfig Base config
     *
     * @return array An array with fieldconfigs
     */
    public function getAttributeEditFields(array $funcDataArray, array $baseConfig)
    {
        $result = [];

        $sortedAttributes = [];
        foreach ($funcDataArray as $funcData) {
            if ($funcData['row']['uid_foreign'] == 0) {
                continue;
            }

            $aData = $this->belib->getAttributeData(
                $funcData['row']['uid_foreign'],
                'uid, title, has_valuelist, multiple, unit, deleted'
            );

            // get correlationtype for this attribute and the product of this article
            // first get the product for this aticle
            $productUid = $this->belib->getProductOfArticle($funcData['row']['uid_local'], false);

            $uidCorrelationType = $this->belib->getCtForAttributeOfProduct(
                $funcData['row']['uid_foreign'],
                $productUid
            );
            $sortedAttributes[$uidCorrelationType][] = $aData;
        }

        ksort($sortedAttributes);
        reset($sortedAttributes);

        foreach ($sortedAttributes as $ctUid => $attributes) {
            // add a userfunction as header
            foreach ($attributes as $attribute) {
                $onlyDisplay = (($ctUid == 1 && ($attribute['has_valuelist'])) || $ctUid == 4);
                $fieldConfig = $this->getAttributeEditField($attribute, $baseConfig, false, $onlyDisplay);

                if (is_array($fieldConfig) && !empty($fieldConfig)) {
                    $result[] = $fieldConfig;
                }
            }
        }

        return $result;
    }

    /**
     * Simply returns the value of an attribute of an article.
     *
     * @param array $parameter Parameter
     *
     * @return string
     */
    public function displayAttributeValue(array $parameter)
    {
        $database = $this->getDatabaseConnection();

        // attribute value uid
        $aUid = $parameter['fieldConf']['config']['aUid'];

        $relRes = $database->exec_SELECTquery(
            'uid_valuelist,default_value,value_char',
            'tx_commerce_articles_article_attributes_mm',
            'uid_local = ' . (int) $parameter['row']['uid'] . ' AND uid_foreign = ' . (int) $aUid
        );

        $attributeData = $this->belib->getAttributeData($aUid, 'has_valuelist,multiple,unit');
        $relationData = null;
        if ($attributeData['multiple'] == 1) {
            while (($relData = $database->sql_fetch_assoc($relRes))) {
                $relationData[] = $relData;
            }
        } else {
            $relationData = $database->sql_fetch_assoc($relRes);
        }

        return htmlspecialchars(strip_tags($this->belib->getAttributeValue(
            $parameter['row']['uid'],
            $aUid,
            'tx_commerce_articles_article_attributes_mm',
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
