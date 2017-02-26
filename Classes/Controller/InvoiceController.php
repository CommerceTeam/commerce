<?php
namespace CommerceTeam\Commerce\Controller;

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

use CommerceTeam\Commerce\Domain\Repository\AddressRepository;
use CommerceTeam\Commerce\Domain\Repository\CurrencyRepository;
use CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\OrderRepository;
use CommerceTeam\Commerce\Factory\HookFactory;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use CommerceTeam\Commerce\ViewHelpers\MoneyViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'commerce_invoice' for the 'commerce_invoice' extension.
 */
class InvoiceController extends BaseController
{
    /**
     * Same as class name.
     *
     * @var string
     */
    public $prefixId = 'tx_commerce_pi6';

    /**
     * Flag if chash should be checked.
     *
     * @var bool
     */
    public $pi_checkCHash = true;

    /**
     * Order id.
     *
     * @var string
     */
    public $order_id;

    /**
     * Frontend user.
     *
     * @var array
     */
    protected $user;

    /**
     * Content.
     *
     * @var string
     */
    protected $content;

    /**
     * Order.
     *
     * @var array
     */
    protected $order;

    /**
     * Order payment.
     *
     * @var string
     */
    protected $orderPayment;

    /**
     * Order delivery.
     *
     * @var string
     */
    protected $orderDelivery;

    /**
     * Main Method.
     *
     * @param string $content Content of this plugin
     * @param array $conf TS configuration for this plugin
     *
     * @return string Compiled content
     */
    public function main($content, array $conf = [])
    {
        $frontend = $this->getTypoScriptFrontendController();
        /** @noinspection PhpInternalEntityUsedInspection */
        $backendUser = $this->getBackendUser()->user;

        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();

        // Checking backend user login
        $this->invoiceBackendOnly(ConfigurationUtility::getInstance()->getExtConf('invoiceBackendOnly'));

        /** @noinspection PhpInternalEntityUsedInspection */
        $user = $this->getFrontendUser()->user;
        // Check for the logged in USER
        // It could be an FE USer, a BE User or an automated script
        if (empty($user)
            && !$backendUser['uid']
            && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']
        ) {
            return $this->pi_getLL('not_logged_in');
        } elseif ($user && !$backendUser['uid']) {
            $this->user = $user;
        }

        // If it's an automated process, no caching
        if ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) {
            $frontend->set_no_cache();
        }

        // Lets make this multilingual, eh?
        $this->generateLanguageMarker();

        // We may need to do some character conversion tricks
        /**
         * Charset converter.
         *
         * @var \TYPO3\CMS\Core\Charset\CharsetConverter
         */
        $convert = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Charset\CharsetConverter::class
        );

        // If there is no order id, this plugin serves no pupose
        $this->order_id = $this->piVars['order_id'];

        // @todo In case of a FE user this should not give a hint
        // about what's wrong, but instead redirect the user
        if (empty($this->order_id)) {
            return $this->pi_wrapInBaseClass($this->pi_getLL('error_orderid'));
        }
        if (empty($this->conf['templateFile'])) {
            return $this->error('init', __LINE__, 'Template File not defined in TS: ');
        }

        // Grab the template
        $this->templateCode = (string) file_get_contents(
            $this->getTypoScriptFrontendController()->tmpl->getFileName($this->conf['templateFile'])
        );
        if (empty($this->templateCode)) {
            return $this->error(
                'init',
                __LINE__,
                'Template File not loaded, maybe it doesn\'t exist: ' . $this->conf['templateFile']
            );
        }

        // Get subparts
        $templateMarker = '###TEMPLATE###';
        $this->template['invoice'] = $this->cObj->getSubpart($this->templateCode, $templateMarker);
        $this->template['item'] = $this->cObj->getSubpart($this->template['invoice'], '###LISTING_ARTICLE###');

        // Markers and content, ready to be populated
        $markerArray = [];
        $this->content = '';
        $this->order = $this->getOrderData();
        if ($this->order) {
            /** @var CurrencyRepository $currencyRepository */
            $currencyRepository = $this->getObjectManager()->get(CurrencyRepository::class);
            $row = $currencyRepository->findByIso3($this->order['cu_iso_3_uid']);
            $this->conf['currency'] = !empty($row) ? $row['cu_iso_3'] : $this->conf['currency'];

            $this->orderPayment = $this->getOrderSystemArticles((int) $this->order['uid'], '2', 'PAYMENT_');
            $this->orderDelivery = $this->getOrderSystemArticles((int) $this->order['uid'], '3', 'SHIPPING_');

            $markerArray['###ORDER_TAX###'] = MoneyViewHelper::format(
                $this->order['sum_price_gross'] - $this->order['sum_price_net'],
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $markerArray['###ORDER_TOTAL###'] = MoneyViewHelper::format(
                $this->order['sum_price_gross'],
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $markerArray['###ORDER_NET_TOTAL###'] = MoneyViewHelper::format(
                $this->order['sum_price_net'],
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $markerArray['###ORDER_GROSS_TOTAL###'] = MoneyViewHelper::format(
                $this->order['sum_price_gross'],
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $markerArray['###ORDER_ID###'] = $this->order['order_id'];
            $markerArray['###ORDER_DATE###'] = strftime($this->conf['orderDateFormat'], $this->order['crdate']);

                // Fill some of the content from typoscript settings, to ease the
            $markerArray['###INVOICE_HEADER###'] = $this->cObj->cObjGetSingle(
                $this->conf['invoiceheader'],
                $this->conf['invoiceheader.']
            );
            $markerArray['###INVOICE_SHOP_NAME###'] = $this->cObj->cObjGetSingle('TEXT', $this->conf['shopname.']);
            $markerArray['###INVOICE_SHOP_ADDRESS###'] = $this->cObj->cObjGetSingle(
                $this->conf['shopdetails'],
                $this->conf['shopdetails.']
            );
            $markerArray['###INVOICE_INTRO_MESSAGE###'] = $this->cObj->cObjGetSingle('TEXT', $this->conf['intro.']);
            $markerArray['###INVOICE_THANKYOU###'] = $this->cObj->cObjGetSingle('TEXT', $this->conf['thankyou.']);

            // Hook to process new/changed marker
            $hooks = HookFactory::getHooks('Controller/InvoiceController', 'main');
            $subpartArray = [];
            foreach ($hooks as $hook) {
                if (method_exists($hook, 'additionalMarker')) {
                    $markerArray = $hook->additionalMarker($markerArray, $subpartArray, $this);
                }
            }

            $subpartArray['###LISTING_ARTICLE###'] = $this->getOrderArticles(
                (int) $this->order['uid'],
                $this->conf['OrderArticles.'],
                'ARTICLE_'
            );
            $subpartArray['###ADDRESS_BILLING_DATA###'] = $this->getAddressData(
                $this->order['cust_invoice'],
                $this->conf['addressBilling.'],
                'ADDRESS_BILLING_'
            );
            $subpartArray['###ADDRESS_DELIVERY_DATA###'] = $this->getAddressData(
                $this->order['cust_deliveryaddress'],
                $this->conf['addressDelivery.'],
                'ADDRESS_DELIVERY_'
            );
            $this->content = $this->substituteMarkerArrayNoCached($this->template['invoice'], [], $subpartArray);

            // Buid content from template + array
            $this->content = $this->cObj->substituteSubpart(
                $this->content,
                '###LISTING_PAYMENT_ROW###',
                $this->orderPayment
            );
            $this->content = $this->cObj->substituteSubpart(
                $this->content,
                '###LISTING_SHIPPING_ROW###',
                $this->orderDelivery
            );
            $this->content = $this->cObj->substituteMarkerArray($this->content, $markerArray);
            $this->content = $this->cObj->substituteMarkerArray($this->content, $this->languageMarker);
        } else {
            $this->content = $this->pi_getLL('error_nodata');
        }
        if ($this->conf['decode'] == '1') {
            $this->content = $convert->specCharsToASCII('utf-8', $this->content);
        }

        $content .= $this->content;

        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Check Access.
     *
     * @param bool|string $enabled Optional, default FALSE
     *
     * @return void
     */
    protected function invoiceBackendOnly($enabled = false)
    {
        /** @noinspection PhpInternalEntityUsedInspection */
        if ($enabled && !$this->getBackendUser()->user['uid'] && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
            /**
             * Error message.
             *
             * @var \TYPO3\CMS\Core\Messaging\ErrorpageMessage
             */
            $messageObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\ErrorpageMessage::class,
                'Login-error',
                'No user logged in! Sorry, I can\'t proceed then!'
            );
            $messageObj->output();
            exit;
        }
    }

    /**
     * Render ordered articles.
     *
     * @param int $orderUid OrderUID
     * @param array $typoScript Optional, default is FALSE, contains TS configuration
     * @param string $prefix Prefix
     *
     * @return string HTML-Output rendered
     */
    protected function getOrderArticles($orderUid, array $typoScript = [], $prefix = '')
    {
        if (empty($typoScript)) {
            $typoScript = $this->conf['OrderArticles.'];
        }

        /** @var OrderArticleRepository $orderArticlesRepository */
        $orderArticlesRepository = GeneralUtility::makeInstance(OrderArticleRepository::class);
        $rows = $orderArticlesRepository->findByOrderIdAndType($orderUid, 1);

        $orderpos = 1;
        $out = '';
        foreach ($rows as $row) {
            $markerArray = $this->generateMarkerArray($row, $typoScript, $prefix, 'tx_commerce_order_articles');
            $markerArray['ARTICLE_PRICE'] = MoneyViewHelper::format(
                $row['price_gross'],
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $markerArray['ARTICLE_PRICE_GROSS'] = MoneyViewHelper::format(
                $row['price_gross'],
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $markerArray['ARTICLE_PRICE_NET'] = MoneyViewHelper::format(
                $row['price_net'],
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $markerArray['ARTICLE_TOTAL'] = MoneyViewHelper::format(
                ($row['amount'] * $row['price_gross']),
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $markerArray['ARTICLE_TOTAL_GROSS'] = MoneyViewHelper::format(
                ($row['amount'] * $row['price_gross']),
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $markerArray['ARTICLE_TOTAL_NET'] = MoneyViewHelper::format(
                ($row['amount'] * $row['price_net']),
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $markerArray['ARTICLE_POSITION'] = $orderpos++;
            $out .= $this->cObj->substituteMarkerArray($this->template['item'], $markerArray, '###|###', 1);
        }

        return $this->cObj->stdWrap($out, $typoScript);
    }

    /**
     * Render address data.
     *
     * @param int $addressUid AddressUID
     * @param array $typoScript Optional, default is FALSE, contains TS configuration
     * @param string $prefix Prefix
     *
     * @return string HTML-Output rendert
     */
    protected function getAddressData($addressUid = 0, array $typoScript = [], $prefix = '')
    {
        if (empty($typoScript)) {
            $typoScript = $this->conf['address.'];
        }

        /** @var AddressRepository $addressRepository */
        $addressRepository = $this->getObjectManager()->get(AddressRepository::class);
        if ($this->user) {
            if ($addressUid) {
                $row = $addressRepository->findByUserAndUid($this->order['cust_fe_user'], $addressUid);
            } else {
                $row = $addressRepository->findByUserAndType($this->order['cust_fe_user'], 1);
            }
        } else {
            if ($addressUid) {
                $row = $addressRepository->findByUid($addressUid);
            } else {
                $row = $addressRepository->findByType(1);
            }
        }

        $markerArray = $this->generateMarkerArray($row, $typoScript, $prefix, 'tt_address');
        $template = $this->cObj->getSubpart($this->templateCode, '###' . $prefix . 'DATA###');
        $content = $this->cObj->substituteMarkerArray($template, $markerArray, '###|###', 1);
        $content = $this->cObj->substituteMarkerArray($content, $this->languageMarker);

        return $this->cObj->stdWrap($content, $typoScript);
    }

    /**
     * Render Data for Orders.
     *
     * @return array orderData
     */
    protected function getOrderData()
    {
        /** @var OrderRepository $orderRepository */
        $orderRepository = GeneralUtility::makeInstance(OrderRepository::class);
        $order = $orderRepository->findByOrderIdAndUser($this->order_id, $this->user['uid']);

        return $order;
    }

    /**
     * Render marker array for System Articles.
     *
     * @param int $orderUid OrderUID
     * @param int $articleType Optional, articleTypeID
     * @param string $prefix Prefix
     *
     * @return string System Articles
     */
    protected function getOrderSystemArticles($orderUid, $articleType = 0, $prefix = '')
    {
        /** @var OrderArticleRepository $orderArticleRepository */
        $orderArticleRepository = GeneralUtility::makeInstance(OrderArticleRepository::class);
        $orderArticles = $orderArticleRepository->findByOrderIdAndType($orderUid, $articleType);

        $content = '';
        foreach ($orderArticles as $orderArticle) {
            $subpart = $this->cObj->getSubpart($this->templateCode, '###LISTING_' . $prefix . 'ROW###');
            // @todo Use $markerArray = $this->generateMarkerArray($row, '', $prefix);
            $markerArray['###' . $prefix . 'AMOUNT###'] = $orderArticle['amount'];
            $markerArray['###' . $prefix . 'METHOD###'] = $orderArticle['title'];
            $markerArray['###' . $prefix . 'COST###'] = MoneyViewHelper::format(
                ($orderArticle['amount'] * $orderArticle['price_gross']),
                $this->conf['currency'],
                (bool) $this->conf['showCurrencySign']
            );
            $content .= $this->cObj->substituteMarkerArray($subpart, $markerArray);
        }

        return $content;
    }
}
