<?php
namespace CommerceTeam\Commerce\Hook;
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

use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * Hook for article_class
 * This class is ment as programming-tutorial
 * for programming hooks for delivery_costs
 *
 * Class \CommerceTeam\Commerce\Hook\ArticleHooks
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class ArticleHooks {
	/**
	 * Basic Method to calculate the delivereycost (net)
	 * Ment as Programming tutorial. Mostly you have to change or add functionality
	 *
	 * @param int $netPrice Net price
	 * @param \CommerceTeam\Commerce\Domain\Model\Article $article Article
	 *
	 * @return void
	 */
	public function calculateDeliveryCostNet(&$netPrice, \CommerceTeam\Commerce\Domain\Model\Article &$article) {
		$deliveryArticle = $this->getDeliveryArticle($article);
		if ($deliveryArticle) {
			$netPrice = $deliveryArticle->getPriceNet();
		} else {
			$netPrice = 0;
		}
	}

	/**
	 * Basic Method to calculate the delivereycost (gross)
	 * Ment as Programming tutorial. Mostly you have to change or add functionality
	 *
	 * @param int $grossPrice Gross price
	 * @param \CommerceTeam\Commerce\Domain\Model\Article $article Article
	 *
	 * @return void
	 */
	public function calculateDeliveryCostGross(&$grossPrice, \CommerceTeam\Commerce\Domain\Model\Article &$article) {
		$deliveryArticle = $this->getDeliveryArticle($article);
		if ($deliveryArticle) {
			$grossPrice = $deliveryArticle->getPriceGross();
		} else {
			$grossPrice = 0;
		}
	}

	/**
	 * Load the deliveryArticle
	 *
	 * @param \CommerceTeam\Commerce\Domain\Model\Article $article Article
	 *
	 * @return \CommerceTeam\Commerce\Domain\Model\Article $result
	 */
	protected function getDeliveryArticle(\CommerceTeam\Commerce\Domain\Model\Article &$article) {
		$deliveryConf = SettingsFactory::getInstance()->getConfiguration('SYSPRODUCTS.DELIVERY.types');
		$classname = array_shift(array_keys($deliveryConf));

		/**
		 * Article repository
		 *
		 * @var ArticleRepository $articleRepository
		 */
		$articleRepository = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Repository\\ArticleRepository');
		$row = $articleRepository->findByClassname($classname);

		$result = FALSE;
		if (!empty($row)) {
			/**
			 * Instantiate article class
			 *
			 * @var \CommerceTeam\Commerce\Domain\Model\Article $deliveryArticle
			 */
			$deliveryArticle = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'CommerceTeam\\Commerce\\Domain\\Model\\Article',
				$row['uid'],
				$article->getLang()
			);

			/**
			 * Do not call loadData at this point, since loadData recalls this hook,
			 * so we have a non endingrecursion
			 */
			if (is_object($deliveryArticle)) {
				$deliveryArticle->loadPrices();
			}
			$result = $deliveryArticle;
		}

		return $result;
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
