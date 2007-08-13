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
<dl>
<dt>###LABEL_NAME###</dt> <dd>###FIELD_NAME###</dd>
<dt>###LABEL_SURNAME###</dt> <dd>###FIELD_SURNAME###</dd>
<dt>###LABEL_COMPANY###</dt> <dd>###FIELD_COMPANY###</dd>
<dt>###LABEL_ADDRESS###</dt> <dd>###FIELD_ADDRESS###</dd>
<dt>###LABEL_CITY###</dt> <dd>###FIELD_CITY###</dd>
<dt>###LABEL_ZIP###</dt> <dd>###FIELD_ZIP###</dd>
<dt>###LABEL_COUNTRY###</dt> <dd>###FIELD_COUNTRY###</dd>
<dt>###LABEL_EMAIL###</dt> <dd>###FIELD_EMAIL###</dd>
<dt>###LABEL_PHONE###</dt> <dd>###FIELD_PHONE###</dd>
</dl>
<!--###ADDRESS_EDIT_FORM###-->

<!--###ADDRESS_LIST###-->
<h3>###HEADER###</h3>
<dl>
<dt>###LABEL_NAME###</dt> <dd>###NAME###</dd>
<dt>###LABEL_SURNAME###</dt> <dd>###SURNAME###</dd>
<dt>###LABEL_COMPANY###</dt> <dd>###COMPANY###</dd>
<dt>###LABEL_ADDRESS###</dt> <dd>###ADDRESS###</dd>
<dt>###LABEL_CITY###</dt> <dd>###CITY###</dd>
<dt>###LABEL_ZIP###</dt> <dd>###ZIP###</dd>
<dt>###LABEL_COUNTRY###</dt> <dd>###COUNTRY###</dd>
<dt>###LABEL_EMAIL###</dt> <dd>###EMAIL###</dd>
<dt>###LABEL_PHONE###</dt> <dd>###PHONE###</dd>
</dl>
<!--###ADDRESS_LIST###-->


<!--###SINGLE_INPUT### begin-->
	<dt class="com-chkout-address-label"><label for="###FIELD_INPUTID###">###FIELD_LABEL###</label> ###FIELD_ERROR###</dt>
	<dd class="com-chkout-address-input">###FIELD_INPUT###</dd>	
<!--###SINGLE_INPUT### end-->


<!--###SINGLE_CHECKBOX### begin-->
	<dt class="com-chkout-address-label">###FIELD_INPUT###</dt>
	<dd class="com-chkout-address-input"><label for="###FIELD_INPUTID###">###FIELD_LABEL###</label> ###FIELD_ERROR###</dd>	
<!--###SINGLE_CHECKBOX### end-->

<!--=# BASIC SUBPARTS END #=-->



<!--###ADDRESS_CONTAINER### begin-->
###CHECKOUT_STEPS###
<div class="com-chkout-address">
	<h2>###ADDRESS_TITLE###</h2>
	<p class="com-chkout-address-desc">###ADDRESS_DESCRIPTION###</p>
	<div class="com-chkout-address-fields">
		<form action="###GENERAL_FORM_ACTION###" method="post">
		###HIDDEN_STEP###
		<dl>
			###ADDRESS_FORM_INPUTFIELDS###
		
		<dd class="com-chkout-address-radio">###ADDRESS_RADIOFORM_DELIVERY###</dd>
		<dt class="com-chkout-address-radiolabel">###ADDRESS_LABEL_DELIVERY###</dt>
		<dd class="com-chkout-address-radio">###ADDRESS_RADIOFORM_NODELIVERY###</dd>
		<dt class="com-chkout-address-radiolabel">###ADDRESS_LABEL_NODELIVERY###</dt>
		</dl>	
			
		<p class="com-chkout-address-fields-submit"> ###ADDRESS_FORM_SUBMIT###</p>
		</form>
	</div>
	<p class="com-chkout-address-fields-disclaimer">###ADDRESS_DISCLAIMER###</p>
</div>
<!--###ADDRESS_CONTAINER### end-->



<!--###PAYMENT### begin-->
###CHECKOUT_STEPS###
<div id="payment">
	<h2>###PAYMENT_TITLE###</h2>
	<p>###PAYMENT_DESCRIPTION###</p>
	<p>###PAYMENT_FORM_FIELDS###</p>
	<p>###PAYMENT_FORM_SUBMIT###</p>
	<p>###PAYMENT_DISCLAIMER###</p>
</div>
<!--###PAYMENT### end-->



<!--###LISTING### begin-->
<form action="###GENERAL_FORM_ACTION###" method="post">
###HIDDEN_STEP###
###CHECKOUT_STEPS###
<div class="com-chkout-listing">
	<h2>###LISTING_TITLE###</h2>
	<p class="com-chkout-listing-descr">###LISTING_DESCRIPTION###</p>
	<div class="com-chkout-listing-basket">###LISTING_BASKET###</div>
	<div class="com-chkout-listing-billing-address">
	###BILLING_ADDRESS###
	</div>
	<div class="com-chkout-listing-delivery-address">
	###DELIVERY_ADDRESS###
	</div>
	<div class="com-chkout-listing-footer">
	<p class="com-chkout-listing-disclaimer">###LISTING_DISCLAIMER###</p>
	<p class="com-chkout-listing-terms"><span class="error">###ERROR_TERMS_ACCEPT###</span>###LISTING_TERMS_ACCEPT_LABEL### ###LISTING_TERMS_ACCEPT_FIELD###</p>
	<p class="com-chkout-listing-comment">###LISTING_COMMENT_LABEL### ###LISTING_COMMENT_FIELD###</p>
	<p class="com-chkout-listing-submit">###LISTING_FORM_SUBMIT###</p>
	</div>
</div>
</form>
<!--###LISTING### end-->


<!--
/**
 * Basket View for displaying the Basket with all Items from this basket 
 */
-->
<!--###BASKET_VIEW### begin -->
<table class="com-basket-list" cellspacing="0" cellpadding="0" border="0">
	<thead>
		<tr class="com-basket-header">
			<th class="com-basket-header-art-nr">###LANG_ARTICLE_NUMBER###</th>
			<th class="com-basket-header-title">###LANG_TITLE###</th>
			<th class="com-basket-header-price-gross">###LANG_PRICE_GROSS###</th>
			<th class="com-basket-header-count">###LANG_COUNT###</th>
			<th class="com-basket-header-price-sum">###LANG_PRICESUM_GROSS###</th>
		</tr>
	</thead>
	<tbody>
<!--###LISTING_ARTICLE### begin-->
<tr class="com-basket-even">
		<td class="com-text-right">###ARTICLE_ORDERNUMBER###</td>
		<td>###PRODUCT_TITLE###</td>
		<td class="com-text-right">###BASKET_ITEM_PRICEGROSS###</td>
		<td>###BASKET_ITEM_COUNT###</td>
		<td class="com-text-right">###BASKET_ITEM_PRICESUM_GROSS###</td>
</tr>
<!--###LISTING_ARTICLE### end-->

<!--###LISTING_BASKET_WEB### begin-->
	<tr>
		<td colspan="4" class="com-text-right">###SHIPPING_TITLE###</td>
		<td class="com-text-right">###SUM_SHIPPING_NET###</td>
	</tr>
	<tr>
		<td colspan="4" class="com-text-right">###PAYMENT_TITLE###</td>
		<td class="com-text-right">###SUM_PAYMENT_GROSS###</td>
	</tr>
	<tr>
		<td colspan="4" class="com-text-right">###LABEL_SUM_ARTICLE_GROSS###</td>
		<td class="com-text-right">###SUM_ARTICLE_GROSS###</td>
	</tr>
	<tr>
		<td colspan="4" class="com-text-right">###LABEL_SUM_TAX###</td>
		<td class="com-text-right">###SUM_TAX###</td>
	</tr>
	<!--###TAX_RATE_SUMS### begin -->
		<tr>
			<td colspan="4" class="com-text-right">###LABEL_SUM_TAX### ###TAX_RATE######LABEL_PERCENT###</td>
			<td class="com-text-right">###TAX_RATE_SUM###</td>
		</tr>
	<!--###TAX_RATE_SUMS### end -->
	<tr class="com-chkout-sum">
		<td colspan="4" class="com-text-right com-bold">###LABEL_SUM_GROSS###</td>
		<td class="com-text-right com-bold">###SUM_GROSS###</td>
	</tr>
<!--###LISTING_BASKET_WEB### end-->
</table>
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

<!--###CHECKOUT_STEPS_BAR### begin -->
<div class="com-chkout-steps">

<!-- ###CHECKOUT_ONE_STEP_ACTIVE### begin -->
<div class="com-chkout-step-active">
	###LINKTOSTEP###
</div>
<!-- ###CHECKOUT_ONE_STEP_ACTIVE### end -->
<!-- ###CHECKOUT_ONE_STEP_ACTUAL### begin -->
<div class="com-chkout-step-actual">
	###STEPNAME###
</div>
<!-- ###CHECKOUT_ONE_STEP_ACTUAL### end -->
<!-- ###CHECKOUT_ONE_STEP_INACTIVE### begin -->
<div class="com-chkout-step-inactive">
	###STEPNAME###
</div>
<!-- ###CHECKOUT_ONE_STEP_INACTIVE### end -->
</div>
<!--###CHECKOUT_STEPS_BAR### end -->