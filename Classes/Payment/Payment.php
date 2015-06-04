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
 * Paymentklasse zum anbinden von Shops usw. an Zahlungssysteme
 * Die Paymentklasse erbt hierbei von der eigentlichen Schnittstelle zum
 * Paymentanbieter und wird via Vererbung mit den notwendigen Daten versorgt
 * Die Grundfunktionen der Paymentklasse sind wietestgehen Statusmeldungen und
 * Errohandling.
 * getPaymetmethods   - Liste der Methoden und Typen die die Schnittstelle bietet
 * getStatus                - allgemeine Daten der Klasse
 * getError                - gab es Fehler wen ja welche meldung
 * checkTransactiondata    - wurden alle daten komplett in der Klasse belegt?
 * Elemente der Parentklasse die in payment definiert und ggf. vorbelegt werden
 * setPaymentmethod        - ELV, Bank, KK (ggf Intern)
 * setPaymenttype        - reserve, book  (ggf Intern)
 * setData - Speichert KK Nummer, Betrag, Name usw.
 *  (vielleicht splitte ich das noch auf z.B. setKKnumber usw)
 * prepareMethod   - bereitet die Transaktion vor - erstellt die Parameterliste
 * ggf. auch prepareMethod->KK oder >ELV Muss ich nochmal drüber nachdenken ich
 *         denke aber das wäre kein schlechter weg, sonst als array übergeben
 * sendTransaction        - sendet zur Schnittstelle
 * getErrorOfErrorcode    - Gibt den Fehlertext zur�ck
 * getErrortype            - Warning, schwer, unbekannt, usw.
 *
 * Class Tx_Commerce_Payment_Payment
 *
 * @author 2005-2008 Marco Klawonn <info@webprog.de>
 */
class Tx_Commerce_Payment_Payment extends Tx_Commerce_Payment_Wirecard {
	/**
	 * @var array
	 */
	public $error;

	/**
	 * @var string
	 */
	public $status;

	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
		$this->setReferenzID();
	}

	/**
	 * function: getPaymetmethods
	 * delivers a list of payment types that are provided by the parent class
	 *
	 * @return array
	 */
	public function getPaymetmethods() {
		return array();
	}

	/**
	 * function: getStatus
	 * liefert den Status der Klasse sowie grunddaten welche Schnittstelle benutzt wird
	 *
	 * @return array
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * function: getError
	 * liefert den Errorcode der Paymentklasse
	 *
	 * @return integer
	 */
	public function getError() {
		return $this->error[$this->referenzID];
	}

	/**
	 * @param string $url
	 * @return void
	 */
	public function setUrl($url) {
		$this->url = $url;
	}

	/**
	 * function: setData
	 * Setzt die �bertragunsparameter
	 *
	 * @param array $data
	 * @return void
	 */
	public function setData($data) {
			// Die Benutzerdaten in einem Assoziativen Array �bergeben
			// folgende Benutzerdaten werdem vom System allgemein beachtet:
			// - firstname
			// - lastname
			// - street
			// - zip
			// - city
			// - country
			// KK Spezifisch
		$this->userData = $data;
	}

	/**
	 * function: setPaymentData
	 * Set payment data for transfer
	 *
	 * @param array $data
	 * @return void
	 */
	public function setPaymentData($data) {
			// Die Benutzerdaten in einem Assoziativen Array �bergeben
			// folgende Benutzerdaten werdem vom System allgemein beachtet:
			// Betrifft Kreditkarten
			// - kknumber
			// - exp_month
			// - exp_year
			// - holder
			// - city
			// - country
		$this->paymentData = $data;
	}

	/**
	* function: setTransactionData
	* set the data for payment
	*
	* @param array $data
	* @return void
	*/
	public function setTransactionData($data) {
			// Die Benutzerdaten in einem Assoziativen Array �bergeben
			// folgende Benutzerdaten werdem vom System allgemein beachtet:
			// - amount
			// - currency
		$this->transactionData = $data;
	}

	/**
	 * @param string $method
	 * @return void
	 */
	public function setPaymentmethod($method) {
		$this->paymentmethod = $method;
	}

	/**
	 * @param object $type
	 */
	public function setPaymenttype($type) {
		$this->paymenttype = $type;
	}

	/**
	 * Intern - function: setReferenzID
	 * Setzt eine referenz ID f�r die Tranksaktion
	 *
	 * @param string $referenceId
	 * @return void
	 */
	public function setReferenzID($referenceId = '') {
		if ($referenceId == '') {
			$referenceId = 'ref_' . time();
		}
		$this->referenzID = $referenceId;
	}
}
