<?php
namespace CommerceTeam\Commerce\Form\FormDataProvider;

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
        $mmTable = $fieldConfig['MM'];

        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            $foreignTable . '.uid, CONCAT(' . $foreignTable . '.uid, \'|\', ' . $foreignTable . '.title) AS value',
            $foreignTable . '
                INNER JOIN ' . $mmTable . ' ON ' . $foreignTable . '.uid = ' . $mmTable . '.uid_foreign',
            $mmTable . '.uid_local = ' . $result['databaseRow']['uid'] . ' AND ' . $foreignTable . '.deleted = 0',
            $foreignTable . '.uid',
            '',
            '',
            'uid'
        );
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
}
