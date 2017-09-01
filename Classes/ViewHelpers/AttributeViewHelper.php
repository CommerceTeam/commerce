<?php
namespace CommerceTeam\Commerce\ViewHelpers;

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

use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\AttributeRepository;
use CommerceTeam\Commerce\Domain\Repository\AttributeValueRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A metaclass for creating inputfield fields in the backend.
 *
 * Class \CommerceTeam\Commerce\Utility\AttributeEditorUtility
 */
class AttributeViewHelper
{
    /**
     * Simply returns the value of an attribute of an article.
     *
     * @param array $parameter Parameter
     *
     * @return string
     */
    public function displayAttributeValue(array $parameter)
    {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);

        // attribute value uid
        $attributeUid = $parameter['fieldConf']['config']['aUid'];
        $articleUid = $parameter['row']['uid'];

        $relationData = $articleRepository->findAttributeRelationsByArticleAndAttribute($articleUid, $attributeUid);
        $attributeData = $attributeRepository->findByUid($attributeUid);

        if ($attributeData['multiple'] == 0) {
            $relationData = reset($relationData);
        }

        if ($attributeData['has_valuelist'] == '1') {
            /** @var AttributeValueRepository $attributeValueRepository */
            $attributeValueRepository = GeneralUtility::makeInstance(AttributeValueRepository::class);
            if ($attributeData['multiple'] == 1) {
                $valueUids = [];
                foreach ($relationData as $relation) {
                    $valueUids[] = (int) $relation['uid_valuelist'];
                }

                $values = $attributeValueRepository->findByUids($valueUids);

                $valueLabels = array_map(function ($value) {
                    return $value['value'];
                }, $values);

                $result = '<ul><li>' . implode(
                    '</li><li>',
                    \CommerceTeam\Commerce\Utility\GeneralUtility::removeXSSStripTagsArray($valueLabels)
                ) . '</li></ul>';
            } else {
                $value = $attributeValueRepository->findByUid((int) $relationData['uid_valuelist']);
                $result = $value['value'];
            }
        } elseif (!empty($relationData['value_char'])) {
            $result = $relationData['value_char'] . ' ' . $attributeData['unit'];
        } else {
            $result = $relationData['default_value'] . ' ' . $attributeData['unit'];
        }

        return htmlspecialchars(strip_tags($result));
    }
}
