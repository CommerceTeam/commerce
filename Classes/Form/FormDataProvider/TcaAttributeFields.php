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

use CommerceTeam\Commerce\Domain\Repository\AttributeRepository;
use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use CommerceTeam\Commerce\ViewHelpers\AttributeViewHelper;
use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolve category tree items, set processed item list in processedTca, sanitize and resolve database field
 */
class TcaAttributeFields extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * @var array
     */
    protected $categoryForeignTableWhere = [
        // where for correlationtypes uid != 1
        0 => ' AND sys_language_uid in (0, -1) ORDER BY title',
        // where for correlationtypes uid 1
        1 => ' AND has_valuelist = 1 AND multiple = 0 AND sys_language_uid in (0, -1) ORDER BY title',
    ];

    /**
     * Field config to add for selector field if correlation type 1 is available
     *
     * @var array
     */
    protected $fieldConfig = [
        'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce.ct_###TITLE###',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectCheckBox',
            'foreign_table' => 'tx_commerce_attributes',
            'foreign_label' => 'title',
            'MM' => '###TABLE###_attributes_mm',
            'size' => 5,
            'autoSizeMax' => 20,
            'maxitems' => 30,
        ],
    ];

    /**
     * Resolve select items
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        if (!$this->isValidRecord($result)) {
            return $result;
        }

        switch ($result['tableName']) {
            case 'tx_commerce_categories':
                $result = $this->addAttributeFields($result);
                break;

            case 'tx_commerce_products':
                $result = $this->addAttributeFields($result);
                $result = $this->addProductAttributeEditField($result);
                break;

            case 'tx_commerce_articles':
                $result = $this->addArticleAttributeEditField($result);
                break;
        }

        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function addAttributeFields(array $result)
    {
        $template = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);
        $correlationTypes = $attributeRepository->findAllCorrelationTypes();
        $attributeCount = $attributeRepository->countAttributes();

        $root = &$result['processedTca']['columns']['attributes']['config']['ds']['sheets']['sDEF']['ROOT']['el'];
        if (empty($root) && !is_array($root)) {
            $root = [];
        }
        if ($attributeCount) {
            foreach ($correlationTypes as $correlationType) {
                $config = $this->fieldConfig;

                if ($correlationType['uid'] == 1) {
                    $config['config']['foreign_table_where'] = $this->categoryForeignTableWhere[1];
                } else {
                    $config['config']['foreign_table_where'] = $this->categoryForeignTableWhere[0];
                }

                $markers = [
                    '###TABLE###' => $result['tableName'],
                    '###UID###' => $correlationType['uid'],
                    '###TITLE###' => $correlationType['title'],
                ];
                $config['label'] = $template->substituteMarkerArray($config['label'], $markers);
                if ($config['config']['MM']) {
                    $config['config']['MM'] = $template->substituteMarkerArray($config['config']['MM'], $markers);
                }

                $root['ct_' . $correlationType['uid']] = $config;
            }
        }

        if (empty($root)) {
            $result = $this->setConfigEmptyAttributes($result, 'attributes');
        }

        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function addProductAttributeEditField(array $result)
    {
        // Get PID to select only the Attribute Values in the correct PID
        $attributePid = FolderRepository::initFolders('Attributes', FolderRepository::initFolders());
        $attributes = GeneralUtility::makeInstance(AttributeRepository::class)->findByProductUid($result['vanillaUid']);

        $root = &$result['processedTca']['columns']['attributesedit']['config']['ds']['sheets']['sDEF']['ROOT']['el'];
        foreach ($attributes as $attribute) {
            // Dont display in localised version Attributes with valuelist
            if ($attribute['has_valuelist'] == 1 && $result['databaseRow']['sys_language_uid'] != 0) {
                continue;
            }

            $root['attribute_' . $attribute['uid']] = $this->getAttributeEditFieldConfig(
                $attribute,
                $attributePid
            );
        }

        if (empty($root)) {
            $result = $this->setConfigEmptyAttributes($result, 'attributesedit');
        }

        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function addArticleAttributeEditField(array $result)
    {
        // Get PID to select only the Attribute Values in the correct PID
        $attributePid = FolderRepository::initFolders('Attributes', FolderRepository::initFolders());
        $attributes = GeneralUtility::makeInstance(AttributeRepository::class)->findByArticleUid($result['vanillaUid']);

        $root = &$result['processedTca']['columns']['attributesedit']['config']['ds']['sheets']['sDEF']['ROOT']['el'];
        foreach ($attributes as $attribute) {
            // Dont display in localised version Attributes with valuelist
            if ($attribute['has_valuelist'] == 1 && $result['databaseRow']['sys_language_uid'] != 0) {
                continue;
            }

            $root['attribute_' . $attribute['uid']] = $this->getAttributeEditFieldConfig(
                $attribute,
                $attributePid,
                // change type to display attribute value function in case of only display (in articles)
                (
                    ($attribute['uid_correlationtype'] == 1 && $attribute['has_valuelist'])
                    || $attribute['uid_correlationtype'] == 4
                )
            );
        }

        if (empty($root)) {
            $result = $this->setConfigEmptyAttributes($result, 'attributesedit');
        }

        return $result;
    }

    /**
     * @param array $attribute Attribute to get coonfig
     * @param int $attributePid Page id of the attribute folder
     * @param bool $displayOnly Flag if the attribute value should be displayed only defaults to false

     * @return mixed
     */
    protected function getAttributeEditFieldConfig(array $attribute, $attributePid, $displayOnly = false)
    {
        // set label
        $config['label'] = $attribute['title'];

        if ($displayOnly) {
            $config['config'] = [
                'type' => 'user',
                'userFunc' => AttributeViewHelper::class . '->displayAttributeValue',
                'aUid' => $attribute['uid'],
            ];

            return $config;
        }

        if ($attribute['has_valuelist'] == 1) {
            $config['config'] = [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_commerce_attribute_values',
                'foreign_table_where' => ' AND tx_commerce_attribute_values.attributes_uid = ' . $attribute['uid']
                    . ' AND tx_commerce_attribute_values.pid = ' . $attributePid
                    . ' AND tx_commerce_attribute_values.showvalue = 1 ORDER BY tx_commerce_attribute_values.value',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'items' => [
                    ['', 0],
                ],
            ];

            if ($attribute['multiple'] == 1) {
                // create a selectbox for multiple selection
                $config['config']['multiple'] = 1;
                $config['config']['size'] = 5;
                $config['config']['maxitems'] = 100;
                unset($config['config']['items']);
            }
        } else {
            // the field should be a simple input field
            if ($attribute['unit'] != '') {
                $config['label'] .= ' (' . $attribute['unit'] . ')';
            }
            $config['config'] = ['type' => 'input'];
        }

        return $config;
    }

    /**
     * @param array $result
     * @param string $fieldName
     * @return array
     */
    protected function setConfigEmptyAttributes($result, $fieldName)
    {
        // only assign these two values to keep displayCond if set previously
        $result['processedTca']['columns'][$fieldName]['label'] = '';
        $result['processedTca']['columns'][$fieldName]['config'] = [
            'type' => 'none',
            'pass_content' => true,
        ];
        $result['databaseRow'][$fieldName] = $this->getLanguageService()->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce.no_attributes_available'
        );

        return $result;
    }

    /**
     * @param array $result
     *
     * @return bool
     */
    protected function isValidRecord($result)
    {
        return in_array(
            $result['tableName'],
            ['tx_commerce_categories', 'tx_commerce_products', 'tx_commerce_articles']
        );
    }

    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     * @deprecated since 6.0.0 will be removed in 7.0.0
     */
    protected function getDatabaseConnection()
    {
        GeneralUtility::logDeprecatedFunction();
        return $GLOBALS['TYPO3_DB'];
    }
}
