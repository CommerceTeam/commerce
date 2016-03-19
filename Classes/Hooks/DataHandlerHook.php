<?php
namespace CommerceTeam\Commerce\Hooks;

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

use TYPO3\CMS\Core\DataHandling\DataHandler;

class DataHandlerHook
{
    /**
     * Hook needed to remove attribute relations
     *
     * @param DataHandler $dataHandler
     * @param array $currentValueArray
     * @param array $arrValue
     */
    public function checkFlexFormValue_beforeMerge($dataHandler, &$currentValueArray, $arrValue)
    {
        if ((
                isset($dataHandler->datamap['tx_commerce_categories'])
                || isset($dataHandler->datamap['tx_commerce_products'])
            )
            && isset($arrValue['data'])
            && isset($arrValue['data']['sDEF'])
            && isset($arrValue['data']['sDEF']['lDEF'])
        ) {
            foreach ($arrValue['data']['sDEF']['lDEF'] as $key => $value) {
                if (empty($value['vDEF'])) {
                    $currentValueArray['data']['sDEF']['lDEF'][$key] = $value;
                }
            }
        }
    }
}
