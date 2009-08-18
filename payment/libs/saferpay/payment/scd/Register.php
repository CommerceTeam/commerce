<?php

include_once("Saferpay.class.php");

// get the selfurl
$self_url = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'];
$self_url = substr($self_url, 0, strrpos($self_url, '/')) . "/";

// create saferpay object: set path to executable and configuration directory
$saferpay = new Saferpay("../../bin/saferpay",
                         "../../bin/");

// create new CARDREFID
$cardrefid = "TEST".mktime();

$attributes = array(
    "ACCOUNTID" => "97614-17785576",
    "CARDREFID" => $cardrefid,
    "SUCCESSLINK" => $self_url."RegisterDone.php?status=success",
    "FAILLINK" => $self_url."RegisterDone.php?status=failed");

$registerURL = $saferpay->CreatePayInit($attributes);
?>
<HTML>
<HEAD><TITLE>Saferpay Secure Card Data Test</TITLE></HEAD>
<BODY>

<H1><font color="#000080" face="Arial">Saferpay &quot;Secure Card Data&quot; Test</font></H1>
<form action='<? echo $registerURL ?>' method='POST'>

	<input type='hidden' name='land' value='1'/>
	<input type='hidden' name='beraternr' value='100001'/>
	<input type='hidden' name='mandant' value='1'/>

<table cellSpacing='0' cellPadding='0' width='400' border='1' bordercolor="#000080"><tr><td>

<table cellSpacing='0' cellPadding='0' width='100%' border='0'>
  <tr>
	<td colspan=2 class="bgfett" bgcolor="#000080" height="23" valign="middle" align="left"><b><font face="Arial" size="3" color="#FFFFFF">&nbsp;
      </font><font face="Arial" color="#FFFFFF" size="2">Kreditkarteninformationen</font></b></td>
  </tr>
  <tr class="bghell">
	<td height="30" bgcolor="#D7D7FF" align="right"><font face="Arial">&nbsp;Kreditkarte:&nbsp;&nbsp;</font></td>
	<td height="30" bgcolor="#D7D7FF"><font face="Arial"><select name='cardbrand' size='1'>
		<option value='M'>MasterCard</option>
		<option value='V'>VISA</option>
	</select></font></td>
  </tr>
  <tr class="bghell">
	<td height="30" bgcolor="#D7D7FF" align="right"><font face="Arial">&nbsp;Name
      Karteninhaber:&nbsp;&nbsp;</font></td>
	<td height="30" bgcolor="#D7D7FF"><font face="Arial"><input type='text' name='cardholder' size='25' maxlength='50' value="Karl Mustermann"/></font></td>
  </tr>
  <tr class="bghell">
	<td height="30" bgcolor="#D7D7FF" align="right"><font face="Arial">&nbsp;Kreditkartennummer:&nbsp;&nbsp;</font></td>
	<td height="30" bgcolor="#D7D7FF"><font face="Arial"><input type='text' name='sfpCardNumber' value="9451123100000004" size='16' maxlength='16'/>&nbsp;&nbsp;&nbsp;<sup>(16-stellig)</sup></font></td>
  </tr>
  <tr>
	<td height="30" bgcolor="#D7D7FF" align="right"><font face="Arial">&nbsp;G&uuml;ltig bis:&nbsp;&nbsp;</font></td>
	<td height="30" bgcolor="#D7D7FF"><font face="Arial">
	<select name="sfpCardExpiryMonth" size='1'> <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option><option>7</option><option>8</option><option>9</option><option selected>10</option><option>11</option><option>12</option>
	</select>
	&nbsp;/&nbsp;
	<select name='sfpCardExpiryYear' size='1'> <option selected>2005</option><option>2006</option><option>2007</option><option>2008</option><option>2009</option><option>2010</option><option>2011</option><option>2012</option><option>2013</option><option>2014</option><option>2015</option>
	</select></font></td>
  </tr>
  <tr class="bghell">
	<td height="30" bgcolor="#D7D7FF" align="right"><font face="Arial">&nbsp;Kartenpr&uuml;fnummer:&nbsp;&nbsp;</font></td>
	<td height="30" bgcolor="#D7D7FF"><font face="Arial"><input type='text' name='CVC' value="123" size='4' maxlength='4'/>&nbsp;&nbsp;&nbsp;<sup>(3- oder 4-stellig)</sup></font></td>
  </tr>
  <tr class="bghell">
	<td height="30" bgcolor="#D7D7FF" align="right"><font face="Arial">&nbsp;</font></td>
	<td height="30" bgcolor="#D7D7FF">
      <p align="right"><font face="Arial"><input type='submit' value='--> Karte registrieren...'/>&nbsp;</font></p>
    </td>
  </tr>
</table>
</td></tr></table>
</form>
<br>
<table cellSpacing='0' cellPadding='2' width='400' border='1' bordercolor="#C0C0C0">
  <tr class="bghell">
	<td valign="top" align="left" bgcolor="#EAEAEA"><font color="#888888" face="Arial" size="2">
	Kartenersatznummer</font></td>
	<td bgcolor="#EAEAEA"><font color="#888888" face="Arial" size="2"><? echo $cardrefid ?></font></td>
  </tr>
  <tr class="bghell">
	<td valign="top" align="left" bgcolor="#EAEAEA"><font color="#888888" face="Arial" size="2">Registierungs-URL</font></td>
	<td bgcolor="#EAEAEA"><font color="#888888" face="Arial" size="2"><? echo $registerURL ?></font></td>
  </tr>
</table>
</BODY>
</HTML>
