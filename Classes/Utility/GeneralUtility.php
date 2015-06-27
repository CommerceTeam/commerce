<?php
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

/**
 * Misc COMMERCE functions
 *
 * Class Tx_Commerce_Utility_GeneralUtility
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Utility_GeneralUtility {
	/**
	 * Removes XSS code and strips tags from an array recursivly
	 *
	 * @param string $input Array of elements or other
	 *
	 * @return bool|array is an array, otherwise false
	 */
	public static function removeXSSStripTagsArray($input) {
		/**
		 * In Some cases this function is called with an empty variable, therfore
		 * check the Value and the type
		 */
		if (!isset($input)) {
			return NULL;
		}
		if (is_bool($input)) {
			return $input;
		}
		if (is_string($input)) {
			return (string) \TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS(strip_tags($input));
		}
		if (is_array($input)) {
			$returnValue = array();
			foreach ($input as $key => $value) {
				if (is_array($value)) {
					$returnValue[$key] = self::removeXSSStripTagsArray($value);
				} else {
					$returnValue[$key] = \TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS(strip_tags($value));
				}
			}
			return $returnValue;
		}
		return FALSE;
	}

	/**
	 * This method initilize the basket for the fe_user from
	 * Session. If the basket is already initialized nothing happend
	 * at this point.
	 *
	 * @return void
	 */
	public static function initializeFeUserBasket() {
		$feUser = self::getFrontendController()->fe_user;
		/**
		 * Basket
		 *
		 * @var Tx_Commerce_Domain_Model_Basket $basket
		 */
		$basket = & $feUser->tx_commerce_basket;

		if (!is_object($basket)) {
			$basketId = $feUser->getKey('ses', 'commerceBasketId');
			if (
				empty($basketId) &&
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['extConf']['useCookieAsBasketIdFallback']
				&& $_COOKIE['commerceBasketId']
			) {
				$basketId = $_COOKIE['commerceBasketId'];
			}

			if (empty($basketId)) {
				$basketId = md5($feUser->id . ':' . rand(0, PHP_INT_MAX));
				$feUser->setKey('ses', 'commerceBasketId', $basketId);
				self::setCookie($basketId);
			}

			$basket = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Basket');
			$basket->setSessionId($basketId);
			$basket->loadData();

			if (
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['extConf']['useCookieAsBasketIdFallback']
				&& !$_COOKIE['commerceBasketId']
			) {
				self::setCookie($basketId);
			}
		}
	}

	/**
	 * Set cookie for basket
	 *
	 * @param string $basketId Basket id
	 *
	 * @return void
	 */
	protected function setCookie($basketId) {
		setcookie(
			'commerceBasketId',
			$basketId,
			$GLOBALS['EXEC_TIME'] + intval($GLOBALS['TYPO3_CONF_VARS']['FE']['sessionDataLifetime']),
			'/'
		);
	}

	/**
	 * Remove Products from list wich have no articles wich are available from Stock
	 *
	 * @param array $productUids List of productUIDs to work onn
	 * @param int $dontRemoveProducts Switch to show or not show articles
	 *
	 * @return array Cleaned up Product array
	 */
	public static function removeNoStockProducts(array $productUids = array(), $dontRemoveProducts = 1) {
		if ($dontRemoveProducts == 1) {
			return $productUids;
		}

		foreach ($productUids as $arrayKey => $productUid) {
			/**
			 * Product
			 *
			 * @var Tx_Commerce_Domain_Model_Product $productObj
			 */
			$productObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $productUid);
			$productObj->loadData();

			if (!($productObj->hasStock())) {
				unset($productUids[$arrayKey]);
			}
			$productObj = NULL;
		}

		return $productUids;
	}

	/**
	 * Remove article from product for frontendviewing, if articles
	 * with no stock should not shown
	 *
	 * @param Tx_Commerce_Domain_Model_Product $productObj ProductObject to work on
	 * @param int $dontRemoveArticles Switch to show or not show articles
	 *
	 * @return Tx_Commerce_Domain_Model_Product Cleaned up Productobjectt
	 */
	public static function removeNoStockArticles(Tx_Commerce_Domain_Model_Product $productObj, $dontRemoveArticles = 1) {
		if ($dontRemoveArticles == 1) {
			return $productObj;
		}

		$articleUids = $productObj->getArticleUids();
		$articles = $productObj->getArticleObjects();
		foreach ($articleUids as $arrayKey => $articleUid) {
			/**
			 * Article
			 *
			 * @var Tx_Commerce_Domain_Model_Article $article
			 */
			$article = $articles[$articleUid];
			if ($article->getStock() <= 0) {
				$productObj->removeArticleUid($arrayKey);
				$productObj->removeArticle($articleUid);
			}
		}

		return $productObj;
	}

	/**
	 * Generates a session key for identifiing session contents and matching to user
	 *
	 * @param string $key Key
	 *
	 * @return string Encoded Key as mixture of key and FE-User Uid
	 */
	public static function generateSessionKey($key) {
		if ((int) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['userSessionMd5Encrypt']) {
			$sessionKey = md5($key . ':' . $GLOBALS['TSFE']->fe_user->user['uid']);
		} else {
			$sessionKey = $key . ':' . $GLOBALS['TSFE']->fe_user->user['uid'];
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
	 * Example for $mailconf
	 *
	 * $mailconf = array(
	 * 	'plain' => Array (
	 * 		'content'=> ''				// plain content as string
	 * 	),
	 * 	'html' => Array (
	 * 		'content'=> '',				// html content as string
	 * 		'path' => '',
	 * 		'useHtml' => ''				// is set mail is send as multipart
	 * 	),
	 * 	'defaultCharset' => 'utf-8',	// your chartset
	 * 	'encoding' => '8-bit',			// your encoding
	 * 	'attach' => Array (),			// your attachment as array
	 * 	'alternateSubject' => '',		// is subject empty will be ste alternateSubject
	 * 	'recipient' => '', 				// comma seperate list of recipient
	 * 	'recipient_copy' => '',			// bcc
	 * 	'fromEmail' => '', 				// fromMail
	 * 	'fromName' => '',				// fromName
	 * 	'replyTo' => '', 				// replyTo
	 * 	'priority' => '3', 				// priority of your Mail
	 * 		1 = highest,
	 * 		5 = lowest,
	 * 		3 = normal
	 * );
	 *
	 * @param array $mailconf Configuration for the mailerengine
	 *
	 * @return bool
	 */
	public static function sendMail(array $mailconf) {
		$hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Utility/GeneralUtility', 'sendMail');

		$additionalData = array();
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
			} elseif ( $mailconf['plain']['subject']) {
				$subject = $mailconf['plain']['subject'];
			} else {
				$subject = $mailconf['alternateSubject'];
			}

			/**
			 * Mail message
			 *
			 * @var \TYPO3\CMS\Core\Mail\MailMessage $message
			 */
			$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
			$message->setCharset($mailconf['defaultCharset']);

			if ($mailconf['encoding'] == 'base64') {
				$message->setEncoder(Swift_Encoding::getBase64Encoding());
			} elseif ($mailconf['encoding'] == '8bit') {
				$message->setEncoder(Swift_Encoding::get8BitEncoding());
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
				\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $mailconf['replyTo'] ? '' : $mailconf['fromName'])
			);
			$message->setReplyTo($replyAddress, $replyName);

			if (isset($mailconf['recipient_copy']) && $mailconf['recipient_copy'] != '') {
				if ($mailconf['recipient_copy'] != '') {
					$message->setCc($mailconf['recipient_copy']);
				}
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
						$message->attach(Swift_Attachment::fromPath($file));
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

		return FALSE;
	}

	/**
	 * Helperfunction for email validation
	 *
	 * @param string $list Comma seperierte list of email addresses
	 *
	 * @return string
	 */
	public static function validEmailList($list) {
		$dataArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $list);

		$returnArray = array();
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
	 * Sanitize string to have character and numbers only
	 *
	 * @param string $value Value
	 *
	 * @return string
	 */
	public static function sanitizeAlphaNum($value) {
		preg_match('@[ a-z0-9].*@i', $value, $matches);
		return $matches[0];
	}


	/**
	 * Get typoscript frontend controller
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected static function getFrontendController() {
		return $GLOBALS['TSFE'];
	}
}
