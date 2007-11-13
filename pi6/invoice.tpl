<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<TITLE>INVOICE Template</TITLE>

</HEAD>
<BODY>
<!-- Be careful, if you use pdf_generator, just build Plain HTML 3.2 HTML with templates -->
<!-- ###TEMPLATE### begin -->
<table border="0" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th colspan="2" class="com-invoice-header">###INVOICE_HEADER###</th>
		</tr>
		<tr>
			<th class="com-invoice-customer-address">
				<!-- ###ADDRESS_BILLING_DATA### begin-->
					<h2>###LANG_BILLING_ADDRESS###</h2>
					###ADDRESS_BILLING_NAME###
					###ADDRESS_BILLING_SURNAME###
					###ADDRESS_BILLING_COMPANY###
					###ADDRESS_BILLING_ADDRESS###
					###ADDRESS_BILLING_ZIP###
					###ADDRESS_BILLING_CITY###
				<!-- ###ADDRESS_BILLING_DATA### end-->	
				
				<!-- ###ADDRESS_DELIVERY_DATA### begin-->
					<h2>###LANG_DELIVERY_ADDRESS###</h2>
					###ADDRESS_DELIVERY_NAME###
					###ADDRESS_DELIVERY_SURNAME###
					###ADDRESS_DELIVERY_COMPANY###
					###ADDRESS_DELIVERY_ADDRESS###
					###ADDRESS_DELIVERY_ZIP###
					###ADDRESS_DELIVERY_CITY###
				<!-- ###ADDRESS_DELIVERY_DATA### end-->	
			</th>
			<th class="com-invoice-additional">
				<div class="com-invoice-shop-address">
					###INVOICE_SHOP_NAME###<br />
					###INVOICE_SHOP_ADDRESS###
				</div>
				<div class="com-invoice-orderdata">
					###LANG_ORDER_NUM###: ###ORDER_ID###<br/>
					###LANG_ORDER_DATE###: ###ORDER_DATE###
				</div>
				
			</th>
		</tr>
		
		<tr>
			<th colspan="2" class="com-invoice-introduction">
				###INVOICE_INTRO_MESSAGE###
			</th>
		</tr>	
	</thead>
	
	<tfoot>
		<tr>
			<td colspan="2" class="com-invoice-thankyou">
				###INVOICE_THANKYOU###
			</td>
		</tr>
	</tfoot>
	<tbody>
		<tr>
			<td colspan="2">
				<table border="1" cellspacing="0" cellpadding="0" class="com-invoice-order">
					<tbody>
						<tr class="com-invoice-order-header">
							<th>###LANG_POS###</th>
							<th>###LANG_ARTICLE_NUMBER###</th>
							<th>###LANG_ARTICLE_DESCRIPTION###</th>
							<th>###LANG_QUANTITY###</th>
							<th>###LANG_PRICE###</th>
							<th>###LANG_SUM_TOTAL###</th>
						</tr>
						<!--###LISTING_ARTICLE### begin-->
						<tr>
								<td>###ARTICLE_POSITION###</td>
								<td>###ARTICLE_ARTICLE_NUMBER###</td>
								<td>###ARTICLE_TITLE###</td>
								<td>###ARTICLE_AMOUNT###</td>
								<td>###ARTICLE_PRICE_GROSS###</td>
								<td>###ARTICLE_TOTAL_GROSS###</td>
						</tr>
						<!--###LISTING_ARTICLE### end-->
						<tr>
							<td colspan="5">###PAYMENT_METHOD### </td>
							<td>###PAYMENT_COST###</td>
						</tr>
						<tr>
							<td colspan="5">###SHIPPING_METHOD### </td>
							<td>###SHIPPING_COST###</td>
						</tr>
						<tr>
							<td colspan="5">###LANG_TAX### </td>
							<td>###ORDER_TAX###</td>
						</tr>
						<tr>
							<td colspan="5">###LANG_TOTAL### </td>
							<td>###ORDER_TOTAL###</td>
						</tr>
						
					</tbody>
				</table>
			
			</td>
		<tr>
	</tbody>
</table>
<!-- ###TEMPLATE### end -->
</body>
</html>

