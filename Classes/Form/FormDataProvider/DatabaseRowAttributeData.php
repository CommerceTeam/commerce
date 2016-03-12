<?php
namespace CommerceTeam\Commerce\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * Provider to fix some error with AbstractItemProvider::processDatabaseFieldValue
 * @todo remove if AbstractItemProvider::processDatabaseFieldValue is array aware
 *
 * @package CommerceTeam\Commerce\Form\FormDataProvider
 */
class DatabaseRowAttributeData implements FormDataProviderInterface
{
    /**
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['databaseRow'] as &$correlationValue) {
            if (is_array($correlationValue)) {
                $correlationValue = implode(',', $correlationValue);
            }
        }

        return $result;
    }
}
