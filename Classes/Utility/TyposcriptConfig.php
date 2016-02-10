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

/**
 * Typoscript config functions.
 *
 * Class \CommerceTeam\Commerce\Utility\TyposcriptConfig
 *
 * @author 2014 Sebastian Fischer <typo3@marketing-factory.de>
 */
class TyposcriptConfig extends \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractCondition
{
    /**
     * Is commerce page check.
     *
     * @param array $conditionParameters
     * @return bool
     */
    public function matchCondition(array $conditionParameters)
    {
        $module = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('M');

        if ($conditionParameters[0] == $module) {
            $result = true;
        } else {
            $record = $this->getPageRecord();
            $isCommerceModulePage = isset($record['module']) && $record['module'] == 'commerce';

            $result = $isCommerceModulePage;
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getPageRecord()
    {
        $pageId = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');

        return (array) \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $pageId);
    }
}
