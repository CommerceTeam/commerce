<?php
namespace CommerceTeam\Commerce\Domain\Repository;

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

/**
 * Abstract Class for handling almost all Database-Calls for all
 * FE Rendering processes. This Class is mostly extended by distinct
 * Classes for spezified Objects.
 *
 * Basic abtract Class for Database Query for
 * tx_commerce_product
 * tx_commerce_article
 * tx_commerce_category
 * tx_commerce_attribute
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\Repository
 */
class Repository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = '';

    /**
     * Order field for most select statments.
     *
     * @var string
     */
    protected $orderField = ' sorting ';

    /**
     * Stores the relation for the attributes to product, category, article.
     *
     * @var string Database attribute rel table
     */
    protected $databaseAttributeRelationTable = '';

    /**
     * Debugmode for errorHandling.
     *
     * @var bool
     */
    protected $debugMode = false;

    /**
     * Translation mode for getRecordOverlay.
     *
     * @var string
     */
    protected $translationMode = 'hideNonTranslated';

    /**
     * Uid.
     *
     * @var int
     */
    protected $uid;

    /**
     * Get data.
     *
     * @param int $uid UID for Data
     * @param int $langUid Language Uid
     * @param bool $translationMode Translation Mode for recordset
     *
     * @return array assoc Array with data
     * @todo implement access_check concering category tree
     */
    public function getData($uid, $langUid = -1, $translationMode = false)
    {
        $database = $this->getDatabaseConnection();
        $frontend = $this->getFrontendController();

        if ($translationMode == false) {
            $translationMode = $this->translationMode;
        }

        $uid = (int) $uid;
        $langUid = (int) $langUid;
        if ($langUid == -1) {
            $langUid = 0;
        }

        if (empty($langUid) && $frontend->sys_language_uid) {
            $langUid = $frontend->sys_language_uid;
        }

        $proofSql = '';
        if (is_object($frontend->sys_page)) {
            $proofSql = $this->enableFields($this->databaseTable, $frontend->showHiddenRecords);
        }

        $returnData = $database->exec_SELECTgetSingleRow(
            '*',
            $this->databaseTable,
            'uid = ' . $uid . $proofSql
        );

        // Result should contain only one Dataset
        if (!empty($returnData)) {
            // get workspace version if available
            if (!empty($frontend->sys_page)) {
                $frontend->sys_page->versionOL($this->databaseTable, $returnData);
            }

            if (!is_array($returnData)) {
                $this->error('There was an error overlaying the record with the version');

                return false;
            }

            if ($langUid > 0) {
                /*
                 * Get Overlay, if available
                 */
                switch ($translationMode) {
                    case 'basket':
                        // special Treatment for basket, so you could have
                        // a product not translated init a language
                        // but the basket is in the not translated laguage
                        $newData = $frontend->sys_page->getRecordOverlay(
                            $this->databaseTable,
                            $returnData,
                            $langUid,
                            $this->translationMode
                        );

                        if (!empty($newData)) {
                            $returnData = $newData;
                        }
                        break;

                    default:
                        $returnData = $frontend->sys_page->getRecordOverlay(
                            $this->databaseTable,
                            $returnData,
                            $langUid,
                            $this->translationMode
                        );
                }
            }

            return $returnData;
        }

        // error Handling
        $this->error(
            'exec_SELECTquery(\'*\', ' . $this->databaseTable . ', "uid = ' .
            $uid . '"); returns no or more than one Result'
        );

        return false;
    }

    /**
     * Checks if one given UID is availiabe.
     *
     * @param int $uid Uid
     *
     * @return bool true id availiabe
     * @todo implement access_check
     */
    public function isUid($uid)
    {
        if (!$uid) {
            return false;
        }

        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            $this->databaseTable,
            'uid = ' . (int) $uid
        );

        return count($row) == 1;
    }

    /**
     * Checks in the Database if a UID is accessiblbe,
     * basically checks against the enableFields.
     *
     * @param int $uid Record Uid
     *
     * @return bool TRUE if is accessible
     *      FALSE if is not accessible
     */
    public function isAccessible($uid)
    {
        $return = false;
        $uid = (int) $uid;
        if ($uid) {
            $proofSql = '';
            if (is_object($this->getFrontendController()->sys_page)) {
                $proofSql = $this->enableFields(
                    $this->databaseTable,
                    $this->getFrontendController()->showHiddenRecords
                );
            }

            $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                '*',
                $this->databaseTable,
                'uid = ' . $uid . $proofSql
            );

            if (count($row) == 1) {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Error Handling Funktion.
     *
     * @param string $err Errortext
     *
     * @return void
     */
    public function error($err)
    {
        if ($this->debugMode) {
            debug('Error: ' . $err);
        }
    }

    /**
     * Gets all attributes from this product.
     *
     * @param int $uid Product uid
     * @param array|NULL $attributeCorrelationTypeList Corelation types
     *
     * @return array of attribute UID
     */
    public function getAttributes($uid, $attributeCorrelationTypeList = null)
    {
        $database = $this->getDatabaseConnection();
        $uid = (int) $uid;
        if ($this->databaseAttributeRelationTable == '') {
            return false;
        }

        $additionalWhere = '';
        if (is_array($attributeCorrelationTypeList)) {
            $additionalWhere = ' AND ' . $this->databaseAttributeRelationTable . '.uid_correlationtype IN (' .
                implode(',', $attributeCorrelationTypeList) . ')';
        }

        $result = $database->exec_SELECT_mm_query(
            'tx_commerce_attributes.uid',
            $this->databaseTable,
            $this->databaseAttributeRelationTable,
            'tx_commerce_attributes',
            ' AND ' . $this->databaseTable . '.uid = ' . (int) $uid . $additionalWhere . ' ORDER BY ' .
            $this->databaseAttributeRelationTable . '.sorting'
        );

        $attributeUidList = false;
        if ($database->sql_num_rows($result)) {
            $attributeUidList = [];
            while (($returnData = $database->sql_fetch_assoc($result))) {
                $attributeUidList[] = (int) $returnData['uid'];
            }
            $database->sql_free_result($result);
        }

        return $attributeUidList;
    }

    /**
     * Update record data.
     *
     * @param int $uid Uid of the item
     * @param array $fields Assoc. array with update fields
     *
     * @return bool
     */
    public function updateRecord($uid, array $fields)
    {
        if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid) || empty($fields)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'updateRecord (db_alib) gets passed invalid parameters.',
                    'commerce',
                    3
                );
            }

            return false;
        }

        $database = $this->getDatabaseConnection();
        $database->exec_UPDATEquery($this->databaseTable, 'uid = ' . (int) $uid, $fields);
        if ($database->sql_error()) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'updateRecord (db_alib): invalid sql.',
                    'commerce',
                    3
                );
            }

            return false;
        }

        return true;
    }

    /**
     * Get enableFields.
     *
     * @param string $tableName Table name
     * @param bool|int $showHiddenRecords Show hidden records
     * @param string $as Alias to use for the table name
     *
     * @return string
     */
    public function enableFields($tableName, $showHiddenRecords = -1, $as = '')
    {
        if (TYPO3_MODE === 'FE') {
            $showHiddenRecords = $showHiddenRecords ?
                $showHiddenRecords :
                $this->getFrontendController()->showHiddenRecords;
            $result = $this->getFrontendController()->sys_page->enableFields($tableName, $showHiddenRecords);
        } else {
            $result = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($tableName);
        }

        if ($as !== '') {
            $result = str_replace($tableName, $as, $result);
        }

        return $result;
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

    /**
     * Get typoscript frontend controller.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
