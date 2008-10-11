<!--
This template is for checkout. It's splitted into several parts.
Each parts stands for a single step in the process of checking
out a basket.

Step 1:	address
		Here we collect the addresses for the order. In this version
		possible addresses are
			- billing address
			- delivery address
			
Step 2: payment
		
Step 3: finish


@TODO Checkt used Marker within description
-->

<!--=# BASIC SUBPARTS BEGIN #=-->

<!--###ADDRESS_LISTING### begin-->
<table border="1" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<ol>
			###ADDRESS_ITEMS###
			</ol>
			<!--###LINK_NEW###-->###LABEL_LINK_NEW###<!--###LINK_NEW###-->
		</td>
	</tr>
</table>
<!--###ADDRESS_LISTING### end-->

<!--###ADDRESS_ITEM###-->
<li>
###SELECT###<strong>###LABEL_NAME### ###NAME###</strong><br />
###LABEL_COMPANY### ###COMPANY###<br />
###LABEL_ADDRESS### ###ADDRESS###<br />
###LABEL_CITY### ###CITY###<br />
###LABEL_ZIP### ###ZIP###<br />
###LABEL_COUNTRY### ###COUNTRY###<br />
<!--###LINK_EDIT###-->###LABEL_LINK_EDIT###<!--###LINK_EDIT###-->
</li>
<!--###ADDRESS_ITEM###-->

<!--###ADDRESS_EDIT_FORM###-->
<strong>###LABEL_NAME###</strong> ###FIELD_NAME###<br />
<strong>###LABEL_COMPANY###</strong> ###FIELD_COMPANY###<br />
<strong>###LABEL_ADDRESS###</strong> ###FIELD_ADDRESS###<br />
<strong>###LABEL_CITY###</strong> ###FIELD_CITY###<br />
<strong>###LABEL_ZIP###</strong> ###FIELD_ZIP###<br />
<strong>###LABEL_COUNTRY###</strong> ###FIELD_COUNTRY###<br />
<!--###ADDRESS_EDIT_FORM###-->

<!--###SINGLE_INPUT### begin-->
<p>
<span class="error">###FIELD_ERROR###</span><br />
###FIELD_INPUT###
</p>
<!--###SINGLE_INPUT### end-->

<!--=# BASIC SUBPARTS END #=-->


<!--###ADDRESS_CONTAINER### begin-->
<div id="address">
	<h2>###ADDRESS_TITLE###</h2>
	<p>###ADDRESS_DESCRIPTION###</p>
	<p>###ADDRESS_FORM_FIELDS###</p>
	<p>###ADDRESS_FORM_SUBMIT###</p>
	<p>###ADDRESS_DISCLAIMER###</p>
</div>
<!--###ADDRESS_CONTAINER### end-->


<!--###PAYMENT### begin-->
<div id="payment">
	<h2>###PAYMENT_TITLE###</h2>
	<p>###PAYMENT_DESCRIPTION###</p>
	<p>###PAYMENT_FORM_FIELDS###</p>
	<p>###PAYMENT_FORM_SUBMIT###</p>
	<p>###PAYMENT_DISCLAIMER###</p>
</div>
<!--###PAYMENT### end-->


<!--###LISTING### begin-->
<div id="listing">
	<h2>###LISTING_TITLE###</h2>
	<p>###LISTING_DESCRIPTION###</p>
	<p>###LISTING_FORM_FIELDS###</p>
	<p>###LISTING_BASKET###</p>

	<p>###BILLING_ADDRESS###</p>
	<p>###DELIVERY_ADDRESS###</p>
	<p>###LISTING_DISCLAIMER###</p>
	<p>###ERROR_TERMS_ACCEPT###
	###LISTING_TERMS_ACCEPT### </p>
	<p>###LISTING_COMMENT###</p>
	<p>###LISTING_FORM_SUBMIT###</p>
</div>
<!--###LISTING### end-->

<!--
/**
 * Basket View for displaying the Basket with all Items from this basket 
 */
-->
<!--###BASKET_VIEW### begin -->
<!--###LISTING_ARTICLE### begin-->
<p>
###PRODUCT_TITLE###<BR/>
###PRODUCT_IMAGES###<br/>
<SPAN>###PRODUCT_SUBTITLE###<BR/>###LANG_ARTICLE_NUMBER### ###ARTICLE_EANCODE###<br/>###PRODUCT_LINK_DETAIL###</SPAN>

###LANG_PRICE_NET### ###BASKET_ITEM_PRICENET###<br/>
###LANG_PRICE_GROSS### ###BASKET_ITEM_PRICEGROSS###<br/>
###LANG_TAX### ###BASKET_ITEM_TAX_VALUE###<br/>
###LANG_COUNT### ###BASKET_ITEM_COUNT###<br/>
###LANG_PRICESUM_NET### ###BASKET_ITEM_PRICESUM_NET### <br/>   
###LANG_PRICESUM_GROSS### ###BASKET_ITEM_PRICESUM_GROSS### <br/>  

</p>
<!--###LISTING_ARTICLE### end-->


<!--###LISTING_BASKET_WEB### begin-->
<table border="1" cellspacing="0" cellpadding="0">
	<tr>
		<td>###LABEL_SUM_ARTICLE_NET### ###SUM_ARTICLE_NET###</td>
		<td>###LABEL_SUM_ARTICLE_GROSS### ###SUM_ARTICLE_GROSS###</td>
	</tr>
	<tr>
		<td>###SHIPPING_TITLE### ###LABEL_SUM_SHIPPING_NET### ###SUM_SHIPPING_NET###</td>
		<td>###LABEL_SUM_SHIPPING_GROSS### ###SUM_SHIPPING_GROSS###</td>
	</tr>
	<tr>
		<td>###PAYMENT_TITLE### ###LABEL_SUM_PAYMENT_NET### ###SUM_PAYMENT_NET###</td>
		<td>###LABEL_SUM_PAYMENT_GROSS### ###SUM_PAYMENT_GROSS###</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><hr /></td>
	</tr>
	<tr>
		<td>###LABEL_SUM_NET###</td>
		<td>###SUM_NET###</td>
	</tr>
	<tr>
		<td>###LABEL_SUM_TAX###</td>
		<td>###SUM_TAX###</td>
	</tr>
	<tr>
		<td colspan="2"><hr /></td>
	</tr>
	<tr>
		<td colspan="2" align="center"><strong>
			###LABEL_SUM_GROSS### ###SUM_GROSS###
		</strong></td>
	</tr>
</table>
<!--###LISTING_BASKET_WEB### end-->

<!--###BASKET_VIEW### end -->







<!--###FINISH### begin-->
###MESSAGE###
<h2>###LISTING_TITLE###</h2>
<p><table>
    	<tr><th>Artikel</th><th>Einzelpreis<br/> inkl. MWSt.</th><th>Menge</th><th>Gesamtpreis<br /> inkl. MWSt.</th><th>MWSt.</th></tr>
	###LISTING_BASKET###
</table></p>

<!--###FINISH### end-->