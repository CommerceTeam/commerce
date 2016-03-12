<?php
namespace CommerceTeam\Commerce\Hooks;

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

use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains some hooks for processing formdata.
 * Hook for saving order data and order_articles.
 *
 * Class \CommerceTeam\Commerce\Hook\OrdermailHooks
 *
 * @author 2006-2011 Joerg Sprung <jsp@marketing-factory.de>
 */
class OrdermailHook
{
    /**
     * Content object.
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $cObj;

    /**
     * The Conversionobject.
     *
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    protected $csConvObj;

    /**
     * The content of the template in progress.
     *
     * @var string
     */
    protected $templateCode = '';

    /**
     * Template html code.
     *
     * @var string
     */
    protected $templateCodeHtml;

    /**
     * Path where finding Templates in CMS-File Structure.
     *
     * @var string
     */
    protected $templatePath;

    /**
     * Containg the actual Usermailadress which is in Progress.
     *
     * @var string
     */
    protected $customermailadress = '';

    /**
     * Tablename of table containing the Template for the specified Situations.
     *
     * @var string
     */
    protected $tablename = 'tx_commerce_moveordermails';

    /**
     * Constructor
     * Just instantiates the backend library.
     */
    public function __construct()
    {
        $this->cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->csConvObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
        $this->templatePath = PATH_site . 'uploads/tx_commerce/';
    }

    /**
     * This method converts an sends mails.
     *
     * @param array $mailconf Mail configuration
     * @param array $orderdata Order data
     * @param string $template Template
     *
     * @return bool of \TYPO3\CMS\Core\Mail\MailMessage
     */
    protected function ordermoveSendMail(array $mailconf, array &$orderdata, &$template)
    {
        // First line is subject
        $parts = explode(chr(10), $mailconf['plain']['content'], 2);
        // add mail subject
        $mailconf['alternateSubject'] = trim($parts[0]);
        // replace plaintext content
        $mailconf['plain']['content'] = trim($parts[1]);

        /*
         * Convert Text to charset
         */
        $this->csConvObj->initCharset('utf-8');
        $this->csConvObj->initCharset('8bit');

        $mailconf['plain']['content'] = $this->csConvObj->conv($mailconf['plain']['content'], 'utf-8', 'utf-8');
        $mailconf['alternateSubject'] = $this->csConvObj->conv($mailconf['alternateSubject'], 'utf-8', 'utf-8');

        $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Hook/OrdermailHooks', 'ordermoveSendMail');
        foreach ($hooks as $hook) {
            if (method_exists($hook, 'postOrdermoveSendMail')) {
                $hook->postOrdermoveSendMail($mailconf, $orderdata, $template);
            }
        }

        return \CommerceTeam\Commerce\Utility\GeneralUtility::sendMail($mailconf);
    }

    /**
     * Getting a template with all Templatenames in the Mailtemplaterecords
     * according to the given mailkind and pid.
     *
     * @param int $mailkind Move the Order in the Orderfolder
     * @param int $pid The PID of the order to move
     * @param int $orderSysLanguageUid Order language uid
     *
     * @return array of templatenames found in Filelist
     */
    protected function generateTemplateArray($mailkind, $pid, $orderSysLanguageUid)
    {
        /**
         * Page repository.
         *
         * @var \TYPO3\CMS\Frontend\Page\PageRepository
         */
        $pageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);

        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $this->tablename,
            'sys_language_uid = 0 AND pid = ' . $pid . ' AND mailkind = ' . $mailkind .
            \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($this->tablename)
        );

        $templates = [];
        foreach ($rows as $row) {
            $templates[] = $pageRepository->getRecordOverlay($this->tablename, $row, $orderSysLanguageUid);
        }

        return $templates;
    }

    /**
     * This method will be used by the initial methods before and after the Order
     * will be moved to another Orderstate.
     *
     * @param array $orderdata Containing the orderdatea like UID and PID
     * @param array $detaildata Containing the detaildata to Order like
     *      order_id and CustomerUIDs
     * @param int $mailkind Mail kind
     *
     * @return void
     */
    protected function processOrdermails(array &$orderdata, array &$detaildata, $mailkind)
    {
        $pid = $orderdata['pid'] ? $orderdata['pid'] : $detaildata['pid'];
        $templates = $this->generateTemplateArray($mailkind, $pid, $detaildata['order_sys_language_uid']);

        foreach ($templates as $template) {
            $this->templateCode = GeneralUtility::getUrl($this->templatePath . $template['mailtemplate']);
            $this->templateCodeHtml = GeneralUtility::getUrl($this->templatePath . $template['htmltemplate']);

            $settingsFactory = ConfigurationUtility::getInstance();
            $senderemail = $template['senderemail'] == '' ?
                $settingsFactory->getExtConf('defEmailAddress') :
                $template['senderemail'];
            if ($template['sendername'] == '') {
                if ($senderemail == $settingsFactory->getExtConf('defEmailAddress')) {
                    $sendername = $settingsFactory->getExtConf('defEmailSendername');
                } else {
                    $sendername = $senderemail;
                }
            } else {
                $sendername = $template['sendername'];
            }

            $pluginConfig = $this->getTypoScriptFrontendController()->tmpl->setup['plugin.']['tx_commerce_pi3'];

            // Mailconf for tx_commerce_div::sendMail($mailconf);
            $mailconf = [
                'plain' => [
                    'content' => $this->generateMail($orderdata['order_id'], $detaildata, $this->templateCode),
                ],
                'html' => [
                    'content' => $this->generateMail($orderdata['order_id'], $detaildata, $this->templateCodeHtml),
                    'path' => '',
                    'useHtml' => ($this->templateCodeHtml) ? '1' : '',
                ],
                'defaultCharset' => 'utf-8',
                'encoding' => '8bit',
                'attach' => '',
                'alternateSubject' => 'TYPO3 :: commerce',
                'recipient' => '',
                'recipient_copy' => $template['BCC'],
                'fromEmail' => $senderemail,
                'fromName' => $sendername,
                'replyTo' => $pluginConfig['usermail.']['from'],
                'priority' => '3',
                'callLocation' => 'processOrdermails',
            ];

            if ($template['otherreceiver'] != '') {
                $mailconf['recipient'] = $template['otherreceiver'];
                $this->ordermoveSendMail($mailconf, $orderdata, $template);
            } else {
                $mailconf['recipient'] = $this->customermailadress;
                $this->ordermoveSendMail($mailconf, $orderdata, $template);
            }
        }
    }

    /**
     * Initial method for hook that will be performed after the Order
     * will be moved to another Orderstate.
     *
     * @param array $orderdata Containing the orderdatea like UID and
     *      PID after moving
     * @param array $detaildata Containing the detaildata to Order like
     *      order_id and CustomerUIDs
     *
     * @return void
     */
    public function moveOrdersPreMoveOrder(array &$orderdata, array &$detaildata)
    {
        $this->processOrdermails($orderdata, $detaildata, 1);
    }

    /**
     * Initial method for hook that will be performed before the Order
     * will be moved to another Orderstate.
     *
     * @param array $orderdata Containing the orderdatea like UID and
     *      PID before moving
     * @param array $detaildata Containing the detaildata to Order like
     *      order_id and CustomerUIDs
     *
     * @return void
     */
    public function moveOrdersPostMoveOrder(array &$orderdata, array &$detaildata)
    {
        $this->processOrdermails($orderdata, $detaildata, 0);
    }

    /**
     * Renders on Adress in the template
     * This Method will not replace the Subpart, you have to replace your subpart
     * in your template by you own.
     *
     * @param array $addressArray Address (als Resultset from Select DB or Session)
     * @param string $subpartMarker Subpart marker
     * @param string $template Template
     *
     * @return string $content HTML-Content from the given Subpart.
     */
    protected function makeAdressView(array $addressArray, $subpartMarker, $template)
    {
        $template = $this->cObj->getSubpart($template, $subpartMarker);
        $content = $this->cObj->substituteMarkerArray($template, $addressArray, '###|###', 1);

        return $content;
    }

    /**
     * This Method generates a Mailcontent with $this->templatecode
     * as Mailtemplate. First Line in Template represents the Mailsubject.
     * The other required data can be queried from database by Parameters.
     *
     * @param string $orderUid The uid for the specified Order
     * @param array $orderData Contaning additional data like Customer UIDs.
     * @param string $templateCode Template code
     *
     * @return string The built Mailcontent
     */
    protected function generateMail($orderUid, array $orderData, $templateCode)
    {
        $database = $this->getDatabaseConnection();

        $markerArray = ['###ORDERID###' => $orderUid];

        $content = $this->cObj->getSubpart($templateCode, '###MAILCONTENT###');

        // Get The addresses
        $deliveryAdress = '';
        if ($orderData['cust_deliveryaddress']) {
            $data = $database->exec_SELECTgetSingleRow(
                '*',
                'tt_address',
                'uid = ' . (int) $orderData['cust_deliveryaddress']
            );
            if (is_array($data)) {
                $deliveryAdress = $this->makeAdressView($data, '###DELIVERY_ADDRESS###', $content);
            }
        }
        $content = $this->cObj->substituteSubpart($content, '###DELIVERY_ADDRESS###', $deliveryAdress);

        $billingAdress = '';
        if ($orderData['cust_invoice']) {
            $data = $database->exec_SELECTgetSingleRow('*', 'tt_address', 'uid = ' . (int) $orderData['cust_invoice']);
            if (is_array($data)) {
                $billingAdress = $this->makeAdressView($data, '###BILLING_ADDRESS###', $content);
                $this->customermailadress = $data['email'];
            }
        }
        $content = $this->cObj->substituteSubpart($content, '###BILLING_ADDRESS###', $billingAdress);

        $content = $this->cObj->substituteSubpart($content, '###INVOICE_VIEW###', '');

        /*
         * Hook for processing Marker Array
         */
        $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook('Hook/OrdermailHooks', 'generateMail');
        if (is_object($hookObject) && method_exists($hookObject, 'processMarker')) {
            $markerArray = $hookObject->processMarker($markerArray, $this);
        }

        $content = $this->cObj->substituteMarkerArray($content, $markerArray);

        // Since The first line of the mail is the Subject, trim the template
        return ltrim($content);
    }


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontend
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
