<?php

class Saferpay {
	var $execPath;
	var $configPath;

	// set values for executables and configuration directory
	function Saferpay($execPath, $configPath) {
		$this->execPath = $execPath;
		$this->configPath = $configPath;
	}

	// create payment url
	function CreatePayInit($attributes) {
		// create command line
		$cmd = $this->BuildCommandLine("-payinit", $attributes);

		// get payinit url
		exec($cmd, $url, $retvalue);

		if ($retvalue != 0) {
			die("Error $retvalue (Saferpay.CreatePayInit, exec())");
		}
		$url = join("", $url);

		//return url
		return $url;
	}

	// verify payment response message
	// return: 1 - verification successful
	//         0 - verification failed
	function VerifyPayConfirm($data, $signature) {
		// create command line
		$cmd = "$this->execPath -payconfirm -p $this->configPath -d ".escapeshellarg($data)." -s ".escapeshellarg($signature)." 2>&1";

		// verify data
		exec($cmd, $result, $retvalue);

		// check if command has been proceeded successfully
		if ($retvalue != 0) {
			return 0;
		}

		// join result values
		$result = join(" ", $result);

		// check if error message
		if (stristr($result, "ERROR:") != "") {
			return 0;
		}

		// return success!
		return 1;
	}

	function capture($id, $token) {
		$id = escapeshellarg($id);
		$token = escapeshellarg($token);
		$cmd  = $this->BuildCommandLine("-capt", array());
		$cmd .= " -i $id -t $token";
		$cmd  = "$cmd 2>&1";

		// execute command
		exec($cmd, $result, $retvalue);
		$attributes = $this->GetAttributes($result[0]);
		return $result[0];
	}

	function payconfirm($data, $signature) {
		$data = escapeshellarg($data);
		$signature = escapeshellarg($signature);
		$cmd  = $this->BuildCommandLine("-payconfirm", array());
		$cmd .=" -d $data -s $signature";
		$cmd  = "$cmd 2>&1";
		// execute command
		exec($cmd, $result, $retvalue);
		$attributes = $this->GetAttributes($result[0]);
		return $attributes;
	}

	// online authorization
	// return: attribute array
	function Execute($attributes, $message) {
		// store return values in temp file
		$tmpfile = tempnam("c:\\tmp", "Trx");
		// create command line
		if($message) {
			$cmd = $this->BuildCommandLine("-exec -m $message -f $tmpfile", $attributes);
		}
		$cmd = "$cmd 2>&1";

		// execute command
		exec($cmd, $result, $retvalue);

		if ($retvalue != 0) {
			unlink($tmpfile);
			die("Error $retvalue (Saferpay.Execute, exec())");
		}

		// parse response
		$file = fopen($tmpfile, "r");
		while (!feof($file)) {
			$buffer = fgets($file, 4096);
		}
		fclose($file);
		unlink($tmpfile);

		// check for error
		if (strlen($buffer) > 0) {
			// convert XML string into attribute array
			$attributes = $this->GetAttributes($buffer);
		} else {
			$attributes = array(
				"ERROR" => join(" ", $result),
				"RESULT" => "9999",
				"BUFFER" => $buffer
			);
		}

		return $attributes;
	}

	// internal function to create a command line
	function BuildCommandLine($option, $attributes) {
		// create command line
		$cmd = "$this->execPath $option -p $this->configPath";

		// add attributes
		while(list($name, $value) = each($attributes)) {
			$cmd = "$cmd -a $name \"$value\"";
		}
		return $cmd;
	}

	// return XML attributs as array
	function GetAttributes($xml) {
		// delete starting and trailing markes <IDP .... />
		$data = ereg_replace("^<IDP( )*", "", $xml);
		$data = ereg_replace("( )*/( )>$", "", $data);
		$data = trim($data);
		while(strlen($data) > 0) {
			$pos = strpos($data, "=\"");
			$name = substr($data, 0, $pos); // get attribute name
			$data = substr($data, $pos + 2); // skip ="
			$pos = strpos($data, "\"");
			$value = substr($data, 0, $pos); // get attribute value
			$data = substr($data, $pos + 1); // skip "
			$data = trim($data);
			$attributes[$name] = $value;
		}
		return $attributes;
	}
}

?>