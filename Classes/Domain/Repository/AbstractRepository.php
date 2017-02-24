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

use TYPO3\CMS\Core\SingletonInterface;

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
abstract class AbstractRepository implements SingletonInterface
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
        $frontend = $this->getTypoScriptFrontendController();

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

        $returnData = $this->findByUid($uid);

        // Result should contain only one Dataset
        if (!empty($returnData)) {
            // get workspace version if available
            if (!empty($frontend->sys_page)) {
                $frontend->sys_page->versionOL($this->databaseTable, $returnData);
            }

            if (!is_array($returnData)) {
                $this->error('There was an error overlaying the record with the version');

                return [];
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
                            $translationMode
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
                            $translationMode
                        );
                }
            }

            return $returnData;
        }

        // error Handling
        $this->error(
            'exec_SELECTquery(\'*\', ' . $this->databaseTable . ', "uid = ' . $uid . '"); returns no result'
        );

        return [];
    }

    /**
     * Find by uid.
     *
     * @param int $uid Record uid
     * @param string $additionalWhere
     *
     * @return array
     */
    public function findByUid($uid, $additionalWhere = ''): array
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            );

        if ($additionalWhere !== '') {
            $queryBuilder->andWhere($additionalWhere);
        }

        $result = $queryBuilder->execute()->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * Checks if one given UID is available.
     *
     * @param int $uid Uid
     *
     * @return bool true id availiabe
     */
    public function isUid($uid)
    {
        if (!$uid) {
            return false;
        }

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('uid')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute();

        return $result->rowCount() > 0;
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
        if (!$uid) {
            return false;
        }

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('uid')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute();

        return $result->rowCount() > 0;
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
     * Update record data.
     *
     * @param int $uid Uid of the item
     * @param array $data Assoc. array with update fields
     * @return bool
     */
    public function updateRecord($uid, array $data)
    {
        if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid) || empty($data)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'updateRecord (AbstractRepository) gets passed invalid parameters.',
                    'commerce',
                    3
                );
            }

            return false;
        }

        $result = true;
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            );
        foreach ($data as $field => $value) {
            $queryBuilder->set($field, $value);
        }
        $queryResult = $queryBuilder->execute();

        if ($queryResult->errorCode()) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'updateRecord (AbstractRepository): invalid sql.',
                    'commerce',
                    3
                );
            }

            $result = false;
        }

        return $result;
    }

    /**
     * Get enableFields.
     *
     * @param string $tableName Table name
     * @param string $as Alias to use for the table name
     * @param bool|int $showHiddenRecords Show hidden records
     *
     * @return string
     */
    public function enableFields($tableName = '', $as = '', $showHiddenRecords = 0)
    {
        if (empty($tableName)) {
            $tableName = $this->databaseTable;
        }

        if (TYPO3_MODE === 'FE') {
            $showHiddenRecords = $showHiddenRecords ?
                $showHiddenRecords :
                $this->getTypoScriptFrontendController()->showHiddenRecords;
            $result = $this->getTypoScriptFrontendController()->sys_page->enableFields($tableName, $showHiddenRecords);
        } else {
            $result = \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableName);
        }

        if ($as !== '') {
            $result = str_replace($tableName, $as, $result);
        }

        return $result;
    }

    /**
     * @param array $data field values for use for new record
     * @return int uid of the new record
     */
    public function addRecord($data)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->insert($this->databaseTable)
            ->values($data)
            ->execute();

        return $queryBuilder->getConnection()->lastInsertId();
    }


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     * @deprecated
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param string $table
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilderForTable($table): \TYPO3\CMS\Core\Database\Query\QueryBuilder
    {
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getQueryBuilderForTable($table);
        $queryBuilder->setRestrictions(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer::class
        ));
        return $queryBuilder;
    }

    /**
     * Get typoscript frontend controller.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
