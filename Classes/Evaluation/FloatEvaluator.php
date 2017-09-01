<?php
namespace CommerceTeam\Commerce\Evaluation;

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

use TYPO3\CMS\Saltedpasswords\Evaluation\Evaluator;

/**
 * Class FrontendEvaluator
 */
class FloatEvaluator extends Evaluator
{
    /**
     * Class constructor.
     */
    public function returnFieldJS()
    {
        return '
			return value.replace(",", ".");
		';
    }

    /**
     * @param array $parameter
     *
     * @return null|string
     */
    public function deevaluateFieldValue($parameter)
    {
        $value = $parameter['value'];
        if ('' == $value) {
            return '';
        }

        return sprintf('%01.2f', $value);
    }

    /**
     * @param mixed $value
     * @param string $is_in
     * @param bool $set
     *
     * @return null|string
     */
    public function evaluateFieldValue($value, $is_in, &$set)
    {
        if ('' == $value) {
            return 0;
        }

        return intval($value);
    }
}
