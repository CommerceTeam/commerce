<?php
print_r( $_GET);
include_once("Saferpay.class.php");

parse_str($QUERY_STRING);
// create saferpay object: set path to executable and configuration directory
$saferpay = new Saferpay("../../bin/saferpay",
                         "../../bin/");
$DATA=$GLOBALS['_GET']['DATA'];

// get attributes out of XML response message
$attributes = $saferpay->GetAttributes(stripslashes($DATA)); 
$resultMessage = "";

if ($attributes["RESULT"] == "0") 
{
	$resultMessage = "Registrierung erfolgreich!";
}
else
{
	$resultMessage = "Registrierung fehlgeschlagen (".$attributes["RESULT"].")";
}

// verify the PayConfirm message sent by the Saferpay host (urlencode($DATA) for windows!)
if ($saferpay->VerifyPayConfirm(stripslashes($_GET['DATA']), stripslashes($_GET['SIGNATURE'])) == 0)
	$resultMessage = "Registrierung fehlgeschlagen (VerifyPayConfirm)!";
?>

<HTML>
<HEAD><TITLE>Saferpay Secure Card Data Test</TITLE></HEAD>
<BODY>
<H1><font color="#000080" face="Arial">Saferpay &quot;Secure Card Data&quot; Test</font></H1>

<H2><font color="#008000" face="Arial"><? echo $resultMessage; ?></font></H2>
<table cellSpacing='0' cellPadding='0' width='400' border='1' bordercolor="#000080"><tr><td>

<table cellSpacing='0' cellPadding='0' width='100%' border='0'>
  <tr>
	<td colspan=2 class="bgfett" bgcolor="#000080" height="23" valign="middle" align="left">
	<b><font face="Arial" color="#FFFFFF" size="2">&nbsp;Registrierte Karte</font></b></td>
  </tr>
  <tr class="bghell">
	<td height="30" bgcolor="#D7D7FF" align="right"><font face="Arial">&nbsp;Kreditkarte:&nbsp;&nbsp;</font></td>
	<td height="30" bgcolor="#D7D7FF"><font face="Arial"><b>Saferpay Test Card</b></font></td>
  </tr>
  <tr class="bghell">
	<td height="30" bgcolor="#D7D7FF" align="right"><font face="Arial">&nbsp;Kartennummer:&nbsp;&nbsp;</font></td>
	<td height="30" bgcolor="#D7D7FF"><font face="Arial"><b>
	<? echo $attributes["CARDMASK"]; ?>&nbsp;&nbsp;&nbsp; <? echo $attributes["EXPIRYMONTH"]; ?>/20<? echo $attributes["EXPIRYYEAR"]; ?>
	</b></font></td>
  </tr>
  <tr class="bghell">
	<td height="30" bgcolor="#D7D7FF" align="right"><font face="Arial">&nbsp;Karteninhaber:&nbsp;&nbsp;</font></td>
	<td height="30" bgcolor="#D7D7FF"><font face="Arial"><b><? echo $cardholder; ?></b></font></td>
  </tr>
</table>
</td></tr></table>
<br>
	
<br>
<table cellSpacing='0' cellPadding='2' width='400' border='1' bordercolor="#C0C0C0">
  <tr class="bghell">
	<td colspan="2" valign="top" align="left" bgcolor="#C0C0C0"><font color="white" face="Arial" size="2">
	<b>&nbsp;Saferpay Antwortdaten</b></font></td>
  </tr>
  <tr class="bghell">
	<td valign="top" align="left" bgcolor="#EAEAEA"><font color="black" face="Arial" size="2">
	<b>VerifyPayConfirm</b></font></td>
	<td bgcolor="#EAEAEA"><font color="black" face="Arial" size="2">Signature in Ordnung!</font></td>
  </tr>

<?	
// show attributes
while(list($name, $value) = each($attributes))
{
?>
	<tr class="bghell">
		<td valign="top" align="left" bgcolor="#EAEAEA"><font color="black" face="Arial" size="2">
		<b><? echo $name ?></b></font></td>
		<td bgcolor="#EAEAEA"><font color="black" face="Arial" size="2"><? echo $value ?></font></td>
	</tr>
<?
}
?>	
		
</table>	
</BODY>
</HTML>
