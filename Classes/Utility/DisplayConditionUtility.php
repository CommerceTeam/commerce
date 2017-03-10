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

use CommerceTeam\Commerce\Domain\Repository\ProductRepository;

/**
 * Class DisplayConditionUtility
 */
class DisplayConditionUtility
{
    /**
     * @param array $parameter
     * @return bool
     */
    public function checkProductCorrelationType($parameter)
    {
        $count = 0;
        if (!empty($parameter['record']) && isset($parameter['record']['uid'])) {
            /** @var ProductRepository $productRepository */
            $productRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ProductRepository::class);
            $count = $productRepository->countProductAttributesByProductAndCorrelationType(
                $parameter['record']['uid'],
                4
            );

            if (!$count) {
                $count = $productRepository->countCategoryAttributesByProductAndCorrelationType(
                    $parameter['record']['uid'],
                    4
                );
            }
        }
        return $count > 0;
    }
}
