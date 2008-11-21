<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title>COMMERCE TEMPLATE FOR PI1</title>
    <link href="../res/css/commerce.css" rel="stylesheet" type="text/css" />
</head>

<body>

<h1>COMMERCE TEMPLATE FOR PI1</h1>
<h2>DEFAULT</h2>

<!-- Documentation -->

<!-- 
Subparts ###ARTICLE_VIEW###  ###ARTICLE_VIEW_NOSTOCK### 
These subparts are used to render the article into the templates, 
if an article has stock, ARTICLE_VIEW is used,
if not ARTICLE_VIEW_NOSTOCK

Inside these subparts following markers are availiabe by default
###ARTICLE_EANCODE##
###ARTICLE_STOCK###
###ARTICLE_ORDERNUMBER###
###ARTICLE_PRICE_GROSS###
###DELIVERY_PRICE_GROSS###
###ARTICLE_SELECT_ATTRIBUTES###
###LINKTOPUTINBASKET###

You cann add more Marken by generating these. Just add as prefix 
ARTICLE_ and than the filedname in uppercase, like
###ARTICLE_TITLE###
The layout of each field and wraps could be defined by TypoScript, in the fields setup

Generating of forms
If you want one form per articleview interation you can use 
following marker to generate the form tags and hidden values

<form name="###ARTICLE_FORMNAME###" action="###ARTICLE_FORMACTION###" method="post">
###ARTICLE_HIDDENCATUID###
###ARTICLE_HIDDENFIELDS###

If you want one form per page you should only use the ###ARTICLE_HIDDENFIELDS### 
inside the ARTICLE_VIEW subparts, since the hidden tag for the catuid will be rendered 
globally at the beginning of the form tag as marker ###GENERAL_HIDDENCATUID###

-->


<h3>CATEGORY LIST</h3>

<!-- ###CATEGORY_LIST### begin -->
	<div class="com-category">
		<!-- ###CATEGORY_LIST_ITEM### begin -->
			###CATEGORY_ITEM_TITLE###
			###CATEGORY_ITEM_TEASER###
			###CATEGORY_ITEM_TEASERIMAGES###			
			
			<!--###CATEGORY_ITEM_DESCRIPTION### ###CATEGORY_ITEM_IMAGES###-->
			###CATEGORY_ITEM_SUBTITLE###
	
			###CATEGORY_ITEM_PRODUCTLIST###
			
			
		<!-- ###CATEGORY_LIST_ITEM### end -->
	</div>
<!-- ###CATEGORY_LIST### end -->


<br />
<br />
<br />
<h3>CATEGORY LIST</h3>
<em></em>
<br />

<!-- ###CATEGORY_VIEW_DISPLAY### begin -->
<div class="com-category">
	###CATEGORY_TITLE###
	###CATEGORY_DESCRIPTION###
	###CATEGORY_IMAGES###
	###CATEGORY_SUB_LIST###
</div>

###SUBPART_CATEGORY_ITEMS_LISTVIEW_TOP###

<table class="com-list" cellspacing="0" cellpadding="0" border="0">
	<thead>
		<tr class="com-list-header">
	      <th class="com-list-header-img">###LANG_HEADER_IMAGE###</th>
	      <th class="com-list-header-title">###LANG_HEADER_TITLE###</th>
	      <th class="com-list-header-teaser">###LANG_HEADER_TEASER###</th>
	      <th class="com-list-header-price">###LANG_HEADER_PRICE###</th>
	      <th class="com-list-header-action">###LANG_HEADER_ACTION###</th>
	    </tr>
	</thead>
	<tbody>
		###SUBPART_CATEGORY_ITEMS_LISTVIEW###
	</tbody>
</table>

###CATEGORY_BROWSEBOX###	
<!-- ###CATEGORY_VIEW_DISPLAY### end -->


<br />
<br />
<br />
<h3>TOP PRODUCT</h3>
<em>YOU CAN DISPLAY IT IN A SPECIAL WAY </em>
<br />

<!-- ###CATEGORY_ITEMS_LISTVIEW_1### begin -->
<div class="com-list-entry-top">
	###PRODUCT_TITLE###
	###PRODUCT_TEASERIMAGES###
	###PRODUCT_TEASER###
</div>
<!-- ###CATEGORY_ITEMS_LISTVIEW_1### end -->

<br />
<br />
<br />
<h3>LIST PRODUCT ITEM - EVEN</h3>
<em></em>
<br />
<!-- ###CATEGORY_ITEMS_LISTVIEW_2### begin-->
<tr class="com-list-even">
	<td class="com-list-col-img">
		###PRODUCT_TEASERIMAGES###
	</td>

	<td class="com-list-col-title">
		###PRODUCT_TITLE###
	</td>

	<td class="com-list-col-teaser">
		###PRODUCT_TEASER###
	</td>

	<td class="com-list-col-price">
	###PRODUCT_CHEAPEST_PRICE_GROSS###			
	</td>
	<td class="com-list-col-action">
	<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### -->
		<!-- ###ARTICLE_VIEW### -->   
		<div class="com-list-action-entry">
			###ARTICLE_EANCODE###
			###ARTICLE_STOCK###
            ###ARTICLE_ORDERNUMBER###
			###ARTICLE_PRICE_GROSS###
			###DELIVERY_PRICE_GROSS###
			###ARTICLE_SELECT_ATTRIBUTES###
			###LINKTOPUTINBASKET###
			<form action="###GENERAL_FORM_ACTION###" method="post">
			###GENERAL_HIDDENCATUID###
			###ARTICLE_HIDDENFIELDS###
			<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="2"/>
			<input type="submit" value="###LANG_SUBMIT###"/>
			</form>
		</div>
		<!-- ###ARTICLE_VIEW### -->
		
		<!-- ###ARTICLE_VIEW_NOSTOCK### --> 
		<div class="com-list-action-entry">    
			###ARTICLE_EANCODE###
			###ARTICLE_STOCK###
            ###ARTICLE_ORDERNUMBER###
			###ARTICLE_PRICE_GROSS###
			###DELIVERY_PRICE_GROSS###
			###ARTICLE_SELECT_ATTRIBUTES###
			###LINKTOPUTINBASKET###
			<form action="###GENERAL_FORM_ACTION###" method="post">
			###GENERAL_HIDDENCATUID###
			###ARTICLE_HIDDENFIELDS###
			<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="2"/>
            <input type="submit" value="###LANG_SUBMIT###"/>
            </form>
        </div>
		<!-- ###ARTICLE_VIEW_NOSTOCK### -->
	<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### -->		
	</td>
</tr>
<!-- ###CATEGORY_ITEMS_LISTVIEW_2### end -->

<br />
<br />
<br />
<h3>LIST PRODUCT ITEM NO STOCK - EVEN</h3>
<em></em>
<br />
<!-- ###CATEGORY_ITEMS_LISTVIEW_2_NOSTOCK### begin-->
<tr class="com-list-even">
	<td class="com-list-col-img">
		###PRODUCT_TEASERIMAGES###
	</td>

	<td class="com-list-col-title">
		###PRODUCT_TITLE###
	</td>

	<td class="com-list-col-teaser">
		###PRODUCT_TEASER###
	</td>

	<td class="com-list-col-price">
	###PRODUCT_CHEAPEST_PRICE_GROSS###			
	</td>
	<td class="com-list-col-action">
	<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### -->
		<!-- ###ARTICLE_VIEW### -->   
		<div class="com-list-action-entry">
			###ARTICLE_EANCODE###
			###ARTICLE_STOCK###
            ###ARTICLE_ORDERNUMBER###
			###ARTICLE_PRICE_GROSS###
			###DELIVERY_PRICE_GROSS###
			###ARTICLE_SELECT_ATTRIBUTES###
			###LINKTOPUTINBASKET###
			<form action="###GENERAL_FORM_ACTION###" method="post">
			###GENERAL_HIDDENCATUID###
			###ARTICLE_HIDDENFIELDS###
			<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="2"/>
			<input type="submit" value="###LANG_SUBMIT###"/>
			<form>
		</div>
		<!-- ###ARTICLE_VIEW### -->
		
		<!-- ###ARTICLE_VIEW_NOSTOCK### --> 
		<div class="com-list-action-entry">    
			###ARTICLE_EANCODE###
			###ARTICLE_STOCK###
            ###ARTICLE_ORDERNUMBER###
			###ARTICLE_PRICE_GROSS###
			###DELIVERY_PRICE_GROSS###
			###ARTICLE_SELECT_ATTRIBUTES###
			###LINKTOPUTINBASKET###
			<form action="###GENERAL_FORM_ACTION###" method="post">
				###GENERAL_HIDDENCATUID###
				###ARTICLE_HIDDENFIELDS###
			<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="2"/>
            <input type="submit" value="###LANG_SUBMIT###"/>
            </form>
        </div>
		<!-- ###ARTICLE_VIEW_NOSTOCK### -->
	<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### -->		
	</td>
</tr>
<!-- ###CATEGORY_ITEMS_LISTVIEW_2_NOSTOCK### end -->


<br />
<br />
<br />
<h3>LIST PRODUCT ITEM 2 - ODD</h3>
<em></em>
<br />
<!-- ###CATEGORY_ITEMS_LISTVIEW_3### begin-->
<tr class="com-list-odd">
	<td class="com-list-col-img">
		###PRODUCT_TEASERIMAGES###
	</td>

	<td class="com-list-col-title">
		###PRODUCT_TITLE###
	</td>

	<td class="com-list-col-teaser">
		###PRODUCT_TEASER###
	</td>

	<td class="com-list-col-price">
	###PRODUCT_CHEAPEST_PRICE_GROSS###			
	</td>
	<td class="com-list-col-action">
	<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### -->
		<!-- ###ARTICLE_VIEW### -->   
		<div class="com-list-action-entry">
			###ARTICLE_EANCODE###
			###ARTICLE_STOCK###
            ###ARTICLE_ORDERNUMBER###
			###ARTICLE_PRICE_GROSS###
			###DELIVERY_PRICE_GROSS### 
			###ARTICLE_SELECT_ATTRIBUTES###
			###LINKTOPUTINBASKET###
			<form action="###GENERAL_FORM_ACTION###" method="post">
			###GENERAL_HIDDENCATUID###
			###ARTICLE_HIDDENFIELDS###
			<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="2"/>
			<input type="submit" value="###LANG_SUBMIT###"/>
			</form>
		</div>
		<!-- ###ARTICLE_VIEW### -->
		
		<!-- ###ARTICLE_VIEW_NOSTOCK### --> 
		<div class="com-list-action-entry">    
			###ARTICLE_EANCODE###
			###ARTICLE_STOCK###
            ###ARTICLE_ORDERNUMBER###
			###ARTICLE_PRICE_GROSS###
			###DELIVERY_PRICE_GROSS###
			###ARTICLE_SELECT_ATTRIBUTES###
			###LINKTOPUTINBASKET###
			<form action="###GENERAL_FORM_ACTION###" method="post">
			###GENERAL_HIDDENCATUID###
			###ARTICLE_HIDDENFIELDS###
			<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="2"/>
            <input type="submit" value="###LANG_SUBMIT###"/>
            </form>
        </div>
		<!-- ###ARTICLE_VIEW_NOSTOCK### -->
	<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### -->		
	</td>
</tr>
<!-- ###CATEGORY_ITEMS_LISTVIEW_3### end -->

<br />
<br />
<br />
<h3>LIST PRODUCT ITEM NO STOCK 2 - ODD</h3>
<em></em>
<br />
<!-- ###CATEGORY_ITEMS_LISTVIEW_3_NOSTOCK### begin-->
<tr class="com-list-odd">
	<td class="com-list-col-img">
		###PRODUCT_TEASERIMAGES###
	</td>

	<td class="com-list-col-title">
		###PRODUCT_TITLE###
	</td>

	<td class="com-list-col-teaser">
		###PRODUCT_TEASER###
	</td>

	<td class="com-list-col-price">
	###PRODUCT_CHEAPEST_PRICE_GROSS###			
	</td>
	<td class="com-list-col-action">
	<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### -->
		<!-- ###ARTICLE_VIEW### -->   
		<div class="com-list-action-entry">
			###ARTICLE_EANCODE###
			###ARTICLE_STOCK###
            ###ARTICLE_ORDERNUMBER###
			###ARTICLE_PRICE_GROSS###
			###DELIVERY_PRICE_GROSS###
			###ARTICLE_SELECT_ATTRIBUTES###
			###LINKTOPUTINBASKET###
			<form action="###GENERAL_FORM_ACTION###" method="post">
			###GENERAL_HIDDENCATUID###
			###ARTICLE_HIDDENFIELDS###
			<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="2"/>
			<input type="submit" value="###LANG_SUBMIT###"/>
			</form>
		</div>
		<!-- ###ARTICLE_VIEW### -->
		
		<!-- ###ARTICLE_VIEW_NOSTOCK### --> 
		<div class="com-list-action-entry">    
			###ARTICLE_EANCODE###
			###ARTICLE_STOCK###
            ###ARTICLE_ORDERNUMBER###
			###ARTICLE_PRICE_GROSS###
			###DELIVERY_PRICE_GROSS###
			###ARTICLE_SELECT_ATTRIBUTES###
			###LINKTOPUTINBASKET###
			<form action="###GENERAL_FORM_ACTION###" method="post">
			###GENERAL_HIDDENCATUID###
			###ARTICLE_HIDDENFIELDS###
			<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="2"/>
            <input type="submit" value="###LANG_SUBMIT###"/>
            </form>
        </div>
		<!-- ###ARTICLE_VIEW_NOSTOCK### -->
	<!-- ###PRODUCT_BASKET_FOR_LISTVIEW### -->		
	</td>
</tr>
<!-- ###CATEGORY_ITEMS_LISTVIEW_3_NOSTOCK### end -->


<br />
<br />
<br />
<h3>PRODUCT SINGLEVIEW</h3>
<em></em>
<br />
<!-- ###PRODUCT_VIEW_DETAIL### begin-->
<form action="###GENERAL_FORM_ACTION###" method="post">
###GENERAL_HIDDENCATUID###
<div class="com-single">
	###PRODUCT_TITLE###
	###PRODUCT_IMAGES###
	###PRODUCT_DESCRIPTION###
	###SUBPART_PRODUCT_ATTRIBUTES###


	
	<div class="com-single-aticle">
	<!-- ###PRODUCT_BASKET_FOR_SINGLEVIEW### -->
			<!-- ###ARTICLE_VIEW### -->
			<div class="com-single-action-entry">
			    ###ARTICLE_HIDDENFIELDS###
				###ARTICLE_EANCODE###
				###ARTICLE_STOCK###
	            ###ARTICLE_ORDERNUMBER###
				###ARTICLE_PRICE_GROSS###
				###DELIVERY_PRICE_GROSS###
				###ARTICLE_SELECT_ATTRIBUTES###
				###SUBPART_ARTICLE_ATTRIBUTES###
				###LINKTOPUTINBASKET###
				<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="3"/>
				<input type="submit" value="###LANG_SUBMIT###"/>
			</div>
			<!-- ###ARTICLE_VIEW### -->
			
			<!-- ###ARTICLE_VIEW_NOSTOCK### -->
			<div class="com-single-action-entry">
			    ###ARTICLE_HIDDENFIELDS###
				###ARTICLE_EANCODE###
				###ARTICLE_STOCK###
	            ###ARTICLE_ORDERNUMBER###
				###ARTICLE_PRICE_GROSS###
				###DELIVERY_PRICE_GROSS###
				###ARTICLE_SELECT_ATTRIBUTES###
				###SUBPART_ARTICLE_ATTRIBUTES###
				###LINKTOPUTINBASKET###
				<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="3"/>
				<input type="submit" value="###LANG_SUBMIT###"/>
			</div>
			<!-- ###ARTICLE_VIEW_NOSTOCK### -->
	<!-- ###PRODUCT_BASKET_FOR_SINGLEVIEW### -->
	</div>
</div>

<!-- ###CATEGORY_ITEM### begin -->
<div class="com-single-cat">
		###CATEGORY_ITEM_TITLE###
		###CATEGORY_ITEM_SUBTITLE###
		###CATEGORY_ITEM_DESCRIPTION###
		###CATEGORY_ITEM_IMAGES###
</div>
<!-- ###CATEGORY_ITEM### end -->

<!-- ###RELATED_PRODUCTS### begin -->
<!-- ###RELATED_PRODUCT_SINGLE### begin -->
	###PRODUCT_TITLE###
	###PRODUCT_IMAGES###
	###PRODUCT_DESCRIPTION###
<!-- ###RELATED_PRODUCT_SINGLE### end -->

<!-- ###RELATED_PRODUCT_SINGLE_NOSTOCK### begin -->
nostock
	###PRODUCT_TITLE###
	###PRODUCT_IMAGES###
	###PRODUCT_DESCRIPTION###
<!-- ###RELATED_PRODUCT_SINGLE_NOSTOCK### end -->

<!-- ###RELATED_PRODUCTS### end -->
</form>
<!-- ###PRODUCT_VIEW_DETAIL### end -->




<br />
<br />
<br />
<h3>PRODUCT SINGLEVIEW NO STOCK</h3>
<em></em>
<br />
<!-- ###PRODUCT_VIEW_DETAIL_NOSTOCK### begin-->
<form action="###GENERAL_FORM_ACTION###" method="post">
###GENERAL_HIDDENCATUID###
<div class="com-single">
	###PRODUCT_TITLE###
	###PRODUCT_IMAGES###
	###PRODUCT_DESCRIPTION###
	###SUBPART_PRODUCT_ATTRIBUTES###

	<div class="com-single-aticle">
	<!-- ###PRODUCT_BASKET_FOR_SINGLEVIEW### -->
			<!-- ###ARTICLE_VIEW### -->
			<div class="com-single-action-entry">
			    ###ARTICLE_HIDDENFIELDS###
				###ARTICLE_EANCODE###
				###ARTICLE_STOCK###
	            ###ARTICLE_ORDERNUMBER###
				###ARTICLE_PRICE_GROSS###
				###DELIVERY_PRICE_GROSS### 
				###ARTICLE_SELECT_ATTRIBUTES###
				###SUBPART_ARTICLE_ATTRIBUTES###
				###LINKTOPUTINBASKET###
				<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="3"/>
				<input type="submit" value="###LANG_SUBMIT###"/>
			</div>
			<!-- ###ARTICLE_VIEW### -->
			
			<!-- ###ARTICLE_VIEW_NOSTOCK### -->
			<div class="com-single-action-entry">
			    ###ARTICLE_HIDDENFIELDS###
				###ARTICLE_EANCODE###
				###ARTICLE_STOCK###
	            ###ARTICLE_ORDERNUMBER###
				###ARTICLE_PRICE_GROSS###
				###DELIVERY_PRICE_GROSS### 
				###ARTICLE_SELECT_ATTRIBUTES###
				###SUBPART_ARTICLE_ATTRIBUTES###
				###LINKTOPUTINBASKET###
				<input type="input" class="com-input-qty" value="###QTY_INPUT_VALUE###" name="###QTY_INPUT_NAME###" size="3"/>
				<input type="submit" value="###LANG_SUBMIT###"/>
			</div>
			<!-- ###ARTICLE_VIEW_NOSTOCK### -->
	<!-- ###PRODUCT_BASKET_FOR_SINGLEVIEW### -->
	</div>
</div>

<!-- ###CATEGORY_ITEM### begin -->
<div class="com-single-cat">
		###CATEGORY_ITEM_TITLE###
		###CATEGORY_ITEM_SUBTITLE###
		###CATEGORY_ITEM_DESCRIPTION###
		###CATEGORY_ITEM_IMAGES###
</div>
<!-- ###CATEGORY_ITEM### end -->
</form>
<!-- ###PRODUCT_VIEW_DETAIL_NOSTOCK### end -->


<br />
<br />
<br />
<h3>PRODUCT_ATTRIBUTES</h3>
<em></em>
<br />
<!-- ###PRODUCT_ATTRIBUTES### begin-->
<tr class="com-select-even"><td>###PRODUCT_ATTRIBUTES_ICON###	###PRODUCT_ATTRIBUTES_TITLE###
</td><td>	###PRODUCT_ATTRIBUTES_VALUE### ###PRODUCT_ATTRIBUTES_UNIT### </td></tr>
<!-- ###PRODUCT_ATTRIBUTES### end -->

<br />
<br />
<br />
<h3>PRODUCT_ATTRIBUTES2</h3>
<em></em>
<br />
<!-- ###PRODUCT_ATTRIBUTES2### begin-->
<tr class="com-select-odd"><td>###PRODUCT_ATTRIBUTES_ICON###	###PRODUCT_ATTRIBUTES_TITLE###
</td><td>	###PRODUCT_ATTRIBUTES_VALUE### ###PRODUCT_ATTRIBUTES_UNIT### </td></tr>
<!-- ###PRODUCT_ATTRIBUTES2### end -->

<br />
<br />
<br />
<h3>ARTICLE_ATTRIBUTES</h3>
<em></em>
<br />
<!-- ###ARTICLE_ATTRIBUTES### begin-->
<tr class="com-select-even">
	<td>###ARTICLE_ATTRIBUTES_ICON###	###ARTICLE_ATTRIBUTES_TITLE###</td>
	<td>###ARTICLE_ATTRIBUTES_VALUE### ###ARTICLE_ATTRIBUTES_UNIT### </td>
</tr>
<!-- ###ARTICLE_ATTRIBUTES### end -->

<br />
<br />
<br />
<h3>ARTICLE_ATTRIBUTES2</h3>
<em></em>
<br />
<!-- ###ARTICLE_ATTRIBUTES2### begin-->
<tr class="com-select-odd">
	<td>###ARTICLE_ATTRIBUTES_ICON###	###ARTICLE_ATTRIBUTES_TITLE###</td>
	<td>###ARTICLE_ATTRIBUTES_VALUE### ###ARTICLE_ATTRIBUTES_UNIT### </td>
</tr>
<!-- ###ARTICLE_ATTRIBUTES2### end -->

<br />
<br />
<br />
<h3>SELECT_ATTRIBUTES</h3>
<em></em>
<br />
<!-- ###SELECT_ATTRIBUTES### begin-->
	<tr class="com-select-row-odd">	
		<td>###SELECT_ATTRIBUTES_ICON###	###SELECT_ATTRIBUTES_TITLE###</td>
		<td>###SELECT_ATTRIBUTES_VALUE### ###SELECT_ATTRIBUTES_UNIT### </td>
	</tr>
<!-- ###SELECT_ATTRIBUTES### end -->

<!-- ###SELECT_ATTRIBUTES###2 begin-->
	<tr class="com-select-row-even">	
		<td>###SELECT_ATTRIBUTES_ICON###	###SELECT_ATTRIBUTES_TITLE###</td>
		<td>###SELECT_ATTRIBUTES_VALUE### ###SELECT_ATTRIBUTES_UNIT### </td>
	</tr>
<!-- ###SELECT_ATTRIBUTES###2 end -->

<br />
<br />
<br />
<h3>PRODUCT_BASKET_SELECT_ATTRIBUTES</h3>
<em></em>
<br />
<!-- ###PRODUCT_BASKET_SELECT_ATTRIBUTES### begin-->
<div class="com-basket-sel-att">
	<span><u>###SELECT_ATTRIBUTES_TITLE###:</u> ###SELECT_ATTRIBUTES_SELECTBOX###</span>
</div>
<!-- ###PRODUCT_BASKET_SELECT_ATTRIBUTES### end -->

</body>
</html>