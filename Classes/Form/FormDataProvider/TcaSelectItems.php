<?php
namespace CommerceTeam\Commerce\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * Resolve category tree items, set processed item list in processedTca, sanitize and resolve database field
 */
class TcaSelectItems extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * Resolve select items
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'select') {
                continue;
            }

            // Make sure we are only processing supported renderTypes
            if (!$this->isTargetRenderType($fieldConfig)) {
                continue;
            }

            $result['databaseRow'][$fieldName] = $this->getSelectedItems($result, $fieldName);

            $fieldConfig['config']['maxitems'] = $this->sanitizeMaxItems($fieldConfig['config']['maxitems']);
            $result['processedTca']['columns'][$fieldName] = $fieldConfig;
        }

        return $result;
    }

    /**
     * @param array $result
     * @param string $fieldName
     * @return array
     */
    protected function getSelectedItems(array $result, $fieldName)
    {
        $fieldConfig = $result['processedTca']['columns'][$fieldName]['config'];
        $foreignTable = $fieldConfig['foreign_table'];
        $mmTable = isset($fieldConfig['MM']) ? $fieldConfig['MM'] : '';

        $rows = [];
        if ($mmTable !== '') {
            $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                $foreignTable . '.uid, CONCAT(' . $foreignTable . '.uid, \'|\', ' . $foreignTable . '.title) AS value',
                $foreignTable . '
                INNER JOIN ' . $mmTable . ' ON ' . $foreignTable . '.uid = ' . $mmTable . '.uid_foreign',
                $mmTable . '.uid_local = ' . (int) $result['databaseRow']['uid']
                . ' AND ' . $foreignTable . '.deleted = 0',
                $foreignTable . '.uid',
                '',
                '',
                'uid'
            );
        } elseif ($result['databaseRow']['tx_commerce_mountpoints']) {
            // this is the case for be_user and be_group mounts where the selected categories are stored as uid list
            $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'uid, CONCAT(uid, \'|\', title) AS value',
                $foreignTable,
                'uid IN (' . $result['databaseRow']['tx_commerce_mountpoints'] . ') AND deleted = 0',
                'uid',
                '',
                '',
                'uid'
            );
        }

        $defaultValues = isset($result['databaseRow'][$fieldName]) ? $result['databaseRow'][$fieldName] : null;
        if (empty($rows) && !empty($defaultValues)) {
            if (is_array($defaultValues)) {
                $where = 'uid IN (' . implode(',', $defaultValues) . ')';
            } else {
                $where = 'uid = ' . (int) $defaultValues;
            }

            $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'uid, CONCAT(uid, \'|\', title) AS value',
                $foreignTable,
                $where . ' AND deleted = 0',
                '',
                '',
                '',
                'uid'
            );
        }

        return $rows;
    }

    /**
     * Determines whether the current field is a valid target for this DataProvider
     *
     * @param array $fieldConfig
     * @return bool
     */
    protected function isTargetRenderType(array $fieldConfig)
    {
        return $fieldConfig['config']['renderType'] == 'commerceCategoryTree';
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
