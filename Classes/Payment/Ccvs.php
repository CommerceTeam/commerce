<?php
namespace CommerceTeam\Commerce\Payment;

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

require_once dirname(__FILE__) . '/../../Resources/PHP/ccvs-php/ccvs.inc';

/**
 * Class \CommerceTeam\Commerce\Payment\Ccvs
 */
class Ccvs extends \CreditCardValidationSolution
{
    /**
     * @var string
     */
    public $CCVSCheckNumber = '';

    /**
     * Language service.
     *
     * @var \TYPO3\CMS\Lang\LanguageService
     */
    public $language;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->language = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Lang\LanguageService::class);
        if (is_object($this->getBackendUser())) {
            $languageKey = $this->getBackendUser()->uc['lang'];
        } else {
            $languageKey = $this->getFrontendController()->config['config']['language'];
        }
        $this->language->init($languageKey);
        $this->language->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_ccsv.xlf');
    }

    /**
     * Ensures credit card information is keyed in correctly.
     *
     * @param   string   $Number      the number of the credit card to
     *                                  validate.
     * @param   string   $CheckNumber CVS Number
     * @param   array    $Accepted    credit card types you accept.  If
     *                                  not used in function call, all
     *                                  known cards are accepted.  Set
     *                                  it before calling the function: <br /><kbd>
     *                                  $A = array('Visa', 'JCB');
     *                                  </kbd><br />
     *                                       Known types:        <ul>
     *                                  <li> American Express    </li>
     *                                  <li> Australian BankCard </li>
     *                                  <li> Carte Blanche       </li>
     *                                  <li> Diners Club         </li>
     *                                  <li> Discover/Novus      </li>
     *                                  <li> JCB                 </li>
     *                                  <li> MasterCard          </li>
     *                                  <li> Visa                </li></ul>
     * @param   string   $RequireExp  should the expiration date be
     *                                  checked?  Y or N.
     * @param   integer  $Month       the card's expiration month
     *                                  in M, 0M or MM foramt.
     * @param   integer  $Year        the card's expiration year in YYYY format.
     * @return  boolean  TRUE if everything is fine.  FALSE if problems.
     *
     * @version    $Name: rel-5-14 $
     * @author     Daniel Convissor <danielc@analysisandsolutions.com>
     * @copyright  The Analysis and Solutions Company, 2002-2006
     * @link       http://www.analysisandsolutions.com/software/ccvs/ccvs.htm
     * @link       http://www.loc.gov/standards/iso639-2/langcodes.html
     * @link       http://www.analysisandsolutions.com/donate/
     * @license    http://www.analysisandsolutions.com/software/license.htm Simple Public License
     */
    public function validateCreditCard(
        $Number,
        $CheckNumber,
        $Accepted = [],
        $RequireExp = 'N',
        $Month = 0,
        $Year = 0
    ) {
        $this->CCVSCheckNumber = trim($CheckNumber);

        $result = parent::validateCreditCard($Number, 'en', $Accepted, $RequireExp, $Month, $Year);

        /* Check CheckNumber. */
        if (!empty($this->CCVSType)) {
            switch ($this->CCVSType) {
                case 'American Express':
                    if (strlen($this->CCVSCheckNumber) != 4) {
                        $this->CCVSError = sprintf($this->language->getLL('ErrCheckNumber'), $this->CCVSCheckNumber);
                        return false;
                    }
                    break;

                case 'MasterCard':
                    if (strlen($this->CCVSCheckNumber) != 3) {
                        $this->CCVSError = sprintf($this->language->getLL('ErrCheckNumber'), $this->CCVSCheckNumber);
                        return false;
                    }
                    break;

                case 'Visa':
                    if (strlen($this->CCVSCheckNumber) != 3) {
                        $this->CCVSError = sprintf($this->language->getLL('ErrCheckNumber'), $this->CCVSCheckNumber);
                        return false;
                    }
                    break;
            }
        }

        return $result;
    }


    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get typoscript frontend controller.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
