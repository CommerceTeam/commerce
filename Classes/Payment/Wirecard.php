<?php
namespace CommerceTeam\Commerce\Payment;

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
 * Schnittstelle zu wirecard
 * setPaymentmethod     - ELV, Bank, KK (ggf Intern)
 * setPaymenttype       - reserve, book  (ggf Intern)
 * setData              - Speichert KK Nummer, Betrag,
 * Name usw. (vielleicht splitte ich das noch
 *                          auf z.B. setKKnumber usw)
 * prepareMethod        - bereitet die Transaktion vor
 *                      - erstellt die Parameterliste
 *                        ggf. auch prepareMethod->KK oder
 *                      - ELV Muss ich nochmal drüber nachdenken ich
 *                        denke aber das wäre kein schlechter
 *                        weg, sonst als array übergeben
 * sendTransaction      - sendet zur Schnittstelle
 * getErrorOfErrorcode  - Gibt den Fehlertext zur�ck
 * getErrortype         - Warning, schwer, unbekannt, usw.
 *
 * Class \CommerceTeam\Commerce\Payment\Wirecard
 *
 * @author 2005-2011 Marco Klawonn <info@webprog.de>
 */
class Wirecard
{
    /**
     * Don't put this in a public readable place.
     *
     * @var string
     */
    protected $merchantCode = '56500';

    /**
     * Don't put this in a public readable place.
     *
     * @var string
     */
    protected $password = 'TestXAPTER';

    /**
     * Don't put this in a public readable place.
     *
     * @var string
     */
    protected $businesscasesignature = '56500';

    /**
     * Reference id.
     *
     * @var string
     */
    public $referenzID;

    /**
     * Order code.
     *
     * @var string
     */
    protected $orderCode = '';

    /**
     * It is better to keep this url outside your
     * HTML dir which has public (internet) access.
     *
     * @var string
     */
    protected $url = 'https://frontend-test.wirecard.com/secure/ssl-gateway';

    /**
     * Error.
     *
     * @var array
     */
    public $error;

    /**
     * Payment method.
     *
     * @var string
     */
    public $paymentmethod;

    /**
     * Payment type.
     *
     * @var string
     */
    public $paymenttype;

    /**
     * Payment data.
     *
     * @var array
     */
    public $paymentData = array();

    /**
     * Transaction data.
     *
     * @var array
     */
    public $transactionData = array();

    /**
     * User data.
     *
     * @var array
     */
    public $userData = array();

    /**
     * Constructor.
     *
     * @return self
     */
    public function __construct()
    {
        // Ordercode immer neu setzen
        $this->orderCode = $this->referenzID;

        // daten die versendet werden
        $this->sendData = '';
    }

    /*
     * Required function - checkTransactiondata
     *
     * checks which data are required for wirecard and the choosen payment
     */

    /**
     * Check transaction data.
     *
     * @return NULL
     */
    public function checkTransactiondata()
    {
        print_r($this->userData);

        return null;
    }

    /**
     * Prepare method.
     *
     * @return NULL
     */
    public function prepareMethod()
    {
        return null;
    }

    /**
     * Send transaction.
     *
     * @return bool
     */
    public function sendTransaction()
    {
        $header = array(
            'Authorization: Basic ' . base64_encode($this->merchantCode . ':' . $this->password . LF),
            'Content-Type: text/xml',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->sendData);

        if ($header != '') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        ob_start();
        $result = curl_exec($ch);
        ob_end_clean();

        curl_close($ch);

            // das von wirecard zur�ckgelieferte XML Parsen
        $this->parseResult($result);

            // return 1 = alles ok return = 0 es gab errors
        if ($this->isError()) {
            return false;
        } else {
            return array(1);
        }
    }

    /**
     * Get error.
     *
     * @return NULL
     */
    public function getErrorOfErrorcode()
    {
        return null;
    }

    /**
     * Get error type.
     *
     * @return NULL
     */
    public function getErrortype()
    {
        return null;
    }

    /*
     * Internal functions
     */

    /**
     * Is error.
     *
     * @return int
     */
    protected function isError()
    {
        return (int) is_array($this->error);
    }

    /**
     * Parse result.
     *
     * @param string $result Result
     *
     * @return int
     */
    protected function parseResult($result)
    {
        if (!$result) {
            $this->error['no_data']['this shouldn\'t be'] = 'No result, so nopayment possible';

            return 1;
        }

        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $result, $vals, $tags);
        xml_parser_free($parser);

        while (list($k, $v) = each($tags)) {
            switch ($k) {
                    // ERROR auswerten und in $this->error schreiben
                    // -----------------------------------------------------------
                case 'ERROR':
                    while (($v2 = current(array_slice(each($v), 1, 1)))) {
                        if ($vals[$v2 + 1]['tag'] != 'ERROR') {
                            $this->error[$this->referenzID][$vals[$v2 + 1]['tag']] = $vals[$v2 + 1]['value'];
                        }
                    }
                    break;

                default:
            }
        };

        return 1;
    }

    /**
     * Intern - function: parseResult
     * Erzeugt ein XML das an die Bibit Schnittstelle gesendet wird.
     *
     * @return string
     */
    public function getwirecardXML()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
			<WIRECARD_BXML xmlns:xsi="http://www.w3.org/1999/XMLSchema-instance"
			    xsi:noNamespaceSchemaLocation="wirecard.xsd">
				<W_REQUEST>
					<W_JOB>
						<JobID>' . $this->orderCode . '</JobID>
						<BusinessCaseSignature>' . $this->businesscasesignature . '</BusinessCaseSignature>
						<FNC_CC_TRANSACTION>
							<FunctionID>WireCard Test</FunctionID>
							<CC_TRANSACTION>
								<TransactionID>2</TransactionID>
								<Amount>' . $this->transactionData['amount'] . '</Amount>
								<Currency>' . $this->transactionData['currency'] . '</Currency>
								<CountryCode>US</CountryCode>
								<RECURRING_TRANSACTION>
									<Type>Single</Type>
								</RECURRING_TRANSACTION>
								' . $this->getPaymentMask() . '
								<CONTACT_DATA>
									<IPAddress>127.0.0.1</IPAddress>
								</CONTACT_DATA>
								<CORPTRUSTCENTER_DATA>
									<ADDRESS>
										<Address1>' . $this->userData['street'] . '</Address1>
										<City>' . $this->userData['city'] . '</City>
										<ZipCode>' . $this->userData['zip'] . '</ZipCode>
										<State></State>
										<Country>' . $this->userData['country'] . '</Country>
										<Phone>' . $this->userData['telephone'] . '</Phone>
										<Email>' . $this->userData['email'] . '</Email>
									</ADDRESS>
								</CORPTRUSTCENTER_DATA>
							</CC_TRANSACTION>
						 </FNC_CC_TRANSACTION>
					</W_JOB>
				</W_REQUEST>
			</WIRECARD_BXML>';

        return $xml;
    }

    /**
     * Get payment mask.
     *
     * @return string
     */
    protected function getPaymentMask()
    {
        $xml = '';

        if ($this->paymenttype == 'cc') {
            $xml = '
			<CREDIT_CARD_DATA>
				<CreditCardNumber>' . $this->paymentData['kk_number'] . '</CreditCardNumber>
				<CVC2>' . $this->paymentData['cvc'] . '</CVC2>
				<ExpirationYear>' . $this->paymentData['exp_year'] . '</ExpirationYear>
				<ExpirationMonth>' . $this->paymentData['exp_month'] . '</ExpirationMonth>
				<CardHolderName>' . $this->paymentData['holder'] . '</CardHolderName>
			</CREDIT_CARD_DATA>';
        };

        return $xml;
    }

    /**
     * Get country code.
     *
     * @param string $country Country
     *
     * @return string
     */
    protected function getCountryCode($country = 'DEU')
    {
        return $country;
    }
}
