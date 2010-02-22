<?php
/**
* klasse: payment
* 
* Paymentklasse zum anbinden von Shops usw. an Zahlungssysteme
* Die Paymentklasse erbt hierbei von der eigentlichen Schnittstelle zum 
* Paymentanbieter und wird via Vererbung mit den notwendigen Daten versorgt
*
* Die Grundfunktionen der Paymentklasse sind wietestgehen Statusmeldungen und 
* Errohandling.
*
* @since 0.01 - 2005/04/11
*
* @package payment
* @version 0.1
* @author Marco Klawonn <info@webprog.de> 
* 
/////////////////////////////////////////////////////////////////////////////////////
* 
*
* getPaymetmethods		- Liste der Methoden und Typen die die Schnittstelle bietet
* getStatus			    - allgemeine Daten der Klasse
* getError			    - gab es Fehler wen ja welche meldung
* 
* checkTransactiondata	- wurden alle daten komplett in der Klasse belegt?
* 
* Elemente der Parentklasse die in payment definiert und ggf. vorbelegt werden
*
* setPaymentmethod		- ELV, Bank, KK (ggf Intern)
* setPaymenttype		- reserve, book  (ggf Intern)
* setData				- Speichert KK Nummer, Betrag, Name usw. (vielleicht splitte ich das noch 
*  				  		  auf z.B. setKKnumber usw)
* 
* prepareMethod		- bereitet die Transaktion vor - erstellt die Parameterliste
* 					  ggf. auch prepareMethod->KK oder ->ELV Muss ich nochmal dr�ber nachdenken ich
* 					  denke aber das w�re kein schlechter weg, sonst als array �bergeben
* 
* sendTransaction		- sendet zur Schnittstelle
* 
* getErrorOfErrorcode	- Gibt den Fehlertext zur�ck
* getErrortype		    - Warning, schwer, unbekannt, usw.
* 
////////////////////////////////////////////////////////////////////////////////////////
*/

// Parentklasse einbinden
// Diese Klasse ist die eigentliche Schnittstelle die benutzt werden soll
// -------------------------------------------------------------------------------------

class payment extends wirecard
{
	
	var $referenzID;
	
	// Konstruktor
	// ---------------------------------------------------------------------------------
	function payment(){
		
		$this->referenzID = $this->setReferenzID();
	}
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* function: getPaymetmethods
	* 
	* liefert eine Liste von Zahlungsm�glichkeiten die von der Parentklasse zur 
	* Verf�gunggestellt werden
	* 
	* @param  
	* @return array
	* 
	* @since 0.1 - 2005/04/11
	*
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de> 
	*
	*/
	/////////////////////////////////////////////////////////////////////////////////////
    function getPaymetmethods (){
        return array();
    }
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* function: getStatus
	* 
	* liefert den Status der Klasse sowie grunddaten welche Schnittstelle benutzt wird
	* 
	* @param  
	* @return array
	*
	* @since 0.1 - 2005/04/11
	*
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de> 
	*
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	function getStatus (){
        return $this->status;
    }
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* function: isError
	* 
	* liefert 1 bei einem Fehler
	* 
	* @param  
	* @return integer
	*
	* @since 0.1 - 2005/04/11
	*
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de> 
	*
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	function isError (){
		if(is_array($this->error)){
			return 1;
		}else{
			return 0;
		};
    }
		/////////////////////////////////////////////////////////////////////////////////////
	/**
	* function: getError
	* 
	* liefert den Errorcode der Paymentklasse
	* 
	* @param  
	* @return integer
	*
	* @since 0.1 - 2005/04/11
	*
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de> 
	*
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	function getError (){
        return $this->error[$this->referenzID];
    }
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* function: setData
	* 
	* Setzt die �bertragunsparameter
	* 
	* @param  
	* @return bool
	*
	* @since 0.1 - 2005/04/11
	*
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de> 
	*
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	function setData ($data){
        
		// Die Benutzerdaten in einem Assoziativen Array �bergeben
		// folgende Benutzerdaten werdem vom System allgemein beachtet:
		// 
		// - firstname
		// - lastname
		// - street
		// - zip
		// - city
		// - country
		// 
		// KK Spezifisch
		
		$this->userData = $data;
		
    }
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* function: setPaymentData
	* 
	* Setzt die �bertragunsparameter - Zahlungsdaten
	* 
	* @param  
	* @return bool
	*
	* @since 0.1 - 2005/04/11
	*
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de> 
	*
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	function setPaymentData ($data){
        
		// Die Benutzerdaten in einem Assoziativen Array �bergeben
		// folgende Benutzerdaten werdem vom System allgemein beachtet:
		// 
		// Betrifft Kreditkarten
		//   
		// - kknumber
		// - exp_month
		// - exp_year
		// - holder
		// - city
		// - country
		
		$this->PaymentData = $data;
		
    }
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* function: setTransactionData
	* 
	* Setzt die Daten f�r eine bezahlung
	* 
	* @param  
	* @return bool
	*
	* @since 0.1 - 2005/04/11
	*
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de> 
	*
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	function setTransactionData ($data){
        
		// Die Benutzerdaten in einem Assoziativen Array �bergeben
		// folgende Benutzerdaten werdem vom System allgemein beachtet:
		//   
		// - amount
		// - currency
		
		$this->TransactionData = $data;
		
    }
	
	function setPaymentmethod ($method){
        $this->paymentmethod = $method;
		return true;
    }
	
	function setPaymenttype ($type){
        $this->paymenttype = $type;
    }
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* Intern - function: setReferenzID
	* 
	* Setzt eine referenz ID f�r die Tranksaktion
	* 
	* @return bool
	* @since 0.1 - 2005/04/13
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	
	function setReferenzID($id = ""){
			$id = "ref_".time();
			return $id;
	}
	
	////////////////////////////////////////////////////////////////////////////////////
	/**
	* Vererbende Klassen der Schnittstelle werden hier vorbelegt / �berschrieben
	* ----------------------------------------------------------------------------
	* Ab hier werden die wirklichen Schnittstellen aufgerufen und ausgef�hrt.
	* die Methoden in Payment werden hierbei �berschrieben ggf. aber von dieser
	* Klasse vorbelegt.
	*/
	////////////////////////////////////////////////////////////////////////////////////
	
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* function: checkTransactiondata
	* 
	* kontrolliert ob alle Daten vollst�ndig sind und damit zum absenden 
	* an die Schnittstelle geeignet (abh�ngig vom zu testenden Zahlungstyp)
	* 
	* @param  string Welcher Paymenttype
	* @return bool
	*
	* @since 0.1 - 2005/04/11
	*
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de> 
	*
	*/
	/////////////////////////////////////////////////////////////////////////////////////

	// Hier erst dann Funktionen rein, wenn sie auch genutzt werden...	
	
}

/////////////////////////////////////////////////////////////////////////////////////
/**
* klasse: payment_wirdecard
* 
* Schnittstelle zu wirecard 
* 
* setPaymentmethod		- ELV, Bank, KK (ggf Intern)
* setPaymenttype		- reserve, book  (ggf Intern)
* setData				- Speichert KK Nummer, Betrag, Name usw. (vielleicht splitte ich das noch 
* 				  	      auf z.B. setKKnumber usw)
* 
* prepareMethod		- bereitet die Transaktion vor - erstellt die Parameterliste
* 				  	  ggf. auch prepareMethod->KK oder ->ELV Muss ich nochmal dr�ber nachdenken ich
* 				  	  denke aber das w�re kein schlechter weg, sonst als array �bergeben
* 
* sendTransaction		- sendet zur Schnittstelle
* 
* getErrorOfErrorcode	- Gibt den Fehlertext zur�ck
* getErrortype			- Warning, schwer, unbekannt, usw.
*
* @since 0.01 - 2005/04/14
*
* @package TYPO3
* @subpackage commerce
* @version 0.1
* @author Marco Klawonn <info@webprog.de> 
* 
* $Id: class.tx_commerce_payment_ccwirecard.php 155 2006-04-05 13:13:24Z thomas $
*/

class wirecard
{
	
	// Daten von Wordpay
	// -----------------------------------------------------------------------------------
	
	var $merchantCode		= "56500";  // Don't put this in a public readable place
	var $password			= "TestXAPTER";  // Don't put this in a public readable place
	var $businesscasesignature = "56500"; // Don't put this in a public readable place
	var $orderCode			= "";
	var $url			= "https://frontend-test.wirecard.com/secure/ssl-gateway"; //it is better to keep this url outside your HTML dir which has public (internet) access
	
	// Construktor
	// ----------------------------------------------------------------------------------
	function wirecard(){
		
		// Ordercode immer neu setzen
		$this->orderCode = $this->referenzID;
		
		// daten die versendet werden
		$this->sendData = "";
		
	}
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* Pflichtfunktion - function: checkTransactiondata
	* 
	* kontrolliert welche Daten f�r wirecard und den gew�hlten Paymenttyp wichtig sind
	* gibt zur�ck ob alle daten ok sind oder einen Array mit den Daten die Fehlen
	* 
	* @return bool / array
	*
	* @since 0.1 - 2005/04/11
	*
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de> 
	*
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	
	function checkTransactiondata (){
        print_r($this->userData);
		return;
    }
	
	function prepareMethod (){
        return;
    }
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* Pflichtfunktion - function: sendTransaction
	* 
	* Sendet die Daten - bei Wirecard mit einem Post auf Curl SSL
	* Das Ergebniss steht im result und muss in das Error/Status Array geschrieben werden.
	* 
	* @return bool
	*
	* @since 0.1 - 2005/04/13
	*
	* @version 0.1
	* @author Marco Klawonn <info@webprog.de> 
	*
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	
	function sendTransaction (){
		
		$header = array ("Authorization: Basic "
                     . base64_encode ($this->merchantCode . ":" . $this->password . "\n"),
	         		   "Content-Type: text/xml");
		
    	    $ch = curl_init ();
	    curl_setopt ($ch, CURLOPT_URL, $this->url);
	    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt ($ch, CURLOPT_POST, 0);
	    curl_setopt ($ch, CURLOPT_POSTFIELDS,  $this->sendData);
	    if ($header != "") {
	        curl_setopt ($ch, CURLOPT_HTTPHEADER, $header);
	    }
		
	    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);

	    ob_start ();
	    $result = curl_exec ($ch);
	    ob_end_clean ();
		
	    curl_close ($ch);
		
		// das von wirecard zur�ckgelieferte XML Parsen
		$this->parseResult($result);
		
		// return 1 = alles ok return = 0 es gab errors
		if($this->isError()){
			return false;
		}else{
			return array(1);;
		};
    }
	
	function getErrorOfErrorcode (){
        return ;
    }
	
	function getErrortype (){
        return ;
    }
	
	
	
	
	///////////////////////////////////////////////////////////////////////////////
	// 
	// Interne Funktionen
	// Diese Funktionen sind private und werden nie von aussen aufgerufen 
	// K�nnen deshalb auch frei mit den anderen Klassenfunktionen reden
	//
	///////////////////////////////////////////////////////////////////////////////
	
		
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* Intern - function: parseResult
	* 
	* Parst das ergebnis und schreibt die Werte in das Status/Error Array
	* 
	* @return bool
	* @since 0.1 - 2005/04/13
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	
	function parseResult($result){
		
		if(!$result) {
		    $this->error['no_data']['this shouldn\'t be'] = 'No result, so nopayment possible';
		    return 1;		    
		}
	
		$parser = xml_parser_create();
		xml_parse_into_struct($parser,$result,$vals,$tags);
		xml_parser_free($parser);
		while(list($k,$v) = each($tags)){
			
			switch($k){
				
				// ERROR auswerten und in $this->error schreiben
				// -----------------------------------------------------------
				case "ERROR":
				    while(list($k2,$v2)=each($v)){
					if($vals[$v2+1]['tag']<>'ERROR'){
						$this->error[$this->referenzID][$vals[$v2+1]['tag']] = $vals[$v2+1]['value'];
					}
				}
					
				break;
			
			}
		};
		

		
		return 1;
	}
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* Intern - function: parseResult
	* 
	* Erzeugt ein XML das an die Bibit Schnittstelle gesendet wird
	* 
	* @return string
	* @since 0.1 - 2005/04/13
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	
	function getwirecardXML(){
		
		$xml="
		<?xml version='1.0' encoding='UTF-8'?>
			<WIRECARD_BXML xmlns:xsi='http://www.w3.org/1999/XMLSchema-instance'
			               xsi:noNamespaceSchemaLocation='wirecard.xsd'>
			    <W_REQUEST>
			        <W_JOB>
			            <JobID>".$this->orderCode."</JobID>
			            <BusinessCaseSignature>".$this->businesscasesignature."</BusinessCaseSignature>
			            <FNC_CC_TRANSACTION>
			                <FunctionID>WireCard Test</FunctionID>
			                <CC_TRANSACTION>
			                    <TransactionID>2</TransactionID>
			                    <Amount>".$this->TransactionData['amount']."</Amount>
			                    <Currency>".$this->TransactionData['currency']."</Currency>
			                    <CountryCode>US</CountryCode>
			                    <RECURRING_TRANSACTION>
			                        <Type>Single</Type>
			                    </RECURRING_TRANSACTION>
								".$this->getPaymentMask()."
			                    <CONTACT_DATA>
			                        <IPAddress>127.0.0.1</IPAddress>
			                    </CONTACT_DATA>
			                    <CORPTRUSTCENTER_DATA>
			                        <ADDRESS>
			                            <Address1>".$this->userData['street']."</Address1>
			                            <City>".$this->userData['city']."</City>
			                            <ZipCode>".$this->userData['zip']."</ZipCode>
			                            <State></State>
			                            <Country>".$this->userData['country']."</Country>
			                            <Phone>".$this->userData['telephone']."</Phone>
			                            <Email>".$this->userData['email']."</Email>
			                        </ADDRESS>
			                    </CORPTRUSTCENTER_DATA>
			                </CC_TRANSACTION>
			             </FNC_CC_TRANSACTION>
			        </W_JOB>
			    </W_REQUEST>
			</WIRECARD_BXML>";//$xml is the order string to send to bibit
		
		// $this->getCountryCode($this->userData['country'])
		// $this->userData['lastname']
		// $this->userData['firstname']
		// $this->orderCode
		#debug($xml);
		return $xml;
	}
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* Intern - function: getPaymentMask
	* 
	* XML f�r die Payment Daten
	* Unterfunktion von getwirecardXML
	* 
	* @return string
	* @since 0.1 - 2005/04/13
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	
	function getPaymentMask(){
		
		if($this->paymenttype == "cc"){
			$xml = "
					 <CREDIT_CARD_DATA>
			                        <CreditCardNumber>".$this->PaymentData['kk_number']."</CreditCardNumber>
			                        <CVC2>".$this->PaymentData['cvc']."</CVC2>
			                        <ExpirationYear>".$this->PaymentData['exp_year']."</ExpirationYear>
			                        <ExpirationMonth>".$this->PaymentData['exp_month']."</ExpirationMonth>
			                        <CardHolderName>".$this->PaymentData['holder']."</CardHolderName>
			         </CREDIT_CARD_DATA>";
		};
		
		return $xml;
	
	}
	
	/////////////////////////////////////////////////////////////////////////////////////
	/**
	* Intern - function: getCountryCode
	* 
	* Gibt einen Country Code zur�ck
	* 
	* @return string
	* @since 0.1 - 2005/04/13
	*/
	/////////////////////////////////////////////////////////////////////////////////////
	
	function getCountryCode($country){
		return "DEU";
	}
} 

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/payment/libs/class.tx_commerce_payment_wirecard_lib.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/payment/libs/class.tx_commerce_payment_wirecard_lib.php']);
}
?>