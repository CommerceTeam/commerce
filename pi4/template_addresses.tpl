<!--###ADDRESS_LISTING### begin-->
<div>###SYS_MESSAGE###</div>

<div class="com-address-list">
	<h2>###LABEL_ADDRESSES_OF_TYPE_1###</h2>
	<ol>
	###ADDRESS_ITEMS_OF_TYPE_1###
	</ol>
	<div class="com-address-list-link"><!--###LINK_NEW_TYPE_1###-->###LABEL_LINK_NEW_TYPE_1###<!--###LINK_NEW_TYPE_1###--></div>
</div>
<div class="com-address-list">
	<h2>###LABEL_ADDRESSES_OF_TYPE_2###</h2>
	<ol>
	###ADDRESS_ITEMS_OF_TYPE_2###
	</ol>
	<div class="com-address-list-link"><!--###LINK_NEW_TYPE_2###-->###LABEL_LINK_NEW_TYPE_2###<!--###LINK_NEW_TYPE_2###--></div>
</div>
<!--###ADDRESS_LISTING### end-->

<!--###ADDRESS_ITEM###-->
<li>

<dl>
	<dt>###LABEL_NAME###</dt> <dd>###NAME###</dd>
	<dt>###LABEL_SURNAME###</dt> <dd>###SURNAME###</dd>
	<dt>###LABEL_COMPANY###</dt> <dd>###COMPANY###</dd>
	<dt>###LABEL_ADDRESS###</dt> <dd>###ADDRESS###</dd>
	<dt>###LABEL_CITY###</dt> <dd>###CITY###</dd>
	<dt>###LABEL_ZIP###</dt> <dd>###ZIP###</dd>
	<dt>###LABEL_COUNTRY###</dt> <dd>###COUNTRY###</dd>
	<dt>###LABEL_EMAIL###</dt> <dd>###EMAIL###</dd>
</dl>
<div class="com-address-item-link"><!--###LINK_EDIT###-->###LABEL_LINK_EDIT###<!--###LINK_EDIT###--><!--###LINK_DELETE###-->###LABEL_LINK_DELETE###<!--###LINK_DELETE###--></div>
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
</dl>
<!--###ADDRESS_EDIT_FORM###-->

<!--###ADDRESS_EDIT### begin-->
<form method="post" action="###ADDRESS_FORM_ACTION###">
<div class="com-chkout-address-fields">
	<h2>###MESSAGE_EDIT###</h2>
	###ADDRESS_FORM_FIELDS###
	<p>###ADDRESS_FORM_IS_MAIN_ADDRESS_FIELD### ###ADDRESS_FORM_IS_MAIN_ADDRESS_LABEL###</p>
	<p>###ADDRESS_FORM_SUBMIT###</p>
	<p>###ADDRESS_FORM_BACK###</p>
</div>
</form>
<!--###ADDRESS_EDIT### end-->

<!--###ADDRESS_DELETE### begin-->
<div class="com-address-items">
<dl>
	<dt>###LABEL_NAME###</dt> <dd>###NAME###&nbsp;</dd>
	<dt>###LABEL_SURNAME###</dt> <dd>###SURNAME###&nbsp;</dd>
	<dt>###LABEL_COMPANY###</dt> <dd>###COMPANY###&nbsp;</dd>
	<dt>###LABEL_ADDRESS###</dt> <dd>###ADDRESS###&nbsp;</dd>
	<dt>###LABEL_CITY###</dt> <dd>###CITY###&nbsp;</dd>
	<dt>###LABEL_ZIP###</dt> <dd>###ZIP###&nbsp;</dd>
	<dt>###LABEL_COUNTRY###</dt> <dd>###COUNTRY###&nbsp;</dd>
	<dt>###LABEL_EMAIL###</dt> <dd>###EMAIL###&nbsp;</dd>
</dl>
</div>
<hr />

<p class="com-address-question">###QUESTION###</p>
<div class="com-address-link"><!--###LINK_YES###-->###YES###<!--###LINK_YES###-->&nbsp;<!--###LINK_NO###-->###NO###<!--###LINK_NO###--></div>
<!--###ADDRESS_DELETE### end-->

<!--###SINGLE_INPUT### begin-->
<span class="error">###FIELD_ERROR###</span>###FIELD_INPUT###
<!--###SINGLE_INPUT### end-->