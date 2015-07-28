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
 * Class \CommerceTeam\Commerce\Payment\Payment
 *
 * @author 2005-2008 Marco Klawonn <info@webprog.de>
 */
class Payment extends Wirecard
{
    /**
     * Error.
     *
     * @var array
     */
    public $error;

    /**
     * Status.
     *
     * @var string
     */
    public $status;

    /**
     * Constructor.
     *
     * @return self
     */
    public function __construct()
    {
        parent::__construct();
        $this->setReferenzID();
    }

    /**
     * Get Payment methods
     * delivers a list of payment types that are provided by the parent class.
     *
     * @return array
     */
    public function getPaymetmethods()
    {
        return array();
    }

    /**
     * Get status.
     *
     * @return array
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get error.
     *
     * @return int
     */
    public function getError()
    {
        return $this->error[$this->referenzID];
    }

    /**
     * Set url.
     *
     * @param string $url Url
     *
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Set data.
     *
     * @param array $data Data
     *
     * @return void
     */
    public function setData(array $data)
    {
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
     * Set payment data for transfer.
     *
     * @param array $data Data
     *
     * @return void
     */
    public function setPaymentData(array $data)
    {
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
     * Set the data for the transaction.
     *
     * @param array $data Data
     *
     * @return void
     */
    public function setTransactionData(array $data)
    {
        // Die Benutzerdaten in einem Assoziativen Array �bergeben
        // folgende Benutzerdaten werdem vom System allgemein beachtet:
        // - amount
        // - currency
        $this->transactionData = $data;
    }

    /**
     * Set payment method.
     *
     * @param string $method Method
     *
     * @return void
     */
    public function setPaymentmethod($method)
    {
        $this->paymentmethod = $method;
    }

    /**
     * Set payment type.
     *
     * @param object $type Type
     *
     * @return void
     */
    public function setPaymenttype($type)
    {
        $this->paymenttype = $type;
    }

    /**
     * Set reference id.
     *
     * @param string $referenceId Reference id
     *
     * @return void
     */
    public function setReferenzId($referenceId = '')
    {
        if ($referenceId == '') {
            $referenceId = 'ref_' . $GLOBALS['EXEC_TIME'];
        }
        $this->referenzID = $referenceId;
    }
}
