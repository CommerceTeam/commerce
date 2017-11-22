<?php
namespace CommerceTeam\Commerce\Hooks\DataHandling;

use CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PricesDataMapProcessor extends AbstractDataMapProcessor
{
    /**
     * After database price handling.
     *
     * @param string $_
     * @param array $fieldArray Field array
     * @param int $id Id
     * @param DataHandler $dataHandler
     * @param string  $___
     */
    public function afterDatabase($_, $id, array $fieldArray, DataHandler $dataHandler, $___)
    {
        $id = $this->getSubstitutedId($dataHandler, $id);

        if (!isset($fieldArray['uid_article'])) {
            /** @var ArticlePriceRepository $articlePriceRepository */
            $articlePriceRepository = GeneralUtility::makeInstance(ArticlePriceRepository::class);
            $uidArticleRow = $articlePriceRepository->findByUid($id);
            $uidArticle = isset($uidArticleRow['uid_article']) ? $uidArticleRow['uid_article'] : 0;
        } else {
            $uidArticle = $fieldArray['uid_article'];
        }

        // @todo what to do with this? it was empty before refactoring
        $this->belib->savePriceFlexformWithArticle($id, (int) $uidArticle, $fieldArray);
    }
}
