<?php

/**
 * Credit Card Validation Solution, PHP Edition,
 * English language error messages.
 *
 * @package    CreditCardValidationSolution
 * @author     Daniel Convissor <danielc@analysisandsolutions.com>
 * @copyright  The Analysis and Solutions Company, 2002-2006
 * @version    $Name: rel-5-14 $ $Id: ccvs_en.inc,v 1.3 2006-03-18 17:31:31 danielc Exp $
 * @link       http://www.analysisandsolutions.com/software/ccvs/ccvs.htm
 */

/** */
$CCVSErrNumberString = 'Number isn\'t a string';
$CCVSErrVisa14       = 'Visa usually has 16 or 13 digits, but you entered 14';
$CCVSErrUnknown      = 'First four digits, %s, indicate unknown card type';
$CCVSErrAccepted     = 'Programmer improperly used the Accepted argument';
$CCVSErrNoAccept     = 'We don\'t accept %s cards';
$CCVSErrShort        = 'Number is missing %s digit(s)';
$CCVSErrLong         = 'Number has %s too many digit(s)';
$CCVSErrChecksum     = 'Number failed checksum test';
$CCVSErrMonthString  = 'Month isn\'t a string';
$CCVSErrMonthFormat  = 'Month has invalid format';
$CCVSErrYearString   = 'Year isn\'t a string';
$CCVSErrYearFormat   = 'Year has invalid format';
$CCVSErrExpired      = 'Card has expired';


// Code to override the translation handling. By this the validateCreditCard
// method needs to be called always with language 'en'.
// Readd this on every update of the library ccvs-php.
/** @var \CommerceTeam\Commerce\Payment\Ccvs $object */
$object = $this;
foreach ($GLOBALS['LOCAL_LANG']['default'] as $languageKey => $_) {
    if (substr($languageKey, 0, 3) === 'Err') {
        ${'CCVS' . $languageKey} = $object->language->getLL($languageKey);
    }
}
