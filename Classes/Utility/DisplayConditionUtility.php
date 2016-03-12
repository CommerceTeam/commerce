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
use TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions;

/**
 * Class DisplayConditionUtility
 */
class DisplayConditionUtility
{
    /**
     * @param array $parameter
     * @param EvaluateDisplayConditions $conditionEvaluator
     * @return bool
     */
    public function checkCorrelationType($parameter, $conditionEvaluator)
    {
        // return true if attributes with correlationtype 4 exist
        // for $parameter['record']['uid'] as uid_local or from parent category
        return true;
    }
}
