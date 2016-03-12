<?php
namespace CommerceTeam\Commerce\Utility;

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
 * Typoscript config functions.
 *
 * Class \CommerceTeam\Commerce\Utility\TyposcriptConfig
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
        $moduleFound = $folderFound = false;

        preg_match('|M=([^&]*)|i', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl'), $matches);
        $module = is_array($matches) && !empty($matches) ? $matches[1] : '';

        if ($conditionParameters[0] == $module) {
            $moduleFound = true;
        }

        $record = $this->getPageRecord();
        if (!empty($record)) {
            $folderNames = explode(',', $conditionParameters[1]);
            foreach ($folderNames as $folderName) {
                if ($record['tx_commerce_foldername'] == $folderName) {
                    $folderFound = true;
                    break;
                }
            }
        }

        return $moduleFound && $folderFound;
    }

    /**
     * @return array
     */
    protected function getPageRecord()
    {
        $pageId = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');

        return (array)\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $pageId);
    }
}
