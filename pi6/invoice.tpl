<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<TITLE>INVOICE Template</TITLE>

</HEAD>
<BODY>
<!-- Be careful, if you use pdf_generator, just build Plain HTML 3.2 HTML with templates -->
<!-- ###TEMPLATE### begin -->

<div class="invoice-header">
	###INVOICE_HEADER###
</div>

<div class="invoice-content">
	
	<div class="invoice-addresses">
		<div class="invoice-shop-address">
			###INVOICE_SHOP_NAME###<br />
			###INVOICE_SHOP_ADDRESS###
			<br/>
			<br/>
		</div>
		
		<div class="invoice-customer-address">
			###LANG_DELIVERY_ADDRESS### <br/>
			###INVOICE_DELIVERY_ADDRESS### <br/>
			###LANG_BILLING_ADDRESS### <br/>
			###INVOICE_BILLING_ADDRESS### <br/>
		</div>
		
	</div>
	
	<br />UID-Nummer: ATU 581 83 922<br />
###LANG_ORDER_NUM###: ###ORDER_ID### <br/>
###LANG_ORDER_DATE###: ###ORDER_DATE###<br/><br />
	
	<div class="invoice-introduction">
		###INVOICE_INTRO_MESSAGE###
	</div>
	
	<div class="invoice-order">
              
		<table border="1" cellspacing="0">
			<tbody>
				<tr>
					<th>###LANG_POS###</th>
					<th>###LANG_ARTICLE_NUMBER###</th>
					<th>###LANG_ARTICLE_DESCRIPTION###</th>
					<th>###LANG_QUANTITY###</th>
					<th>###LANG_PRICE###</th>
					<th>###LANG_TOTAL###</th>
				</tr>
		
				<!-- ###ORDER_ITEMS### begin-->
				<!-- ###ORDER_ITEM### begin-->
				<tr>
					<td>###POSITION###</td>
					<td>###ARTICLE_NUMBER###</td>
					<td>###ARTICLE_TITLE###</td>
					<td>###QUANTITY###</td>
					<td>###PRICE###</td>
					<td>###TOTAL###</td>
				</tr>
				<!-- ###ORDER_ITEM### end-->
				<!-- ###ORDER_ITEMS### end-->
				
		
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
	</div>
	
	<div class="invoice-thankyou">
		<br />###INVOICE_THANKYOU###<br /><br />
	</div>
</div>

<!-- ###TEMPLATE### end -->
</body>
</html>

