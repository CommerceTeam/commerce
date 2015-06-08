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
 * Schnittstelle zu wirecard
 * setPaymentmethod        - ELV, Bank, KK (ggf Intern)
 * setPaymenttype        - reserve, book  (ggf Intern)
 * setData                - Speichert KK Nummer, Betrag,
 * 	Name usw. (vielleicht splitte ich das noch
 *                          auf z.B. setKKnumber usw)
 * prepareMethod        - bereitet die Transaktion vor
 * 	- erstellt die Parameterliste
 *                      ggf. auch prepareMethod->KK oder
 * 	->ELV Muss ich nochmal drüber nachdenken ich
 *                      denke aber das wäre kein schlechter
 * 	weg, sonst als array übergeben
 * sendTransaction        - sendet zur Schnittstelle
 * getErrorOfErrorcode    - Gibt den Fehlertext zur�ck
 * getErrortype            - Warning, schwer, unbekannt, usw.
 *
 * Class Tx_Commerce_Payment_Wirecard
 *
 * @author 2005-2011 Marco Klawonn <info@webprog.de>
 */
class Tx_Commerce_Payment_Wirecard {
	/**
	 * Don't put this in a public readable place
	 *
	 * @var string
	 */
	protected $merchantCode = '56500';

	/**
	 * Don't put this in a public readable place
	 *
	 * @var string
	 */
	protected $password = 'TestXAPTER';

	/**
	 * Don't put this in a public readable place
	 *
	 * @var string
	 */
	protected $businesscasesignature = '56500';

	/**
	 * @var string
	 */
	public $referenzID;

	/**
	 * @var string
	 */
	protected $orderCode = '';

	/**
	 * it is better to keep this url outside your HTML dir which has public (internet) access
	 *
	 * @var string
	 */
	protected $url = 'https://frontend-test.wirecard.com/secure/ssl-gateway';

	/**
	 * @var array
	 */
	public $error;

	/**
	 * @var string
	 */
	public $paymentmethod;

	/**
	 * @var string
	 */
	public $paymenttype;

	/**
	 * @var array
	 */
	public $paymentData = array();

	/**
	 * @var array
	 */
	public $transactionData = array();

	/**
	 * @var array
	 */
	public $userData = array();

	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
			// Ordercode immer neu setzen
		$this->orderCode = $this->referenzID;

			// daten die versendet werden
		$this->sendData = '';
	}

	/**
	 * Pflichtfunktion - function: checkTransactiondata
	 * kontrolliert welche Daten f�r wirecard und den gew�hlten Paymenttyp wichtig sind
	 * gibt zur�ck ob alle daten ok sind oder einen Array mit den Daten die Fehlen
	 */

	/**
	 * @return NULL
	 */
	public function checkTransactiondata() {
		print_r($this->userData);

		return NULL;
	}

	/**
	 * @return NULL
	 */
	public function prepareMethod() {
		return NULL;
	}

	/**
	* Pflichtfunktion - function: sendTransaction
	* Sendet die Daten - bei Wirecard mit einem Post auf Curl SSL
	* Das Ergebniss steht im result und muss in das Error/Status Array geschrieben werden.
	*
	* @return bool
	*/
	public function sendTransaction() {
		$header = array(
			'Authorization: Basic ' . base64_encode($this->merchantCode . ':' . $this->password . LF),
			'Content-Type: text/xml'
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
			return FALSE;
		} else {
			return array(1);
		}
	}

	/**
	 * @return NULL
	 */
	public function getErrorOfErrorcode() {
		return NULL;
	}

	/**
	 * @return NULL
	 */
	public function getErrortype() {
		return NULL;
	}

	/**
	 * Interne Funktionen
	 * Diese Funktionen sind private und werden nie von aussen aufgerufen
	 * K�nnen deshalb auch frei mit den anderen Klassenfunktionen reden
	 */

	/**
	 * @return int
	 */
	public function isError() {
		if (is_array($this->error)) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Intern - function: parseResult
	 * Parst das ergebnis und schreibt die Werte in das Status/Error Array
	 *
	 * @param string $result
	 *
	 * @return int
	 */
	public function parseResult($result) {
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
					while ($v2 = current(array_slice(each($v), 1, 1))) {
						if ($vals[$v2 + 1]['tag'] <> 'ERROR') {
							$this->error[$this->referenzID][$vals[$v2 + 1]['tag']] = $vals[$v2 + 1]['value'];
						}
					}
				break;
			}
		};

		return 1;
	}

	/**
	 * Intern - function: parseResult
	 * Erzeugt ein XML das an die Bibit Schnittstelle gesendet wird
	 *
	 * @return string
	 */
	public function getwirecardXML() {
		$xml = "
			<?xml version='1.0' encoding='UTF-8'?>
			<WIRECARD_BXML xmlns:xsi='http://www.w3.org/1999/XMLSchema-instance' xsi:noNamespaceSchemaLocation='wirecard.xsd'>
				<W_REQUEST>
					<W_JOB>
						<JobID>" . $this->orderCode . '</JobID>
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
	 * Intern - function: getPaymentMask
	 * XML f�r die Payment Daten
	 * Unterfunktion von getwirecardXML
	 *
	 * @return string
	 */
	public function getPaymentMask() {
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
	 * Intern - function: getCountryCode
	 * Gibt einen Country Code zur�ck
	 *
	 * @param string $country
	 * @return string
	 */
	public function getCountryCode($country) {
		return 'DEU';
	}
}
