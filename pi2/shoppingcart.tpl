<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title>COMMERCE TEMPLATE FOR PI2</title>
    <link href="../res/css/commerce.css" rel="stylesheet" type="text/css" />
</head>

<body>

<h1>COMMERCE TEMPLATE FOR PI2</h1>
<h2>DEFAULT</h2>





<h3>BASKET</h3>

<!--###BASKET### begin -->
<form action="###GENERAL_FORM_ACTION###" method="post" name="basket">
<div class="com-basket-container">
    <h2>###LANG_BASKET_HEADER_TITLE###</h2>
    <p class="com-basket-header-text">###LANG_BASKET_HEADER_TEXT###</p>
    <!--  ###BASKETLOCKED### begin -->
    <p class="com-basket-header-info">###LANG_BASKET_LOCKED###</p>
    <!--  ###BASKETLOCKED### end -->
    <div class="com-basket-box">
	<table class="com-basket-list" cellspacing="0" cellpadding="0" border="0">
		<thead>
			<tr class="com-basket-header">
				<th class="com-basket-header-art-nr">###LANG_ARTICLE_NUMBER###</th>
				<th class="com-basket-header-title">###LANG_TITLE###</th>
				<th class="com-basket-header-price-gross">###LANG_PRICE_GROSS###</th>
				<th class="com-basket-header-count">###LANG_COUNT###</th>
				<th class="com-basket-header-price-sum">###LANG_PRICESUM_GROSS###</th>
				<th class="com-basket-header-basket">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			###BASKET_PRODUCT_LIST###
			<tr>
				<td colspan="6">&nbsp;</td>
			</tr>
	<!-- ###PAYMENTBOX### begin -->
	<!-- PAYMENT Label, Description, SELECT BOX, Price -->
			<tr>
				<td colspan="4" class="com-text-right com-basket-payment">
					<label for="tx_commerce_pi1[payArt]">###LANG_PAYMENT###: </label>###PAYMENT_SELECT_BOX###
				</td>
				<td  class="com-text-right">###PAYMENT_PRICE_GROSS###</td>
				<td colspan="2"></td>
			</tr>
	<!-- ###PAYMENTBOX### end -->
			<tr>
				<td colspan="6">&nbsp;</td>
			</tr>
	<!-- ###DELIVERYBOX### begin -->
		<!-- DELIVERY Label, Description, SELECT BOX, Price -->
			<tr>
				<td colspan="4" class="com-text-right com-basket-delivery">
					<label for="tx_commerce_pi1[delArt]">###LANG_DELIVERY###:</label> ###DELIVERY_SELECT_BOX###
				</td>
				<td class="com-text-right">###DELIVERY_PRICE_GROSS###</td>
				<td colspan="1">&nbsp;</td>
			</tr>
	<!-- ###DELIVERYBOX### end -->
			<tr>
				<td colspan="6">&nbsp;</td>
			</tr>
		<!-- Nettopreis + UST + Gesamtpreis alle drei mit Label -->
			<tr>
				<td colspan="4" class="com-bold com-text-right">###LANG_GROSS_PRICE###</td>
				<td class="com-text-right">###BASKET_GROSS_PRICE###</td>
				<td colspan="1">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="4" class="com-text-right">###LANG_VALUE_ADDED_TAX###</td>
				<td class="com-text-right">###BASKET_VALUE_ADDED_TAX###</td>
				<td colspan="1">&nbsp;</td>
			</tr>
			<!-- ###TAX_RATE_SUMS### -->
			<tr>
				<td colspan="4" class="com-text-right">###LANG_VALUE_ADDED_TAX### (###TAX_RATE###)</td>
				<td class="com-text-right">###TAX_RATE_SUM###</td>
				<td colspan="1">&nbsp;</td>
			</tr>
			<!-- ###TAX_RATE_SUMS### -->
		</table>
	</div>
<!--
	###BASKET_ARTICLES_NET_SUM###
	###BASKET_ARTICLES_GROSS_SUM###
	###BASKET_DELIVERY_NET_SUM###
	###BASKET_DELIVERY_GROSS_SUM###
	###BASKET_PAYMENT_NET_SUM###
	###BASKET_PAYMENT_GROSS_SUM###
-->

	<p class="com-basket-submit"><input type="submit" value="###LANG_BASKET_UPDATE###" /></p>
	<p class="com-basket-previous"><a href="###BASKET_LASTPRODUCTURL###" title="###LANG_LAST_PRODUCT###">###LANG_LAST_PRODUCT###</a></p>
    <p class="com-basket-next">###BASKET_NEXTBUTTON###</p>
    <div class="com-basket-no-stock">###NO_STOCK MESSAGE###</div>
</div>
</form>
<!--###BASKET### end-->

		    
<br />
<br />
<br />
<h3>BASKET_ITEMS_LISTVIEW - EVEN</h3>
<br />
<!-- ###BASKET_ITEMS_LISTVIEW### begin-->
	<tr class="com-basket-even">
		<td class="com-text-right">###ARTICLE_ORDERNUMBER###</td>
		<td>###PRODUCT_TITLE###</td>
		###PRODUCT_BASKET_FOR_LISTVIEW###
	</tr>
<!-- ###BASKET_ITEMS_LISTVIEW### end -->


<br />
<br />
<br />
<h3>BASKET_ITEMS_LISTVIEW - ODD</h3>
<br />
<!-- ###BASKET_ITEMS_LISTVIEW2### begin-->
	<tr class="com-basket-odd">
		<td class="com-text-right">###ARTICLE_ORDERNUMBER###</td>
		<td>###PRODUCT_TITLE###</td>
		###PRODUCT_BASKET_FOR_LISTVIEW###
	</tr>
<!-- ###BASKET_ITEMS_LISTVIEW2### end -->



<br />
<br />
<br />
<h3>PRODUCT_BASKET_FOR_LISTVIEW</h3>
<br />
<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### begin-->
<div class="com-ProdBasketListContainer">
	<span class="com-ProdBasketListText">
		###ARTICLE_NUMBER###<br>
		###ARTICLE_PRICE###<br>
		###PRODUCT_BASKET_FORM###
	</span>
</div>
<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### end -->

<br />
<br />
<br />
<h3>PRODUCT_BASKET_SELECT_ATTRIBUTES</h3>
<br />
<!-- ###PRODUCT_BASKET_SELECT_ATTRIBUTES### begin-->
<div class="com-basket-sel-att">
	<span class="com-basket-sel-att-text"><u>###SELECT_ATTRIBUTES_TITLE###:</u> ###SELECT_ATTRIBUTES_SELECTBOX###</span>
</div>
<!-- ###PRODUCT_BASKET_SELECT_ATTRIBUTES### end -->


<br />
<br />
<br />
<h3>PRODUCT_BASKET_FORM_SMALL</h3>
<br />
<!-- ###PRODUCT_BASKET_QUICKVIEW### begin -->
<div class="com-basket-qv">
	<div class="com-basket-qv-value">###LANG_BASKET_QV_VALUE### (###BASKET_ITEMS###) ###PRICE_GROSS###</div>
	<div class="com-basket-qv-link">
		<a href="###URL###" class="com-basket-qv-basket">###LANG_BASKET_QV_LINK###</a>
		<a href="###URL_CHECKOUT###" class="com-basket-qv-chkout">###LANG_BASKET_QV_CHKOUT###</a>
	</div>
</div>
<!-- ###PRODUCT_BASKET_QUICKVIEW### end -->

<!-- ###PRODUCT_BASKET_QUICKVIEW_EMPTY### begin -->
	###EMPTY_BASKET###
	<div class="com-basket-no-stock">###NO_STOCK MESSAGE###</div>				
<!-- ###PRODUCT_BASKET_QUICKVIEW_EMPTY### end -->

<br />
<br />
<br />
<h3>PRODUCT_BASKET_FORM_SMALL</h3>
<br />
<!-- ###PRODUCT_BASKET_EMPTY### begin -->
	###EMPTY_BASKET###
	<div class="com-basket-no-stock">###NO_STOCK MESSAGE###</div>				
<!-- ###PRODUCT_BASKET_EMPTY### end -->

<br />
<br />
<br />
<h3>PRODUCT_BASKET_FORM_SMALL</h3>
<br />
<!-- ###PRODUCT_BASKET_FORM_SMALL### end -->
		<td class="com-text-right">###BASKET_ITEM_PRICEGROSS###</td>
		<td>
			###ARTICLE_HIDDENFIELDS###
			<input class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" type="input" id="articleqty_###ARTICLE_UID###" size="5" />
		</td>
		<td class="com-text-right">###BASKET_ITEM_PRICESUM_GROSS###</td>
		<td>
		<input type="image" src="typo3conf/ext/commerce/pi2/res/basket.gif"/>
		###DELIOTMFROMBASKETLINK###
		</td>
		
<!-- ###PRODUCT_BASKET_FORM_SMALL### end -->	


</body>
</html>	