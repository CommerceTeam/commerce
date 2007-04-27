<!--###BASKET### begin -->
<div class="cmrc_mb_container">
    <div class="cmrc_mb_header_headline"><h1>###LANG_BASKET_HEADER_TITLE###</h1></div>
    <div class="cmrc_mb_header_text">###LANG_BASKET_HEADER_TEXT###</div>
    <div class="cmrc_mb_box">
		<table>
			<tbody><tr class="cmrc_ProdList2Container_hd">
				<th>###LANG_ARTICLE_NUMBER###</th>
				<th width="70%"></th>
				<th width="70">###LANG_PRICE_GROSS###</th>
				<th width="50">###LANG_COUNT###</th>
				<th width="60">###LANG_PRICESUM_GROSS###</th>
				<th></th>
				<th></th>
			</tr>
			###BASKET_PRODUCT_LIST###
			<tr>
				<td colspan="7">&nbsp;</td>

			</tr>
	<!-- ###PAYMENTBOX### begin -->
	<!-- PAYMENT Label, Description, SELECT BOX, Price -->
			<tr>
				<td colspan="4" align="right">
					<table><tr><td>###LANG_PAYMENT###: </td><td>###PAYMENT_SELECT_BOX###</td></tr></table>
				</td>
				<td align="right">###PAYMENT_PRICE_GROSS###</td>
				<td colspan="2"></td>
			</tr>
	<!-- ###PAYMENTBOX### end -->
			<tr>
				<td colspan="7">&nbsp;</td>
			</tr>
	<!-- ###DELIVERYBOX### begin -->
		<!-- LIEFERUNG Label, Description, SELECT BOX, Price -->
			<tr>
				<td colspan="4" align="right">
					<table><tr><td>###LANG_DELIVERY###: </td><td>###DELIVERY_SELECT_BOX###</td></tr></table>
				</td>
				<td align="right">###DELIVERY_PRICE_GROSS###</td>
				<td colspan="2"></td>
			</tr>
	<!-- ###DELIVERYBOX### end -->
			<tr>
				<td colspan="7">&nbsp;</td>
			</tr>
		<!-- Nettopreis + UST + Gesamtpreis alle drei mit Label -->
			<tr>
				<td colspan="4" style="font-weight: bold;" align="right">###LANG_GROSS_PRICE###</td>
				<td class="cmrc_mb_total" align="right">###BASKET_GROSS_PRICE###</td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td colspan="4" align="right">###LANG_VALUE_ADDED_TAX###</td>
				<td align="right">###BASKET_VALUE_ADDED_TAX###</td>
				<td colspan="2"></td>
			</tr>
			<!-- ###TAX_RATE_SUMS### -->
			<tr>
				<td colspan="4" align="right">###LANG_VALUE_ADDED_TAX### (###TAX_RATE###)</td>
				<td align="right">###TAX_RATE_SUM###</td>
				<td colspan="2"></td>
			</tr>
			<!-- ###TAX_RATE_SUMS### -->
		</table>
	</div>
	###BASKET_ARTICLES_NET_SUM###
	###BASKET_ARTICLES_GROSS_SUM###
	###BASKET_DELIVERY_NET_SUM###
	###BASKET_DELIVERY_GROSS_SUM###
	###BASKET_PAYMENT_NET_SUM###
	###BASKET_PAYMENT_GROSS_SUM###
	<a href="###BASKET_LASTPRODUCTURL###">###LANG_LAST_PRODUCT###</a>
    <div class="cmrc_mb_next">###BASKET_NEXTBUTTON###</div>
    <div class="cmrc_mb_no_stock">###NO_STOCK MESSAGE###</div>
</div>
<!--###BASKET### end-->

		    

<!-- ###BASKET_ITEMS_LISTVIEW### begin-->
	<tr class="cmrc_ProdList2Container_even">
		<td style="padding: 0px 3px;" align="right"><div class="basket_artnr">###ARTICLE_EANCODE###</div></td>
		<td style="padding: 0px 3px;"><span class="cmrc_ProdListHeadline"><!-- ###PRODUCT_LINK_DETAIL### --> ###PRODUCT_TITLE###<!-- ###PRODUCT_LINK_DETAIL### --></span></td>
		###PRODUCT_BASKET_FOR_LISTVIEW###
	</tr>
<!-- ###BASKET_ITEMS_LISTVIEW### end -->



<!-- ###BASKET_ITEMS_LISTVIEW2### begin-->
	<tr class="cmrc_ProdList2Container_even">
		<td style="padding: 0px 3px;" align="right"><div class="basket_artnr">###ARTICLE_EANCODE###</div></td>
		<td style="padding: 0px 3px;"><span class="cmrc_ProdListHeadline"><!-- ###PRODUCT_LINK_DETAIL### --> ###PRODUCT_TITLE###<!-- ###PRODUCT_LINK_DETAIL### --></span></td>
		###PRODUCT_BASKET_FOR_LISTVIEW###
	</tr>
<!-- ###BASKET_ITEMS_LISTVIEW2### end -->


<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### begin-->
<div class="cmrc_ProdBasketListContainer">
	<span class="cmrc_ProdBasketListText">
		###ARTICLE_NUMBER###<br>
		###ARTICLE_PRICE###<br>
		###PRODUCT_BASKET_FORM###
	</span><br>
</div>
<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### end -->


<!-- ###PRODUCT_BASKET_SELECT_ATTRIBUTES### begin-->
<div class="cmrc_ProdBasketSelAttrContainer">
	<span class="cmrc_ProdBasketSelAttrText"><u>###SELECT_ATTRIBUTES_TITLE###:</u> ###SELECT_ATTRIBUTES_SELECTBOX###</span>
</div>
<!-- ###PRODUCT_BASKET_SELECT_ATTRIBUTES### end -->

<!-- ###PRODUCT_BASKET_QUICKVIEW### begin -->
				<div class="top_basket_value">Warenkorb (brutto): ###PRICE_GROSS###</div>
				<div class="top_basket_link_row">
					<a href="###URL###" class="top_basket_link_basket">Warenkorb</a>
					<a href="###URL_CHECKOUT###" class="top_basket_link_chkout">Bestellen</a>
					
				</div>
<!-- ###PRODUCT_BASKET_QUICKVIEW### end -->
###BASKET_ITEMS### (anzahl der Items)

<!-- ###PRODUCT_BASKET_EMPTY### begin -->
	###EMPTY_BASKET###
	<div class="cmrc_mb_no_stock">###NO_STOCK MESSAGE###</div>				
<!-- ###PRODUCT_BASKET_EMPTY### end -->


<!-- ###PRODUCT_BASKET_FORM_SMALL### end -->
		<td style="padding: 0px 3px;" align="right">###BASKET_ITEM_PRICEGROSS###</td>
		<span class="basketItemForm">
		###STARTFRM###
		###HIDDENFIELDS###
		<td style="padding: 0px 3px; "><input class="qtyInput" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" type="input" id="articleqty_###ARTICLE_UID###" size="5" /></td>
		<td style="padding: 0px 3px; white-space:nowrap; text-align: right; ">###BASKET_ITEM_PRICESUM_GROSS###</td>
		<td style="padding: 0px 3px;"><input type="image" src="typo3conf/ext/commerce/pi2/res/basket.gif" class="basket_link_img" /></td>
		<td style="padding: 0px 3px;"><a href="#" style="color:black" onClick="document.getElementById('articleqty_###ARTICLE_UID###').value=0; document.basket_###ARTICLE_UID###.submit(); return false;"><img src="typo3conf/ext/commerce/pi2/res/basket_del.gif" border="0" class="basket_link_img" /></td>
		</form>
<!-- ###PRODUCT_BASKET_FORM_SMALL### end -->		