<?php
namespace CommerceTeam\Commerce\Configuration;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Typoscript config functions.
 *
 * Class \CommerceTeam\Commerce\Utility\TyposcriptConfig
 */
class NoCategorySet extends \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractCondition
{
    /**
     * Is commerce page check.
     *
     * @param array $conditionParameters
     * @return bool
     */
    public function matchCondition(array $conditionParameters)
    {
        $categorySet = false;
        $defaultCategorySet = false;

        // Get category uid from control
        $controlFromGetPost = GeneralUtility::_GP('control');
        if (is_array($controlFromGetPost) && isset($controlFromGetPost['categoryUid'])) {
            $categorySet = true;
        }

        // Get category from default values
        $defaultValues = GeneralUtility::_GP('defVals');
        if (is_array($defaultValues) && isset($defaultValues['tx_commerce_categories'])) {
            $defaultCategorySet = true;
        }

        return !$categorySet && !$defaultCategorySet;
    }
}
