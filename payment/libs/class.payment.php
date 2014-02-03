<?php

/**
 * Class payment
 * Parentklasse einbinden
 * Diese Klasse ist die eigentliche Schnittstelle die benutzt werden soll
 */
class payment extends wirecard {
	public $referenzID;

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
	 */
	public function payment() {
		$this->referenzID = $this->setReferenzID();
	}

	/**
	* function: getPaymetmethods
	* liefert eine Liste von Zahlungsm�glichkeiten die von der Parentklasse zur
	* Verf�gunggestellt werden
	*
	* @param
	* @return array
	* @since 0.1 - 2005/04/11
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de>
	*/
	public function getPaymetmethods() {
        return array();
    }

	/**
	* function: getStatus
	* liefert den Status der Klasse sowie grunddaten welche Schnittstelle benutzt wird
	*
	* @param
	* @return array
	* @since 0.1 - 2005/04/11
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de>
	*/
	public function getStatus() {
        return $this->status;
    }

	/**
	* function: isError
	* liefert 1 bei einem Fehler
	*
	* @param
	* @return integer
	* @since 0.1 - 2005/04/11
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de>
	*/
	public function isError() {
		if (is_array($this->error)) {
			return 1;
		} else {
			return 0;
    }
	}

	/**
	* function: getError
	* liefert den Errorcode der Paymentklasse
	*
	* @param
	* @return integer
	* @since 0.1 - 2005/04/11
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de>
	*/
	public function getError() {
        return $this->error[$this->referenzID];
    }

	/**
	* function: setData
	* Setzt die �bertragunsparameter
	*
	* @param
	* @return bool
	* @since 0.1 - 2005/04/11
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de>
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
	* Setzt die �bertragunsparameter - Zahlungsdaten
	*
	* @param
	* @return bool
	* @since 0.1 - 2005/04/11
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de>
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
		$this->PaymentData = $data;
    }

	/**
	* function: setTransactionData
	* Setzt die Daten f�r eine bezahlung
	*
	* @param
	* @return bool
	* @since 0.1 - 2005/04/11
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de>
	*/
	public function setTransactionData($data) {
		// Die Benutzerdaten in einem Assoziativen Array �bergeben
		// folgende Benutzerdaten werdem vom System allgemein beachtet:
		// - amount
		// - currency
		$this->TransactionData = $data;
    }

	public function setPaymentmethod($method) {
        $this->paymentmethod = $method;

		return TRUE;
    }

	public function setPaymenttype($type) {
        $this->paymenttype = $type;
    }

	/**
	* Intern - function: setReferenzID
	* Setzt eine referenz ID f�r die Tranksaktion
	*
	 * @param string $id
	 * @return string
	* @since 0.1 - 2005/04/13
	*/
	public function setReferenzID($id = '') {
		$id = 'ref_' . time();
			return $id;
	}
}

?>