<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Misc COMMERCE functions
 */
class Tx_Commerce_Utility_GeneralUtility {
	/**
	 * Removes XSS code and strips tags from an array recursivly
	 * @Author Ingo Schmitt <is@marketing-factory.de>
	 * @param  string $input Array of elements or other
	 * @return boolean|array is an array, otherwhise false
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
			return (string) t3lib_div::removeXSS(strip_tags($input));
		}
		if (is_array($input)) {
			$returnValue = array();
			foreach ($input as $key => $value) {
				if (is_array($value)) {
					$returnValue[$key] = self::removeXSSStripTagsArray($value);
				} else {
					$returnValue[$key] = t3lib_div::removeXSS(strip_tags($value));
				}
			}
			return $returnValue;
		}
		return FALSE;
	}

	/**
	 * Formates a price for the designated output
	 *
	 * @param 	float	$price
	 * @return	string	formated Price
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getAttributes instead
	 */
	public static function formatPrice($price) {
		t3lib_div::logDeprecatedFunction();
		return sprintf('%01.2f', $price);
	}

	/**
	 * This method initilize the basket for the fe_user from
	 * Session. If the basket is already initialized nothing happend
	 * at this point.
	 *
	 * @return void
	 */
	public static function initializeFeUserBasket() {
		if (!is_object($GLOBALS['TSFE']->fe_user->tx_commerce_basket)) {
		$BasketID = $GLOBALS['TSFE']->fe_user->getKey('ses', 'commerceBasketId');

		if (empty($BasketID)) {
				$BasketID = md5($GLOBALS['TSFE']->fe_user->id . ':' . rand(0, PHP_INT_MAX));
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'commerceBasketId', $BasketID);
		}

		$GLOBALS['TSFE']->fe_user->tx_commerce_basket = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Basket');
		$GLOBALS['TSFE']->fe_user->tx_commerce_basket->set_session_id($BasketID);
		$GLOBALS['TSFE']->fe_user->tx_commerce_basket->loadData();
	}
	}

	/***
	 * Remove Products from list wich have no articles wich are available from Stock
	 *
	 * @param array $productUids List of productUIDs to work onn
	 * @param integer $dontRemoveProducts integer    switch to show or not show articles
	 * @return array Cleaned up Productarrayt
	 */
	public static function removeNoStockProducts($productUids = array(),$dontRemoveProducts = 1) {
		if ($dontRemoveProducts == 1) {
			return $productUids;
		}

		foreach ( $productUids as $arrayKey => $productUid ) {
			/** @var Tx_Commerce_Domain_Model_Product $productObj */
			$productObj = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Product');
			$productObj->init($productUid);
			$productObj->loadData();

			if (!($productObj->hasStock())) {
				unset($productUids[$arrayKey]);
			}
			$productObj = NULL;
		}

		return $productUids;
	}

	/***
	 * Remove article from product for frontendviewing, if articles
	 * with no stock should not shown
	 *
	 * @param Tx_Commerce_Domain_Model_Product $productObj ProductObject to work on
	 * @param integer $dontRemoveArticles switch to show or not show articles
	 * @return Tx_Commerce_Domain_Model_Product Cleaned up Productobjectt
	 */
	public static function removeNoStockArticles( $productObj, $dontRemoveArticles = 1 ) {
		if ($dontRemoveArticles == 1) {
			return $productObj;
		}

		$articleUids = $productObj->getArticleUids();
		$articles = $productObj->getArticleObjects();
		foreach ($articleUids as $arrayKey => $articleUid) {
			/** @var Tx_Commerce_Domain_Model_Article $article */
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
	* @param string $key
	* @return string Encoded Key as mixture of key and FE-User Uid
	*
	*/
	public static function generateSessionKey($key) {
		if (intval($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['userSessionMd5Encrypt']) == 1) {
			$sessionKey = md5($key . ':' . $GLOBALS['TSFE']->fe_user->user['uid']);
		} else {
			$sessionKey = $key . ':' . $GLOBALS['TSFE']->fe_user->user['uid'];
		}

		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_div.php']['generateSessionKey'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_div.php']['generateSessionKey'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postGenerateSessionKey')) {
				$sessionKey = $hookObj->postGenerateSessionKey($key);
			}
		}

		return $sessionKey;
	}

	/**
	* Invokes the HTML mailing class
	*
	* @author	Tom Rüther <tr@e-netconsulting.de>
	* @since	29th June 2008
	* @param	array  $mailconf configuration for the mailerengine
	* Example for $mailconf
	*
	* $mailconf = array(
	* 	'plain' => Array (
	* 				'content'=> '' 	// plain content as string
	* 				),
	* 	'html' => Array (
	* 		'content'=> '', 			// html content as string
	* 		'path' => '',
	* 		'useHtml' => '' 			// is set mail is send as multipart
	* 	),
	* 	'defaultCharset' => 'utf-8',		// your chartset
	* 	'encoding' => '8-bit',			// your encoding
	* 	'attach' => Array (),			// your attachment as array
	* 	'alternateSubject' => '',			// is subject empty will be ste alternateSubject
	* 	'recipient' => '', 				// comma seperate list of recipient
	* 	'recipient_copy' =>  '',			// bcc
	* 	'fromEmail' => '', 				// fromMail
	* 	'fromName' => '',				// fromName
	* 	'replyTo' => '', 				// replyTo
	* 	'priority' => '3', 				// priority of your Mail - 1 = highest, 5 = lowest, 3 = normal
	* 	'callLocation' => 'myFunction' 		// Where call the function it is nescesary when you will use hooks?
	* );
	*
	* @return boolean
	*/
	public static function sendMail($mailconf) {
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_div.php']['sendMail'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_div.php']['sendMail'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

		$additionalData = array();
		if ($mailconf['additionalData']) {
			$additionalData = $mailconf['additionalData'];
		}

		foreach ($hookObjectsArr as $hookObj) {
			/**
			 * this is the current hook
			 */
			if (method_exists($hookObj, 'preProcessMail')) {
				$hookObj->preProcessMail($mailconf, $additionalData);
			}
		}

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'ownMailRendering')) {
				return $hookObj->ownMailRendering($mailconf, $additionalData, $hookObjectsArr);
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

			/** @var t3lib_mail_Message $message */
			$message = t3lib_div::makeInstance('t3lib_mail_Message');
			$message->setCharset($mailconf['defaultCharset']);

			if ($mailconf['encoding'] == 'base64') {
				$message->setEncoder(Swift_Encoding::getBase64Encoding());
			} elseif ($mailconf['encoding'] == '8bit') {
				$message->setEncoder(Swift_Encoding::get8BitEncoding());
			}

				// $htmlMail->mailer = 'TYPO3 Mailer :: commerce';
			$message->setSubject($subject);
			$message->setTo($mailconf['recipient']);
			$message->setFrom(
				self::validEmailList($mailconf['fromEmail']),
				implode(' ', t3lib_div::trimExplode(',', $mailconf['fromName']))
			);
			$message->setReplyTo(
				$mailconf['replyTo'] ? $mailconf['replyTo'] :$mailconf['fromEmail'],
				implode(' ', t3lib_div::trimExplode(',', $mailconf['replyTo'] ? '' : $mailconf['fromName']))
			);

			if (isset($mailconf['recipient_copy']) && $mailconf['recipient_copy'] != '') {
				if ($mailconf['recipient_copy'] != '') {
					$message->setCc($mailconf['recipient_copy']);
				}
			}

			$message->setReturnPath($mailconf['fromEmail']);
				// $htmlMail->organisation = $mailconf['formName'];
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

			foreach ($hookObjectsArr as $hookObj) {
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
	* @author	Tom Rüther <tr@e-netconsulting.de>
	* @param	array	$list comma seperierte list of email addresses
	* @return	string
	*/
	public static function validEmailList($list) {
		$dataArray = t3lib_div::trimExplode(',', $list);

		$returnArray = array();
		foreach ($dataArray as $data) {
			if (t3lib_div::validEmail($data)) {
				$returnArray[] = $data;
			}
		}

		$newList = '';
		if (is_array($returnArray)) {
			$newList = implode(',', $returnArray);
	}

		return $newList;
	}
}

class_alias('Tx_Commerce_Utility_GeneralUtility', 'tx_commerce_div');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_div.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_div.php']);
}

?>