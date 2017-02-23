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

/**
 * Misc COMMERCE functions.
 *
 * Class \CommerceTeam\Commerce\Utility\GeneralUtility
 */
class GeneralUtility
{
    /**
     * Removes XSS code and strips tags from an array recursivly.
     *
     * @param string $input Array of elements or other
     *
     * @return bool|array is an array, otherwise false
     */
    public static function removeXSSStripTagsArray($input)
    {
        /*
         * In Some cases this function is called with an empty variable, there
         * for check the Value and the type
         */
        if (!isset($input)) {
            return null;
        }

        if (is_bool($input)) {
            return $input;
        }

        if (is_string($input)) {
            return htmlspecialchars(strip_tags($input));
        }

        if (is_array($input)) {
            $returnValue = [];
            foreach ($input as $key => $value) {
                if (is_array($value)) {
                    $returnValue[$key] = self::removeXSSStripTagsArray($value);
                } else {
                    $returnValue[$key] = htmlspecialchars(strip_tags($value));
                }
            }

            return $returnValue;
        }

        return false;
    }

    /**
     * This method initilize the basket for the fe_user from
     * Session. If the basket is already initialized nothing happend
     * at this point.
     */
    public static function initializeFeUserBasket()
    {
        $basket = self::getBasket();

        if (!is_object($basket) || !$basket->getSessionId()) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::removeSingletonInstance(
                \CommerceTeam\Commerce\Domain\Model\Basket::class,
                $basket
            );

            $feUser = self::getFrontendUser();

            $commerceBasketIdKey = 'commerceBasketId-' . self::getBasketStoragePid();

            $basketId = $feUser->getKey('ses', $commerceBasketIdKey);

            $useCookieAsBasketIdFallback = ConfigurationUtility::getInstance()->getExtConf(
                'useCookieAsBasketIdFallback'
            );
            if (empty($basketId) && $useCookieAsBasketIdFallback && isset($_COOKIE[$commerceBasketIdKey])) {
                $basketId = $_COOKIE[$commerceBasketIdKey];
            }

            if (empty($basketId)) {
                $basketId = md5($feUser->id . ':' . rand(0, PHP_INT_MAX));
                $feUser->setKey('ses', $commerceBasketIdKey, $basketId);
                self::setCookie($basketId);
            }

            /**
             * Basket
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Basket $basket
             */
            $basket = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Basket::class
            );
            $basket->setSessionId($basketId);
            $basket->loadData();
            // @todo make singleton (setSingletonInstance/removeSingletonInstance)

            if ($useCookieAsBasketIdFallback
                && (
                    !isset($_COOKIE[$commerceBasketIdKey])
                    || !$_COOKIE[$commerceBasketIdKey]
                )
            ) {
                self::setCookie($basketId);
            }
        }
    }

    /**
     * Set cookie for basket.
     *
     * @param string $basketId Basket id
     */
    protected function setCookie($basketId)
    {
        setcookie(
            'commerceBasketId-' . self::getBasketStoragePid(),
            $basketId,
            $GLOBALS['EXEC_TIME'] + intval($GLOBALS['TYPO3_CONF_VARS']['FE']['sessionDataLifetime']),
            '/'
        );
    }

    /**
     * Remove Products from list wich have no articles wich are available from Stock.
     *
     * @param array $productUids List of productUIDs to work onn
     * @param int $dontRemoveProducts Switch to show or not show articles
     *
     * @return array Cleaned up Product array
     */
    public static function removeNoStockProducts(array $productUids = [], $dontRemoveProducts = 1)
    {
        if ($dontRemoveProducts == 1) {
            return $productUids;
        }

        foreach ($productUids as $arrayKey => $productUid) {
            /**
             * Product.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Product $product
             */
            $product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Product::class,
                $productUid
            );
            $product->loadData();

            if (!$product->hasStock()) {
                unset($productUids[$arrayKey]);
            }
            $product = null;
        }

        return $productUids;
    }

    /**
     * Remove article from product for frontendviewing, if articles
     * with no stock should not shown.
     *
     * @param \CommerceTeam\Commerce\Domain\Model\Product $product Product
     * @param int $dontRemoveArticles Switch to show or not show articles
     *
     * @return \CommerceTeam\Commerce\Domain\Model\Product Cleaned up product object
     */
    public static function removeNoStockArticles(
        \CommerceTeam\Commerce\Domain\Model\Product $product,
        $dontRemoveArticles = 1
    ) {
        if ($dontRemoveArticles == 1) {
            return $product;
        }

        $articleUids = $product->getArticleUids();
        $articles = $product->getArticleObjects();
        foreach ($articleUids as $arrayKey => $articleUid) {
            /**
             * Article.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Article $article
             */
            $article = $articles[$articleUid];
            if ($article->getStock() <= 0) {
                $product->removeArticleUid($arrayKey);
                $product->removeArticle($articleUid);
            }
        }

        return $product;
    }

    /**
     * Generates a session key for identifiing session contents and matching to user.
     *
     * @param string $key Key
     *
     * @return string Encoded Key as mixture of key and FE-User Uid
     */
    public static function generateSessionKey($key)
    {
        $frontendUser = self::getFrontendUser();
        if (ConfigurationUtility::getInstance()->getExtConf('userSessionMd5Encrypt')) {
            $sessionKey = md5($key . ':' . $frontendUser->user['uid']);
        } else {
            $sessionKey = $key . ':' . $frontendUser->user['uid'];
        }

        $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Utility/GeneralUtility', 'generateSessionKey');
        foreach ($hooks as $hook) {
            if (method_exists($hook, 'postGenerateSessionKey')) {
                $sessionKey = $hook->postGenerateSessionKey($key);
            }
        }

        return $sessionKey;
    }

    /**
     * Invokes the HTML mailing class
     * Example for $mailconf.
     *
     * $mailconf = [
     *     'plain' => [
     *         'content'=> ''              // plain content as string
     *     ],
     *     'html' => [
     *         'content'=> '',             // html content as string
     *         'path' => '',
     *         'useHtml' => ''             // is set mail is send as multipart
     *     ],
     *     'defaultCharset' => 'utf-8',    // your chartset
     *     'encoding' => '8-bit',          // your encoding
     *     'attach' => [],                 // your attachment as array
     *     'alternateSubject' => '',       // is subject empty will be ste alternateSubject
     *     'recipient' => '',              // comma seperate list of recipient
     *     'recipient_copy' => '',         // bcc
     *     'fromEmail' => '',              // fromMail
     *     'fromName' => '',               // fromName
     *     'replyTo' => '',                // replyTo
     *     'priority' => '3',              // priority of your Mail
     *                                         1 = highest,
     *                                         5 = lowest,
     *                                         3 = normal
     * ];
     *
     * @param array $mailconf Configuration for the mailerengine
     *
     * @return bool
     */
    public static function sendMail(array $mailconf)
    {
        $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Utility/GeneralUtility', 'sendMail');

        $additionalData = [];
        if ($mailconf['additionalData']) {
            $additionalData = $mailconf['additionalData'];
        }

        foreach ($hooks as $hookObj) {
            // this is the current hook
            if (method_exists($hookObj, 'preProcessMail')) {
                $hookObj->preProcessMail($mailconf, $additionalData);
            }
        }

        foreach ($hooks as $hookObj) {
            if (method_exists($hookObj, 'ownMailRendering')) {
                return $hookObj->ownMailRendering($mailconf, $additionalData, $hooks);
            }
        }

        // validate e-mail addesses
        $mailconf['recipient'] = self::validEmailList($mailconf['recipient']);

        if ($mailconf['recipient']) {
            $parts = preg_split('/<title>|<\/title>/i', $mailconf['html']['content'], 3);

            if (trim($parts[1])) {
                $subject = strip_tags(trim($parts[1]));
            } elseif ($mailconf['plain']['subject']) {
                $subject = $mailconf['plain']['subject'];
            } else {
                $subject = $mailconf['alternateSubject'];
            }

            /**
             * Mail message.
             *
             * @var \TYPO3\CMS\Core\Mail\MailMessage $message
             */
            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
            $message->setCharset($mailconf['defaultCharset']);

            if ($mailconf['encoding'] == 'base64') {
                $message->setEncoder(\Swift_Encoding::getBase64Encoding());
            } elseif ($mailconf['encoding'] == '8bit') {
                $message->setEncoder(\Swift_Encoding::get8BitEncoding());
            }

            $message->setSubject($subject);
            $message->setTo($mailconf['recipient']);
            $message->setFrom(
                self::validEmailList($mailconf['fromEmail']),
                implode(' ', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $mailconf['fromName']))
            );

            $replyAddress = $mailconf['replyTo'] ?: $mailconf['fromEmail'];
            $replyName = implode(
                ' ',
                \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
                    ',',
                    $mailconf['replyTo'] ? '' : $mailconf['fromName']
                )
            );
            $message->setReplyTo($replyAddress, $replyName);

            if (isset($mailconf['recipient_copy']) && $mailconf['recipient_copy'] != '') {
                $message->setCc($mailconf['recipient_copy']);
            }

            if (isset($mailconf['blindcopy']) && $mailconf['blindcopy'] != '') {
                $message->setBcc($mailconf['blindcopy']);
            }

            $message->setReturnPath($mailconf['fromEmail']);
            $message->setPriority((int) $mailconf['priority']);

            // add Html content
            if ($mailconf['html']['useHtml'] && trim($mailconf['html']['content'])) {
                $message->addPart($mailconf['html']['content'], 'text/html');
            }

            // add plain text content
            $message->addPart($mailconf['plain']['content']);

            // add attachment
            if (is_array($mailconf['attach'])) {
                foreach ($mailconf['attach'] as $file) {
                    if ($file && file_exists($file)) {
                        $message->attach(\Swift_Attachment::fromPath($file));
                    }
                }
            }

            foreach ($hooks as $hookObj) {
                if (method_exists($hookObj, 'postProcessMail')) {
                    $message = $hookObj->postProcessMail($message, $mailconf, $additionalData);
                }
            }

            return $message->send();
        }

        return false;
    }

    /**
     * Helperfunction for email validation.
     *
     * @param string $list Comma seperierte list of email addresses
     *
     * @return string
     */
    public static function validEmailList($list)
    {
        $dataArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $list);

        $returnArray = [];
        foreach ($dataArray as $data) {
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($data)) {
                $returnArray[] = $data;
            }
        }

        $newList = '';
        if (is_array($returnArray)) {
            $newList = implode(',', $returnArray);
        }

        return $newList;
    }

    /**
     * Sanitize string to have character and numbers only.
     *
     * @param string $value Value
     *
     * @return string
     */
    public static function sanitizeAlphaNum($value)
    {
        preg_match('@[ a-z0-9].*@i', $value, $matches);

        return $matches[0];
    }

    /**
     * Gets the basket storage pid.
     *
     * @return int
     */
    public static function getBasketStoragePid()
    {
        if (self::getFrontendController()->tmpl->setup['plugin.']['tx_commerce_pi2.']['basketStoragePid']) {
            $basketStoragePid = (int) self::getFrontendController()
                ->tmpl
                    ->setup['plugin.']['tx_commerce_pi2.']['basketStoragePid'];
        } else {
            $basketStoragePid = ConfigurationUtility::getInstance()->getExtConf('BasketStoragePid');
        }

        return $basketStoragePid;
    }


    /**
     * Get typoscript frontend controller.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected static function getFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Get frontend user.
     *
     * @return \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
     */
    protected static function getFrontendUser()
    {
        return self::getFrontendController()->fe_user;
    }

    /**
     * Get basket.
     *
     * @return \CommerceTeam\Commerce\Domain\Model\Basket
     */
    public static function getBasket()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Basket::class);
    }
}
