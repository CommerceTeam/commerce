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

use CommerceTeam\Commerce\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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

            $fieldConfig['config']['maxitems'] = MathUtility::forceIntegerInRange(
                $fieldConfig['config']['maxitems'],
                0,
                99999
            );
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
        if ($fieldConfig['foreign_table'] !== 'tx_commerce_categories') {
            return [];
        }

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $mmTable = isset($fieldConfig['MM']) ? $fieldConfig['MM'] : '';

        $rows = [];
        if ($mmTable !== '') {
            $rows = $categoryRepository->findUntranslatedByRelationTable($mmTable, $result['databaseRow']['uid']);
        } elseif ($result['databaseRow']['tx_commerce_mountpoints']) {
            // this is the case for be_user and be_group mounts where the selected categories are stored as uid list
            $uidList = GeneralUtility::intExplode(',', $result['databaseRow']['tx_commerce_mountpoints'], true);
            $rows = $categoryRepository->findUntranslatedByUidList($uidList);
        }

        $defaultValues = isset($result['databaseRow'][$fieldName]) ? $result['databaseRow'][$fieldName] : null;
        if (empty($rows) && !empty($defaultValues)) {
            $uidList = is_array($defaultValues) ? $defaultValues : [$defaultValues];
            $rows = $categoryRepository->findUntranslatedByUidList($uidList);
        }

        return array_keys($rows);
    }

    /**
     * Determines whether the current field is a valid target for this DataProvider
     *
     * @param array $fieldConfig
     * @return bool
     */
    protected function isTargetRenderType(array $fieldConfig)
    {
        return $fieldConfig['config']['renderType'] == 'selectCommerceCategoryTree';
    }
}
