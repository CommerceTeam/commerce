<?php
namespace CommerceTeam\Commerce\Domain\Model;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Libary for frontend rendering of article prices.
 *
 * Class \CommerceTeam\Commerce\Domain\Model\ArticlePrice
 */
class ArticlePrice extends AbstractEntity
{
    /**
     * Database class name.
     *
     * @var string
     */
    protected $repositoryClass = \CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository::class;

    /**
     * Database connection.
     *
     * @var \CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository
     */
    public $databaseConnection;

    /**
     * Field list.
     *
     * @var array
     */
    protected $fieldlist = [
        'price_net',
        'price_gross',
        'fe_group',
        'price_scale_amount_start',
        'price_scale_amount_end',
    ];

    /**
     * Currency for price.
     *
     * @var string
     */
    protected $currency = 'EUR';

    /**
     * Price scale amount start.
     *
     * @var int
     */
    protected $price_scale_amount_start = 1;

    /**
     * Price scale amount end.
     *
     * @var int
     */
    protected $price_scale_amount_end = 1;

    /**
     * Price gross.
     *
     * @var int
     */
    protected $price_gross = 0;

    /**
     * Price net.
     *
     * @var int
     */
    protected $price_net = 0;

    /**
     * Constructor Method, calles init method.
     *
     * @param int $uid Article price uid
     * @param int $languageUid Language uid
     */
    public function __construct($uid = 0, $languageUid = 0)
    {
        if ((int) $uid) {
            $this->init($uid, $languageUid);
        }
    }

    /**
     * Usual init method.
     *
     * @param int $uid         Uid of product
     * @param int $languageUid Uid of language, unused
     *
     * @return bool TRUE if $uid is > 0
     */
    public function init($uid, $languageUid = 0)
    {
        $initializationResult = false;
        $this->uid = (int) $uid;
        if ($this->uid > 0) {
            $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Domain/Model/ArticlePrice', 'init');
            foreach ($hooks as $hook) {
                if (method_exists($hook, 'postinit')) {
                    $hook->postinit($this);
                }
            }

            $initializationResult = true;
        }
        $this->lang_uid = (int) $languageUid;

        return $initializationResult;
    }

    /**
     * Set currency.
     *
     * @param string $currency Currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Get currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set price net.
     *
     * @param int $priceNet Price net
     */
    public function setPriceNet($priceNet)
    {
        $this->price_net = (int) $priceNet;
    }

    /**
     * Get net price.
     *
     * @return int Price net
     */
    public function getPriceNet()
    {
        $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Domain/Model/Article', 'getPriceNet');
        foreach ($hooks as $hook) {
            if (method_exists($hook, 'postpricenet')) {
                $hook->postpricenet($this);
            }
        }

        return $this->price_net;
    }

    /**
     * Price gross.
     *
     * @param int $priceGross Price gross
     */
    public function setPriceGross($priceGross)
    {
        $this->price_gross = (int) $priceGross;
    }

    /**
     * Get price gross.
     *
     * @return int price gross
     */
    public function getPriceGross()
    {
        $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Domain/Model/Article', 'getPriceGross');
        foreach ($hooks as $hook) {
            if (method_exists($hook, 'postpricegross')) {
                $hook->postpricegross($this);
            }
        }

        return $this->price_gross;
    }

    /**
     * Get price scale amount start.
     *
     * @return int Scale amount start
     */
    public function getPriceScaleAmountStart()
    {
        return $this->price_scale_amount_start;
    }

    /**
     * Get price scale amount end.
     *
     * @return int Scale amount end
     */
    public function getPriceScaleAmountEnd()
    {
        return $this->price_scale_amount_end;
    }

    /**
     * Returns TCA label, used in TCA only.
     *
     * @param array $params Record value
     */
    public function getTcaRecordTitle(array &$params)
    {
        $languageService = $this->getLanguageService();

        $feGroup = '';
        if ($params['row']['fe_group']) {
            $feGroups = is_array($params['row']['fe_group']) ?
                implode(',', $params['row']['fe_group']) :
                $params['row']['fe_group'];
            $feGroup = htmlspecialchars($languageService->sL(
                BackendUtility::getItemLabel('tx_commerce_article_prices', 'fe_group')
            )) .
            BackendUtility::getProcessedValueExtra(
                'tx_commerce_article_prices',
                'fe_group',
                $feGroups,
                100,
                $params['row']['uid']
            );
        }

        $params['title'] = htmlspecialchars($languageService->sL(
            BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_gross')
        )) .
        ': ' . sprintf('%01.2f', $params['row']['price_gross']) .
        ', ' . htmlspecialchars($languageService->sL(
            BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_net')
        )) .
        ': ' . sprintf('%01.2f', $params['row']['price_net']) .
        ' (' . htmlspecialchars($languageService->sL(
            BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_scale_amount_start')
        )) .
        ': ' . $params['row']['price_scale_amount_start'] .
        ' ' . htmlspecialchars($languageService->sL(
            BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_scale_amount_end')
        )) .
        ': ' . $params['row']['price_scale_amount_end'] . ') ' . $feGroup;
    }

    /**
     * Get language service.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
