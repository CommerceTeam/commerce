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
<div class="address_item_name">###SELECT###<strong>###NAME### ###SURNAME###</strong></div>
###COMPANY###
<div class="address_item_street">###ADDRESS###</div>
<div class="address_item_city">###ZIP### ###CITY###</div>
<div class="address_item_country">###COUNTRY###</div>
###PHONE###
<div class="address_item_email">###EMAIL###</div>
<!--###LINK_EDIT###-->###LABEL_LINK_EDIT###<!--###LINK_EDIT###-->
</li>
<!--###ADDRESS_ITEM###-->



<!--###ADDRESS_EDIT_FORM###-->
<strong>###LABEL_NAME###</strong> ###FIELD_NAME###<br />
<strong>###LABEL_SURNAME###</strong> ###FIELD_SURNAME###<br />
<strong>###LABEL_COMPANY###</strong> ###FIELD_COMPANY###<br />
<strong>###LABEL_ADDRESS###</strong> ###FIELD_ADDRESS###<br />
<strong>###LABEL_CITY###</strong> ###FIELD_CITY###<br />
<strong>###LABEL_ZIP###</strong> ###FIELD_ZIP###<br />
<strong>###LABEL_COUNTRY###</strong> ###FIELD_COUNTRY###<br />
<strong>###LABEL_EMAIL###</strong> ###FIELD_EMAIL###<br />
<strong>###LABEL_PHONE###</strong> ###FIELD_PHONE###<br />
<!--###ADDRESS_EDIT_FORM###-->

<!--###ADDRESS_LIST###-->
<strong>###HEADER###</strong>
<strong>###LABEL_NAME###</strong> ###NAME###<br />
<strong>###LABEL_SURNAME###</strong> ###SURNAME###<br />
<strong>###LABEL_COMPANY###</strong> ###COMPANY###<br />
<strong>###LABEL_ADDRESS###</strong> ###ADDRESS###<br />
<strong>###LABEL_CITY###</strong> ###CITY###<br />
<strong>###LABEL_ZIP###</strong> ###ZIP###<br />
<strong>###LABEL_COUNTRY###</strong> ###COUNTRY###<br />
<strong>###LABEL_EMAIL###</strong> ###EMAIL###<br />
<strong>###LABEL_PHONE###</strong> ###PHONE###<br />
<!--###ADDRESS_LIST###-->


<!--###SINGLE_INPUT### begin-->
	<tr>
		<td class="chkout_address_left"><div class="chkout_address_label">###FIELD_LABEL### ###FIELD_ERROR###</div></td>
		<td valign="middle"><div class="chkout_address_input">###FIELD_INPUT###</div>
	</tr>
<!--###SINGLE_INPUT### end-->


<!--=# BASIC SUBPARTS END #=-->



<!--###ADDRESS_CONTAINER### begin-->
<div class="chkout_address">
	<div class="chkout_address_title"><h1>###ADDRESS_TITLE###</h1></div>
	<div class="chkout_address_desc">###ADDRESS_DESCRIPTION###</div>
	<div class="chkout_address_fields">
		###ADDRESS_FORM_FIELDS###
		<div class="chkout_address_radio">###ADDRESS_RADIO_DELIVERY###<br/>###ADDRESS_RADIO_NODELIVERY###</div>
		<div class="chkout_address_submit">###ADDRESS_FORM_SUBMIT###</div></form>
	</div>
	<div class="chkout_address_disc">###ADDRESS_DISCLAIMER###</div>
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
<div class="listing">
	<div class="chkout_list_title"><h2>###LISTING_TITLE###</h2></div>
	<div class="chkout_list_descr">###LISTING_DESCRIPTION###</div>
	<div class="chkout_list_form_fields">###LISTING_FORM_FIELDS###</div>
	<div class="chkout_list_basket">###LISTING_BASKET###</div>

	###BILLING_ADDRESS###
	###DELIVERY_ADDRESS###
	<div class="chkout_list_disclaimer">###LISTING_DISCLAIMER###</div>
	<div class="chkout_list_terms"><span class="error">###ERROR_TERMS_ACCEPT###</span>###LISTING_TERMS_ACCEPT_LABEL### ###LISTING_TERMS_ACCEPT_FIELD###</div>
	<div class="chkout_list_comment">###LISTING_COMMENT_LABEL### ###LISTING_COMMENT_FIELD###</div>
	<div class="chkout_list_form_submit">###LISTING_FORM_SUBMIT###</div>
</div>
<!--###LISTING### end-->


<!--
/**
 * Basket View for displaying the Basket with all Items from this basket 
 */
-->
<!--###BASKET_VIEW### begin -->
<table>
	<tr class="cmrc_ProdList2Container_hd">
		<th>###LANG_ARTICLE_NUMBER###</th>
		<th width="70%"></th>
		<th width="70">###LANG_PRICE_GROSS###</th>
		<th width="50">###LANG_COUNT###</th>
		<th width="60">###LANG_PRICESUM_GROSS###</th>
	</tr>
<!--###LISTING_ARTICLE### begin-->
	<tr class="cmrc_ProdList2Container_even">
		<td style="padding: 0px 3px; " align="right"><div class="basket_artnr">###ARTICLE_EANCODE###</div></td>
		<td style="padding: 0px 3px; "><span class="cmrc_ProdListHeadline"><!-- ###PRODUCT_LINK_DETAIL### --> ###PRODUCT_TITLE###<!-- ###PRODUCT_LINK_DETAIL### --></span> ###PRODUCT_SUBTITLE###</td>
		<td align="right" style="padding: 0px 3px; ">###BASKET_ITEM_PRICEGROSS###</td>
		<td style="padding: 0px 3px; ">###BASKET_ITEM_COUNT###</td>
		<td align="right" style="padding: 0px 3px; white-space:nowrap; ">###BASKET_ITEM_PRICESUM_GROSS###</td>
	</tr>
<!--###LISTING_ARTICLE### end-->

<!--###LISTING_BASKET_WEB### begin-->
	<tr>
		<td colspan="4" align="right">###SHIPPING_TITLE###</td>
		<td align="right">###SUM_SHIPPING_NET###</td>
	</tr>
	<tr>
		<td colspan="4" align="right">###PAYMENT_TITLE###</td>
		<td align="right">###SUM_PAYMENT_GROSS###</td>
	</tr>
	<tr>
		<td colspan="4" align="right">###LABEL_SUM_ARTICLE_GROSS###</td>
		<td align="right" class="cmrc_mb_total">###SUM_ARTICLE_GROSS###</td>
	</tr>
	<tr>
		<td colspan="4" class="cmrc_chkout_tax">###LABEL_SUM_TAX###</td>
		<td class="cmrc_chkout_tax">###SUM_TAX###</td>
	</tr>
	<!--###TAX_RATE_SUMS### begin -->
		<tr>
			<td colspan="4" class="cmrc_chkout_tax">###LABEL_SUM_TAX### ###TAX_RATE######LABEL_PERCENT###</td>
			<td class="cmrc_chkout_tax">###TAX_RATE_SUM###</td>
		</tr>
	<!--###TAX_RATE_SUMS### end -->
	<tr>
		<td colspan="5"><hr /></td>
	</tr>
	<tr>
		<td colspan="4" align="right"><strong>###LABEL_SUM_GROSS###</strong></td>
		<td align="right"><strong>###SUM_GROSS###</strong></td>
	</tr>
</table>
<!--###LISTING_BASKET_WEB### end-->

<!--###BASKET_VIEW### end -->

<!--###FINISH### begin-->
###MESSAGE###
###FINISH_MESSAGE_GOOD###
###FINISH_MESSAGE_BAD###
###FINISH_MESSAGE_EMAIL###
###FINISH_MESSAGE_NOEMAIL###
###FINISH_MESSAGE_THANKYOU###
<h2>###LISTING_TITLE###</h2>
	###LISTING_BASKET###
<!--###FINISH### end-->


<!--###CHECKOUT_ERROR### begin-->
<div class="error">###ERROR_MESSAGE###</div>
<!--###CHECKOUT_ERROR### end-->
