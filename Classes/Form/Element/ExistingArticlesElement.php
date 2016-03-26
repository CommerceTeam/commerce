<?php
namespace CommerceTeam\Commerce\Form\Element;

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

use CommerceTeam\Commerce\Form\Container\ExistingArticleContainer;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides article rows for editing in product records.
 *
 * Class \CommerceTeam\Commerce\Form\Element\ExistingArticlesElement
 */
class ExistingArticlesElement extends AbstractFormElement
{
    /**
     * @var string
     */
    protected $table = 'tx_commerce_articles';

    /**
     * Render existing articles element.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $articles = $this->data['databaseRow']['articles'];

        if ($this->data['vanillaUid'] == 0 || empty($articles)) {
            return 'No articles existing for this product';
        }

        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_web_list.xlf');

        $output = '';
        $i = 0;

        /** @var ExistingArticleContainer $existingArticleContainer */
        $existingArticleContainer = GeneralUtility::makeInstance(
            ExistingArticleContainer::class,
            $this->table,
            $this->data,
            $this->iconFactory
        );

        foreach ($articles as $article) {
            $output .= $existingArticleContainer->renderArticleRow($articles, $article, $i);
            $i++;
        }

        $out = '
            <script>
                ' . $this->redirectUrls() . ';
            </script>

            <!--
                DB listing of elements:	"' . htmlspecialchars($this->table) . '"
            -->
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="table-fit" id="recordlist-' . htmlspecialchars($this->table)
            . '" data-state="expanded">
                        <table data-table="' . htmlspecialchars($this->table)
            . '" class="table table-striped table-hover">
                            <tbody>' . $output . '</tbody>
                        </table>
                    </div>
                </div>
            ';

        $resultArray = $this->initializeResultArray();
        $resultArray['html'] = $out;
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/Commerce/ExistingArticles';

        return $resultArray;
    }

    /**
     * Returns JavaScript variables setting the returnUrl and thisScript
     * location for use by JavaScript on the page.
     * Used in fx. db_list.php (Web>List).
     *
     * @param string $thisLocation URL to "this location" / current script
     *
     * @return string Urls are returned as T3_RETURN_URL and T3_THIS_LOCATION
     */
    protected function redirectUrls($thisLocation = '')
    {
        $thisLocation = $thisLocation ? $thisLocation : GeneralUtility::linkThisScript([
            'CB' => '',
            'SET' => '',
            'cmd' => '',
            'popViewId' => '',
        ]);

        $out = '
            var T3_RETURN_URL = \'' .
            str_replace('%20', '', rawurlencode(GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl')))) .
            '\';
            var T3_THIS_LOCATION = \'' . str_replace('%20', '', rawurlencode($thisLocation)) . '\';
        ';

        return $out;
    }
}
